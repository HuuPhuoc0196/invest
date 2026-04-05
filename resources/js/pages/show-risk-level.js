const { baseUrl, stock } = window.__pageData || {};

function getRisk(rating) {
    switch (Number(rating)) {
        case 1: return { label: 'An toàn', color: '#27ae60' };
        case 2: return { label: 'Cảnh báo', color: '#f39c12' };
        case 3: return { label: 'Hạn chế GD', color: '#e74c3c' };
        case 4: return { label: 'Đình chỉ/Huỷ', color: '#c0392b' };
        default: return { label: 'Chưa xác định', color: '#95a5a6' };
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('code').textContent = stock?.code || 'Không có trong hệ thống';
    if (stock?.risk_level) {
        const risk = getRisk(stock.risk_level);
        document.getElementById('risk').textContent = risk.label;
        document.getElementById('risk').style.color = risk.color;
    } else {
        document.getElementById('risk').textContent = 'Không xác định';
        document.getElementById('risk').style.color = 'gray';
    }
});
