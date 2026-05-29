import { api } from './api';
import type { Task } from './types';

const REMINDER_WINDOW_MINUTES = 30;
const CHECK_INTERVAL = 60_000;

const remindedTasks = new Set<number>();

function showInAppReminder(task: Task): void {
    const host = document.getElementById('toast-host');

    if (!host) return;

    const toast = document.createElement('div');

    toast.className = 'toast toast-warning visible';

    toast.innerHTML = `
        <strong>⏰ Task Reminder</strong>
        <div style="margin-top: 0.35rem;">
            "${task.title}" is due soon.
        </div>
    `;

    host.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('visible');

        setTimeout(() => {
            toast.remove();
        }, 250);
    }, 7000);
}

function showBrowserReminder(task: Task): void {
    if (Notification.permission !== 'granted') {
        return;
    }

    new Notification('Task Due Soon', {
        body: `"${task.title}" is due soon.`,
        icon: '/favicon.ico',
    });
}

function shouldRemind(task: Task): boolean {
    if (!task.due_at) return false;

    if (task.status === 'completed') return false;

    const due = new Date(task.due_at).getTime();

    if (Number.isNaN(due)) return false;

    const now = Date.now();

    const diffMinutes = (due - now) / 60000;

    return diffMinutes > 0
        && diffMinutes <= REMINDER_WINDOW_MINUTES;
}

async function checkReminders(): Promise<void> {
    try {
        const res = await api.get<{ tasks: Task[] }>('/api/tasks');

        res.tasks.forEach((task) => {
            if (!shouldRemind(task)) return;

            if (remindedTasks.has(task.id)) return;

            remindedTasks.add(task.id);

            showInAppReminder(task);

            showBrowserReminder(task);
        });

    } catch (err) {
        console.error('Reminder check failed', err);
    }
}

async function requestNotificationPermission(): Promise<void> {
    if (!('Notification' in window)) {
        return;
    }

    if (Notification.permission === 'default') {
        await Notification.requestPermission();
    }
}

export async function initReminders(): Promise<void> {
    await requestNotificationPermission();

    checkReminders();

    setInterval(checkReminders, CHECK_INTERVAL);
}