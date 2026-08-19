document.querySelectorAll('form[method="post"]').forEach((form) => {
    if (window.quantixCsrfToken && !form.querySelector('input[name="csrf_token"]')) {
        const field = document.createElement('input');
        field.type = 'hidden';
        field.name = 'csrf_token';
        field.value = window.quantixCsrfToken;
        form.prepend(field);
    }

    form.addEventListener('submit', () => {
        const submit = form.querySelector('button[type="submit"], button:not([type])');
        if (!submit || form.dataset.submitting === 'true') return;
        form.dataset.submitting = 'true';
        submit.disabled = true;
        submit.textContent = 'Saving...';
    });
});

document.querySelectorAll('.alert').forEach((alert) => {
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'alert-dismiss btn-close float-end';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.addEventListener('click', () => alert.remove());
    alert.prepend(close);
    if (alert.classList.contains('alert-success')) window.setTimeout(() => alert.remove(), 5000);
});

document.querySelectorAll('.panel, .metric, .quick-action').forEach((element, index) => {
    element.classList.add('interactive-reveal');
    element.style.setProperty('--reveal-delay', `${Math.min(index * 35, 280)}ms`);
});

if (window.location.pathname.endsWith('/movement-add.php')) {
    const movementParams = new URLSearchParams(window.location.search);
    const movementType = movementParams.get('type');
    const reference = movementParams.get('reference');
    if (movementType && ['IN', 'OUT'].includes(movementType)) {
        const typeField = document.querySelector('select[name="movement_type"]');
        if (typeField) typeField.value = movementType;
    }
    if (reference) {
        const referenceField = document.querySelector('input[name="reference"]');
        if (referenceField) referenceField.value = reference;
    }
}

document.querySelector('#movement-filter')?.addEventListener('change', (event) => {
    const selectedType = event.target.value;
    const rows = document.querySelectorAll('#movement-table tbody tr');
    rows.forEach((row) => {
        const search = document.querySelector('#movement-search')?.value.toLowerCase() ?? '';
        row.hidden = (selectedType !== 'ALL' && row.dataset.type !== selectedType) || (search && !row.textContent.toLowerCase().includes(search));
    });
    const count = document.querySelector('#movement-visible-count');
    if (count) count.textContent = `${[...rows].filter((row) => !row.hidden).length} shown`;
});

document.querySelector('#movement-search')?.addEventListener('input', () => {
    document.querySelector('#movement-filter')?.dispatchEvent(new Event('change'));
});

document.querySelectorAll('input[name="sku"]').forEach((input) => {
    input.removeAttribute('required');
});
