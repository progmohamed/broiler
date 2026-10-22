import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

export const mountCharts = (root, charts) => {
    root.querySelectorAll('[data-assistant-chart]').forEach((container) => {
        if (charts.has(container)) {
            return;
        }

        const data = JSON.parse(container.dataset.assistantChart);

        if (data.values.length === 0) {
            return;
        }

        const card = document.createElement('div');
        card.className = 'ios-card flex flex-col gap-3 p-3';
        const heading = document.createElement('h4');
        heading.className = 'text-sm font-semibold text-slate-700';
        heading.textContent = `${data.title} (${data.unit})`;
        const chartFrame = document.createElement('div');
        chartFrame.className = 'relative h-56 w-full';
        chartFrame.dir = 'ltr';
        const canvas = document.createElement('canvas');
        canvas.setAttribute('role', 'img');
        canvas.setAttribute('aria-label', `${data.title}، الوحدة ${data.unit}. القيم متاحة في جدول التفاصيل التالي.`);
        chartFrame.append(canvas);
        card.append(heading, chartFrame);
        container.append(card);

        try {
            const chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: data.unit,
                        data: data.values,
                        backgroundColor: { blue: '#007aff', green: '#15803d', orange: '#ea580c', red: '#dc2626' }[data.tone] ?? '#007aff',
                        borderRadius: 4,
                        maxBarThickness: 28,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    locale: 'ar-EG',
                    font: { family: getComputedStyle(root).fontFamily },
                    plugins: { tooltip: { rtl: true, textDirection: 'rtl' } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 6, maxRotation: 0, font: { size: 9 } } },
                        y: { beginAtZero: true, ticks: { maxTicksLimit: 5 } },
                    },
                },
            });
            charts.set(container, chart);
            container.querySelector('[data-assistant-chart-fallback]').hidden = true;
        } catch {
            Chart.getChart(canvas)?.destroy();
            card.remove();
        }
    });
};
