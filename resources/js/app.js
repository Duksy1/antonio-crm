import './bootstrap';

document.querySelectorAll('[data-menu-toggle]').forEach((button) => {
    button.addEventListener('click', () => document.querySelector('#sidebar')?.classList.toggle('open'));
});

window.setTimeout(() => document.querySelector('.toast')?.remove(), 4500);
