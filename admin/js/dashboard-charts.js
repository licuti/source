/* Dashboard charts — chỉ chạy khi có canvas + data JSON */
(function () {
    'use strict';

    function readJson(id) {
        var el = document.getElementById(id);
        if (!el) return null;
        try {
            return JSON.parse(el.textContent || el.innerText || 'null');
        } catch (e) {
            return null;
        }
    }

    function cssColor(name, fallback) {
        try {
            var v = getComputedStyle(document.documentElement).getPropertyValue(name);
            v = (v || '').trim();
            return v || fallback;
        } catch (e) {
            return fallback;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Chart === 'undefined') return;

        var primary = cssColor('--bs-primary', '#0d6efd');
        var success = cssColor('--bs-success', '#198754');
        var warning = cssColor('--bs-warning', '#ffc107');
        var danger  = cssColor('--bs-danger', '#dc3545');
        var info    = cssColor('--bs-info', '#0dcaf0');
        var secondary = cssColor('--bs-secondary', '#6c757d');

        var orders = readJson('dash-chart-orders-data');
        var canvasOrders = document.getElementById('dashChartOrders');
        if (canvasOrders && orders && orders.labels && orders.labels.length) {
            new Chart(canvasOrders.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: orders.labels,
                    datasets: [{
                        label: 'Đơn hàng',
                        data: orders.values,
                        backgroundColor: 'rgba(13, 110, 253, 0.55)',
                        borderColor: primary,
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 28
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ' ' + (ctx.parsed.y || 0) + ' đơn';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(0,0,0,0.06)' }
                        }
                    }
                }
            });
        }

        var status = readJson('dash-chart-status-data');
        var canvasStatus = document.getElementById('dashChartStatus');
        if (canvasStatus && status && status.labels && status.labels.length) {
            var colors = [secondary, info, warning, success, danger, primary];
            new Chart(canvasStatus.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: status.labels,
                    datasets: [{
                        data: status.values,
                        backgroundColor: colors.slice(0, status.labels.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 12 }
                        }
                    },
                    cutout: '62%'
                }
            });
        }
    });
})();