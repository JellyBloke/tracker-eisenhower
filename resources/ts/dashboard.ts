import { api, ApiError } from './api';
import { showToast } from './toast';
import type { CompletionResponse, Quadrant, Task, Tag } from './types';

const QUADRANT_LABELS: Record<Quadrant, string> = {
    do: 'Do',
    schedule: 'Schedule',
    delegate: 'Delegate',
    eliminate: 'Eliminate',
};

const URGENT_WINDOW_HOURS = 48;

function quadrantFor(urgent: boolean, important: boolean): Quadrant {
    if (urgent && important) return 'do';
    if (!urgent && important) return 'schedule';
    if (urgent && !important) return 'delegate';
    return 'eliminate';
}

function inferUrgent(dueAtIso: string | null, estimatedMinutes: number | null): boolean {
    if (!dueAtIso) return false;
    const due = new Date(dueAtIso);
    if (Number.isNaN(due.getTime())) return false;
    const now = Date.now();
    if (due.getTime() <= now) return true;
    const minutesUntilDue = (due.getTime() - now) / 60000;
    if (minutesUntilDue <= URGENT_WINDOW_HOURS * 60) return true;
    if (estimatedMinutes !== null && estimatedMinutes > 0
        && minutesUntilDue <= estimatedMinutes * 2) return true;
    return false;
}

function $(selector: string, root: ParentNode = document): HTMLElement | null {
    return root.querySelector<HTMLElement>(selector);
}

function bindQuadrantPreview(form: HTMLFormElement): () => void {
    const important = form.querySelector<HTMLInputElement>('input[name="is_important"]');
    const due = form.querySelector<HTMLInputElement>('input[name="due_at"]');
    const estimate = form.querySelector<HTMLInputElement>('input[name="estimated_minutes"]');
    const preview = form.querySelector<HTMLElement>('[data-preview]');
    if (!important || !due || !preview) return () => {};

    const update = (): void => {
        const dueIso = due.value || null;
        const est = estimate && estimate.value ? Number(estimate.value) : null;
        const urgent = inferUrgent(dueIso, est);
        const q = quadrantFor(urgent, important.checked);
        preview.textContent = `→ ${QUADRANT_LABELS[q]}`;
        preview.dataset.quadrant = q;
        preview.title = urgent
            ? 'Urgent: due soon or tight schedule.'
            : 'Not urgent based on due date.';
    };

    important.addEventListener('change', update);
    due.addEventListener('input', update);
    estimate?.addEventListener('input', update);
    update();
    return update;
}

function renderTaskItem(task: Task): HTMLLIElement {
    const li = document.createElement('li');
    li.className = `task ${task.status === 'completed' ? 'completed' : ''}`;
    li.dataset.taskId = String(task.id);
    li.dataset.quadrant = task.quadrant;
    li.draggable = true;

    const meta: string[] = [];
    if (task.estimated_minutes) meta.push(`⏱ ${task.estimated_minutes}m`);
    if (task.focus_minutes) meta.push(`🎯 ${task.focus_minutes}m`);
    if (task.points_awarded) meta.push(`⭐ ${task.points_awarded}`);

    const due = task.due_at ? new Date(task.due_at) : null;
    const dueLabel = due
        ? `<span class="due">Due ${due.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>`
        : '';

    const tagsHtml = task.tags?.length
    ? `
        <div class="task-tags">
            ${task.tags.map((tag) => `
                <span
                    class="task-tag"
                    style="background-color: ${tag.color}"
                >
                    ${tag.name}
                </span>
            `).join('')}
        </div>
    `
    : '';

    li.innerHTML = `
        <div class="task-main">
            <label class="checkbox">
                <input type="checkbox" data-action="complete" ${task.status === 'completed' ? 'checked disabled' : ''}>
                <span class="task-title"></span>
            </label>
            ${dueLabel}
        </div>
        ${tagsHtml}
        ${task.description ? '<p class="task-desc"></p>' : ''}
        <div class="task-meta">
            ${meta.map((m) => `<span>${m}</span>`).join('')}
            <button type="button" class="link" data-action="focus" data-task-id="${task.id}">Focus</button>
            <button type="button" class="link danger" data-action="delete">Delete</button>
        </div>
    `;

    const title = li.querySelector<HTMLSpanElement>('.task-title');
    if (title) title.textContent = task.title;
    if (task.description) {
        const desc = li.querySelector<HTMLParagraphElement>('.task-desc');
        if (desc) desc.textContent = task.description;
    }

    attachTaskHandlers(li);
    return li;
}

