import { api, ApiError } from './api';
import { showToast } from './toast';
import type { FocusSessionRecord } from './types';

interface ActiveSession {
    id: number;
    plannedSeconds: number;
    startedAt: number;
    pausedAt: number | null;
    elapsedBeforePause: number;
    taskId: number | null;
}

const RING_CIRCUMFERENCE = 2 * Math.PI * 90;

const configEl = document.getElementById('focus-config')!;
const activeEl = document.getElementById('focus-active')!;
const taskSelect = document.getElementById('focus-task') as HTMLSelectElement | null;
const minutesInput = document.getElementById('focus-minutes') as HTMLInputElement | null;
const startBtn = document.getElementById('focus-start') as HTMLButtonElement | null;
const pauseBtn = document.getElementById('focus-pause') as HTMLButtonElement | null;
const stopBtn = document.getElementById('focus-stop') as HTMLButtonElement | null;
const timeDisplay = document.getElementById('time-display')!;
const statusEl = document.getElementById('focus-status')!;
const ring = document.getElementById('ring-progress') as unknown as SVGCircleElement | null;
const taskTitleEl = document.getElementById('focus-task-title');

let active: ActiveSession | null = null;
let tickHandle: number | null = null;

const focusMessages = [
    'Deep work in progress.',
    'One task at a time.',
    'Stay locked in.',
    'Small progress compounds.',
    'Focus creates momentum.',
];

if (ring) {
    ring.style.strokeDasharray = String(RING_CIRCUMFERENCE);
    ring.style.strokeDashoffset = String(RING_CIRCUMFERENCE);
}

document.querySelectorAll<HTMLButtonElement>('button[data-preset]').forEach((btn) => {
    btn.addEventListener('click', () => {
        if (minutesInput) minutesInput.value = btn.dataset.preset ?? '25';
    });
});

const params = new URLSearchParams(window.location.search);
const preselect = params.get('task_id');

function syncEstimatedMinutes(): void {
    if (!taskSelect || !minutesInput) return;

    const selectedOption = taskSelect.selectedOptions[0];
    if (!selectedOption) return;

    const estimate = selectedOption.dataset.estimate;

    if (estimate && Number(estimate) > 0) {
        minutesInput.value = estimate;
    } else {
        minutesInput.value = '25';
    }
}

if (preselect && taskSelect) {
    taskSelect.value = preselect;
}

taskSelect?.addEventListener('change', syncEstimatedMinutes);

syncEstimatedMinutes();

