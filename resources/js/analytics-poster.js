const colors = {
    navy: '#0f172a',
    navyLight: '#1e293b',
    red: '#ef4444',
    gold: '#fbbf24',
    white: '#ffffff',
    muted: '#94a3b8',
};

function roundedPanel(context, x, y, width, height, radius, fill) {
    context.beginPath();
    context.roundRect(x, y, width, height, radius);
    context.fillStyle = fill;
    context.fill();
}

function label(context, value, x, y, size, color, weight = 500) {
    context.fillStyle = color;
    context.font = `${weight} ${size}px system-ui, -apple-system, sans-serif`;
    context.fillText(value, x, y);
}

function fitText(context, value, maxWidth) {
    let text = String(value);

    while (text.length > 1 && context.measureText(text).width > maxWidth) {
        text = `${text.slice(0, -2)}…`;
    }

    return text;
}

function drawPoster(data) {
    const canvas = document.createElement('canvas');
    canvas.width = 1200;
    canvas.height = 628;
    const context = canvas.getContext('2d');

    context.fillStyle = colors.navy;
    context.fillRect(0, 0, canvas.width, canvas.height);

    const glow = context.createRadialGradient(1130, 40, 0, 1130, 40, 560);
    glow.addColorStop(0, '#7f1d1d');
    glow.addColorStop(1, colors.navy);
    context.fillStyle = glow;
    context.fillRect(600, 0, 600, 470);

    roundedPanel(context, 48, 48, 8, 52, 4, colors.red);
    label(context, 'APPLICATION ANALYTICS', 76, 70, 16, colors.gold, 800);
    context.font = '800 42px system-ui, -apple-system, sans-serif';
    label(context, fitText(context, data.name, 1050), 76, 121, 42, colors.white, 800);
    label(context, `${data.period}  ·  ${data.route}`, 48, 158, 19, '#cbd5e1', 500);

    const metrics = [
        ['TOTAL REQUESTS', data.requests.toLocaleString(), 'Traffic'],
        ['DAILY ANONYMOUS IDS', data.visitors.toLocaleString(), 'Estimated'],
        ['API REQUESTS', data.apiRequests.toLocaleString(), 'Endpoints'],
        ['AVG LATENCY', `${data.latency} ms`, 'Response'],
    ];

    metrics.forEach(([title, value, caption], index) => {
        const x = 48 + index * 282;
        roundedPanel(context, x, 190, 264, 132, 18, colors.navyLight);
        label(context, title, x + 22, 224, 13, colors.muted, 700);
        context.font = '800 37px system-ui, -apple-system, sans-serif';
        label(context, fitText(context, value, 222), x + 22, 274, 37, colors.white, 800);
        label(context, caption, x + 22, 301, 13, colors.gold, 600);
    });

    label(context, 'TRAFFIC VOLUME', 48, 370, 17, colors.white, 800);
    const points = Object.entries(data.chart);
    const chartX = 48;
    const chartY = 397;
    const chartWidth = 1104;
    const chartHeight = 135;
    const maximum = Math.max(1, ...points.map(([, count]) => Number(count)));
    const gap = Math.min(8, chartWidth / Math.max(1, points.length) / 3);
    const barWidth = points.length ? (chartWidth - gap * (points.length - 1)) / points.length : 0;

    context.strokeStyle = '#334155';
    context.lineWidth = 1;
    context.beginPath();
    context.moveTo(chartX, chartY + chartHeight);
    context.lineTo(chartX + chartWidth, chartY + chartHeight);
    context.stroke();

    points.forEach(([, count], index) => {
        const height = Math.max(4, Number(count) / maximum * chartHeight);
        const x = chartX + index * (barWidth + gap);
        const gradient = context.createLinearGradient(0, chartY + chartHeight - height, 0, chartY + chartHeight);
        gradient.addColorStop(0, colors.red);
        gradient.addColorStop(1, '#991b1b');
        roundedPanel(context, x, chartY + chartHeight - height, barWidth, height, Math.min(4, barWidth / 2), gradient);
    });

    if (points.length) {
        context.textAlign = 'left';
        label(context, points[0][0], chartX, 557, 13, colors.muted);
        context.textAlign = 'center';
        label(context, points[Math.floor((points.length - 1) / 2)][0], chartX + chartWidth / 2, 557, 13, colors.muted);
        context.textAlign = 'right';
        label(context, points[points.length - 1][0], chartX + chartWidth, 557, 13, colors.muted);
        context.textAlign = 'left';
    }

    context.fillStyle = '#334155';
    context.fillRect(48, 578, 1104, 1);
    label(context, 'Daily anonymous IDs are estimates, not unique people.', 48, 606, 13, colors.muted);
    context.textAlign = 'right';
    label(context, 'MALAYSIA HOLIDAY API', 1152, 606, 13, colors.gold, 800);

    return canvas;
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-analytics-poster]');

    if (!button) {
        return;
    }

    const data = JSON.parse(button.dataset.analyticsPoster);
    const canvas = drawPoster(data);
    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = `malaysia-holiday-api-analytics-${new Date().toISOString().slice(0, 10)}.png`;
    link.click();
});
