const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const cheerio = require('cheerio');

puppeteer.use(StealthPlugin());

const symbol = process.argv[2];
if (!symbol) {
    console.log("Không xác định được mã cổ phiếu");
    process.exit(1);
}
const url = `https://finance.vietstock.vn/lich-su-kien.htm?page=1&tab=2&code=${symbol}`;

(async () => {
  const browser = await puppeteer.launch({
    headless: false,
    defaultViewport: null,
    args: ['--start-maximized']
  });

  const page = await browser.newPage();

  // Fake navigator
  await page.evaluateOnNewDocument(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => false });
    Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'vi-VN'] });
    Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3] });
  });

  await page.goto(url, { waitUntil: 'networkidle2' });

  const maxWait = 60000; // 60s
  const checkInterval = 1000; // 1s
  let waited = 0;
  let found = false;

  while (waited < maxWait) {
      const hasElement = await page.$('.table-responsive table tbody tr');
      if (hasElement) {
          found = true;
          break;
      }
      await new Promise(res => setTimeout(res, checkInterval));
      waited += checkInterval;
  }

  if (!found) {
      console.log("Không tìm thấy bảng dữ liệu sau khi load. Có thể bị chặn.");
      await browser.close();
      return;
  }

  const tableHTML = await page.$eval('.table-responsive table', el => el.outerHTML);
  const $ = cheerio.load(tableHTML);

  
  let result = 1; // Mặc định là 1: ra khỏi diện cảnh báo
  const rows = $('tr').get().reverse(); // Lấy tất cả các <tr>, đảo ngược thứ tự

  rows.forEach((row) => {
    const cols = $(row).find('td');
    if (cols.length >= 5) {
      const content = $(cols[4]).text().trim();

      if(content.includes("Hủy niêm yết cổ phiếu")){
        result = 4;
      }else if (content.includes("Đưa cổ phiếu vào diện Đình chỉ giao dịch")) {
        result = 4;
      }else if (content.includes("Đưa cổ phiếu ra khỏi diện Đình chỉ giao dịch")) {
        result = 3;
      }else if (content.includes("Đưa cổ phiếu vào diện Cảnh báo và bị kiểm soát")) {
        result = 3;
      }else if (content.includes("Đưa cổ phiếu vào diện Cảnh báo và hạn chế giao dịch")) {
        result = 3;
      }else if (content.includes("Đưa cổ phiếu vào diện hạn chế giao dịch")) {
        result = 3;
      } else if (content.includes("Đưa cổ phiếu ra khỏi diện Cảnh báo và bị kiểm soát")) {
        result = 2;
      } else if (content.includes("Đưa cổ phiếu vào diện kiểm soát")) {
        result = 2;
      } else if (content.includes("Đưa cổ phiếu vào diện cảnh báo")) {
        result = 2;
      } else if (content.includes("Đưa cổ phiếu ra khỏi diện cảnh báo")) {
        result = 1;
      }else if (content.includes("Giao dịch trở lại")) {
        result = 1;
      }
    }
  });

  console.log(JSON.stringify(result));

  await browser.close();
})();
