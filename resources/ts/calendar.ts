import { api } from './api';

const modal =
    document.getElementById(
        'calendar-task-modal'
    );

const title =
    document.getElementById(
        'calendar-task-title'
    );

const desc =
    document.getElementById(
        'calendar-task-desc'
    );

const due =
    document.getElementById(
        'calendar-task-due'
    );

const quadrant =
    document.getElementById(
        'calendar-task-quadrant'
    );

const estimate =
    document.getElementById(
        'calendar-task-estimate'
    );

function closeModal(): void {
    modal?.classList.add('hidden');
}

function openModal(): void {
    modal?.classList.remove('hidden');
}

document
    .querySelectorAll<HTMLElement>('.calendar-task')
    .forEach((card) => {

        card.addEventListener('click', async () => {

            const taskId =
                card.dataset.taskId;

            if (!taskId) {
                return;
            }

            try {

                const res = await api.get<{
                    task: {
                        title: string;
                        description: string | null;
                        due_at: string | null;
                        quadrant: string;
                        estimated_minutes: number | null;
                    };
                }>(`/api/tasks/${taskId}`);

                const task = res.task;

                title!.textContent =
                    task.title;

                desc!.textContent =
                    task.description ||
                    'No description provided.';

                due!.textContent =
                    task.due_at
                        ? new Date(task.due_at)
                            .toLocaleString()
                        : 'No due date';

                quadrant!.textContent =
                    task.quadrant
                        .charAt(0)
                        .toUpperCase()
                    + task.quadrant.slice(1);

                estimate!.textContent =
                    task.estimated_minutes
                        ? `${task.estimated_minutes} minutes`
                        : 'Not specified';

                openModal();

            } catch (error) {

                console.error(error);

                alert(
                    'Could not load task details.'
                );

            }

        });

    });

document
    .getElementById('calendar-close')
    ?.addEventListener(
        'click',
        closeModal
    );

modal?.addEventListener(
    'click',
    (event) => {

        if (event.target === modal) {
            closeModal();
        }

    }
);

document.addEventListener(
    'keydown',
    (event) => {

        if (
            event.key === 'Escape' &&
            modal &&
            !modal.classList.contains('hidden')
        ) {
            closeModal();
        }

    }
);