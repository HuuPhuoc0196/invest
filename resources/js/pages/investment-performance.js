// Moved from UserInvestmentPerformance.blade.php <script> block
(function () {
    const { baseUrl, stocks } = window.__pageData || {};

    var user = null;
    // Hoisted to IIFE scope so syncCloneSelectValue (defined outside DOMContentLoaded) can access it
    var cloneWrap = null;

    document.addEventListener("DOMContentLoaded", function() {
        user = new User();
        user.renderTableInvest(stocks);
        sortInitInvest(stocks);

        // Open date picker on any click inside the input (not just the calendar icon)
        ['startDate', 'endDate'].forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('click', function () {
                try { this.showPicker(); } catch (_) {}
            });
            // Clear quick filter active state when user changes dates manually
            el.addEventListener('change', function () {
                document.querySelectorAll('.inv-perf-quick-btn').forEach(b => b.classList.remove('active'));
            });
        });

        // === Quick filter buttons ===
        document.querySelectorAll('.inv-perf-quick-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const years  = parseInt(this.dataset.years, 10);
                const startEl = document.getElementById('startDate');
                const endEl   = document.getElementById('endDate');

                if (years === 0) {
                    startEl.value = '';
                    endEl.value   = '';
                } else {
                    const now   = new Date();
                    const start = new Date(now);
                    start.setFullYear(start.getFullYear() - years);
                    startEl.value = start.toISOString().split('T')[0];
                    endEl.value   = now.toISOString().split('T')[0];
                }

                document.querySelectorAll('.inv-perf-quick-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                handleInvestmentPerformance();
            });
        });

        // === JS-based sticky header + sticky total row ===
        const stickyTable = document.getElementById('stock-table');
        const stickyContainer = document.querySelector('.table-container');
        if (stickyTable && stickyContainer) {
            const thead = stickyTable.querySelector('thead');
            let cloneTable = null;

            function createClones() {
                if (cloneWrap) cloneWrap.remove();
                cloneWrap = document.createElement('div');
                cloneWrap.className = 'sticky-clone';
                cloneTable = document.createElement('table');
                cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';
                const theadClone = thead.cloneNode(true);
                // Strip ids and inline handlers to avoid duplicate-id warnings
                theadClone.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));
                theadClone.querySelectorAll('[onchange]').forEach(el => el.removeAttribute('onchange'));
                theadClone.querySelectorAll('[onclick]').forEach(el => el.removeAttribute('onclick'));

                // Make the filter select in the clone interactive:
                // override pointer-events (parent is none), sync value → real select → filter
                const cloneSelect = theadClone.querySelector('.th-filter-select');
                if (cloneSelect) {
                    cloneSelect.style.pointerEvents = 'auto';
                    cloneSelect.style.cursor = 'pointer';
                    cloneSelect.style.position = 'relative';
                    cloneSelect.style.zIndex = '1';
                    // Keep clone in sync with real select value on creation
                    const realSelect = document.getElementById('filterType');
                    if (realSelect) cloneSelect.value = realSelect.value;
                    cloneSelect.addEventListener('change', function () {
                        const real = document.getElementById('filterType');
                        if (real) {
                            real.value = this.value;
                            handleInvestmentPerformance();
                        }
                    });
                }

                cloneTable.appendChild(theadClone);
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
                const tableWidth = stickyTable.getBoundingClientRect().width;
                cloneTable.style.width = tableWidth + 'px';
                origCells.forEach((cell, i) => {
                    if (cloneCells[i]) {
                        const w = cell.getBoundingClientRect().width;
                        cloneCells[i].style.boxSizing = 'border-box';
                        cloneCells[i].style.width = w + 'px';
                        cloneCells[i].style.minWidth = w + 'px';
                        cloneCells[i].style.maxWidth = w + 'px';
                    }
                });
            }

            function headerInset() {
                return typeof window.getStickyHeaderInset === 'function'
                    ? window.getStickyHeaderInset()
                    : (window.innerWidth <= 768 ? 56 : 0);
            }

            function syncScroll() {
                if (!cloneWrap) return;
                const containerRect = stickyContainer.getBoundingClientRect();
                const offset = -stickyContainer.scrollLeft + 'px';
                const inset = headerInset();
                cloneWrap.style.left = containerRect.left + 'px';
                cloneWrap.style.width = containerRect.width + 'px';
                cloneWrap.style.top = inset + 'px';
                cloneTable.style.marginLeft = offset;
            }

            function onScroll() {
                if (!cloneWrap) return;
                const tableRect = stickyTable.getBoundingClientRect();
                const theadHeight = thead.offsetHeight;
                const inset = headerInset();

                if (tableRect.top < inset && tableRect.bottom > (inset + theadHeight)) {
                    cloneWrap.style.display = 'block';
                    syncScroll();
                } else {
                    cloneWrap.style.display = 'none';
                }
            }

            createClones();
            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', function () { createClones(); onScroll(); });
            stickyContainer.addEventListener('scroll', syncScroll, { passive: true });
            onScroll();

            const observer = new MutationObserver(function () {
                setTimeout(function () { createClones(); onScroll(); }, 50);
            });
            observer.observe(document.getElementById('stockTableBody'), { childList: true });
        }

        // === Drag-to-scroll on main ===
        const mainEl = document.querySelector('main');
        const dragTarget = document.querySelector('.table-container');
        if (mainEl && dragTarget) {
            let isDown = false;
            let startX;
            let scrollLeft;

            mainEl.addEventListener('mousedown', (e) => {
                if (dragTarget.scrollWidth > dragTarget.clientWidth) {
                    isDown = true;
                    mainEl.classList.add('dragging');
                    startX = e.pageX;
                    scrollLeft = dragTarget.scrollLeft;
                }
            });
            mainEl.addEventListener('mouseleave', () => {
                isDown = false;
                mainEl.classList.remove('dragging');
            });
            mainEl.addEventListener('mouseup', () => {
                isDown = false;
                mainEl.classList.remove('dragging');
            });
            mainEl.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const walk = (e.pageX - startX) * 1.5;
                dragTarget.scrollLeft = scrollLeft - walk;
            });
        }
    });

    function syncCloneSelectValue() {
        const real = document.getElementById('filterType');
        if (!real || !cloneWrap) return;
        const cloneSelect = cloneWrap.querySelector('.th-filter-select');
        if (cloneSelect) cloneSelect.value = real.value;
    }

    function getTypeFilterValue() {
        const sel = document.getElementById('filterType');
        return sel ? sel.value : '';
    }

    function syncTypeFilterActiveClass() {
        const th = document.querySelector('.th-has-filter');
        if (!th) return;
        const val = getTypeFilterValue();
        th.classList.toggle('filter-active', val !== '');
    }

    function perfFilterEndStrFromInputs() {
        const v = document.getElementById('endDate')?.value;
        return v && String(v).trim() ? String(v).trim() : null;
    }

    function searchStock() {
        const keyword = document.getElementById('searchInput')?.value.trim().toUpperCase() || '';
        const typeVal = getTypeFilterValue();
        const filtered = stocks.filter(stock => {
            if (keyword && !stock.code.includes(keyword)) return false;
            if (typeVal === 'Buy' && !(stock.buy_price !== undefined && stock.buy_price !== null)) return false;
            if (typeVal === 'Sell' && !(stock.sell_price !== undefined && stock.sell_price !== null)) return false;
            return true;
        });
        // For Hiệu suất: keyword match only, ignore type filter
        const perfData = typeVal ? stocks.filter(stock => {
            if (keyword && !stock.code.includes(keyword)) return false;
            return true;
        }) : filtered;
        user.renderTableInvest(filtered, stocks, perfData, perfFilterEndStrFromInputs());
        syncCloneSelectValue();
    }
    window.searchStock = searchStock;

    function sortInitInvest(stocks) {
        user.renderTableInvest(stocks, stocks, stocks, null);
    }

    function handleInvestmentPerformance() {
        const startDateInput = document.getElementById('startDate').value;
        const endDateInput = document.getElementById('endDate').value;
        const typeVal = getTypeFilterValue();

        const startDate = startDateInput ? new Date(startDateInput) : null;
        const endDate = endDateInput ? new Date(endDateInput) : new Date();
        endDate.setHours(23, 59, 59, 999);

        const filteredStocks = stocks.filter(stock => {
            const dateStr = stock.buy_date || stock.sell_date;
            if (!dateStr) return false;
            const stockDate = new Date(dateStr);

            if (startDate && endDate) {
                if (stockDate < startDate || stockDate > endDate) return false;
            } else if (startDate) {
                if (stockDate < startDate) return false;
            } else if (endDate) {
                if (stockDate > endDate) return false;
            }

            if (typeVal === 'Buy' && !(stock.buy_price !== undefined && stock.buy_price !== null)) return false;
            if (typeVal === 'Sell' && !(stock.sell_price !== undefined && stock.sell_price !== null)) return false;

            return true;
        });

        syncTypeFilterActiveClass();
        syncCloneSelectValue();

        // For Hiệu suất: date filter only, ignore type filter
        const perfData = typeVal ? stocks.filter(stock => {
            const dateStr = stock.buy_date || stock.sell_date;
            if (!dateStr) return false;
            const stockDate = new Date(dateStr);
            if (startDate && endDate) {
                if (stockDate < startDate || stockDate > endDate) return false;
            } else if (startDate) {
                if (stockDate < startDate) return false;
            } else if (endDate) {
                if (stockDate > endDate) return false;
            }
            return true;
        }) : filteredStocks;

        const perfEndStr = endDateInput && String(endDateInput).trim()
            ? String(endDateInput).trim()
            : null;
        user.renderTableInvest(filteredStocks, stocks, perfData, perfEndStr);
        syncCloneSelectValue();
    }
    window.handleInvestmentPerformance = handleInvestmentPerformance;
})();
