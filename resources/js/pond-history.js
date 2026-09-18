import Chart from 'chart.js/auto';

const CHARTS = {
    temperature: { label: 'Temperatura', unit: '°C', color: '#0e7490' },
    ph: { label: 'pH', unit: '', color: '#2563eb' },
    turbidity: { label: 'Turbidez', unit: 'NTU', color: '#d97706' },
    water_level: { label: 'Nivel del agua', unit: '%', color: '#047857' },
};

const instances = new Map();

const formatTick = (iso) => {
    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return iso;
    }

    return date.toLocaleString('es', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const setHidden = (element, hidden) => {
    if (!element) {
        return;
    }

    element.classList.toggle('hidden', hidden);
};

const thresholdDataset = (label, value, labels, color) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return {
        label,
        data: labels.map(() => Number(value)),
        borderColor: color,
        borderWidth: 1.5,
        borderDash: [6, 4],
        pointRadius: 0,
        pointHoverRadius: 0,
        fill: false,
        tension: 0,
    };
};

const renderChart = (canvas, key, readings, thresholds) => {
    const config = CHARTS[key];
    const labels = readings.map((reading) => reading.recorded_at);
    const values = readings.map((reading) => Number(reading[key]));
    const series = [
        {
            label: config.label,
            data: values,
            borderColor: config.color,
            backgroundColor: `${config.color}1f`,
            borderWidth: 2,
            pointRadius: readings.length > 40 ? 0 : 2.5,
            pointHoverRadius: 4,
            fill: true,
            tension: 0.25,
        },
        thresholdDataset('Mínimo', thresholds?.min, labels, '#94a3b8'),
        thresholdDataset('Máximo', thresholds?.max, labels, '#e11d48'),
    ].filter(Boolean);

    const numericHints = [
        ...values,
        thresholds?.min,
        thresholds?.max,
    ].filter((value) => value !== null && value !== undefined && value !== '').map(Number);

    instances.get(canvas)?.destroy();

    const chart = new Chart(canvas, {
        type: 'line',
        data: { labels, datasets: series },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => formatTick(items[0]?.label ?? ''),
                        label: (item) => {
                            const unit = config.unit ? ` ${config.unit}` : '';
                            return `${item.dataset.label}: ${item.formattedValue}${unit}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 5,
                        callback: (value) => formatTick(labels[value] ?? ''),
                        color: '#64748b',
                        font: { size: 10 },
                    },
                    grid: { display: false },
                },
                y: {
                    suggestedMin: numericHints.length ? Math.min(...numericHints) : undefined,
                    suggestedMax: numericHints.length ? Math.max(...numericHints) : undefined,
                    ticks: {
                        color: '#64748b',
                        font: { size: 10 },
                    },
                    grid: { color: '#e2e8f0' },
                },
            },
        },
    });

    instances.set(canvas, chart);
};

const loadHistory = async (root, range) => {
    const url = new URL(root.dataset.historyUrl, window.location.origin);
    url.searchParams.set('range', range);

    const status = root.querySelector('[data-history-status]');
    const empty = root.querySelector('[data-history-empty]');
    const error = root.querySelector('[data-history-error]');
    const charts = root.querySelector('[data-history-charts]');

    setHidden(error, true);
    setHidden(empty, true);
    setHidden(status, false);
    status.textContent = 'Cargando historial…';

    try {
        const response = await fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('history_failed');
        }

        const payload = await response.json();
        const readings = Array.isArray(payload.readings) ? payload.readings : [];
        const thresholds = JSON.parse(root.dataset.thresholds || '{}');

        setHidden(status, true);

        if (readings.length === 0) {
            instances.forEach((chart) => chart.destroy());
            instances.clear();
            setHidden(charts, true);
            setHidden(empty, false);
            return;
        }

        setHidden(charts, false);
        root.querySelectorAll('[data-history-chart]').forEach((canvas) => {
            renderChart(canvas, canvas.dataset.historyChart, readings, thresholds[canvas.dataset.historyChart] ?? {});
        });
    } catch {
        setHidden(status, true);
        setHidden(charts, true);
        setHidden(empty, true);
        setHidden(error, false);
    }
};

const bindRangeButtons = (root) => {
    const buttons = [...root.querySelectorAll('[data-history-range]')];

    const activate = (selected) => {
        buttons.forEach((button) => {
            const isActive = button.dataset.historyRange === selected;
            button.classList.toggle('border-slate-900', isActive);
            button.classList.toggle('bg-slate-900', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('border-slate-200', !isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('text-slate-600', !isActive);
            button.setAttribute('aria-pressed', String(isActive));
        });
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const range = button.dataset.historyRange;
            activate(range);
            loadHistory(root, range);
        });
    });

    activate('24h');
    loadHistory(root, '24h');
};

document.querySelectorAll('[data-pond-history]').forEach((root) => {
    bindRangeButtons(root);
});
