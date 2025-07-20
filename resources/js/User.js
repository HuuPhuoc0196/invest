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
        this.renderTableProfile(filtered);
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
        `;
        tbody.appendChild(row);
        });
    }

    renderTableProfile(data) {
        const tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = '';

        let totalQuantity = 0;
        let totalProfit = 0;
        let totalCost = 0;
        let totalCurrentValue = 0;

        data.forEach(userPortfolio => {
        const row = document.createElement('tr');

        const total_quantity = Number(userPortfolio.total_quantity);
        const avg_price = Number(userPortfolio.avg_buy_price);
        const current_price = Number(userPortfolio.current_price);

        const cost = avg_price * total_quantity;
        const currentValue = current_price * total_quantity;
        const profit = currentValue - cost;
        const profitPercent = (profit / cost) * 100;

        // Cộng dồn cho dòng tổng
        totalQuantity += total_quantity;
        totalProfit += profit;
        totalCost += cost;
        totalCurrentValue += currentValue;

        // Màu và dấu tiền lãi
        let profitColor = profit > 0 ? 'green' : profit < 0 ? 'red' : 'orange';
        let profitSign = profit > 0 ? '+' : profit < 0 ? '-' : '+';

        // Màu và dấu % lãi
        let percentColor = profitPercent > 0 ? 'green' : profitPercent < 0 ? 'red' : 'orange';
        let percentSign = profitPercent > 0 ? '+' : profitPercent < 0 ? '-' : '+';

        row.innerHTML = `
            <td>${userPortfolio.code}</td>
            <td>${total_quantity.toLocaleString('vi-VN')}</td>
            <td>${avg_price.toLocaleString('vi-VN')}</td>
            <td>${current_price.toLocaleString('vi-VN')}</td>
            <td style="color:${profitColor}">${profitSign}${Math.abs(profit).toLocaleString('vi-VN')}</td>
            <td style="color:${percentColor}">${percentSign}${Math.abs(profitPercent).toFixed(2)}%</td>
        `;
        tbody.appendChild(row);
        });

        // Tính tổng phần trăm lãi
        const totalProfitPercent = (totalCost > 0) ? (totalProfit / totalCost) * 100 : 0;

        // Màu và dấu tổng tiền lãi
        const totalProfitColor = totalProfit > 0 ? 'green' : totalProfit < 0 ? 'red' : 'orange';
        const totalProfitSign = totalProfit > 0 ? '+' : totalProfit < 0 ? '-' : '+';

        // Màu và dấu tổng % lãi
        const totalPercentColor = totalProfitPercent > 0 ? 'green' : totalProfitPercent < 0 ? 'red' : 'orange';
        const totalPercentSign = totalProfitPercent > 0 ? '+' : totalProfitPercent < 0 ? '-' : '+';

        // Dòng tổng cộng
        const totalRow = document.createElement('tr');
        totalRow.innerHTML = `
            <td><strong>Tổng:</strong></td>
            <td><strong>${totalQuantity.toLocaleString('vi-VN')}</strong></td>
            <td><strong>${totalCost.toLocaleString('vi-VN')}</strong></td>
            <td><strong>${totalCurrentValue.toLocaleString('vi-VN')}</strong></td>
            <td style="color:${totalProfitColor}"><strong>${totalProfitSign}${Math.abs(totalProfit).toLocaleString('vi-VN')}</strong></td>
            <td style="color:${totalPercentColor}"><strong>${totalPercentSign}${Math.abs(totalProfitPercent).toFixed(2)}%</strong></td>
        `;
        tbody.appendChild(totalRow);
    }

}
