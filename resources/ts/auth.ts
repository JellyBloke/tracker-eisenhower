function attachLoadingState(form: HTMLFormElement): void {
    form.addEventListener('submit', () => {
        const button = form.querySelector<HTMLButtonElement>('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.dataset.originalText = button.textContent ?? '';
            button.textContent = 'Please wait…';
        }
    });
}

document.querySelectorAll<HTMLFormElement>('form[data-form]').forEach(attachLoadingState);