function formatTime(secondsLeft: number): string {
    const m = Math.floor(secondsLeft / 60).toString().padStart(2, '0');
    const s = Math.floor(secondsLeft % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

function elapsedSeconds(): number {
    if (!active) return 0;
    if (active.pausedAt !== null) {
        return active.elapsedBeforePause;
    }
    return active.elapsedBeforePause + Math.floor((Date.now() - active.startedAt) / 1000);
}

function tick(): void {
    if (!active) return;
    const elapsed = elapsedSeconds();
    const remaining = Math.max(0, active.plannedSeconds - elapsed);
    timeDisplay.textContent = formatTime(remaining);
    if (ring) {
        const progress = Math.min(1, elapsed / active.plannedSeconds);
        ring.style.strokeDashoffset = String(RING_CIRCUMFERENCE * (1 - progress));
    }

    const minuteIndex = Math.floor(elapsed / 60);
    statusEl.textContent =
        focusMessages[minuteIndex % focusMessages.length];

    if (remaining === 0) {
        finishSession(true);
    }
}

function startTicking(): void {
    if (tickHandle !== null) return;
    tickHandle = window.setInterval(tick, 250);
    tick();
}

function stopTicking(): void {
    if (tickHandle !== null) {
        clearInterval(tickHandle);
        tickHandle = null;
    }
}

async function startSession(): Promise<void> {
    if (!minutesInput) return;
    const minutes = Math.max(1, Math.min(180, Number(minutesInput.value) || 25));
    const taskId = taskSelect && taskSelect.value ? Number(taskSelect.value) : null;
    const quadrant =
        taskSelect?.selectedOptions[0]?.dataset.quadrant ?? 'schedule';

    document.body.dataset.focusQuadrant = quadrant;

    if (startBtn) startBtn.disabled = true;
    try {
        const res = await api.post<{ session: FocusSessionRecord }>('/focus/start', {
            task_id: taskId,
            planned_minutes: minutes,
        });
        const selectedText =
            taskSelect?.selectedOptions[0]?.textContent?.trim()
            ?? 'Standalone Focus Session';

        if (taskTitleEl) {
            taskTitleEl.textContent = selectedText;
        }
        active = {
            id: res.session.id,
            plannedSeconds: minutes * 60,
            startedAt: Date.now(),
            pausedAt: null,
            elapsedBeforePause: 0,
            taskId: taskId,
        };
        configEl.classList.add('hidden');
        activeEl.classList.remove('hidden');
        statusEl.textContent = 'Stay focused. Distractions can wait.';
        startTicking();
    } catch (err) {
        showToast(extractError(err, 'Could not start focus session.'), 'error');
    } finally {
        if (startBtn) startBtn.disabled = false;
    }
}

async function finishSession(completed: boolean): Promise<void> {
    if (!active) return;
    stopTicking();
    const sessionId = active.id;
    const actualMinutes = Math.min(
        Math.floor(active.plannedSeconds / 60),
        Math.max(0, Math.round(elapsedSeconds() / 60)),
    );
    const wasActive = active;
    active = null;

    try {
        const res = await api.post<{ session: FocusSessionRecord; points_awarded: number; user: { points: number; level: number; streak_days: number } }>(
            `/focus/${sessionId}/finish`,
            {
                completed,
                actual_minutes: actualMinutes,
            },
        );
        showToast(
            completed
                ? `Focus complete! +${res.points_awarded} pts`
                : 'Session ended early.',
            completed ? 'success' : 'info',
        );
        updateBadges(res.user.points, res.user.level, res.user.streak_days);
        if (completed) {
            window.setTimeout(() => window.location.reload(), 1200);
        } else {
            resetUi();
        }
    } catch (err) {
        active = wasActive;
        startTicking();
        showToast(extractError(err, 'Could not finish session.'), 'error');
    }
}

function updateBadges(points: number, level: number, streak: number): void {
    const pointsBadge = document.querySelector<HTMLElement>('.badge.points');
    const levelBadge = document.querySelector<HTMLElement>('.badge.level');
    const streakBadge = document.querySelector<HTMLElement>('.badge.streak');
    if (pointsBadge) pointsBadge.textContent = `${points} pts`;
    if (levelBadge) levelBadge.textContent = `Lv ${level}`;
    if (streakBadge) streakBadge.textContent = `🔥 ${streak}`;
}

function resetUi(): void {
    activeEl.classList.add('hidden');
    configEl.classList.remove('hidden');
    timeDisplay.textContent = `${(Number(minutesInput?.value) || 25).toString().padStart(2, '0')}:00`;
    statusEl.textContent = 'Stay focused.';
    if (ring) ring.style.strokeDashoffset = String(RING_CIRCUMFERENCE);
    delete document.body.dataset.focusQuadrant;
    if (pauseBtn) pauseBtn.textContent = 'Pause';
}

function togglePause(): void {
    if (!active) return;
    if (active.pausedAt === null) {
        active.elapsedBeforePause = elapsedSeconds();
        active.pausedAt = Date.now();
        stopTicking();
        statusEl.textContent = 'Paused. Take a breath, then resume.';
        if (pauseBtn) pauseBtn.textContent = 'Resume';
    } else {
        active.startedAt = Date.now();
        active.pausedAt = null;
        startTicking();
        statusEl.textContent = 'Back at it.';
        if (pauseBtn) pauseBtn.textContent = 'Pause';
    }
}

function extractError(err: unknown, fallback: string): string {
    if (err instanceof ApiError) {
        const payload = err.payload as { message?: string; errors?: Record<string, string[]> } | null;
        if (payload?.errors) {
            const first = Object.values(payload.errors)[0];
            if (first && first.length > 0) return first[0];
        }
        if (payload?.message) return payload.message;
    }
    return fallback;
}

startBtn?.addEventListener('click', startSession);
pauseBtn?.addEventListener('click', togglePause);
stopBtn?.addEventListener('click', () => {
    if (!active) return;
    if (!confirm('End this focus session early?')) return;
    finishSession(false);
});

window.addEventListener('beforeunload', (event) => {
    if (active) {
        event.preventDefault();
        event.returnValue = '';
    }
});
