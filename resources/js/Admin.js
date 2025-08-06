export default class Admin{
    constructor(){
    }

    getRisk(rating) {
        switch (Number(rating)) {
            case 1:
                return { label: 'An toàn', color: 'green' };
            case 2:
                return { label: 'Tốt', color: 'goldenrod' };
            case 3:
                return { label: 'Nguy hiểm', color: 'orange' };
            case 4:
                return { label: 'Cực kỳ xấu', color: 'red' };
            default:
                return { label: 'Không xác định', color: 'gray' };
        }
    }

    searchStock(stocks) {
        const keyword = document.getElementById('searchInput').value.trim().toUpperCase();
        const filtered = stocks.filter(stock => stock.code.includes(keyword));
        this.renderTable(filtered);
    }

    
    getRowClass(goodPrice, currentPrice) {
        if (currentPrice > goodPrice) {
            const percentDiff = ((currentPrice - goodPrice) / goodPrice) * 100;
            if (percentDiff <= 10) {
                return 'yellow';
            }else {
                return '';
            }
        } else if (currentPrice <= goodPrice) {
            const percentDiff = ((goodPrice - currentPrice) / goodPrice) * 100;
            if (percentDiff > 20) {
                return 'red';
            } else if (percentDiff > 10){
                return 'purple';
            } else{
                return 'green';
            }
        }
    }

    renderTable(data) {
        const tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = '';
        data.forEach(stock => {
            const goodPrice = parseFloat(stock.recommended_buy_price);
            const currentPrice = parseFloat(stock.current_price);
            const valuation = currentPrice !== 0 ? ((currentPrice / goodPrice) * 100 - 100).toFixed(2) : 0;

            let valuationColor = 'yellow';
            let sign = '';
            if (valuation > 0) {
                valuationColor = 'green';
                sign = '+';
            } else if (valuation < 0) {
                valuationColor = 'red';
                sign = '';
            }
            const row = document.createElement('tr');
            row.className = this.getRowClass(parseFloat(stock.recommended_buy_price), parseFloat(stock.current_price));
            row.innerHTML = `
                <td><a href="https://fireant.vn/dashboard/content/symbols/${stock.code}" target="_blank" style="color: inherit; text-decoration: none;">${stock.code}</a></td>
                <td>${Number(stock.recommended_buy_price).toLocaleString('vi-VN')}</td>
                <td>${Number(stock.current_price).toLocaleString('vi-VN')}</td>
                <td style="color: ${this.getRisk(stock.risk_level).color}">
                    ${this.getRisk(stock.risk_level).label}
                </td>
                <td style="color: ${valuationColor}">${sign}${valuation}%</td>
                <td><button onclick="location.href='${baseUrl}/admin/update/${stock.code}'">Update</button>
                <button class="btn-delete" onclick="confirmDelete('${stock.code}')">Delete</button></td>
            `;
            tbody.appendChild(row);
        });
    }

}
