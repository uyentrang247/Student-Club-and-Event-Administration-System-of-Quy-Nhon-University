<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê điểm danh - UniQCLUB</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #6a7ef0f1;
            display: flex;
            justify-content: center;
            padding: 40px 20px;
        }

        .dashboard-container {
            width: 100%;
            max-width: 1000px;
        }

        /* Header Card */
        .header-card {
            background: white;
            border-radius: 20px;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .header-icon {
            background: #f0effb;
            color: #7d70cc;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 24px;
        }

        .header-info h1 {
            font-size: 1.4rem;
            color: #333;
        }

        .header-info p {
            color: #777;
            font-size: 0.9rem;
        }

        .date {
            margin-left: 15px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 8px solid transparent;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #888;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 2rem;
            color: #333;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        /* Border & Colors */
        .border-purple {
            border-left-color: #7d70cc;
        }

        .border-green {
            border-left-color: #4cd137;
        }

        .border-red {
            border-left-color: #ff4757;
        }

        .purple-bg {
            background: #f0effb;
            color: #7d70cc;
        }

        .green-bg {
            background: #eafaf1;
            color: #4cd137;
        }

        .red-bg {
            background: #fff0f0;
            color: #ff4757;
        }

        .text-green {
            color: #4cd137;
        }

        .text-red {
            color: #ff4757;
        }

        /* List Card */
        .list-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            min-height: 400px;
        }

        .list-title {
            color: #ff4757;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        .absent-table {
            width: 100%;
            border-collapse: collapse;
        }

        .absent-table th {
            text-align: left;
            font-size: 0.75rem;
            color: #bbb;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .absent-table td {
            padding: 15px 0;
            color: #555;
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar,
        .avatar-text {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-text {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.8rem;
            color: white;
        }

        .pink {
            background: #ff78ae;
        }

        /* Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .badge-purple {
            background: #f5f3ff;
            color: #a78bfa;
        }

        .badge-green {
            background: #ecfdf5;
            color: #10b981;
        }

        /* Footer */
        .footer-actions {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        button {
            padding: 12px 25px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid white;
        }

        .btn-export {
            background: white;
            color: #7d70cc;
        }

        button:hover {
            opacity: 0.8;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <div class="dashboard-container">
        <div class="header-card">
            <div class="header-icon">
                <i class="fa-solid fa-chart-simple"></i>
            </div>
            <div class="header-info">
                <h1>Thống kê điểm danh</h1>
                <p>UniQCLUB <span class="date" id="current-date"></span></p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card border-purple">
                <div class="stat-content">
                    <p class="stat-label">TỔNG SỐ THÀNH VIÊN</p>
                    <h2 class="stat-value"></h2>
                </div>
                <div class="stat-icon purple-bg"><i class="fa-solid fa-users"></i></div>
            </div>

            <div class="stat-card border-green">
                <div class="stat-content">
                    <p class="stat-label">SỐ LƯỢNG CÓ MẶT</p>
                    <h2 class="stat-value text-green"></h2>
                </div>
                <div class="stat-icon green-bg"><i class="fa-regular fa-circle-check"></i></div>
            </div>

            <div class="stat-card border-red">
                <div class="stat-content">
                    <p class="stat-label">SỐ LƯỢNG VẮNG MẶT</p>
                    <h2 class="stat-value text-red"></h2>
                </div>
                <div class="stat-icon red-bg"><i class="fa-solid fa-bullseye"></i></div>
            </div>
        </div>

        <div class="list-card">
            <h3 class="list-title">Danh sách vắng mặt</h3>
            <table class="absent-table">
                <thead>
                    <tr>
                        <th>THÀNH VIÊN</th>
                        <th>MÃ SINH VIÊN</th>
                        <th>PHÒNG BAN</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="user-info">

                            </div>
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer-actions">
            <button class="btn-back"><i class="fa-solid fa-arrow-left"></i> Quay lại</button>
            <button class="btn-export"><i class="fa-solid fa-file-export"></i> Xuất báo cáo</button>
        </div>
    </div>

</body>

</html>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const now = new Date();
        const dateString = "Ngày: " + now.getDate().toString().padStart(2, '0') +
            "/" + (now.getMonth() + 1).toString().padStart(2, '0') +
            "/" + now.getFullYear();

        document.getElementById("current-date").innerText = dateString;
    });
</script>