function appendTaskToQuadrant(task: Task): void {
    const list = document.querySelector<HTMLUListElement>(`[data-dropzone="${task.quadrant}"]`);
    if (!list) return;
    list.appendChild(renderTaskItem(task));
}

function removeTaskFromDom(taskId: number): void {
    const el = document.querySelector<HTMLLIElement>(`li.task[data-task-id="${taskId}"]`);
    el?.remove();
}

function attachTaskHandlers(li: HTMLLIElement): void {
    li.addEventListener('dragstart', (event) => {
        const id = li.dataset.taskId;
        if (!id || !event.dataTransfer) return;
        event.dataTransfer.setData('text/plain', id);
        event.dataTransfer.effectAllowed = 'move';
        li.classList.add('dragging');
    });
    li.addEventListener('dragend', () => li.classList.remove('dragging'));

    const completeCheckbox = li.querySelector<HTMLInputElement>('input[data-action="complete"]');
    completeCheckbox?.addEventListener('change', async () => {
        const taskId = Number(li.dataset.taskId);
        if (!completeCheckbox.checked) return;
        completeCheckbox.disabled = true;
        try {
            const res = await api.post<CompletionResponse>(`/api/tasks/${taskId}/complete`);
            li.classList.add('completed');
            updateUserBadges(res.user.points, res.user.level, res.user.streak_days);
            showToast(`+${res.points_awarded} pts • ${res.task.title}`, 'success');
        } catch (err) {
            completeCheckbox.checked = false;
            completeCheckbox.disabled = false;
            showToast(formatError(err, 'Could not complete task.'), 'error');
        }
    });

    li.querySelector<HTMLButtonElement>('[data-action="delete"]')?.addEventListener('click', async () => {
        const taskId = Number(li.dataset.taskId);
        if (!confirm('Delete this task?')) return;
        try {
            await api.delete(`/api/tasks/${taskId}`);
            removeTaskFromDom(taskId);
        } catch (err) {
            showToast(formatError(err, 'Could not delete.'), 'error');
        }
    });

    li.querySelector<HTMLButtonElement>('[data-action="focus"]')?.addEventListener('click', () => {
        const taskId = li.dataset.taskId ?? '';
        window.location.href = `/focus?task_id=${encodeURIComponent(taskId)}`;
    });
}

function setupDropzones(): void {
    document.querySelectorAll<HTMLUListElement>('[data-dropzone]').forEach((zone) => {
        zone.addEventListener('dragover', (event) => {
            event.preventDefault();
            zone.classList.add('drag-over');
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
        });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', async (event) => {
            event.preventDefault();
            zone.classList.remove('drag-over');
            const id = event.dataTransfer?.getData('text/plain');
            if (!id) return;
            const targetQuadrant = (zone.dataset.dropzone as Quadrant) ?? 'schedule';
            const li = document.querySelector<HTMLLIElement>(`li.task[data-task-id="${id}"]`);
            if (!li) return;
            if (li.dataset.quadrant === targetQuadrant) return;

            const previousQuadrant = li.dataset.quadrant;
            zone.appendChild(li);
            li.dataset.quadrant = targetQuadrant;

            try {
                await api.patch<{ task: Task }>(`/api/tasks/${id}/quadrant`, { quadrant: targetQuadrant });
                showToast(`Moved to ${QUADRANT_LABELS[targetQuadrant]}`, 'info');
            } catch (err) {
                if (previousQuadrant) {
                    const prevZone = document.querySelector<HTMLUListElement>(`[data-dropzone="${previousQuadrant}"]`);
                    prevZone?.appendChild(li);
                    li.dataset.quadrant = previousQuadrant;
                }
                showToast(formatError(err, 'Could not move task.'), 'error');
            }
        });
    });
}

