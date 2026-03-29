const formatter = new Intl.NumberFormat('vi-VN');

function isNumber(value) {
    return !isNaN(value) && value.trim() !== '';
}

function parseNumber(str) {
    return str.replace(/[^\d]/g, "");
}

function formatToVND(input) {
    let raw = parseNumber(input.value);
    if (raw === "") return input.value = "";
    input.value = formatter.format(raw);
}

function isStockUpdateFormReady() {
    const codeEl = document.getElementById("code");
    if (!codeEl) return false;
    const code = codeEl.value.trim().toUpperCase();
    const currentPrice = parseNumber(document.getElementById("currentPrice").value);
    const priceAvg = parseNumber(document.getElementById("priceAvg").value);
    const buyPrice = parseNumber(document.getElementById("buyPrice").value);
    const sellPrice = parseNumber(document.getElementById("sellPrice").value);
    const percentBuy = document.getElementById("percentBuy").value.trim();
    const percentSell = document.getElementById("percentSell").value.trim();
    const ratingStocks = document.getElementById("ratingStocks").value.trim();
    const stocksVn = document.getElementById("stocksVn").value.trim();

    if (!code) return false;
    if (!currentPrice || !isNumber(currentPrice)) return false;
    if (priceAvg && !isNumber(priceAvg)) return false;
    if (buyPrice && !isNumber(buyPrice)) return false;
    if (sellPrice && !isNumber(sellPrice)) return false;
    if (percentBuy && isNaN(percentBuy)) return false;
    if (percentSell && isNaN(percentSell)) return false;
    if (ratingStocks && isNaN(ratingStocks)) return false;
    if (stocksVn && isNaN(stocksVn)) return false;
    return true;
}

function updateStockUpdateSubmitButton() {
    const btn = document.getElementById("btnFormSubmit");
    if (btn) btn.disabled = !isStockUpdateFormReady();
}

document.addEventListener("DOMContentLoaded", function () {
    // Format price fields on input
    const priceFields = ["currentPrice", "priceAvg", "buyPrice", "sellPrice"];
    priceFields.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener("input", () => {
                formatToVND(el);
                updateStockUpdateSubmitButton();
            });
        }
    });

    ["code", "percentBuy", "percentSell", "risk", "ratingStocks", "stocksVn"].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener("input", updateStockUpdateSubmitButton);
            el.addEventListener("change", updateStockUpdateSubmitButton);
        }
    });

    // Pre-populate fields from stockData (passed from Blade)
    if (typeof stockData !== 'undefined' && stockData) {
        document.getElementById("code").value = stockData.code || "";

        const priceMap = {
            currentPrice: stockData.current_price,
            priceAvg: stockData.price_avg,
            buyPrice: stockData.recommended_buy_price,
            sellPrice: stockData.recommended_sell_price
        };

        for (const [id, val] of Object.entries(priceMap)) {
            const el = document.getElementById(id);
            if (el && val != null && Number(val) > 0) {
                el.value = formatter.format(Number(val));
            }
        }

        const directMap = {
            percentBuy: stockData.percent_buy,
            percentSell: stockData.percent_sell,
            ratingStocks: stockData.rating_stocks,
            stocksVn: stockData.stocks_vn
        };

        for (const [id, val] of Object.entries(directMap)) {
            const el = document.getElementById(id);
            if (el && val != null) {
                el.value = val;
            }
        }

        document.getElementById("risk").value = stockData.risk_level || 1;
    }
    updateStockUpdateSubmitButton();
});

function toastShow(type, message) {
    const toast = document.getElementById("toast");
    toast.classList.remove("toast-success", "toast-error");
    toast.classList.add(type === 'success' ? "toast-success" : "toast-error");
    toast.innerHTML = message;
    toast.classList.add("toast", "show");
    setTimeout(() => {
        toast.className = toast.className.replace("show", "");
    }, 3000);
}

window.submitUpdateForm = function() {
    const code = document.getElementById("code").value.trim().toUpperCase();
    const currentPrice = parseNumber(document.getElementById("currentPrice").value);
    const priceAvg = parseNumber(document.getElementById("priceAvg").value);
    const buyPrice = parseNumber(document.getElementById("buyPrice").value);
    const sellPrice = parseNumber(document.getElementById("sellPrice").value);
    const percentBuy = document.getElementById("percentBuy").value.trim();
    const percentSell = document.getElementById("percentSell").value.trim();
    const risk = document.getElementById("risk").value;
    const ratingStocks = document.getElementById("ratingStocks").value.trim();
    const stocksVn = document.getElementById("stocksVn").value.trim();
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let isValid = true;
    document.querySelectorAll(".error").forEach(el => el.style.display = "none");

    // Required: Mã CK
    if (!code) {
        document.getElementById("errorCode").style.display = "block";
        isValid = false;
    }

    // Required: Giá hiện tại
    if (!currentPrice) {
        document.getElementById("errorCurrent").style.display = "block";
        isValid = false;
    } else if (!isNumber(currentPrice)) {
        document.getElementById("errorCurrentType").style.display = "block";
        isValid = false;
    }

    // Optional: Giá trung bình
    if (priceAvg && !isNumber(priceAvg)) {
        document.getElementById("errorPriceAvgType").style.display = "block";
        isValid = false;
    }

    // Optional: Giá mua tốt
    if (buyPrice && !isNumber(buyPrice)) {
        document.getElementById("errorBuyType").style.display = "block";
        isValid = false;
    }

    // Optional: Giá bán tốt
    if (sellPrice && !isNumber(sellPrice)) {
        document.getElementById("errorSellType").style.display = "block";
        isValid = false;
    }

    // Optional: Tỉ lệ mua
    if (percentBuy && isNaN(percentBuy)) {
        document.getElementById("errorPercentBuyType").style.display = "block";
        isValid = false;
    }

    // Optional: Tỉ lệ bán
    if (percentSell && isNaN(percentSell)) {
        document.getElementById("errorPercentSellType").style.display = "block";
        isValid = false;
    }

    // Optional: Điểm
    if (ratingStocks && isNaN(ratingStocks)) {
        document.getElementById("errorRatingType").style.display = "block";
        isValid = false;
    }

    // Optional: Thuộc VN
    if (stocksVn && isNaN(stocksVn)) {
        document.getElementById("errorStocksVnType").style.display = "block";
        isValid = false;
    }

    if (isValid) {
        const data = {
            code: code,
            currentPrice: currentPrice,
            priceAvg: priceAvg || null,
            buyPrice: buyPrice || null,
            sellPrice: sellPrice || null,
            percentBuy: percentBuy || null,
            percentSell: percentSell || null,
            risk: risk,
            ratingStocks: ratingStocks || null,
            stocksVn: stocksVn || null
        };

        $.ajax({
            url: baseUrl + '/admin/update/' + code,
            type: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            data: JSON.stringify(data),
            success: function (response) {
                if (response.status === "success") {
                    toastShow('success', `✅ Đã cập nhật mã <b>${code}</b>`);
                } else {
                    toastShow('error', `❌ ${response.message}`);
                }
            },
            error: function (xhr) {
                console.log(xhr);
                toastShow('error', '❌ Lỗi: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
};
