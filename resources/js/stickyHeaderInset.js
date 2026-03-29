/**
 * Khoảng từ đỉnh viewport đến dưới thanh header (mobile-topbar / .actions sticky).
 * Dùng đặt top + điều kiện hiện clone thead (.sticky-clone).
 */
export function getStickyHeaderInset() {
    if (typeof window === 'undefined') return 0;
    const w = window.innerWidth;
    if (w <= 768) {
        const tb = document.querySelector('.mobile-topbar');
        if (tb) {
            const r = tb.getBoundingClientRect();
            return Math.max(0, Math.ceil(r.bottom));
        }
        return 56;
    }
    const actions = document.querySelector('.actions');
    if (!actions) return 0;
    const r = actions.getBoundingClientRect();
    if (r.bottom <= 0) return 0;
    return Math.ceil(r.bottom);
}

if (typeof window !== 'undefined') {
    window.getStickyHeaderInset = getStickyHeaderInset;
}