function updateUserBadges(points: number, level: number, streak: number): void {
    const pointsBadge = document.querySelector<HTMLElement>('.badge.points');
    const levelBadge = document.querySelector<HTMLElement>('.badge.level');
    const streakBadge = document.querySelector<HTMLElement>('.badge.streak');
    if (pointsBadge) pointsBadge.textContent = `${points} pts`;
    if (levelBadge) levelBadge.textContent = `Lv ${level}`;
    if (streakBadge) streakBadge.textContent = `🔥 ${streak}`;
}

function formatError(err: unknown, fallback: string): string {
    if (err instanceof ApiError) {
        const payload = err.payload as { errors?: Record<string, string[]>; message?: string } | null;
        if (payload?.errors) {
            const first = Object.values(payload.errors)[0];
            if (first && first.length > 0) return first[0];
        }
        if (payload?.message) return payload.message;
    }
    return fallback;
}

function setupCreateForm(): void {
    const form = document.getElementById('task-form') as HTMLFormElement | null;
    if (!form) return;
    const refreshPreview = bindQuadrantPreview(form);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const payload = {
            title: String(formData.get('title') ?? '').trim(),
            description: (formData.get('description') as string) || null,
            is_important: formData.get('is_important') === '1',
            due_at: (formData.get('due_at') as string) || null,
            estimated_minutes: formData.get('estimated_minutes')
                ? Number(formData.get('estimated_minutes'))
                : null,
            tags: formData.getAll('tags[]').map(Number),
        };

        if (!payload.title) {
            showToast('Title is required.', 'error');
            return;
        }

        try {
            const res = await api.post<{ task: Task }>('/api/tasks', payload);
            appendTaskToQuadrant(res.task);
            form.reset();
            refreshPreview();
            showToast('Task added.', 'success');
        } catch (err) {
            showToast(formatError(err, 'Could not add task.'), 'error');
        }
    });
}

function setupTagModal(): void {
    const modal = document.getElementById('tag-modal');
    const openButton = document.getElementById('open-tag-modal');
    const closeButton = modal?.querySelector<HTMLElement>('[data-close-tag-modal]');
    const form = document.getElementById('tag-form') as HTMLFormElement | null;
    const tagSelect = document.querySelector<HTMLSelectElement>('select[name="tags[]"]');

    if (!modal || !openButton || !closeButton || !form || !tagSelect) {
        return;
    }

    const openModal = (): void => {
        modal.classList.remove('hidden');
    };

    const closeModal = (): void => {
        modal.classList.add('hidden');
        form.reset();
    };

    openButton.addEventListener('click', openModal);

    closeButton.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(form);

        const payload = {
            name: String(formData.get('name') ?? '').trim(),
            color: String(formData.get('color') ?? '#3B82F6'),
        };

        if (!payload.name) {
            showToast('Tag name is required.', 'error');
            return;
        }

        try {
            const res = await api.post<{ tag: Tag }>('/api/tags', payload);

            const option = document.createElement('option');

            option.value = String(res.tag.id);
            option.textContent = res.tag.name;
            option.selected = true;

            tagSelect.appendChild(option);

            closeModal();

            showToast('Tag created.', 'success');

        } catch (err) {
            showToast(formatError(err, 'Could not create tag.'), 'error');
        }
    });
}

function setupTagFilters(): void {
    document.querySelectorAll<HTMLElement>('.tag-filter').forEach((button) => {
        button.addEventListener('click', async () => {
            const tagId = button.dataset.tag ?? '';

            document.querySelectorAll('.tag-filter')
                .forEach((b) => b.classList.remove('active'));

            button.classList.add('active');

            try {
                const query = tagId
                    ? `/api/tasks?tag=${tagId}`
                    : '/api/tasks';

                const res = await api.get<{ tasks: Task[] }>(query);

                document.querySelectorAll<HTMLUListElement>('.task-list')
                    .forEach((list) => {
                        list.innerHTML = '';
                    });

                res.tasks.forEach(appendTaskToQuadrant);

            } catch (err) {
                showToast(formatError(err, 'Could not filter tasks.'), 'error');
            }
        });
    });
}

document.querySelectorAll<HTMLLIElement>('li.task').forEach(attachTaskHandlers);
setupDropzones();
setupCreateForm();
setupTagModal();
setupTagFilters();
