document.querySelectorAll('form[method="post"]').forEach((form) => {
    if (window.quantixCsrfToken && !form.querySelector('input[name="csrf_token"]')) {
        const field = document.createElement('input');
        field.type = 'hidden';
        field.name = 'csrf_token';
        field.value = window.quantixCsrfToken;
        form.prepend(field);
    }
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
    document.querySelectorAll('#movement-table tbody tr').forEach((row) => {
        const search = document.querySelector('#movement-search')?.value.toLowerCase() ?? '';
        row.hidden = (selectedType !== 'ALL' && row.dataset.type !== selectedType) || (search && !row.textContent.toLowerCase().includes(search));
    });
});

document.querySelector('#movement-search')?.addEventListener('input', () => {
    document.querySelector('#movement-filter')?.dispatchEvent(new Event('change'));
});

document.querySelectorAll('input[name="sku"]').forEach((input) => {
    input.removeAttribute('required');
});
