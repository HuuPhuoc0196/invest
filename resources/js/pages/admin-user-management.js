const { users } = window.__pageData || {};

window.currentUserSortKey = 'role';
window.currentUserSortDir = 'desc';

function updateSortIcons() {
    document.querySelectorAll('#user-table th[data-sort-key]').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        const icon = th.querySelector('.sort-icon');
        if (icon) icon.textContent = '⇅';
    });
    const activeTh = document.querySelector(`#user-table th[data-sort-key="${window.currentUserSortKey}"]`);
    if (!activeTh) return;
    activeTh.classList.add(window.currentUserSortDir === 'asc' ? 'sort-asc' : 'sort-desc');
    const icon = activeTh.querySelector('.sort-icon');
    if (icon) icon.textContent = window.currentUserSortDir === 'asc' ? '▲' : '▼';
}

function sortUsers(data) {
    const key = window.currentUserSortKey;
    const dir = window.currentUserSortDir;

    return [...data].sort((a, b) => {
        let va = a[key];
        let vb = b[key];

        if (key === 'email' || key === 'name') {
            va = (va || '').toString().toLowerCase();
            vb = (vb || '').toString().toLowerCase();
            return dir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
        }

        if (key === 'email_verified_at' || key === 'created_at') {
            va = va ? new Date(va).getTime() : 0;
            vb = vb ? new Date(vb).getTime() : 0;
            return dir === 'asc' ? va - vb : vb - va;
        }

        va = Number(va) || 0;
        vb = Number(vb) || 0;

        if (key === 'role' && va === vb) {
            const idA = Number(a.id) || 0;
            const idB = Number(b.id) || 0;
            return idB - idA;
        }

        return dir === 'asc' ? va - vb : vb - va;
    });
}

function getFilteredUsers() {
    const keyword = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
    const filterEmailVerified = document.getElementById('filterEmailVerified')?.value ?? '';
    const filterActive = document.getElementById('filterActive')?.value ?? '';
    const filterRole = document.getElementById('filterRole')?.value ?? '';

    return (users || []).filter(user => {
        const email = (user.email || '').toLowerCase();
        if (keyword && !email.includes(keyword)) return false;

        if (filterEmailVerified !== '') {
            const isVerified = user.email_verified_at ? '1' : '0';
            if (isVerified !== filterEmailVerified) return false;
        }
        if (filterActive !== '' && String(Number(user.active)) !== filterActive) return false;
        if (filterRole !== '' && String(Number(user.role)) !== filterRole) return false;

        return true;
    });
}

function renderFilteredUsers() {
    const filtered = getFilteredUsers();
    const sorted = sortUsers(filtered);
    updateSortIcons();
    if (typeof window.renderUserTable === 'function') {
        window.renderUserTable(sorted);
    }
}

window.searchUser = function() {
    renderFilteredUsers();
};

window.applyFilter = function() {
    renderFilteredUsers();
};

window.resetFilter = function() {
    ['filterEmailVerified', 'filterActive', 'filterRole', 'searchInput'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    renderFilteredUsers();
};

window.toggleFilter = function() {
    const body = document.getElementById('filterBody');
    const icon = document.getElementById('filterToggleIcon');
    if (!body || !icon) return;
    const isHidden = body.style.display === 'none';
    body.style.display = isHidden ? 'block' : 'none';
    icon.textContent = isHidden ? '▲' : '▼';
};

window.sortByUserColumn = function(key) {
    if (window.currentUserSortKey === key) {
        window.currentUserSortDir = window.currentUserSortDir === 'asc' ? 'desc' : 'asc';
    } else {
        window.currentUserSortKey = key;
        window.currentUserSortDir = (key === 'id' || key === 'role') ? 'desc' : 'asc';
    }
    renderFilteredUsers();
};

document.addEventListener('DOMContentLoaded', function() {
    // Handle sort clicks for both original header and sticky cloned header.
    document.addEventListener('click', function(e) {
        const th = e.target.closest('th[data-sort-key]');
        if (!th) return;
        const table = th.closest('table');
        if (!table) return;
        if (table.id !== 'user-table' && table.id !== 'user-table-clone') return;
        const key = th.getAttribute('data-sort-key');
        if (!key) return;
        window.sortByUserColumn(key);
    });

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                window.searchUser();
            }
        });
    }

    renderFilteredUsers();

    // Drag to scroll horizontally within table container
    const container = document.querySelector('.table-container');
    if (container) {
        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;

        container.addEventListener('mousedown', function(e) {
            if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.closest('button')) return;
            isDown = true;
            container.style.cursor = 'grabbing';
            container.style.userSelect = 'none';
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
        });

        container.addEventListener('mouseleave', function() {
            isDown = false;
            container.style.cursor = 'grab';
            container.style.removeProperty('user-select');
        });

        container.addEventListener('mouseup', function() {
            isDown = false;
            container.style.cursor = 'grab';
            container.style.removeProperty('user-select');
        });

        container.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX) * 2;
            container.scrollLeft = scrollLeft - walk;
        });

        container.style.cursor = 'grab';
    }

    // Sticky table header clone (same behavior as admin stocks page)
    const table = document.getElementById('user-table');
    if (table && container) {
        const thead = table.querySelector('thead');
        let cloneTable = null;
        let cloneWrap = null;

        function headerInset() {
            return typeof window.getStickyHeaderInset === 'function'
                ? window.getStickyHeaderInset()
                : (window.innerWidth <= 768 ? 56 : 0);
        }

        function createClone() {
            if (cloneWrap) cloneWrap.remove();

            cloneWrap = document.createElement('div');
            cloneWrap.className = 'sticky-clone user-sticky-clone';

            cloneTable = document.createElement('table');
            cloneTable.id = 'user-table-clone';
            cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';

            const cloneThead = thead.cloneNode(true);
            cloneTable.appendChild(cloneThead);
            cloneWrap.appendChild(cloneTable);
            document.body.appendChild(cloneWrap);

            syncWidths();
            syncScroll();
            cloneWrap.style.display = 'none';
        }

        function syncWidths() {
            if (!cloneTable) return;
            const origCells = thead.querySelectorAll('th');
            const cloneCells = cloneTable.querySelectorAll('th');
            const tableWidth = table.getBoundingClientRect().width;
            cloneTable.style.width = tableWidth + 'px';

            origCells.forEach((cell, i) => {
                if (!cloneCells[i]) return;
                const w = cell.getBoundingClientRect().width;
                cloneCells[i].style.boxSizing = 'border-box';
                cloneCells[i].style.width = w + 'px';
                cloneCells[i].style.minWidth = w + 'px';
                cloneCells[i].style.maxWidth = w + 'px';
            });
        }

        function syncScroll() {
            if (!cloneWrap) return;
            const containerRect = container.getBoundingClientRect();
            cloneWrap.style.left = containerRect.left + 'px';
            cloneWrap.style.width = containerRect.width + 'px';
            cloneWrap.style.top = headerInset() + 'px';
            cloneTable.style.marginLeft = -container.scrollLeft + 'px';
        }

        function onScroll() {
            if (!cloneWrap) return;
            const tableRect = table.getBoundingClientRect();
            const theadHeight = thead.offsetHeight;
            const inset = headerInset();
            if (tableRect.top < inset && tableRect.bottom > (inset + theadHeight)) {
                cloneWrap.style.display = 'block';
                syncScroll();
            } else {
                cloneWrap.style.display = 'none';
            }
        }

        createClone();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', function() {
            createClone();
            onScroll();
        });
        container.addEventListener('scroll', syncScroll, { passive: true });
        onScroll();
    }
});
