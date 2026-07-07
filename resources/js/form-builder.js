import Sortable from 'sortablejs';

document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('form-field-rows');
    const form = document.getElementById('form-field-reorder-form');
    if (!tbody || !form) {
        return;
    }

    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: () => {
            const ids = [...tbody.querySelectorAll('tr[data-field-id]')].map((tr) =>
                parseInt(tr.dataset.fieldId, 10),
            );
            const token = form.querySelector('input[name="_token"]')?.value;
            if (!token) {
                return;
            }
            const fd = new FormData();
            fd.append('_token', token);
            ids.forEach((id) => fd.append('order[]', id));
            fetch(form.action, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            }).then(() => window.location.reload());
        },
    });
});
