jQuery(document).ready(function($) {
    let currentPeriod = '30';
    let billingPeriod = null;
    let timelineChart = null;

    // Cargar datos iniciales
    loadStats();

    // Evento: cambiar período
    $('#stats-period').on('change', function() {
        currentPeriod = $(this).val();
        loadStats();
    });

    // Evento: refresh manual
    $('#refresh-stats').on('click', function() {
        loadStats();
    });

    function loadStats() {
        // Mostrar loading
        $('#plan-info').html('<div class="phsbot-loading">Cargando...</div>');
        $('#activity-summary').html('<div class="phsbot-loading">Cargando...</div>');
        $('#operations-table').html('<div class="phsbot-loading">Cargando...</div>');

        $.ajax({
            url: phsbotStats.ajax_url,
            method: 'POST',
            data: {
                action: 'phsbot_get_stats',
                nonce: phsbotStats.nonce,
                period: currentPeriod
            },
            success: function(response) {
                if (response.success) {
                    billingPeriod = response.data.billing_period;
                    renderPlanInfo(response.data.summary, response.data.plan);
                    renderTokensDisplay(response.data.summary);
                    renderTimelineChart(response.data.daily_timeline);
                    renderActivitySummary(response.data.summary);
                    renderOperationsTable(response.data.by_operation);
                } else {
                    showError(response.data.message || 'No se pudieron cargar las estadísticas');
                }
            },
            error: function() {
                showError('Error al cargar las estadísticas');
            }
        });
    }

    function showError(message) {
        $('#plan-info').html('<div class="phsbot-error">' + message + '</div>');
        $('#activity-summary').html('<div class="phsbot-error">' + message + '</div>');
        $('#operations-table').html('<div class="phsbot-error">' + message + '</div>');
    }

    function renderPlanInfo(summary, plan) {
        if (!plan) {
            $('#plan-info').html('<div class="phsbot-error">No se pudo cargar información del plan</div>');
            return;
        }

        const renewalText = plan.renewal_date || 'No disponible';
        const daysText = plan.days_remaining > 0 ? plan.days_remaining + ' días restantes' : 'Por renovar';

        const html = `
            <div class="phsbot-plan-row">
                <span class="phsbot-plan-label">Plan</span>
                <span class="phsbot-plan-value phsbot-plan-name">${escapeHtml(plan.name)}</span>
            </div>
            <div class="phsbot-plan-row">
                <span class="phsbot-plan-label">Límite mensual</span>
                <span class="phsbot-plan-value">${formatNumber(summary.tokens_limit)} tokens</span>
            </div>
            <div class="phsbot-plan-row">
                <span class="phsbot-plan-label">Renovación</span>
                <span class="phsbot-plan-value">${renewalText}</span>
            </div>
            <div class="phsbot-plan-row">
                <span class="phsbot-plan-label"></span>
                <span class="phsbot-plan-value phsbot-days">${daysText}</span>
            </div>
        `;

        $('#plan-info').html(html);
    }

    function renderTokensDisplay(summary) {
        const tokensAvailable = summary.tokens_available || 0;
        const usagePercent = summary.usage_percentage || 0;

        // Actualizar número
        $('#tokens-number').text(formatNumber(tokensAvailable));

        // Actualizar círculo de progreso (stroke-dashoffset)
        const circumference = 2 * Math.PI * 45; // radio 45
        const offset = circumference - (usagePercent / 100) * circumference;
        $('#tokens-circle').css('stroke-dashoffset', offset);

        // Color según porcentaje
        let color = '#4CAF50'; // Verde
        if (usagePercent > 80) color = '#f44336'; // Rojo
        else if (usagePercent > 60) color = '#ff9800'; // Naranja

        $('#tokens-circle').css('stroke', color);
    }

    function renderTimelineChart(timeline) {
        const ctx = document.getElementById('timeline-chart');

        if (!ctx) return;

        // Destruir gráfico anterior si existe
        if (timelineChart) {
            timelineChart.destroy();
        }

        const labels = timeline.map(d => d.date_formatted);
        const tokensData = timeline.map(d => d.tokens);
        const messagesData = timeline.map(d => d.messages);

        timelineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Tokens',
                        data: tokensData,
                        borderColor: '#667a3a',
                        backgroundColor: 'rgba(102, 122, 58, 0.1)',
                        yAxisID: 'y',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Mensajes',
                        data: messagesData,
                        borderColor: '#4c5e27',
                        backgroundColor: 'rgba(76, 94, 39, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolución Diaria'
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Tokens'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Mensajes'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                }
            }
        });
    }

    function renderActivitySummary(summary) {
        const html = `
            <div class="phsbot-stats-summary">
                <div class="phsbot-summary-item">
                    <div class="phsbot-summary-value">${formatNumber(summary.total_conversations)}</div>
                    <div class="phsbot-summary-label">Conversaciones</div>
                </div>
                <div class="phsbot-summary-item">
                    <div class="phsbot-summary-value">${formatNumber(summary.total_messages)}</div>
                    <div class="phsbot-summary-label">Mensajes</div>
                </div>
                <div class="phsbot-summary-item">
                    <div class="phsbot-summary-value">${formatNumber(summary.total_tokens)}</div>
                    <div class="phsbot-summary-label">Tokens Usados</div>
                </div>
                <div class="phsbot-summary-item">
                    <div class="phsbot-summary-value">${summary.usage_percentage.toFixed(1)}%</div>
                    <div class="phsbot-summary-label">Uso del Plan</div>
                </div>
            </div>
        `;

        $('#activity-summary').html(html);
    }

    function renderOperationsTable(operations) {
        if (!operations || operations.length === 0) {
            $('#operations-table').html('<div class="phsbot-empty">No hay operaciones registradas</div>');
            return;
        }

        let html = '<table class="phsbot-ops-table">';
        html += '<thead><tr>';
        html += '<th>Tipo de Operación</th>';
        html += '<th style="text-align: right;">Operaciones</th>';
        html += '<th style="text-align: right;">Tokens</th>';
        html += '</tr></thead><tbody>';

        operations.forEach(op => {
            const typeName = getOperationTypeName(op.type);
            html += '<tr>';
            html += '<td>' + escapeHtml(typeName) + '</td>';
            html += '<td style="text-align: right;">' + formatNumber(op.count) + '</td>';
            html += '<td style="text-align: right;">' + formatNumber(op.tokens) + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';

        $('#operations-table').html(html);
    }

    function getOperationTypeName(type) {
        const names = {
            'chat': 'Chat de Usuario',
            'translate_welcome': 'Traducción de Bienvenida',
            'generate_kb': 'Generación de Base de Conocimiento',
            'list_models': 'Listado de Modelos'
        };

        return names[type] || type;
    }

    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return parseInt(num).toLocaleString('es-ES');
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
