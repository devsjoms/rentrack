(() => {
    const dialog = document.querySelector('#rt-confirm-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    const title = dialog.querySelector('[data-confirm-dialog-title]');
    const message = dialog.querySelector('[data-confirm-dialog-message]');
    const accept = dialog.querySelector('[data-confirm-dialog-accept]');
    const cancel = dialog.querySelector('[data-confirm-dialog-cancel]');
    let pendingForm = null;
    let opener = null;

    const close = () => {
        if (dialog.open) dialog.close();
        pendingForm = null;
        if (opener) opener.focus();
        opener = null;
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirmTitle) return;

        event.preventDefault();
        pendingForm = form;
        opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        title.textContent = form.dataset.confirmTitle;
        message.textContent = form.dataset.confirmMessage || 'Continue with this action?';
        accept.textContent = form.dataset.confirmButton || 'Confirm';
        accept.classList.toggle('rt-btn-danger', form.dataset.confirmDanger === 'true');
        accept.disabled = false;
        dialog.showModal();
        cancel.focus();
    });

    cancel.addEventListener('click', close);
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        close();
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) close();
    });
    accept.addEventListener('click', () => {
        if (!pendingForm) return;
        accept.disabled = true;
        accept.textContent = 'Processing…';
        pendingForm.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        });
        pendingForm.submit();
    });
})();
