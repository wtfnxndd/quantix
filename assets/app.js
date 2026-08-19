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
