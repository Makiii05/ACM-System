document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-sortable-table]').forEach(function (table) {
        const wrapper = table.closest('[data-table-wrapper]');
        if (!wrapper) return;

        let searchInput = wrapper.querySelector('[data-table-search]');
        const tbody = table.querySelector('tbody');
        const headers = table.querySelectorAll('thead th');
        let currentSort = { col: null, dir: 'asc' };

        // --- Auto-inject search input if none exists ---
        if (!searchInput) {
            const searchDiv = document.createElement('div');
            searchDiv.className = 'table-search flex justify-end m-2 px-1';
            const input = document.createElement('input');
            input.type = 'text';
            input.placeholder = 'Search...';
            input.className = 'input border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500';
            input.setAttribute('data-table-search', '');
            searchDiv.appendChild(input);
            wrapper.insertBefore(searchDiv, wrapper.firstChild);
            searchInput = input;
        }

        // --- Column Sorting ---
        headers.forEach(function (th, colIndex) {
            if (th.hasAttribute('data-no-sort')) return;
            th.style.cursor = 'pointer';
            th.classList.add('select-none', 'hover:bg-base-200', 'transition-colors');
            const indicator = document.createElement('span');
            indicator.className = 'sort-indicator ml-1 text-xs opacity-40';
            indicator.textContent = '⇅';
            th.appendChild(indicator);

            th.addEventListener('click', function () {
                if (currentSort.col === colIndex) {
                    currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort.col = colIndex;
                    currentSort.dir = 'asc';
                }
                sortTable(tbody, colIndex, currentSort.dir);
                updateIndicators(headers, colIndex, currentSort.dir);
            });
        });

        function sortTable(tbody, colIndex, direction) {
            const rows = Array.from(tbody.querySelectorAll('tr:not([data-sort-ignore])'));
            const ignoredRows = Array.from(tbody.querySelectorAll('tr[data-sort-ignore]'));
            rows.sort(function (a, b) {
                const aCell = a.children[colIndex];
                const bCell = b.children[colIndex];
                if (!aCell || !bCell) return 0;
                let aVal = (aCell.getAttribute('data-sort-value') || aCell.textContent).trim().toLowerCase();
                let bVal = (bCell.getAttribute('data-sort-value') || bCell.textContent).trim().toLowerCase();
                const aNum = parseFloat(aVal.replace(/,/g, ''));
                const bNum = parseFloat(bVal.replace(/,/g, ''));
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return direction === 'asc' ? aNum - bNum : bNum - aNum;
                }
                if (aVal < bVal) return direction === 'asc' ? -1 : 1;
                if (aVal > bVal) return direction === 'asc' ? 1 : -1;
                return 0;
            });
            rows.forEach(function (row) { tbody.appendChild(row); });
            ignoredRows.forEach(function (row) { tbody.appendChild(row); });
        }

        function updateIndicators(headers, activeCol, direction) {
            headers.forEach(function (th, i) {
                const ind = th.querySelector('.sort-indicator');
                if (!ind) return;
                if (i === activeCol) {
                    ind.textContent = direction === 'asc' ? '↑' : '↓';
                    ind.classList.remove('opacity-40');
                    ind.classList.add('opacity-100');
                } else {
                    ind.textContent = '⇅';
                    ind.classList.remove('opacity-100');
                    ind.classList.add('opacity-40');
                }
            });
        }

        // --- Search Filtering ---
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = searchInput.value.toLowerCase().trim();
                const rows = tbody.querySelectorAll('tr:not([data-sort-ignore])');
                rows.forEach(function (row) {
                    if (!query) { row.style.display = ''; return; }
                    const cells = Array.from(row.children);
                    const match = cells.some(function (cell) {
                        return cell.textContent.toLowerCase().includes(query);
                    });
                    row.style.display = match ? '' : 'none';
                });
            });
        }
    });
});