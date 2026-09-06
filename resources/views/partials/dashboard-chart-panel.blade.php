<div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 mb-8">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-2">
        <div>
            <h2 id="dashboardChartTitle" class="font-bold text-sm text-gray-800 uppercase tracking-wider"></h2>
            <p id="dashboardChartDescription" class="text-xs text-gray-500 font-medium mt-1 max-w-2xl"></p>
        </div>
        <div class="flex-shrink-0">
            <label for="dashboardChartSelector" class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-1.5">
                Select Insight
            </label>
            <select id="dashboardChartSelector"
                class="py-2.5 px-4 text-xs bg-gray-100 border border-gray-300 rounded-xl font-bold text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none cursor-pointer min-w-[240px]">
                @foreach($chartConfigs as $key => $config)
                    <option value="{{ $key }}" class="bg-white text-gray-700">{{ $config['title'] }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="relative h-72 w-full mt-4">
        <canvas id="dashboardInsightChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartConfigs = @json($chartConfigs);
        const selector = document.getElementById('dashboardChartSelector');
        const titleEl = document.getElementById('dashboardChartTitle');
        const descriptionEl = document.getElementById('dashboardChartDescription');
        const canvas = document.getElementById('dashboardInsightChart');

        if (!selector || !canvas || !chartConfigs) {
            return;
        }

        let activeChart = null;

        function buildOptions(config) {
            const isCircular = config.type === 'doughnut' || config.type === 'pie';

            if (isCircular) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 14,
                                font: { size: 10, weight: 'bold' },
                                color: '#94a3b8',
                                usePointStyle: true
                            }
                        }
                    },
                    cutout: '72%'
                };
            }

            const options = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: config.type === 'line',
                        labels: { color: '#94a3b8', font: { size: 10, weight: 'bold' } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: config.yMax ?? undefined,
                        title: config.yLabel ? {
                            display: true,
                            text: config.yLabel,
                            color: '#cbd5e1',
                            font: { size: 11, weight: 'bold' }
                        } : undefined,
                        grid: { color: '#1e293b' },
                        ticks: { color: '#94a3b8', font: { weight: 'bold', size: 11 } }
                    },
                    x: {
                        grid: { display: config.type === 'line', color: '#1e293b' },
                        ticks: { color: '#94a3b8', font: { size: 10, weight: 'bold' }, maxRotation: 45, minRotation: 0 }
                    }
                }
            };

            if (config.type === 'bar') {
                options.plugins.legend = { display: false };
                options.scales.y.grid.display = false;
            }

            return options;
        }

        function renderChart(key) {
            const config = chartConfigs[key];
            if (!config) {
                return;
            }

            titleEl.textContent = config.title;
            descriptionEl.textContent = config.description;

            if (activeChart) {
                activeChart.destroy();
            }

            activeChart = new Chart(canvas.getContext('2d'), {
                type: config.type,
                data: {
                    labels: config.labels,
                    datasets: config.datasets
                },
                options: buildOptions(config)
            });
        }

        selector.addEventListener('change', function () {
            renderChart(this.value);
        });

        renderChart(selector.value);
    });
</script>