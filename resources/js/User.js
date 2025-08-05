export default class User{
    constructor(){
    }

    getRisk(rating) {
        switch (rating) {
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


    searchStock(stocks, userPortfolios) {
        const keyword = document.getElementById('searchInput').value.trim().toUpperCase();
        const filtered = stocks.filter(stock => stock.code.includes(keyword));
        this.renderTable(filtered, userPortfolios);
    }

    searchStockProfile(stocks) {
        const keyword = document.getElementById('searchInput').value.trim().toUpperCase();
        const filtered = stocks.filter(stock => stock.code.includes(keyword));
        this.renderTableProfile(filtered);
    }

    searchStockInvest(stocks) {
        const keyword = document.getElementById('searchInput').value.trim().toUpperCase();
        const filtered = stocks.filter(stock => stock.code.includes(keyword));
        this.renderTableInvest(filtered);
    }

    searchStockUserFollow(stocks, userFollow) {
        const keyword = document.getElementById('searchInput').value.trim().toUpperCase();
        const filtered = stocks.filter(stock => stock.code.includes(keyword));
        this.renderTable(filtered, userFollow);
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

    renderTable(data, userProfile) {
         // 1. Tạo danh sách mã CK trong danh mục đầu tư
        const portfolioCodes = userProfile.map(p => p.code);

        // 2. Tách dữ liệu thành 2 nhóm: ưu tiên và còn lại
        const prioritizedStocks = data.filter(stock => portfolioCodes.includes(stock.code));
        const remainingStocks = data.filter(stock => !portfolioCodes.includes(stock.code));

        // 3. Gộp lại: mã trong danh mục đầu tư sẽ hiển thị trước
        const sortedData = [...prioritizedStocks, ...remainingStocks];

        const tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = '';
        sortedData.forEach(stock => {
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
            // Gộp class màu định giá và class nổi bật
            const isInPortfolio = userProfile.some(port => port.code === stock.code);
            const valuationClass = this.getRowClass(parseFloat(stock.recommended_buy_price), parseFloat(stock.current_price));
            row.className = valuationClass;
            if (isInPortfolio) {
                row.className = `${valuationClass} highlighted-row`;
            } else {
                row.className = valuationClass;
            }

            row.innerHTML = `
                <td><a href="https://fireant.vn/dashboard/content/symbols/${stock.code}" target="_blank" style="color: inherit; text-decoration: none;">${stock.code}</a></td>
                <td>${Number(stock.recommended_buy_price).toLocaleString('vi-VN')}</td>
                <td>${Number(stock.current_price).toLocaleString('vi-VN')}</td>
                <td style="color: ${this.getRisk(stock.risk_level).color}">
                    ${this.getRisk(stock.risk_level).label}
                </td>
                <td style="color: ${valuationColor}">
                <div class="action-cell">
                    <span class="profit">${sign}${valuation}%</span>
                </div>
                </td>
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
        let totalInvested = 0;

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
            totalInvested += avg_price * total_quantity;

            // Màu và dấu tiền lãi
            let profitColor = profit > 0 ? 'green' : profit < 0 ? 'red' : 'orange';
            let profitSign = profit > 0 ? '+' : profit < 0 ? '-' : '+';

            // Màu và dấu % lãi
            let percentColor = profitPercent > 0 ? 'green' : profitPercent < 0 ? 'red' : 'orange';
            let percentSign = profitPercent > 0 ? '+' : profitPercent < 0 ? '-' : '+';

            row.innerHTML = `
                <td><a href="https://fireant.vn/dashboard/content/symbols/${userPortfolio.code}" target="_blank" style="color: inherit; text-decoration: none;">${userPortfolio.code}</a></td>
                <td>${total_quantity.toLocaleString('vi-VN')}</td>
                <td>${avg_price.toLocaleString('vi-VN')}</td>
                <td>${current_price.toLocaleString('vi-VN')}</td>
                <td>${(avg_price * total_quantity).toLocaleString('vi-VN')}</td>
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
            <td><strong>${totalInvested.toLocaleString('vi-VN')}</strong></td>
            <td style="color:${totalProfitColor}"><strong>${totalProfitSign}${Math.abs(totalProfit).toLocaleString('vi-VN')}</strong></td>
            <td style="color:${totalPercentColor}"><strong>${totalPercentSign}${Math.abs(totalProfitPercent).toFixed(2)}%</strong></td>
        `;
        tbody.appendChild(totalRow);
    }
    
    renderTableInvest(data) {
        const tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = '';

        let totalQuantity = 0;
        let totalValue = 0;
        let totalProfit = 0;
        let capitalIn = 0; // tiền nạp thực tế
        let cash = 0; // tiền mặt hiện có từ bán ra
        

        // 👉 Sắp xếp data theo ngày giao dịch tăng dần
        data.sort((a, b) => {
            const dateA = new Date(a.buy_date || a.sell_date);
            const dateB = new Date(b.buy_date || b.sell_date);
            return dateA - dateB; // Nếu muốn giảm dần thì dùng: dateB - dateA
        });

        data.forEach(stock => {
            const price = stock.buy_price !== undefined && stock.buy_price !== null ? stock.buy_price : stock.sell_price;
            const date = stock.buy_date !== undefined && stock.buy_date !== null ? stock.buy_date : stock.sell_date;
            const type =  stock.buy_price !== undefined && stock.buy_price !== null ? "Buy" : "Sell";

            const quantity = parseInt(stock.quantity);
            const currentPrice = parseFloat(stock.current_price);
            const row = document.createElement('tr');

            // Tính tổng số lượng và giá trị
            totalQuantity += quantity;
            totalValue += quantity * price;

            // Tính lãi/lỗ:
            if (type === "Buy") {
                const totalBuyCost = price * quantity;

                // Nếu có đủ tiền mặt thì dùng tiền mặt
                if (cash >= totalBuyCost) {
                    cash -= totalBuyCost;
                } else {
                    const needToInject = totalBuyCost - cash;
                    capitalIn += needToInject; // phải nạp thêm tiền thật
                    cash = 0;
                }

                totalProfit += (currentPrice - price) * quantity;
            } else {
                const totalSellRevenue = price * quantity;
                cash += totalSellRevenue;

                totalProfit += (price - currentPrice) * quantity; // hoặc điều chỉnh tuỳ logic bạn
            }

            row.className = this.getRowClass(parseFloat(stock.recommended_buy_price), parseFloat(stock.current_price));
            row.innerHTML = `
                <td><a href="https://fireant.vn/dashboard/content/symbols/${stock.code}" target="_blank" style="color: inherit; text-decoration: none;">${stock.code}</a></td>
                <td>${Number(stock.quantity).toLocaleString('vi-VN')}</td>
                <td>${Number(price).toLocaleString('vi-VN')}</td>
                <td>${Number(stock.quantity * price).toLocaleString('vi-VN')}</td>
                <td>${date}</td>
                <td><div class="${type}">${type}</div></td>
            `;
            tbody.appendChild(row);
        });

        // Xác định màu dựa vào tổng lãi
        let profitColor = 'orange';
        let profitSign = '';
        if (totalProfit > 0) {
            profitColor = 'green';
            profitSign = '+';
        } else if (totalProfit < 0) {
            profitColor = 'red';
            profitSign = '-';
        }

        // Tạo dòng tổng cộng
        const totalRow = document.createElement('tr');
        totalRow.innerHTML = `
            <td><strong>Tổng :</strong></td>
            <td><strong>${totalQuantity.toLocaleString('vi-VN')}</strong></td>
            <td><strong>${capitalIn.toLocaleString('vi-VN')}</strong></td>
            <td><strong>${totalValue.toLocaleString('vi-VN')}</strong></td>
            <td></td>
            <td><strong style="color:${profitColor}">Tiền lãi: ${profitSign}${Math.abs(totalProfit).toLocaleString('vi-VN')}</strong></td>
        `;
        tbody.appendChild(totalRow);
    }

    renderTableUserFollow(data, userFollow) {
         // 1. Tạo danh sách mã CK trong danh mục đầu tư
        const portfolioCodes = userFollow.map(p => p.code);

        // 2. Tách dữ liệu thành 2 nhóm: ưu tiên và còn lại
        const prioritizedStocks = data.filter(stock => portfolioCodes.includes(stock.code));
        const remainingStocks = data.filter(stock => !portfolioCodes.includes(stock.code));

        // 3. Gộp lại: mã trong danh mục đầu tư sẽ hiển thị trước
        const sortedData = [...prioritizedStocks, ...remainingStocks];

        const tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = '';
        sortedData.forEach(stock => {
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
            // Gộp class màu định giá và class nổi bật
            const isInPortfolio = userFollow.some(port => port.code === stock.code);
            const valuationClass = this.getRowClass(parseFloat(stock.recommended_buy_price), parseFloat(stock.current_price));
            if (isInPortfolio) {
                row.className = `${valuationClass} highlighted-row`;
            } else {
                row.className = valuationClass;
            }

            row.innerHTML = `
                <td><a href="https://fireant.vn/dashboard/content/symbols/${stock.code}" target="_blank" style="color: inherit; text-decoration: none;">${stock.code}</a></td>
                <td>${Number(stock.recommended_buy_price).toLocaleString('vi-VN')}</td>
                <td>${Number(stock.current_price).toLocaleString('vi-VN')}</td>
                <td style="color: ${this.getRisk(stock.risk_level).color}">
                    ${this.getRisk(stock.risk_level).label}
                </td>
                <td style="color: ${valuationColor}">
                <div class="action-cell">
                    <span class="profit">${sign}${valuation}%</span>
                    ${isInPortfolio ? `<button class="btn-delete" onclick="confirmDelete('${stock.code}')">Delete</button>` : ''}
                </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
}
