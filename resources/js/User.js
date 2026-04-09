export default class User{
    constructor(){
    }

    getRisk(rating) {
        switch (Number(rating)) {
            case 1:
                return { label: 'An toàn', color: 'green' };
            case 2:
                return { label: 'Tốt', color: 'orange' };
            case 3:
                return { label: 'Nguy hiểm', color: 'OrangeRed' };
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
        this.renderTableUserFollow(filtered, userFollow);
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
                <td class="col-code-sticky"><a href="https://fireant.vn/dashboard/content/symbols/${userPortfolio.code}" target="_blank" style="color: inherit; text-decoration: none;">${userPortfolio.code}</a></td>
                <td>${total_quantity.toLocaleString('vi-VN')}</td>
                <td>${avg_price.toLocaleString('vi-VN')}</td>
                <td>${current_price.toLocaleString('vi-VN')}</td>
                <td>${(avg_price * total_quantity).toLocaleString('vi-VN')}</td>
                <td>${(current_price * total_quantity).toLocaleString('vi-VN')}</td>
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
        totalRow.classList.add('total-row');
        totalRow.innerHTML = `
            <td class="col-code-sticky"><strong>Tổng:</strong></td>
            <td><strong>${totalQuantity.toLocaleString('vi-VN')}</strong></td>
            <td></td>
            <td></td>
            <td><strong>${totalInvested.toLocaleString('vi-VN')}</strong></td>
            <td><strong>${totalCurrentValue.toLocaleString('vi-VN')}</strong></td>
            <td style="color:${totalProfitColor}"><strong>${totalProfitSign}${Math.abs(totalProfit).toLocaleString('vi-VN')}</strong></td>
            <td style="color:${totalPercentColor}"><strong>${totalPercentSign}${Math.abs(totalProfitPercent).toFixed(2)}%</strong></td>
        `;
        tbody.appendChild(totalRow);
    }

    renderInvestTableProfile(data) {
        const tbody = document.getElementById('investTableBody');

        const totalProfit = data.cash - data.cash_in;

        // Tính tổng phần trăm lãi
        const totalProfitPercent = (totalProfit / data.cash_in) * 100 ;

        // Màu và dấu tổng tiền lãi
        const totalProfitColor = totalProfit > 0 ? 'green' : totalProfit < 0 ? 'red' : 'orange';
        const totalProfitSign = totalProfit > 0 ? '+' : totalProfit < 0 ? '-' : '+';

        // Màu và dấu tổng % lãi
        const totalPercentColor = totalProfitPercent > 0 ? 'green' : totalProfitPercent < 0 ? 'red' : 'orange';
        const totalPercentSign = totalProfitPercent > 0 ? '+' : totalProfitPercent < 0 ? '-' : '+';

        // Dòng tổng cộng
        const totalRow = document.createElement('tr');
        totalRow.innerHTML = `
            <td class="col-code-sticky"><strong>Tổng:</strong></td>
            <td><strong>${Number(data.cash_in).toLocaleString('vi-VN')}</strong></td>
            <td><strong>${Number(data.cash).toLocaleString('vi-VN')}</strong></td>
            <td style="color:${totalProfitColor}"><strong>${totalProfitSign}${Math.abs(totalProfit).toLocaleString('vi-VN')}</strong></td>
            <td style="color:${totalPercentColor}"><strong>${totalPercentSign}${Math.abs(totalProfitPercent).toFixed(2)}%</strong></td>
        `;
        tbody.appendChild(totalRow);
    }
    
    /**
     * @param {string|null} perfFilterEndStr - Ngày kết thúc filter (input date YYYY-MM-DD).
     *   Dùng để ước giá phần cổ còn lại: lấy lần bán đầu tiên SAU ngày này; không có thì current_price.
     */
    renderTableInvest(data, allStocks = null, perfData = null, perfFilterEndStr = null) {
        const tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = '';

        let totalQuantity = 0;
        let totalBuyValue  = 0;  // SUM(giá trị GD Mua)
        let totalSellValue = 0;  // SUM(giá trị GD Bán)

        // 👉 Sắp xếp data theo ngày giao dịch tăng dần
        data.sort((a, b) => {
            const dateA = new Date(a.buy_date || a.sell_date);
            const dateB = new Date(b.buy_date || b.sell_date);
            return dateA - dateB; // Nếu muốn giảm dần thì dùng: dateB - dateA
        });

        data.forEach(stock => {
            const price = stock.buy_price !== undefined && stock.buy_price !== null ? stock.buy_price : stock.sell_price;
            const date = stock.buy_date !== undefined && stock.buy_date !== null ? stock.buy_date : stock.sell_date;
            const type = stock.buy_price !== undefined && stock.buy_price !== null ? "Buy" : "Sell";
            const typeLabel = type === "Buy" ? "Mua" : "Bán";

            const quantity = parseInt(stock.quantity);
            const row = document.createElement('tr');

            // Tính tổng số lượng và giá trị theo loại
            totalQuantity += quantity;
            if (type === "Buy") {
                totalBuyValue  += quantity * price;
            } else {
                totalSellValue += quantity * price;
            }

            row.className = this.getRowClass(parseFloat(stock.recommended_buy_price), parseFloat(stock.current_price));
            row.innerHTML = `
                <td class="col-code-sticky"><a href="https://fireant.vn/dashboard/content/symbols/${stock.code}" target="_blank" style="color: inherit; text-decoration: none;">${stock.code}</a></td>
                <td>${Number(stock.quantity).toLocaleString('vi-VN')}</td>
                <td>${Number(price).toLocaleString('vi-VN')}</td>
                <td>${Number(stock.quantity * price).toLocaleString('vi-VN')}</td>
                <td>${date}</td>
                <td><div class="${type}">${typeLabel}</div></td>
            `;
            tbody.appendChild(row);
        });

        // ── Tính Lãi / Lỗ và % ──────────────────────────────────────────────────
        // Nếu chỉ filter 1 chiều (Mua hoặc Bán), 1 trong 2 sẽ = 0 → lãi/lỗ không có nghĩa
        const bothSidesPresent  = totalBuyValue > 0 && totalSellValue > 0;
        const profitLoss        = bothSidesPresent ? totalSellValue - totalBuyValue : 0;
        const profitPercent     = bothSidesPresent && totalBuyValue > 0
                                    ? (profitLoss / totalBuyValue) * 100
                                    : 0;

        // Tổng tiền Mua: luôn xanh biển, không dấu
        const buyColor = '#60a5fa';

        // Tổng tiền Bán: so sánh với Tổng tiền Mua
        const sellColor = totalSellValue > totalBuyValue ? '#4ade80'
                        : totalSellValue < totalBuyValue ? '#f87171'
                        : '#facc15';

        // Lãi / Lỗ và % Lãi / Lỗ
        function profitColor(v) {
            return v > 0 ? '#4ade80' : v < 0 ? '#f87171' : '#facc15';
        }
        // Dấu + / - với space phía sau
        function signedValue(v, formatted) {
            if (v > 0) return '+ ' + formatted;
            if (v < 0) return '- ' + formatted;
            return formatted;
        }
        function signedPercent(v) {
            const abs = Math.abs(v).toFixed(2) + '%';
            if (v > 0) return '+ ' + abs;
            if (v < 0) return '- ' + abs;
            return abs;
        }

        const titleStyle = 'font-size:11px;color:#fff;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;';

        // ── Dòng Tổng ────────────────────────────────────────────────────────────
        const totalRow = document.createElement('tr');
        totalRow.classList.add('total-row');
        totalRow.innerHTML = `
            <td class="col-code-sticky"><strong>Tổng :</strong></td>
            <td>
                <div style="${titleStyle}">Tổng khối lượng</div>
                <strong>${totalQuantity.toLocaleString('vi-VN')}</strong>
            </td>
            <td>
                <div style="${titleStyle}">Tổng tiền Mua</div>
                <strong style="color:${buyColor};">${totalBuyValue.toLocaleString('vi-VN')}</strong>
            </td>
            <td>
                <div style="${titleStyle}">Tổng tiền Bán</div>
                <strong style="color:${sellColor};">${totalSellValue.toLocaleString('vi-VN')}</strong>
            </td>
            <td>
                <div style="${titleStyle}">Lãi / Lỗ</div>
                <strong style="color:${profitColor(profitLoss)};">${signedValue(profitLoss, Math.abs(profitLoss).toLocaleString('vi-VN'))}</strong>
            </td>
            <td>
                <div style="${titleStyle}">% Lãi / Lỗ</div>
                <strong style="color:${profitColor(profitPercent)};">${signedPercent(profitPercent)}</strong>
            </td>
        `;
        tbody.appendChild(totalRow);

        // ── Dòng Hiệu suất ────────────────────────────────────────────────────────
        // Luôn tính theo toàn bộ loại GD (bỏ qua type filter); chỉ bỏ qua nếu không có allStocks
        const hieuSuatData = perfData ?? data;

        const perfRow = document.createElement('tr');
        perfRow.classList.add('total-row', 'perf-row');

        if (!allStocks) {
            perfRow.innerHTML = `
                <td class="col-code-sticky"><strong>Hiệu suất :</strong></td>
                <td>
                    <div style="${titleStyle}">Tổng khối lượng</div>
                    <strong>0</strong>
                </td>
                <td>
                    <div style="${titleStyle}">Tổng tiền Mua</div>
                    <strong style="color:${buyColor};">0</strong>
                </td>
                <td>
                    <div style="${titleStyle}">Tổng tiền Bán</div>
                    <strong style="color:#facc15;">0</strong>
                </td>
                <td>
                    <div style="${titleStyle}">Lãi / Lỗ</div>
                    <strong style="color:#facc15;">0</strong>
                </td>
                <td>
                    <div style="${titleStyle}">% Lãi / Lỗ</div>
                    <strong style="color:#facc15;">0%</strong>
                </td>
            `;
        } else {
            // Cùng logic phân loại Mua/Bán như khi render bảng (tránh nhầm dòng / field lẫn current_price)
            const isBuyRow = (stock) => stock.buy_price !== undefined && stock.buy_price !== null;
            const isSellRow = (stock) =>
                !isBuyRow(stock) && stock.sell_price !== undefined && stock.sell_price !== null;

            // Bước 1: gom nhóm filtered data theo mã CK
            // Cũng tracking earliestSellDate để dùng làm cutoff khi tìm giá vốn lịch sử
            const codeStats = {};

            hieuSuatData.forEach(stock => {
                const code = stock.code;
                const qty  = parseInt(stock.quantity, 10);
                if (!codeStats[code]) {
                    codeStats[code] = {
                        buyQty: 0, buyValue: 0,
                        sellQty: 0, sellValue: 0,
                        earliestSellDate: null,   // ngày bán sớm nhất trong filter
                    };
                }
                if (isBuyRow(stock)) {
                    codeStats[code].buyQty   += qty;
                    codeStats[code].buyValue += qty * parseFloat(stock.buy_price);
                } else if (isSellRow(stock)) {
                    codeStats[code].sellQty   += qty;
                    codeStats[code].sellValue += qty * parseFloat(stock.sell_price);
                    if (stock.sell_date) {
                        const d = new Date(stock.sell_date);
                        if (!isNaN(d.getTime()) && (!codeStats[code].earliestSellDate || d < codeStats[code].earliestSellDate)) {
                            codeStats[code].earliestSellDate = d;
                        }
                    }
                }
            });

            // Chuẩn ngày → YYYY-MM-DD để sort (ổn định hơn new Date — tránh Invalid Date / TZ làm sai thứ tự)
            const normDateStr = (v) => {
                if (v == null || v === '') return '';
                const s = String(v).trim();
                return s.length >= 10 ? s.slice(0, 10) : s;
            };

            const pickLatestPriceByDateStr = (rows) => {
                if (!rows.length) return null;
                rows.sort((a, b) => {
                    const c = b.ds.localeCompare(a.ds);
                    if (c !== 0) return c;
                    return b.price - a.price;
                });
                return rows[0].price;
            };

            /** Lần bán đầu tiên có ngày > endDs (YYYY-MM-DD), theo nghiệp vụ “bán tiếp theo” sau kỳ filter */
            const pickFirstSellPriceAfterEndDs = (rows, endDs) => {
                if (!endDs || !rows.length) return null;
                const after = rows.filter((r) => r.ds && r.ds > endDs);
                if (!after.length) return null;
                after.sort((a, b) => a.ds.localeCompare(b.ds) || a.price - b.price);
                return after[0].price;
            };

            // Bước 2a: lịch sử Mua (Date) cho Bước 4 — cutoff theo earliestSellDate
            const allBuyHistory = {};
            allStocks.forEach((stock) => {
                if (!isBuyRow(stock)) return;
                const code = stock.code;
                const price = Number(stock.buy_price);
                if (!Number.isFinite(price) || price <= 0) return;
                const buyDate = stock.buy_date ? new Date(stock.buy_date) : null;
                if (!buyDate || isNaN(buyDate.getTime())) return;
                if (!allBuyHistory[code]) allBuyHistory[code] = [];
                allBuyHistory[code].push({ price, date: buyDate });
            });

            // Bước 2b: theo dõi từng dòng Mua/Bán (chuỗi ngày) + giá hiện tại từ DB (fallback khi không còn lần bán sau filter)
            const sellRowsByCode = {};
            const buyRowsByCode = {};
            const codeCurrentPrice = {};
            allStocks.forEach((stock) => {
                const code = stock.code;
                const cp = Number(stock.current_price);
                if (Number.isFinite(cp) && cp > 0 && codeCurrentPrice[code] === undefined) {
                    codeCurrentPrice[code] = cp;
                }
                if (isSellRow(stock)) {
                    const price = Number(stock.sell_price);
                    if (!Number.isFinite(price) || price <= 0) return;
                    if (!sellRowsByCode[code]) sellRowsByCode[code] = [];
                    sellRowsByCode[code].push({ ds: normDateStr(stock.sell_date), price });
                }
                if (isBuyRow(stock)) {
                    const price = Number(stock.buy_price);
                    if (!Number.isFinite(price) || price <= 0) return;
                    if (!buyRowsByCode[code]) buyRowsByCode[code] = [];
                    buyRowsByCode[code].push({ ds: normDateStr(stock.buy_date), price });
                }
            });

            const latestSellPriceByCode = {};
            const latestBuyPriceByCode = {};
            Object.keys(sellRowsByCode).forEach((code) => {
                const p = pickLatestPriceByDateStr([...sellRowsByCode[code]]);
                if (p != null) latestSellPriceByCode[code] = p;
            });
            Object.keys(buyRowsByCode).forEach((code) => {
                const p = pickLatestPriceByDateStr([...buyRowsByCode[code]]);
                if (p != null) latestBuyPriceByCode[code] = p;
            });

            const endDsNorm = perfFilterEndStr ? normDateStr(perfFilterEndStr) : '';

            // Bước 3: tính Tổng tiền Bán (Hiệu suất)
            //   - tiền bán thực trong filter
            //   - phần (mua − bán) trong filter: giá = lần bán đầu tiên SAU ngày kết thúc filter; không có → giá hiện tại; vẫn không có → lần mua gần nhất
            let perfSellValue = 0;
            Object.entries(codeStats).forEach(([code, stats]) => {
                perfSellValue += stats.sellValue;

                const filterRemaining = Math.max(0, stats.buyQty - stats.sellQty);
                if (filterRemaining > 0) {
                    const sells = sellRowsByCode[code] || [];
                    let estimatedPrice = null;
                    if (endDsNorm) {
                        estimatedPrice = pickFirstSellPriceAfterEndDs(sells, endDsNorm);
                    }
                    if (estimatedPrice == null) {
                        estimatedPrice = latestSellPriceByCode[code] ?? null;
                    }
                    if (estimatedPrice == null || !Number.isFinite(estimatedPrice)) {
                        estimatedPrice = codeCurrentPrice[code];
                    }
                    if (estimatedPrice == null || !Number.isFinite(estimatedPrice)) {
                        estimatedPrice = latestBuyPriceByCode[code] ?? 0;
                    }
                    perfSellValue += filterRemaining * estimatedPrice;
                }
            });

            // Bước 4: Lãi / Lỗ và %
            //   Fix Tổng tiền Mua: nếu trong filter có Bán nhiều hơn Mua (deficit > 0),
            //   chỉ dùng giao dịch Mua xảy ra TRƯỚC HOẶC BẰNG ngày bán sớm nhất của mã đó trong filter
            //   để tránh dùng giá mua "tương lai" (buy_date > sell_date) làm giá vốn sai
            let perfTotalBuyValue = 0;
            let perfTotalQuantity = 0;
            Object.entries(codeStats).forEach(([code, stats]) => {
                let effectiveBuyValue = stats.buyValue;

                const deficit = stats.sellQty - stats.buyQty;
                if (deficit > 0) {
                    const cutoff   = stats.earliestSellDate; // ngày bán sớm nhất trong filter
                    const validBuys = (allBuyHistory[code] || [])
                        .filter(b => !cutoff || b.date <= cutoff) // chỉ mua trước/bằng ngày bán sớm nhất
                        .sort((a, b) => b.date - a.date);        // sắp xếp giảm dần → lấy gần nhất
                    if (validBuys.length > 0) {
                        effectiveBuyValue += deficit * validBuys[0].price;
                    }
                    // Nếu không có giao dịch mua nào trước đó: chi phí vốn = 0 (không xác định)
                }

                perfTotalBuyValue += effectiveBuyValue;
                perfTotalQuantity += stats.buyQty + stats.sellQty;
            });

            const perfProfit  = perfSellValue - perfTotalBuyValue;
            const perfPercent = perfTotalBuyValue > 0 ? (perfProfit / perfTotalBuyValue) * 100 : 0;

            const perfSellColor = perfSellValue > perfTotalBuyValue ? '#4ade80'
                                : perfSellValue < perfTotalBuyValue ? '#f87171'
                                : '#facc15';

            perfRow.innerHTML = `
                <td class="col-code-sticky"><strong>Hiệu suất :</strong></td>
                <td>
                    <div style="${titleStyle}">Tổng khối lượng</div>
                    <strong>${perfTotalQuantity.toLocaleString('vi-VN')}</strong>
                </td>
                <td>
                    <div style="${titleStyle}">Tổng tiền Mua</div>
                    <strong style="color:${buyColor};">${perfTotalBuyValue.toLocaleString('vi-VN')}</strong>
                    <div style="font-size:11px;color:#fff;opacity:0.7;margin-top:2px;">tiền mua tạm tính</div>
                </td>
                <td>
                    <div style="${titleStyle}">Tổng tiền Bán</div>
                    <strong style="color:${perfSellColor};">${perfSellValue.toLocaleString('vi-VN')}</strong>
                    <div style="font-size:11px;color:#fff;opacity:0.7;margin-top:2px;">tiền bán tạm tính</div>
                </td>
                <td>
                    <div style="${titleStyle}">Lãi / Lỗ</div>
                    <strong style="color:${profitColor(perfProfit)};">${signedValue(perfProfit, Math.abs(perfProfit).toLocaleString('vi-VN'))}</strong>
                </td>
                <td>
                    <div style="${titleStyle}">% Lãi / Lỗ</div>
                    <strong style="color:${profitColor(perfPercent)};">${signedPercent(perfPercent)}</strong>
                </td>
            `;
        }
        tbody.appendChild(perfRow);
    }

    renderTableUserFollow(data, userFollow) {
         // 1. Tạo danh sách mã CK trong danh mục đầu tư
        // const portfolioCodes = userFollow.map(p => p.code);
        const portfolioCodes = userFollow.reduce((acc, p) => {
            acc[p.code] = p.follow_price_buy;
            return acc;
        }, {});

        // 2. Tách dữ liệu thành 2 nhóm: ưu tiên và còn lại
        // const prioritizedStocks = data.filter(stock => portfolioCodes.includes(stock.code));
        // const remainingStocks = data.filter(stock => !portfolioCodes.includes(stock.code));

        const prioritizedStocks = data
            .filter(stock => portfolioCodes.hasOwnProperty(stock.code))
            .map(stock => {
                return {
                    ...stock,
                    recommended_buy_price: portfolioCodes[stock.code] ?? stock.recommended_buy_price
                };
            });
        
        const remainingStocks = data
            .filter(stock => !portfolioCodes.hasOwnProperty(stock.code))
            .map(stock => {
                return {...stock };
            });

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
            const valuationClass = this.getRowClass(parseFloat(stock.follow_price_buy), parseFloat(stock.current_price));
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
                    ${isInPortfolio ? `<button onclick="location.href='${baseUrl}/user/updateFollow/${stock.code}'">Update</button><button class="btn-delete" onclick="confirmDelete('${stock.code}')">Delete</button>` : ''}
                </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    renderTableUserInfoProfile(data) {
        const tbody = document.getElementById('userInfoTableBody');
        tbody.innerHTML = '';
        data.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.code}</td>
                <td>${Number(item.total_quantity).toLocaleString('vi-VN')}</td>
                <td>${Number(item.avg_buy_price).toLocaleString('vi-VN')}</td>
                <td>
                <div class="action-cell">
                    <button class="btn-delete" onclick="confirmDelete('${item.code}')">Delete</button>
                </div>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
}
