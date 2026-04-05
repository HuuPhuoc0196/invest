// Moved from UserProfile.blade.php <script> block
(function () {
    const { userPortfolios, userInvestCash } = window.__pageData || {};

    var user = null;

    document.addEventListener("DOMContentLoaded", function () {
        user = new User();
        user.renderTableProfile(userPortfolios);
        user.renderInvestTableProfile(userInvestCash);

        // === JS-based sticky header + sticky total row ===
        const stickyTable = document.getElementById('stock-table');
        const stickyContainer = document.querySelector('.table-container');
        if (stickyTable && stickyContainer) {
            function headerInset() {
                return typeof window.getStickyHeaderInset === 'function'
                    ? window.getStickyHeaderInset()
                    : (window.innerWidth <= 768 ? 56 : 0);
            }
            const thead = stickyTable.querySelector('thead');
            let cloneWrap = null;
            let cloneTable = null;
            let totalCloneWrap = null;
            let totalCloneTable = null;

            function createClones() {
                if (cloneWrap) cloneWrap.remove();
                cloneWrap = document.createElement('div');
                cloneWrap.className = 'sticky-clone';
                cloneTable = document.createElement('table');
                cloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:#34495e;margin:0;table-layout:fixed;';
                cloneTable.appendChild(thead.cloneNode(true));
                cloneWrap.appendChild(cloneTable);
                document.body.appendChild(cloneWrap);

                if (totalCloneWrap) totalCloneWrap.remove();
                totalCloneWrap = null;
                totalCloneTable = null;
                const totalRow = stickyTable.querySelector('.total-row');
                if (totalRow) {
                    totalCloneWrap = document.createElement('div');
                    totalCloneWrap.className = 'sticky-clone-total';
                    totalCloneTable = document.createElement('table');
                    totalCloneTable.style.cssText = 'border-collapse:separate;border-spacing:0;background:transparent;margin:0;table-layout:fixed;';
                    const cloneTbody = document.createElement('tbody');
                    cloneTbody.appendChild(totalRow.cloneNode(true));
                    totalCloneTable.appendChild(cloneTbody);
                    totalCloneWrap.appendChild(totalCloneTable);
                    document.body.appendChild(totalCloneWrap);
                    totalCloneWrap.style.display = 'none';
                }

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
                if (totalCloneTable) {
                    const totalRow = stickyTable.querySelector('.total-row');
                    if (totalRow) {
                        totalCloneTable.style.width = tableWidth + 'px';
                        const origTds = totalRow.querySelectorAll('td');
                        const cloneTds = totalCloneTable.querySelectorAll('td');
                        origTds.forEach((cell, i) => {
                            if (cloneTds[i]) {
                                const w = cell.getBoundingClientRect().width;
                                cloneTds[i].style.boxSizing = 'border-box';
                                cloneTds[i].style.width = w + 'px';
                                cloneTds[i].style.minWidth = w + 'px';
                                cloneTds[i].style.maxWidth = w + 'px';
                            }
                        });
                    }
                }
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
                if (totalCloneWrap && totalCloneTable) {
                    totalCloneWrap.style.left = containerRect.left + 'px';
                    totalCloneWrap.style.width = containerRect.width + 'px';
                    totalCloneTable.style.marginLeft = offset;
                    totalCloneWrap.style.top = (inset + thead.offsetHeight) + 'px';
                }
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

                if (totalCloneWrap) {
                    const totalRow = stickyTable.querySelector('.total-row');
                    if (totalRow) {
                        const totalRect = totalRow.getBoundingClientRect();
                        const totalHeight = totalRow.offsetHeight;
                        if (
                            tableRect.top < inset &&
                            totalRect.top <= (inset + theadHeight) &&
                            tableRect.bottom > (inset + theadHeight + totalHeight)
                        ) {
                            totalCloneWrap.style.display = 'block';
                            totalCloneWrap.style.top = (inset + theadHeight) + 'px';
                            syncScroll();
                        } else {
                            totalCloneWrap.style.display = 'none';
                        }
                    }
                }
            }

            createClones();
            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', function () { createClones(); onScroll(); });
            stickyContainer.addEventListener('scroll', syncScroll, { passive: true });
            onScroll();
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

    function searchStock() {
        user.searchStockProfile(userPortfolios);
    }
    window.searchStock = searchStock;
})();
