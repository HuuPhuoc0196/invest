<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Le Huu Phuoc — PHP Developer</title>
<style>
:root{
  --bg:#0f1724;
  --card:#0b1220;
  --muted:#9aa4b2;
  --accent:#16a34a;
  --accent2:#06b6d4;
  --text:#e6eef6;
  --radius:14px;
  --gap:20px;
  font-family: 'Inter', sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  background:linear-gradient(180deg,#061021 0%,#071824 60%);
  color:var(--text);
  font-size:16px;
  line-height:1.6;
  display:flex;
  justify-content:center;
  padding:24px;
}
.container{
  width:100%;
  max-width:1100px;
  display:grid;
  grid-template-columns:320px 1fr;
  gap:var(--gap);
}
.card{
  background:linear-gradient(180deg,rgba(255,255,255,0.03),rgba(255,255,255,0.01));
  border:1px solid rgba(255,255,255,0.05);
  border-radius:var(--radius);
  padding:20px;
}
.left .logo{
  display:flex;align-items:center;gap:10px;margin-bottom:16px;
}
.logo .mark{
  width:60px;height:60px;border-radius:12px;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:22px;
}
.logo h1{font-size:20px;margin:0;}
.logo p{font-size:13px;color:var(--muted);margin:0;}
.photo{
  width:100%;height:160px;border-radius:10px;
  background:rgba(255,255,255,0.03);
  display:flex;align-items:center;justify-content:center;
  color:var(--muted);font-size:13px;margin:12px 0;
}
.section-title{font-weight:700;font-size:18px;margin-bottom:8px;}
.section{margin-top:16px;}
.item{margin-bottom:6px;color:var(--muted);font-size:14px;}
.skill-badge,.tag{
  display:inline-block;padding:6px 10px;border-radius:8px;
  background:rgba(255,255,255,0.05);color:var(--muted);
  font-size:13px;margin:4px 4px 0 0;
}
.right .block{
  background:var(--card);border:1px solid rgba(255,255,255,0.04);
  border-radius:var(--radius);padding:16px;margin-bottom:16px;
}
.right h2{font-size:18px;margin-bottom:8px;}
.job{margin-bottom:12px;}
.job h3{margin-bottom:4px;font-size:16px;}
.job .meta{font-size:13px;color:var(--muted);}
ul{margin-left:18px;color:var(--muted);}
a{color:var(--text);text-decoration:none}
@media(max-width:850px){
  body{padding:14px;}
  .container{grid-template-columns:1fr;}
  .photo{height:120px;}
  .logo{flex-direction:row;gap:12px;}
  .left,.right{padding:16px;}
  .right h2{font-size:17px;}
  .job h3{font-size:15px;}
  .item,.skill-badge,.tag{font-size:14px;}
}
</style>
</head>
<body>
<div class="container">

  <div class="left card">
    <div class="logo">
      <div class="mark">LP</div>
      <div>
        <h1>LÊ HỮU PHƯỚC</h1>
        <p>PHP Developer — Backend Engineer</p>
      </div>
    </div>
    <!-- <div class="photo">Profile Photo</div> -->

    <div class="section">
      <div class="section-title">Liên hệ</div>
      <div class="item">📞 0382 834 597</div>
      <div class="item">✉️ lehuuphuoc0196@gmail.com</div>
      <div class="item">📍 297 Phan Huy Ích, Gò Vấp, TP.HCM</div>
      <div class="item">🌐 <a href="https://github.com/HuuPhuoc0196" target="_blank">github.com/HuuPhuoc0196</a></div>
      <div class="item">📘 <a href="https://facebook.com/huu.phuoc.568" target="_blank">facebook.com/huu.phuoc.568</a></div>
    </div>

    <div class="section">
      <div class="section-title">Mục tiêu nghề nghiệp</div>
      <div class="item">
        - Ngắn hạn: Có việc làm ổn định, rèn luyện kỹ năng chuyên môn và kỹ năng mềm.<br>
        - Dài hạn: Trở thành lập trình viên chuyên nghiệp, tham gia đội ngũ phát triển chính, đóng góp vào quản lý dự án và phát triển bền vững.
      </div>
    </div>

    <div class="section">
      <div class="section-title">Kỹ năng chuyên môn</div>
      <div class="item"><b>Ngôn ngữ & Web:</b></div>
      <div>
        <span class="skill-badge">PHP</span>
        <span class="skill-badge">CodeIgniter</span>
        <span class="skill-badge">Laravel</span>
        <span class="skill-badge">HTML/CSS/JS</span>
        <span class="skill-badge">Bootstrap</span>
        <span class="skill-badge">Ajax</span>
        <span class="skill-badge">RESTful API</span>
      </div>
      <div class="item" style="margin-top:8px"><b>Cơ sở dữ liệu & Khác:</b></div>
      <div>
        <span class="skill-badge">MySQL</span>
        <span class="skill-badge">PostgreSQL</span>
        <span class="skill-badge">Docker</span>
        <span class="skill-badge">AWS</span>
        <span class="skill-badge">Git / GitLab</span>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Kỹ năng mềm & Sở thích</div>
      <div class="item">Giao tiếp, Làm việc nhóm, Tư duy logic, Học hỏi nhanh.</div>
      <div>
        <span class="tag">Du lịch</span>
        <span class="tag">Phát triển bản thân</span>
        <span class="tag">Học tiếng Anh</span>
      </div>
    </div>
  </div>

  <div class="right">
    <div class="block">
      <h2>Tóm tắt nghề nghiệp</h2>
      <p class="item">
        Lập trình viên PHP với hơn 4 năm kinh nghiệm thực tế từ thực tập đến làm việc chính thức. Thành thạo CodeIgniter và Laravel, xây dựng RESTful API, xử lý cơ sở dữ liệu (MySQL, PostgreSQL, SQL Server, Oracle), triển khai ứng dụng bằng Docker/AWS. Có kinh nghiệm phát triển hệ thống thực tế như quản lý bệnh viện, bản đồ khu vực, và kiểm thử tự động.
      </p>
    </div>

    <div class="block">
      <h2>Kinh nghiệm làm việc</h2>
	  <br/>
      <div class="job">
        <div class="meta">02/2023 – 08/2025</div>
        <h3>Thực hiện nghĩa vụ công an</h3>
		<ul>
			<li><div class="item">Tham gia phục vụ trong lực lượng công an theo quy định của nhà nước.</div></li>
		</ul>
      </div>
	  <br/>
      <div class="job">
        <div class="meta">11/2019 – 01/2023</div>
        <h3>Cloud Nine Solutions — Project Area Marker</h3>
        <ul>
          <li>Phát triển và triển khai website nền tảng bản đồ quản lý khu vực cửa hàng.</li>
          <li>Sử dụng PHP, Python, Angular, Bootstrap.</li>
          <li>Sử dụng nhiều DBMS: SQL Server, Oracle, PostgreSQL</li>
          <li>Áp dụng Docker, AWS, Git/GitLab và kiểm thử giao diện (UI Testing).</li>
        </ul>
      </div>
	  <br/>
      <div class="job">
        <div class="meta">10/2018 – 09/2019</div>
        <h3>FPT Software — Dự án EWO-365 (Bệnh viện thú y)</h3>
        <ul>
          <li>Phát triển backend bằng CodeIgniter theo mô hình H-MVC.</li>
          <li>Xây dựng RESTful API và viết unit test.</li>
          <li>Công nghệ: PHP, Ajax, Bootstrap, MySQL, JavaScript, jQuery.</li>
        </ul>
      </div>
	  <br/>
      <div class="job">
        <div class="meta">07/2018 – 10/2018</div>
        <h3>FPT Software — Fresher PHP</h3>
        <ul>
          <li>Xây dựng hệ thống mô phỏng E-commerce (Shopping Cart) có phân quyền người dùng.</li>
          <li>Rèn luyện kỹ năng kiểm thử, gỡ lỗi và phân tích yêu cầu.</li>
        </ul>
      </div>
    </div>
	
    <div class="block">
      <h2>Học vấn & Thành tích</h2>
      <div class="job">
        <div class="meta">09/2015 – 10/2019</div>
        <h3>Đại học Mở TP.HCM — Cử nhân CNTT</h3>
        <ul>
          <li>Nền tảng vững về lập trình, thuật toán, cấu trúc dữ liệu.</li>
          <li>08/2018: Giải Nhì cuộc thi Thuật toán cấp trường.</li>
          <li>12/2018: Đại diện trường tham dự Olympic Tin học Toàn quốc.</li>
          <li>04/2019: Giải Nhì Nghiên cứu khoa học với đề tài “Hệ thống hỗ trợ giao thông thông minh”.</li>
        </ul>
      </div>
    </div>

    <div class="block">
      <h2>Dự án tiêu biểu</h2>
      <ul>
        <li><b>EWO-365:</b> Hệ thống quản lý bệnh viện thú y.</li>
        <li><b>Area Marker:</b> Nền tảng bản đồ quản lý khu vực cửa hàng, triển khai trên AWS + Docker.</li>
        <li><b>Shopping Cart:</b> Dự án mô phỏng thương mại điện tử.</li>
      </ul>
    </div>

    <div class="block">
      <h2>Liên hệ</h2>
      <div class="item">📞 0382834597</div>
      <div class="item">✉️ <a href="mailto:lehuuphuoc0196@gmail.com">lehuuphuoc0196@gmail.com</a></div>
      <div class="item">🌐 <a href="https://github.com/HuuPhuoc0196" target="_blank">github.com/HuuPhuoc0196</a></div>
      <div class="item">📘 <a href="https://facebook.com/huu.phuoc.568" target="_blank">facebook.com/huu.phuoc.568</a></div>
    </div>
  </div>
</div>
</body>
</html>
