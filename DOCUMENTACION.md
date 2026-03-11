# Raffle System — Documentación del Plugin

**Nombre del Plugin:** Raffle System  
**Versión:** 1.0.0  
**Autor:** WP Rifas  
**Descripción:** Sistema de rifas online con boletos aleatorios por paquetes.  
**Licencia:** GPL-2.0+

---

## Índice

1. [Resumen General](#resumen-general)
2. [Base de Datos](#base-de-datos)
3. [Panel de Administración](#panel-de-administración)
4. [Página Pública (Frontend)](#página-pública-frontend)
5. [Sistema de Boletos](#sistema-de-boletos)
6. [Proceso de Compra](#proceso-de-compra)
7. [Correo Electrónico](#correo-electrónico)
8. [Cuenta Atrás](#cuenta-atrás)
9. [Barra de Progreso](#barra-de-progreso)
10. [Sorteo del Ganador](#sorteo-del-ganador)
11. [Detección y Corrección de Duplicados](#detección-y-corrección-de-duplicados)
12. [Endpoints AJAX](#endpoints-ajax)
13. [Seguridad](#seguridad)
14. [Estructura de Archivos](#estructura-de-archivos)

---

## Resumen General

Raffle System es un plugin de WordPress diseñado para gestionar rifas online. Permite crear rifas con un número definido de boletos, venderlos en paquetes predefinidos, enviar correos de confirmación con los números asignados y, cuando se desee, realizar el sorteo seleccionando un ganador al azar de forma criptográficamente segura.

El plugin se integra en WordPress mediante un **shortcode** (`[raffle id="X"]`) que muestra la rifa completa en cualquier página o entrada, incluyendo imagen del premio, paquetes de compra, barra de progreso y cuenta atrás.

---

## Base de Datos

El plugin crea **3 tablas** al activarse:

### `wp_raffles` — Rifas

| Columna          | Tipo          | Descripción                                                |
| ---------------- | ------------- | ---------------------------------------------------------- |
| id               | bigint(20)    | Clave primaria, auto-incremento                            |
| title            | varchar(255)  | Nombre de la rifa                                          |
| description      | text          | Descripción detallada (opcional)                           |
| prize_value      | decimal(10,2) | Valor monetario del premio                                 |
| prize_image      | varchar(500)  | URL de la imagen del premio (opcional)                     |
| total_tickets    | int(11)       | Cantidad total de boletos disponibles                      |
| sold_tickets     | int(11)       | Boletos vendidos hasta el momento                          |
| ticket_price     | decimal(10,2) | Precio por boleto individual                               |
| packages         | text          | Paquetes disponibles en formato JSON (ej: `[15,30,45,90]`) |
| draw_date        | datetime      | Fecha/hora programada del sorteo (opcional)                |
| status           | varchar(20)   | `active` o `finished`                                      |
| winner_ticket_id | bigint(20)    | ID del boleto ganador (nulo hasta sortear)                 |
| created_at       | datetime      | Fecha de creación                                          |

### `wp_raffle_purchases` — Compras

| Columna        | Tipo          | Descripción                   |
| -------------- | ------------- | ----------------------------- |
| id             | bigint(20)    | Clave primaria                |
| raffle_id      | bigint(20)    | FK → wp_raffles.id            |
| buyer_name     | varchar(255)  | Nombre del comprador          |
| buyer_email    | varchar(255)  | Email del comprador           |
| quantity       | int(11)       | Cantidad de boletos comprados |
| total_amount   | decimal(10,2) | Monto total de la compra      |
| payment_status | varchar(20)   | Estado del pago (`completed`) |
| purchase_date  | datetime      | Fecha/hora de la transacción  |

### `wp_raffle_tickets` — Boletos individuales

| Columna       | Tipo         | Descripción                           |
| ------------- | ------------ | ------------------------------------- |
| id            | bigint(20)   | Clave primaria                        |
| raffle_id     | bigint(20)   | FK → wp_raffles.id                    |
| purchase_id   | bigint(20)   | FK → wp_raffle_purchases.id           |
| ticket_number | int(11)      | Número del boleto (1 a total_tickets) |
| buyer_email   | varchar(255) | Email del titular del boleto          |

> La tabla `wp_raffle_tickets` tiene una **clave UNIQUE** en `(raffle_id, ticket_number)` para prevenir duplicados a nivel de base de datos.

---

## Panel de Administración

El plugin agrega un menú **"Rifas"** en el panel de WordPress con las siguientes secciones:

### Todas las Rifas (Lista)

- Tabla con todas las rifas creadas.
- Columnas: ID, Título, Valor del Premio, Boletos vendidos/totales, Precio por boleto, Fecha del Sorteo, Estado.
- Acciones por rifa: **Ver** detalles, **Editar**, **Eliminar** (con confirmación).
- Muestra instrucciones de uso del shortcode.

### Crear / Editar Rifa (Formulario)

Campos disponibles:

- **Título** (obligatorio)
- **Descripción** (área de texto)
- **Valor del premio** (decimal)
- **Imagen del premio** (selector de medios de WordPress)
- **Total de boletos** (solo editable si no hay ventas)
- **Precio por boleto** (decimal)
- **Paquetes** (separados por coma, ej: `15, 30, 45, 90`)
- **Fecha del sorteo** (campo datetime)
- **Estado** (activa / finalizada)

### Detalles de la Rifa

Vista completa con:

- **Tarjetas estadísticas:** Boletos vendidos, restantes, dinero recaudado, fecha del sorteo.
- **Shortcode** listo para copiar: `[raffle id="X"]`.
- **Sección de sorteo:** Botón para seleccionar ganador o tarjeta mostrando el ganador si ya se realizó.
- **Control de duplicados:** Toggle de auto-corrección, botones para comprobar y corregir duplicados.
- **Tabla de compras:** Lista todos los pedidos con nombre, email, cantidad, total, estado y los números de boleto asignados.

---

## Página Pública (Frontend)

Se muestra con el shortcode `[raffle id="X"]` y contiene:

1. **Banner de finalización** — Si la rifa terminó, se muestra un aviso: _"🏁 Esta rifa ha finalizado"_.
2. **Cabecera** — Imagen del premio, título, valor del premio (🏆), descripción y fecha del sorteo (📅).
3. **Cuenta atrás** — Temporizador en tiempo real (días, horas, minutos, segundos) hasta la fecha del sorteo.
4. **Barra de progreso** — Muestra boletos vendidos vs. totales con porcentaje y estadísticas.
5. **Grilla de paquetes** — Tarjetas de cada paquete con cantidad, precio total y botón "Comprar". Los paquetes que exceden el inventario restante se ocultan automáticamente.
6. **Modal de compra** — Formulario con nombre y email para completar la compra.
7. **Modal de confirmación** — Muestra los números de boleto asignados y avisa que se envió un correo con la información.
8. **Banner de agotado** — Si no quedan boletos: _"🎟️ ¡Todos los boletos han sido vendidos!"_.

---

## Sistema de Boletos

La generación de boletos se realiza con las siguientes características:

- Los números se asignan de forma **aleatoria** usando `random_int()` (criptográficamente seguro).
- Se construye un **pool de números disponibles** (1 al total) excluyendo los ya asignados.
- Se seleccionan al azar sin repetición.
- Se insertan en la base de datos y se actualiza el contador `sold_tickets` de forma **atómica**.
- Los números se formatean con **ceros a la izquierda** según el total de boletos (ej: `0042` para una rifa de 1000 boletos).

---

## Proceso de Compra

Flujo completo de una compra:

1. El usuario selecciona un paquete en la página pública.
2. Se abre un modal pidiendo nombre y correo electrónico.
3. Al confirmar, se envía una petición AJAX al servidor.
4. **Validaciones del servidor:**
   - Campos obligatorios completos.
   - Email con formato válido.
   - La rifa existe y está activa.
   - La cantidad corresponde a un paquete disponible.
   - Quedan suficientes boletos.
5. Se crea el registro de compra.
6. Se generan los números de boleto aleatorios.
7. Se envía un correo de confirmación.
8. Si la auto-corrección de duplicados está activada, se ejecuta automáticamente.
9. Se devuelven los números de boleto formateados al frontend.
10. Se muestra el modal de confirmación con los números y la página se recarga al cerrar (para actualizar la barra de progreso).

---

## Correo Electrónico

Tras cada compra exitosa se envía un correo HTML al comprador con:

- Saludo personalizado con el nombre del comprador.
- Título y detalles de la rifa.
- Resumen de la compra: cantidad de boletos, monto total, fecha del sorteo.
- **Lista de números de boleto** formateados con ceros a la izquierda.
- Diseño responsive (600px max-width) con cabecera verde y secciones estilizadas.
- Asunto: _"Confirmación de compra — [Nombre de la Rifa]"_.

---

## Cuenta Atrás

Si la rifa tiene una fecha de sorteo configurada y está activa:

- Se muestra un temporizador visual con **días, horas, minutos y segundos**.
- Se actualiza cada segundo en tiempo real mediante JavaScript (`setInterval`).
- Cuando el tiempo expira, se oculta el temporizador y se muestra el mensaje: _"🎉 ¡Es hora del sorteo!"_.
- Los labels están en español: días, horas, minutos, segundos.
- Diseño con fondo degradado oscuro y cajas con efecto glass-morphism para cada unidad de tiempo.

---

## Barra de Progreso

La barra de progreso muestra el avance de ventas de la rifa:

- **Cabecera:** Boletos vendidos / total, porcentaje, boletos restantes.
- **Barra animada:** Degradado verde con ancho proporcional al porcentaje de venta, texto interior mostrando `vendidos / total`.
- **Estadísticas:** Precio por boleto individual y total de boletos disponibles.

---

## Sorteo del Ganador

El sorteo se realiza exclusivamente desde el panel de administración:

- Solo usuarios con permisos de `manage_options` pueden ejecutarlo.
- Requiere confirmación con un diálogo antes de proceder.
- **Algoritmo:** Se obtienen todos los boletos vendidos con información del comprador y se selecciona uno al azar usando `random_int()`.
- Se actualiza la rifa: se guarda el `winner_ticket_id` y el estado cambia a `finished`.
- Se muestra en pantalla: nombre del ganador, número de boleto ganador y email.
- No se puede sortear si ya existe un ganador o si no hay boletos vendidos.

---

## Detección y Corrección de Duplicados

Sistema de seguridad adicional para prevenir y corregir boletos duplicados:

### Detección manual

- Botón **"Comprobar Duplicados"** en los detalles de la rifa.
- Ejecuta una consulta SQL (`GROUP BY` + `HAVING COUNT(*) > 1`).
- Muestra cuántos duplicados se encontraron y qué números están afectados.

### Corrección manual

- Si se detectan duplicados, aparece el botón **"Corregir Duplicados"**.
- El algoritmo conserva el boleto con el ID más bajo y reasigna nuevos números aleatorios únicos a las copias restantes.
- Recalcula el contador `sold_tickets` para mantener la consistencia.

### Auto-corrección

- Toggle activable desde los detalles de la rifa: **"Corregir automáticamente duplicados después de cada compra"**.
- Cuando está habilitado, se ejecuta la corrección automáticamente al finalizar cada compra.
- La configuración se guarda en la opción de WordPress `raffle_auto_fix_duplicates`.
- Viene **activada por defecto**.

---

## Endpoints AJAX

| Endpoint                  | Acceso  | Nonce                   | Función                            |
| ------------------------- | ------- | ----------------------- | ---------------------------------- |
| `raffle_purchase`         | Público | `raffle_purchase_nonce` | Procesar compra de boletos         |
| `raffle_draw`             | Admin   | `raffle_draw_nonce`     | Seleccionar ganador al azar        |
| `raffle_check_duplicates` | Admin   | `raffle_draw_nonce`     | Detectar boletos duplicados        |
| `raffle_fix_duplicates`   | Admin   | `raffle_draw_nonce`     | Corregir boletos duplicados        |
| `raffle_toggle_auto_fix`  | Admin   | `raffle_draw_nonce`     | Activar/desactivar auto-corrección |

---

## Seguridad

El plugin implementa las siguientes medidas de seguridad:

- **Nonces de WordPress** en todos los formularios y peticiones AJAX para prevenir CSRF.
- **Verificación de permisos** (`current_user_can('manage_options')`) en todas las acciones administrativas.
- **Sanitización de entradas:**
  - `sanitize_text_field()` para campos de texto.
  - `sanitize_textarea_field()` para textos largos.
  - `sanitize_email()` + `is_email()` para correos electrónicos.
  - `absint()` para enteros.
  - `floatval()` para decimales.
  - `esc_url_raw()` para URLs.
- **Escape de salida** (`esc_html()`, `esc_attr()`, `esc_url()`) en vistas.
- **Prepared statements** (`$wpdb->prepare()`) en todas las consultas SQL para prevenir inyecciones SQL.
- **Clave UNIQUE** en la base de datos para `(raffle_id, ticket_number)` como protección adicional contra duplicados.
- **`random_int()`** en vez de `rand()` para generación criptográficamente segura de números aleatorios.

---

## Estructura de Archivos

```
raffle-system/
├── raffle-system.php                  # Archivo principal del plugin
├── includes/
│   ├── class-raffle-activator.php     # Creación de tablas al activar
│   ├── class-raffle-tickets.php       # Generación de boletos
│   ├── class-raffle-purchase.php      # Procesamiento de compras (AJAX)
│   ├── class-raffle-draw.php          # Selección del ganador (AJAX)
│   ├── class-raffle-email.php         # Envío de correos de confirmación
│   └── class-raffle-duplicates.php    # Detección y corrección de duplicados
├── admin/
│   ├── class-raffle-admin.php         # Menús, formularios y CRUD del admin
│   └── views/
│       ├── raffle-list.php            # Vista: lista de rifas
│       ├── raffle-form.php            # Vista: formulario crear/editar
│       └── raffle-details.php         # Vista: detalles y estadísticas
├── public/
│   ├── class-raffle-public.php        # Shortcode y assets del frontend
│   └── views/
│       └── raffle-display.php         # Vista: página pública de la rifa
└── assets/
    ├── css/
    │   ├── admin.css                  # Estilos del panel de administración
    │   └── public.css                 # Estilos de la página pública
    └── js/
        ├── admin.js                   # JavaScript del admin (AJAX sorteo, duplicados)
        └── public.js                  # JavaScript público (modales, compra, cuenta atrás)
```

---

## Uso Rápido

1. **Activar el plugin** desde `Plugins > Plugins instalados`.
2. **Crear una rifa** desde `Rifas > Crear Rifa` rellenando todos los campos.
3. **Insertar el shortcode** `[raffle id="X"]` en la página o entrada donde se quiera mostrar la rifa.
4. Los usuarios podrán **comprar paquetes** de boletos desde la página pública.
5. Cuando se desee, ir a `Rifas > Ver detalles` y pulsar **"Seleccionar Ganador"** para realizar el sorteo.
