import './bootstrap';

const sidebar = document.querySelector('#sidebar');

document.querySelectorAll('[data-menu-toggle]').forEach((button) => {
    button.addEventListener('click', () => sidebar?.classList.toggle('open'));
});

window.setTimeout(() => document.querySelector('.toast')?.remove(), 4500);

// Demo pristup: popuni obrazac za prijavu jednim klikom.
document.querySelectorAll('[data-demo-fill]').forEach((button) => {
    button.addEventListener('click', () => {
        const form = button.closest('form');
        const email = form?.querySelector('input[name="email"]');
        const password = form?.querySelector('input[name="password"]');

        if (email && password) {
            email.value = button.dataset.demoEmail ?? '';
            password.value = button.dataset.demoPassword ?? '';
            button.textContent = 'Podaci su popunjeni ✓';
        }
    });
});

// Ctrl/⌘ + K skače u globalnu pretragu, Escape zatvara mobilnu navigaciju.
document.addEventListener('keydown', (event) => {
    const isSearchShortcut = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k';
    const input = document.querySelector('[data-global-search]');

    if (isSearchShortcut && input) {
        event.preventDefault();
        input.focus();
        input.select();
    }

    if (event.key === 'Escape') {
        sidebar?.classList.remove('open');
    }
});

// Kanban: povlačenje kartica između kolona.
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const refreshColumnCounts = (board) => {
    board.querySelectorAll('.kanban-column').forEach((column) => {
        const count = column.querySelector('.kanban-count');
        if (count) {
            count.textContent = column.querySelectorAll('.kanban-card').length;
        }
    });
};

const persistMove = async (card, column) => {
    const field = column.dataset.dropField;
    const value = column.dataset.dropValue;
    const url = card.dataset.moveUrl;

    if (!field || !value || !url) {
        return;
    }

    const payload = new FormData();
    payload.append('_method', 'PATCH');
    payload.append('_token', csrfToken ?? '');
    payload.append(field, value);

    const response = await fetch(url, {
        method: 'POST',
        body: payload,
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`Kanban update failed with status ${response.status}`);
    }

    const select = card.querySelector(`select[name="${field}"]`);
    if (select) {
        select.value = value;
    }

    card.classList.add('just-moved');
    window.setTimeout(() => card.classList.remove('just-moved'), 1500);
};

document.querySelectorAll('[data-kanban]').forEach((board) => {
    board.querySelectorAll('.kanban-card[draggable="true"]').forEach((card) => {
        card.addEventListener('dragstart', (event) => {
            event.dataTransfer.setData('text/plain', card.dataset.moveUrl ?? 'card');
            event.dataTransfer.effectAllowed = 'move';
            card.classList.add('is-dragging');
        });

        card.addEventListener('dragend', () => card.classList.remove('is-dragging'));
    });

    board.querySelectorAll('.kanban-column').forEach((column) => {
        column.addEventListener('dragover', (event) => {
            if (!board.querySelector('.kanban-card.is-dragging')) {
                return;
            }
            event.preventDefault();
            column.classList.add('is-drop-target');
        });

        column.addEventListener('dragleave', () => column.classList.remove('is-drop-target'));

        column.addEventListener('drop', async (event) => {
            event.preventDefault();
            column.classList.remove('is-drop-target');

            const card = board.querySelector('.kanban-card.is-dragging');
            const target = column.querySelector('.kanban-cards');
            const origin = card?.parentElement;

            if (!card || !target) {
                return;
            }

            target.appendChild(card);

            try {
                await persistMove(card, column);
                refreshColumnCounts(board);
            } catch (error) {
                origin?.appendChild(card);
                refreshColumnCounts(board);
                window.alert('Promjenu nije bilo moguće spremiti. Pokušajte ponovno.');
            }
        });
    });
});
