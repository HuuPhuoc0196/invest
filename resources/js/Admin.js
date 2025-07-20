export default class Admin{
    constructor(){
    }

    getRisk(rating) {
        switch (rating) {
        case 1:
            return 'Rất tốt';
        case 2:
            return 'Tốt';
        case 3:
            return 'Nguy hiểm';
        case 4:
            return 'Cực kỳ xấu';
        default:
            return 'Không xác định';
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
        const row = document.createElement('tr');
        row.className = this.getRowClass(parseFloat(stock.recommended_buy_price), parseFloat(stock.current_price));
        row.innerHTML = `
            <td>${stock.code}</td>
            <td>${Number(stock.recommended_buy_price).toLocaleString('vi-VN')}</td>
            <td>${Number(stock.current_price).toLocaleString('vi-VN')}</td>
            <td>${this.getRisk(stock.risk_level)}</td>
            <td><button onclick="location.href='${baseUrl}/admin/update/${stock.code}'">Update</button>
            <button class="btn-delete" onclick="confirmDelete('${stock.code}')">Delete</button></td>
        `;
        tbody.appendChild(row);
        });
    }

}
