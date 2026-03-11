<p align="center">
  <h1 align="center">🎟️ Raffle System</h1>
  <p align="center">
    Plugin de WordPress para crear y gestionar rifas digitales con boletos aleatorios por paquetes.
  </p>
  <p align="center">
    <img src="https://img.shields.io/badge/version-1.0.0-blue?style=flat-square" alt="Version">
    <img src="https://img.shields.io/badge/WordPress-5.8%2B-21759b?style=flat-square&logo=wordpress" alt="WordPress">
    <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
    <img src="https://img.shields.io/badge/WooCommerce-7.0%2B-96588A?style=flat-square&logo=woocommerce&logoColor=white" alt="WooCommerce">
    <img src="https://img.shields.io/badge/license-GPL--2.0-green?style=flat-square" alt="License">
  </p>
</p>

---

## Descripción

**Raffle System** es un plugin de WordPress que permite crear rifas digitales con asignación automática de boletos. Los compradores seleccionan paquetes de boletos, pagan a través de WooCommerce, y reciben sus números de boleto al instante por email.

### Características principales

- **Rifas configurables** — Título, imagen, premio, total de boletos, fecha de sorteo
- **Paquetes de boletos** — Descuentos por volumen con paquetes personalizables
- **Generación aleatoria** — Boletos únicos asignados automáticamente al comprar
- **Integración WooCommerce** — Checkout completo con cualquier pasarela de pago
- **Panel de administración** — Dashboard, lista de rifas, detalle con compradores
- **Sorteo en vivo** — Botón de sorteo con selección aleatoria del ganador
- **Email de confirmación** — Notificación automática con los números de boleto
- **Shortcode** — `[raffle id="X"]` para insertar rifas en cualquier página

## Estructura del proyecto

```
raffle-system/
├── raffle-system.php            # Punto de entrada del plugin
├── includes/
│   ├── class-raffle-activator.php   # Creación de tablas en BD
│   ├── class-raffle-tickets.php     # Generación de boletos
│   ├── class-raffle-purchase.php    # Lógica de compras
│   ├── class-raffle-draw.php        # Sistema de sorteo
│   ├── class-raffle-email.php       # Emails de confirmación
│   ├── class-raffle-duplicates.php  # Control de duplicados
│   └── class-raffle-woocommerce.php # Integración WooCommerce
├── admin/
│   ├── class-raffle-admin.php       # Controlador admin
│   └── views/                       # Vistas del panel
├── public/
│   ├── class-raffle-public.php      # Controlador público
│   └── views/raffle-display.php     # Vista del shortcode
└── assets/
    ├── css/                         # Estilos admin + público
    └── js/                          # Scripts admin + público
```

## Instalación

1. Descarga o clona este repositorio:
   ```bash
   git clone https://github.com/Jhoan266/raffle-system.git
   ```
2. Copia la carpeta `raffle-system/` a `wp-content/plugins/`
3. Activa el plugin en **WordPress → Plugins**
4. Asegúrate de tener **WooCommerce** instalado y activo

## Uso

1. Ve a **Rifas → Crear nueva** en el panel de WordPress
2. Configura la rifa: nombre, imagen del premio, cantidad de boletos, paquetes y fecha
3. Inserta el shortcode `[raffle id="X"]` en cualquier página o post
4. Los compradores seleccionan un paquete, completan el checkout de WooCommerce y reciben sus boletos por email

## Requisitos

| Componente | Versión mínima |
|---|---|
| WordPress | 5.8+ |
| PHP | 7.4+ |
| WooCommerce | 7.0+ |
| MySQL | 5.7+ |

## Licencia

Este proyecto está licenciado bajo [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html).

---

> **Nota**: Este plugin fue la versión inicial del proyecto. La versión profesional con arquitectura SaaS-ready, anti-colisión y sistema de reservas está disponible en [RaffleCore](https://github.com/Jhoan266/rafflecore).
