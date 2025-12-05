<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap phsbot-stats-wrapper">
    <div class="phsbot-stats-header">
        <h1>Estadísticas de Uso del Chatbot</h1>
        <div class="phsbot-stats-controls">
            <select id="stats-period" class="phsbot-period-select">
                <option value="current">Período actual de facturación</option>
                <option value="7">Últimos 7 días</option>
                <option value="30" selected>Últimos 30 días</option>
                <option value="60">Últimos 60 días</option>
                <option value="90">Últimos 90 días</option>
            </select>
            <button class="button button-primary" id="refresh-stats">Actualizar</button>
        </div>
    </div>

    <div class="phsbot-stats-container">
        <!-- Panel de Resumen: 3 Cards en fila -->
        <div class="phsbot-stats-grid-3">
            <!-- Card 1: Plan Actual -->
            <div class="phsbot-stat-card phsbot-card-plan">
                <div class="phsbot-card-body">
                    <div class="phsbot-plan-info" id="plan-info">
                        <div class="phsbot-loading">Cargando...</div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Gráfico de Evolución -->
            <div class="phsbot-stat-card phsbot-card-chart">
                <div class="phsbot-card-body">
                    <canvas id="timeline-chart"></canvas>
                </div>
            </div>

            <!-- Card 3: Tokens Disponibles (Animación) -->
            <div class="phsbot-stat-card phsbot-card-tokens">
                <div class="phsbot-card-body">
                    <div id="tokens-display">
                        <div class="tokens-circle">
                            <svg viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="45" class="tokens-bg"></circle>
                                <circle cx="50" cy="50" r="45" class="tokens-progress" id="tokens-circle"></circle>
                            </svg>
                            <div class="tokens-text">
                                <div class="tokens-number" id="tokens-number">0</div>
                                <div class="tokens-label">tokens disponibles</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla: Resumen de Actividad -->
        <div class="phsbot-stat-card phsbot-card-full">
            <div class="phsbot-card-header">
                <h3>Resumen de Actividad</h3>
            </div>
            <div class="phsbot-card-body">
                <div id="activity-summary">
                    <div class="phsbot-loading">Cargando actividad...</div>
                </div>
            </div>
        </div>

        <!-- Tabla: Detalle de Operaciones -->
        <div class="phsbot-stat-card phsbot-card-full">
            <div class="phsbot-card-header">
                <h3>Detalle de Operaciones</h3>
            </div>
            <div class="phsbot-card-body">
                <div id="operations-table">
                    <div class="phsbot-loading">Cargando operaciones...</div>
                </div>
            </div>
        </div>
    </div>
</div>
