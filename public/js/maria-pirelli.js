(function () {
    'use strict';

    const table = document.getElementById('mp-sucursales-mes-tabla');
    if (!table) return;

    const body = table.tBodies[0];
    const buttons = Array.from(table.querySelectorAll('.mp-mes-ordenar'));
    const rows = Array.from(body.rows).filter(row => row.cells.length === buttons.length);
    const status = document.getElementById('mp-mes-orden-estado');
    const collator = new Intl.Collator('es', { numeric: true, sensitivity: 'base' });
    let activeColumn = null;
    let direction = 'asc';

    function orderDescription(button, order) {
        if (button.dataset.sortType === 'text') {
            return order === 'asc' ? 'de A a Z' : 'de Z a A';
        }
        return order === 'asc' ? 'de menor a mayor' : 'de mayor a menor';
    }

    function updateHeaders() {
        buttons.forEach(button => {
            const active = Number(button.dataset.sortColumn) === activeColumn;
            button.closest('th').setAttribute('aria-sort', active
                ? (direction === 'asc' ? 'ascending' : 'descending') : 'none');
            button.querySelector('i').className = 'fas ' + (active
                ? (direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort');
            const nextOrder = active && direction === 'asc' ? 'desc' : 'asc';
            const label = 'Ordenar por ' + button.dataset.sortLabel + ' ' + orderDescription(button, nextOrder);
            button.setAttribute('aria-label', label);
            button.title = label;
        });
    }

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            const column = Number(button.dataset.sortColumn);
            direction = activeColumn === column && direction === 'asc' ? 'desc' : 'asc';
            activeColumn = column;

            const sorted = rows.map((row, index) => {
                const raw = row.cells[column].dataset.sortValue;
                const value = raw === undefined || raw === '' ? null
                    : (button.dataset.sortType === 'number' ? Number(raw) : raw);
                return { row, index, value };
            }).sort((a, b) => {
                // El inventario ausente queda al final en ambos sentidos; cero sí es un dato.
                if (a.value === null || b.value === null) {
                    return a.value === b.value ? a.index - b.index : (a.value === null ? 1 : -1);
                }
                const comparison = button.dataset.sortType === 'number'
                    ? a.value - b.value : collator.compare(a.value, b.value);
                return (direction === 'asc' ? comparison : -comparison) || a.index - b.index;
            });

            const fragment = document.createDocumentFragment();
            sorted.forEach(item => fragment.appendChild(item.row));
            body.appendChild(fragment);
            updateHeaders();
            status.textContent = 'Ordenado por ' + button.dataset.sortLabel + ' ' + orderDescription(button, direction) + '.';
        });
    });

    updateHeaders();
}());
