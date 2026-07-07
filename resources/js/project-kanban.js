import Sortable from 'sortablejs';

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function collectColumns(board) {
    const columns = {};
    board.querySelectorAll('[data-kanban-wrap]').forEach((wrap) => {
        const status = wrap.dataset.status;
        const list = wrap.querySelector('[data-kanban-column]');
        if (!list || !status) {
            return;
        }
        columns[status] = [...list.querySelectorAll('[data-task-id]')].map((el) => el.dataset.taskId);
    });

    return columns;
}

function syncReorder(board) {
    const url = board.dataset.reorderUrl;
    if (!url) {
        return;
    }
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ columns: collectColumns(board) }),
    }).catch(() => {
        window.location.reload();
    });
}

document.querySelectorAll('[data-kanban-board]').forEach((board) => {
    board.querySelectorAll('[data-kanban-column]').forEach((column) => {
        Sortable.create(column, {
            group: 'kanban',
            animation: 160,
            ghostClass: 'flow-kanban-ghost',
            dragClass: 'flow-kanban-drag',
            onEnd: () => syncReorder(board),
        });
    });
});
