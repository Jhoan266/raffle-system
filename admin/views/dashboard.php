<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap rs-wrap">
    <div class="rs-page-header">
        <h1 class="rs-page-title">
            <i class="fas fa-chart-line"></i> Dashboard Analítico
        </h1>
        <div class="rs-header-actions">
            <button id="rs-refresh-dashboard" class="rs-btn rs-btn-secondary">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="rs-stats-grid rs-stats-grid-5" id="rs-kpi-cards">
        <div class="rs-stat-card border-blue">
            <div class="rs-stat-icon blue"><i class="fas fa-dollar-sign"></i></div>
            <div class="rs-stat-body">
                <div class="rs-stat-label">Ingresos Totales</div>
                <div class="rs-stat-number" id="kpi-revenue">—</div>
            </div>
        </div>
        <div class="rs-stat-card border-green">
            <div class="rs-stat-icon green"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="rs-stat-body">
                <div class="rs-stat-label">Ganancia Neta</div>
                <div class="rs-stat-number" id="kpi-net-profit">—</div>
            </div>
        </div>
        <div class="rs-stat-card border-orange">
            <div class="rs-stat-icon orange"><i class="fas fa-ticket-alt"></i></div>
            <div class="rs-stat-body">
                <div class="rs-stat-label">Boletos Vendidos</div>
                <div class="rs-stat-number" id="kpi-tickets">—</div>
            </div>
        </div>
        <div class="rs-stat-card border-purple">
            <div class="rs-stat-icon purple"><i class="fas fa-users"></i></div>
            <div class="rs-stat-body">
                <div class="rs-stat-label">Compradores Únicos</div>
                <div class="rs-stat-number" id="kpi-buyers">—</div>
            </div>
        </div>
        <div class="rs-stat-card border-teal">
            <div class="rs-stat-icon teal"><i class="fas fa-percentage"></i></div>
            <div class="rs-stat-body">
                <div class="rs-stat-label">Tasa de Venta</div>
                <div class="rs-stat-number" id="kpi-sell-rate">—</div>
            </div>
        </div>
    </div>

    <!-- Secondary KPIs -->
    <div class="rs-kpi-secondary">
        <div class="rs-kpi-pill" id="kpi-active-raffles">
            <i class="fas fa-bullseye"></i> <span>—</span> Rifas Activas
        </div>
        <div class="rs-kpi-pill" id="kpi-total-raffles">
            <i class="fas fa-layer-group"></i> <span>—</span> Total Rifas
        </div>
        <div class="rs-kpi-pill" id="kpi-avg-price">
            <i class="fas fa-tag"></i> Precio Promedio: $<span>—</span>
        </div>
        <div class="rs-kpi-pill" id="kpi-month-trend">
            <i class="fas fa-arrow-up"></i> Este Mes: $<span>—</span>
        </div>
    </div>

    <!-- Charts Row 1: Revenue + Tickets -->
    <div class="rs-row">
        <div class="rs-card">
            <h3 class="rs-card-title"><i class="fas fa-chart-bar"></i> Ingresos por Rifa</h3>
            <div class="rs-chart-container">
                <canvas id="chart-revenue-raffle"></canvas>
            </div>
        </div>
        <div class="rs-card">
            <h3 class="rs-card-title"><i class="fas fa-poll-h"></i> Boletos Vendidos por Rifa</h3>
            <div class="rs-chart-container">
                <canvas id="chart-tickets-raffle"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Net Profit + Sales Trend -->
    <div class="rs-row">
        <div class="rs-card">
            <h3 class="rs-card-title"><i class="fas fa-coins"></i> Ganancia Neta por Rifa</h3>
            <p class="rs-chart-subtitle">Ingresos menos el valor del premio</p>
            <div class="rs-chart-container">
                <canvas id="chart-net-profit"></canvas>
            </div>
        </div>
        <div class="rs-card">
            <h3 class="rs-card-title">
                <i class="fas fa-chart-area"></i> Tendencia de Ventas
            </h3>
            <div class="rs-chart-toolbar">
                <button class="rs-chip rs-chip-active" data-period="daily">Diario</button>
                <button class="rs-chip" data-period="monthly">Mensual</button>
                <button class="rs-chip" data-period="annual">Anual</button>
            </div>
            <div class="rs-chart-container">
                <canvas id="chart-sales-trend"></canvas>
            </div>
        </div>
    </div>

    <!-- Row 3: Top Buyers + Recent Transactions -->
    <div class="rs-row">
        <div class="rs-card">
            <h3 class="rs-card-title"><i class="fas fa-trophy"></i> Top 10 Compradores</h3>
            <table class="rs-table" id="table-top-buyers">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Compras</th>
                        <th>Boletos</th>
                        <th>Gastado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="rs-table-empty"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="rs-card">
            <h3 class="rs-card-title"><i class="fas fa-receipt"></i> Últimas Transacciones</h3>
            <table class="rs-table" id="table-recent-txns">
                <thead>
                    <tr>
                        <th>Rifa</th>
                        <th>Comprador</th>
                        <th>Boletos</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="rs-table-empty"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
