const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');

puppeteer.use(StealthPlugin());

const symbol = process.argv[2];
if (!symbol) {
    console.log("Không xác định được mã cổ phiếu");
    process.exit(1);
}
const url = `https://s.cafef.vn/Lich-su-giao-dich-${symbol}-1.chn`;

(async () => {

    const browser = await puppeteer.launch({
        headless: false, // Tắt chế độ headless để kiểm tra
        defaultViewport: null,
        args: ['--start-maximized']
    });

    const page = await browser.newPage();

    // Thêm giả lập môi trường thật hơn
    await page.evaluateOnNewDocument(() => {
        Object.defineProperty(navigator, 'webdriver', { get: () => false });
        Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'vi-VN'] });
        Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3, 4, 5] });
    });

    await page.goto(url, { waitUntil: 'networkidle2' });

    await new Promise(res => setTimeout(res, 2000)); // Đợi 2s trước cho chắc

    const maxWait = 60000; // 60s
    const checkInterval = 1000; // 1s
    let waited = 0;
    let found = false;

    while (waited < maxWait) {
        const hasElement = await page.$('.oddOwner');
        if (hasElement) {
            found = true;
            break;
        }
        await new Promise(res => setTimeout(res, checkInterval));
        waited += checkInterval;
    }

    if (!found) {
        console.error("Không tìm thấy bảng dữ liệu sau 60s. Có thể bị chặn.");
        await browser.close();
        process.exit(2); // trả về lỗi rõ ràng cho PHP
    }

    // ✅ Lấy HTML phần bảng
    const html = await page.$eval('.oddOwner', el => `
    <table>
      <tbody>
        <tr class="oddOwner">${el.innerHTML}</tr>
      </tbody>
    </table>
  `);

    const cheerio = require('cheerio');
    const $ = cheerio.load(html);

    const owner_time = $('td.owner_time').first().text().trim();
    const owner_priceClose_1 = $('td.owner_priceClose').eq(0).text().trim();
    const owner_priceClose_2 = $('td.owner_priceClose').eq(1).text().trim();

    const result = {
      owner_time,
      owner_priceClose_1,
      owner_priceClose_2
    };

    console.log(JSON.stringify(result));

    await browser.close();
})();
