const { stocks } = window.__pageData || {};

let currentSortKey = 'valuation';
let currentSortDir = 'asc';

function sortByColumn(key) {
    if (currentSortKey === key) {
        currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortKey = key;
        currentSortDir = 'asc';
    }
    updateSortIcons();
    renderStockTable(getFilteredStocks());
}
window.sortByColumn = sortByColumn;

function updateSortIcons() {
    document.querySelectorAll('th[data-sort-key]').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
        const icon = th.querySelector('.sort-icon');
        if (icon) icon.textContent = '⇅';
    });
    document.querySelectorAll('th[data-sort-key="' + currentSortKey + '"]').forEach(th => {
        th.classList.add(currentSortDir === 'asc' ? 'sort-asc' : 'sort-desc');
        const icon = th.querySelector('.sort-icon');
        if (icon) icon.textContent = currentSortDir === 'asc' ? '▲' : '▼';
    });
}

var renderReadyAttempts = 0;
var renderReadyMaxAttempts = 200;

function runWhenRenderReady() {
    if (typeof window.renderStockTable === 'function') {
        updateSortIcons();
        renderStockTable(stocks || []);
    } else if (renderReadyAttempts < renderReadyMaxAttempts) {
        renderReadyAttempts++;
        setTimeout(runWhenRenderReady, 30);
    }
}
document.addEventListener('DOMContentLoaded', runWhenRenderReady);

function getFilteredStocks() {
    const keyword = document.getElementById('searchInput').value.trim().toUpperCase();
    const risk = document.getElementById('filterRisk').value;
    const stocksVn = document.getElementById('filterStocksVn').value;
    const ratingMin = document.getElementById('filterRatingMin').value;
    const ratingMax = document.getElementById('filterRatingMax').value;
    const volumeMin = document.getElementById('filterVolumeMin').value;
    const volumeMax = document.getElementById('filterVolumeMax').value;
    const valuationMin = document.getElementById('filterValuationMin').value;
    const valuationMax = document.getElementById('filterValuationMax').value;

    return (stocks || []).filter(stock => {
        if (keyword && !stock.code.includes(keyword)) return false;
        if (risk && Number(stock.risk_level) !== Number(risk)) return false;
        if (stocksVn === '30') { if (Number(stock.stocks_vn) !== 30) return false; }
        else if (stocksVn === '100') { if (![30, 100].includes(Number(stock.stocks_vn))) return false; }

        const rating = parseFloat(stock.rating_stocks);
        if (ratingMin !== '' && (isNaN(rating) || rating < parseFloat(ratingMin))) return false;
        if (ratingMax !== '' && (isNaN(rating) || rating > parseFloat(ratingMax))) return false;

        const vol = parseFloat(stock.volume_avg) || 0;
        if (volumeMin !== '' && vol < parseFloat(volumeMin)) return false;
        if (volumeMax !== '' && vol > parseFloat(volumeMax)) return false;

        const buyPrice = parseFloat(stock.recommended_buy_price) || 0;
        const currentPrice = parseFloat(stock.current_price) || 0;
        const valuation = buyPrice !== 0 ? ((currentPrice / buyPrice) * 100 - 100) : 0;
        let minVal = valuationMin !== '' ? parseFloat(valuationMin) : null;
        let maxVal = valuationMax !== '' ? parseFloat(valuationMax) : null;
        if (minVal !== null && maxVal !== null) {
            if (minVal > maxVal) { const tmp = minVal; minVal = maxVal; maxVal = tmp; }
            if (valuation < minVal || valuation > maxVal) return false;
        } else if (minVal !== null) {
            if (valuation < minVal) return false;
        } else if (maxVal !== null) {
            if (valuation > maxVal) return false;
        }
        return true;
    });
}

function searchStock() { renderStockTable(getFilteredStocks()); }
window.searchStock = searchStock;

function applyFilter() { renderStockTable(getFilteredStocks()); }
window.applyFilter = applyFilter;

function resetFilter() {
    ['filterRisk', 'filterStocksVn', 'filterRatingMin', 'filterRatingMax',
     'filterVolumeMin', 'filterVolumeMax', 'filterValuationMin', 'filterValuationMax', 'searchInput']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    renderStockTable(stocks || []);
}
window.resetFilter = resetFilter;

function toggleFilter() {
    const body = document.getElementById('filterBody');
    const icon = document.getElementById('filterToggleIcon');
    if (body.style.display === 'none') { body.style.display = 'block'; icon.textContent = '▲'; }
    else { body.style.display = 'none'; icon.textContent = '▼'; }
}
window.toggleFilter = toggleFilter;
