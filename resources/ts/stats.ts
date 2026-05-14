import type { StatsRow } from './types';

interface Series {
    label: string;
    values: number[];
    color: string;
}

function loadData(): StatsRow[] {
    const el = document.getElementById('stats-data');
    if (!el) return [];
    try {
        return JSON.parse(el.textContent ?? '[]') as StatsRow[];
    } catch {
        return [];
    }
}

function maxOf(series: Series[]): number {
    let max = 0;
    for (const s of series) {
        for (const v of s.values) {
            if (v > max) max = v;
        }
    }
    return Math.max(1, max);
}

function drawChart(canvas: HTMLCanvasElement, rows: StatsRow[]): void {
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const ratio = window.devicePixelRatio || 1;
    const cssWidth = canvas.clientWidth || canvas.width;
    const cssHeight = canvas.clientHeight || canvas.height;
    canvas.width = Math.floor(cssWidth * ratio);
    canvas.height = Math.floor(cssHeight * ratio);
    ctx.scale(ratio, ratio);

    const padding = { top: 24, right: 24, bottom: 36, left: 40 };
    const innerW = cssWidth - padding.left - padding.right;
    const innerH = cssHeight - padding.top - padding.bottom;

    ctx.clearRect(0, 0, cssWidth, cssHeight);

    if (rows.length === 0) {
        ctx.fillStyle = '#777';
        ctx.font = '14px system-ui';
        ctx.textAlign = 'center';
        ctx.fillText('No data yet — complete a task or run a focus session.', cssWidth / 2, cssHeight / 2);
        return;
    }

    const series: Series[] = [
        { label: 'Points', values: rows.map((r) => r.points_earned), color: '#6366f1' },
        { label: 'Focus minutes', values: rows.map((r) => r.focus_minutes), color: '#10b981' },
        { label: 'On time', values: rows.map((r) => r.tasks_on_time), color: '#f59e0b' },
    ];
    const max = maxOf(series);

    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 1;
    ctx.beginPath();
    for (let i = 0; i <= 4; i++) {
        const y = padding.top + (innerH * i) / 4;
        ctx.moveTo(padding.left, y);
        ctx.lineTo(padding.left + innerW, y);
    }
    ctx.stroke();

    ctx.fillStyle = '#9ca3af';
    ctx.font = '11px system-ui';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    for (let i = 0; i <= 4; i++) {
        const y = padding.top + (innerH * i) / 4;
        const value = Math.round(max - (max * i) / 4);
        ctx.fillText(String(value), padding.left - 6, y);
    }

    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    rows.forEach((row, i) => {
        const x = padding.left + (innerW * i) / Math.max(1, rows.length - 1);
        if (i % 2 === 0) {
            ctx.fillText(row.label, x, padding.top + innerH + 8);
        }
    });

    const xFor = (i: number): number =>
        padding.left + (innerW * i) / Math.max(1, rows.length - 1);
    const yFor = (v: number): number =>
        padding.top + innerH - (innerH * v) / max;

    for (const s of series) {
        ctx.strokeStyle = s.color;
        ctx.fillStyle = s.color;
        ctx.lineWidth = 2;
        ctx.beginPath();
        s.values.forEach((v, i) => {
            const x = xFor(i);
            const y = yFor(v);
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.stroke();

        s.values.forEach((v, i) => {
            ctx.beginPath();
            ctx.arc(xFor(i), yFor(v), 3, 0, Math.PI * 2);
            ctx.fill();
        });
    }
}

const canvas = document.getElementById('stats-chart') as HTMLCanvasElement | null;
if (canvas) {
    const rows = loadData();
    drawChart(canvas, rows);
    window.addEventListener('resize', () => drawChart(canvas, rows));
}
