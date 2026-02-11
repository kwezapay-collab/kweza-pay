<?php
require_once '../../backend/api/session.php';
require_once '../../backend/api/db.php';
requireLogin('../index.php');

if ($_SESSION['user_type'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}

$currentUser = getCurrentUser($pdo);

// Ensure campus cafes table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS campus_cafes (
        cafe_id INT AUTO_INCREMENT PRIMARY KEY,
        cafe_name VARCHAR(255) NOT NULL,
        cafe_description TEXT,
        cafe_logo VARCHAR(255),
        airtel_money_code VARCHAR(100) NOT NULL,
        qr_code_image VARCHAR(255),
        created_by INT,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");

// Ensure help desk reports table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS help_reports (
        report_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type VARCHAR(50) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('NEW', 'VIEWED', 'RESOLVED') DEFAULT 'NEW',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// Ensure cafe meals table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS cafe_meals (
        meal_id INT AUTO_INCREMENT PRIMARY KEY,
        cafe_id INT NOT NULL,
        meal_name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cafe_id) REFERENCES campus_cafes(cafe_id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// Get statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'Student'")->fetchColumn();
$totalMerchants = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'Merchant'")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'Admin'")->fetchColumn();
$totalTransactions = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$totalVolume = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions")->fetchColumn();

// Get today's stats
$todayTransactions = $pdo->query("SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayVolume = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Get transaction data for chart (last 7 days)
$chartData = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count, SUM(amount) as volume
    FROM transactions
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll();

// Get all users
$allUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Get all transactions with user names (Sender)
$allTransactions = $pdo->query("
    SELECT t.*, u.full_name as user_name 
    FROM transactions t 
    LEFT JOIN users u ON t.sender_id = u.user_id 
    ORDER BY t.created_at DESC
")->fetchAll();

// Cafe meals
$cafesList = $pdo->query("SELECT cafe_id, cafe_name FROM campus_cafes ORDER BY cafe_name ASC")->fetchAll();
$cafeMeals = $pdo->query("
    SELECT m.*, c.cafe_name
    FROM cafe_meals m
    LEFT JOIN campus_cafes c ON m.cafe_id = c.cafe_id
    ORDER BY m.created_at DESC
")->fetchAll();

// Help desk reports
$helpReports = $pdo->query("
    SELECT r.*, u.full_name, u.phone_number
    FROM help_reports r
    LEFT JOIN users u ON r.user_id = u.user_id
    ORDER BY r.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kweza Pay</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="apple-touch-icon" href="../assets/img/apple-touch-icon.png">
    <link rel="stylesheet" href="../assets/css/paypal_ui.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --admin-dark: #11295E;
            --admin-blue: #0070BA;
            --bg-gray: #F5F7FA;
            --side-bg: #FFFFFF;
        }

        body {
            background-color: var(--bg-gray);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            color: #242729;
        }

        .sidebar {
            width: 260px;
            background: var(--side-bg);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            color: #242729;
            padding: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #DDE1E3;
            z-index: 1000;
        }

        .sidebar-logo {
            height: 100px; /* Fixed height to prevent shifting */
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0; /* Ensures container doesn't shrink */
            cursor: pointer;
        }

        .sidebar-logo img {
            height: 180px; /* You can now increase this freely */
            width: auto;
            position: fixed;
            left: 90px;
            top: -45px;
            
             /* Optional: limit width if needed */
            object-fit: contain;
            transition: height 0.2s ease;
        }

        .search-box {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border-radius: 10px;
            border: 1px solid #DDE1E3;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6C7378;
        }

        .side-link {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #6C7378;
            text-decoration: none;
            font-weight: 600;
            padding: 14px 25px;
            transition: all 0.2s ease;
            font-size: 15px;
            border-left: 4px solid transparent;
        }

        .side-link:hover {
            color: var(--admin-blue);
            background: #f8fafc;
        }

        .side-link.active {
            color: var(--admin-blue);
            background: #f0f7ff;
            border-left-color: var(--admin-blue);
        }

        .side-link i {
            width: 20px;
            text-align: center;
            font-size: 18px;
        }

        .main-content {
            margin-left: 260px;
            padding: 40px;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 20px 40px;
            margin: -40px -40px 40px;
            border-bottom: 1px solid #DDE1E3;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid #DDE1E3;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-val {
            font-size: 24px;
            font-weight: 700;
            color: #11295E;
            margin: 8px 0 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #6C7378;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            background: #f0f7ff;
            color: #0070BA;
        }

        .chart-section {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid #DDE1E3;
            margin-bottom: 32px;
        }

        .data-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }

        .data-card {
            background: white;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid #DDE1E3;
            overflow-x: auto;
        }

        .full-card {
            margin: -40px -40px -40px;
            border-radius: 0 !important;
            border: none;
            border-top: 1px solid #DDE1E3;
            padding: 40px;
            min-height: calc(100vh - 105px);
            box-shadow: none;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            text-align: left;
            padding: 12px 16px;
            color: #6C7378;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid #DDE1E3;
        }

        .data-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-student { background: #E7F6EC; color: #097230; }
        .badge-merchant { background: #E8F1FF; color: #004494; }
        .badge-admin { background: #FFF0F0; color: #C41212; }
        .badge-report-new { background: #fff7ed; color: #b45309; }
        .badge-report-viewed { background: #e0f2fe; color: #0369a1; }
        .badge-report-resolved { background: #ecfdf3; color: #16a34a; }

        .report-message {
            max-width: 380px;
            white-space: normal;
            line-height: 1.4;
            word-break: break-word;
        }

        .admin-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .admin-input,
        .admin-textarea,
        .admin-select {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #DDE1E3;
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
        }

        .admin-textarea {
            min-height: 110px;
            resize: vertical;
        }

        .admin-btn {
            background: var(--admin-dark);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            font-weight: 700;
            cursor: pointer;
        }

        .admin-btn:hover {
            background: var(--admin-blue);
        }

        .event-form-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
            background: #ffffff;
        }

        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-top: 10px;
        }

        .event-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .event-image {
            width: 100%;
            height: 160px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid #eef2f7;
            background: #f8fafc;
        }

        .event-title {
            font-weight: 800;
            color: var(--admin-dark);
            font-size: 16px;
        }

        .event-meta {
            font-size: 12px;
            color: #6C7378;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .event-price {
            font-weight: 800;
            color: var(--admin-blue);
        }

        .event-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .event-actions .admin-btn {
            padding: 8px 12px;
            font-size: 12px;
        }

        .qr-preview {
            max-width: 180px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 6px;
            background: #f8fafc;
        }

        .qr-preview-box {
            border: 1px dashed #cbd5f5;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8fafc;
        }

        .event-pill {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 999px;
            padding: 4px 10px;
            font-weight: 700;
            font-size: 11px;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 30px;
            background: #f8fafc;
            border: 1px solid #DDE1E3;
            cursor: pointer;
            transition: all 0.2s;
        }

        .profile-btn:hover {
            background: #f1f5f9;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--admin-dark);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        @media (max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .data-grid { grid-template-columns: 1fr; }
            .admin-form-grid { grid-template-columns: 1fr; }
        }

        /* Mobile Specific Styles */
        .mobile-toggle {
            display: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 260px;
                box-shadow: 10px 0 15px rgba(0,0,0,0.1);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .header {
                margin: -20px -20px 30px;
                padding: 15px 20px;
                gap: 10px;
            }

            .header > div:first-child h1 {
                font-size: 18px !important;
            }

            .header > div:first-child p {
                font-size: 12px !important;
            }

            .stats-grid {
                grid-template-columns: 1fr !important;
                gap: 15px;
            }

            .mobile-toggle {
                display: flex !important;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                background: #f0f7ff;
                color: var(--admin-blue);
                border-radius: 10px;
                cursor: pointer;
                font-size: 20px;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(2px);
                z-index: 999;
            }

            .sidebar-overlay.active {
                display: block;
            }

            .profile-btn {
                padding: 6px 12px;
            }

            .profile-btn > div:first-child {
                display: none;
            }

            .chart-section {
                padding: 20px;
            }

            .data-card {
                padding: 20px;
            }

            .data-table th, .data-table td {
                padding: 12px 10px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="sidebar">
        <div class="sidebar-logo" onclick="if(window.innerWidth <= 768) toggleSidebar()">
            <img src="../assets/img/logo.png" alt="Kweza Pay" onerror="this.src='https://ui-avatars.com/api/?name=KP&background=0D8ABC&color=fff'">
            <div style="font-weight: 800; font-size: 18px; color: #11295E;">Admin Portal</div>
        </div>

        <nav style="display: flex; flex-direction: column; flex: 1;">
            <a href="#" class="side-link active" onclick="showAdminSection('dashboard')" id="link-dashboard"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="#" class="side-link" onclick="showAdminSection('merchants')" id="link-merchants"><i class="fas fa-store"></i> Merchant Apps</a>
            <a href="#" class="side-link" onclick="showAdminSection('users')" id="link-users"><i class="fas fa-users"></i> Users</a>
            <a href="#" class="side-link" onclick="showAdminSection('transactions')" id="link-transactions"><i class="fas fa-exchange-alt"></i> Transactions</a>
            <a href="#" class="side-link" onclick="showAdminSection('events')" id="link-events"><i class="fas fa-ticket-alt"></i> Ticket Events</a>
            <a href="#" class="side-link" onclick="showAdminSection('cafes')" id="link-cafes"><i class="fas fa-utensils"></i> Cafe Meals</a>
            <a href="#" class="side-link" onclick="showAdminSection('reports')" id="link-reports"><i class="fas fa-life-ring"></i> Reports</a>
            <a href="#" class="side-link" onclick="showAdminSection('settings')" id="link-settings"><i class="fas fa-cog"></i> Settings</a>
            
            <div style="margin-top: auto; padding-top: 20px; padding-bottom: 20px; border-top: 1px solid #f1f5f9;">
                <a href="../logout.php" class="side-link" style="color: #ef4444;"><i class="fas fa-power-off"></i> Logout</a>
            </div>
        </nav>
    </div>

    <div class="main-content">
        <header class="header">
            <div style="display: flex; align-items: center;">
                <div class="mobile-toggle" onclick="toggleSidebar()" style="margin-right: 15px;">
                    <i class="fas fa-bars"></i>
                </div>
                <div>
                    <h1 style="font-size: 24px; font-weight: 800; color: #11295E; margin: 0;">Admin Overview</h1>
                    <p style="color: #6C7378; margin: 4px 0 0; font-size: 14px;">Managing Kweza Pay Ecosystem</p>
                </div>
            </div>
            
            <div class="profile-btn" onclick="document.getElementById('adminPicInput').click()">
                <div style="text-align: right;">
                    <div style="font-weight: 700; color: #11295E; font-size: 14px;">
                        <?php echo htmlspecialchars($currentUser['full_name']); ?>
                    </div>
                    <div style="font-size: 11px; color: #6C7378; font-weight: 700; text-transform: uppercase;">System Administrator</div>
                </div>
                <?php if (!empty($currentUser['profile_pic'])): ?>
                    <img src="../<?php echo htmlspecialchars($currentUser['profile_pic']); ?>" class="admin-avatar">
                <?php else: ?>
                    <div class="admin-avatar"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
                <?php endif; ?>
                <input type="file" id="adminPicInput" style="display: none;" accept="image/*" onchange="uploadAdminPic(this)">
            </div>
        </header>
        <div class="section-container">
            <div id="section-dashboard">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-val"><?php echo number_format($totalUsers); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #f0fdf4; color: #10b981;"><i
                            class="fas fa-graduation-cap"></i></div>
                    <div class="stat-val"><?php echo number_format($totalStudents); ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff7ed; color: #f59e0b;"><i class="fas fa-store"></i></div>
                    <div class="stat-val"><?php echo number_format($totalMerchants); ?></div>
                    <div class="stat-label">Merchants</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef2f2; color: #ef4444;"><i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stat-val">MWK <?php echo number_format($todayVolume / 1000, 1); ?>K</div>
                    <div class="stat-label">Today's Volume</div>
                </div>
            </div>

            <div class="chart-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--admin-dark); margin: 0;">Growth Analytics
                    </h3>
                    <div style="display: flex; gap: 20px; font-size: 12px; font-weight: 700; color: #64748b;">
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <div style="width: 10px; height: 10px; background: var(--admin-blue); border-radius: 3px;">
                            </div> Volume
                        </span>
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <div style="width: 10px; height: 10px; background: #10b981; border-radius: 3px;"></div>
                            Transactions
                        </span>
                    </div>
                </div>
                <canvas id="growthChart" height="80"></canvas>
            </div>

            <div class="data-grid">
                <div class="data-card">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--admin-dark); margin: 0 0 25px;">Recent
                        Transactions</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $txns = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 8")->fetchAll();
                            foreach ($txns as $t):
                                ?>
                                <tr>
                                    <td style="font-family: monospace; font-weight: 600;">
                                        <?php echo substr($t['reference_code'], -10); ?>
                                    </td>
                                    <td><?php echo $t['txn_type']; ?></td>
                                    <td style="font-weight: 700; color: var(--admin-dark);">MWK
                                        <?php echo number_format($t['amount'], 0); ?>
                                    </td>
                                    <td><span style="color: #10b981; font-weight: 700; font-size: 12px;"><i
                                                class="fas fa-check-circle"></i> Completed</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="data-card">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--admin-dark); margin: 0 0 25px;">Top Users
                    </h3>
                    <?php
                    $topUsers = $pdo->query("SELECT * FROM users ORDER BY wallet_balance DESC LIMIT 5")->fetchAll();
                    foreach ($topUsers as $u):
                        $badge = 'badge-' . strtolower($u['user_type']);
                        ?>
                        <div
                            style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['full_name']); ?>&background=random"
                                    style="width: 35px; height: 35px; border-radius: 10px;">
                                <div>
                                    <div style="font-weight: 700; font-size: 14px; color: var(--admin-dark);">
                                        <?php echo htmlspecialchars($u['full_name']); ?>
                                    </div>
                                    <span class="badge <?php echo $badge; ?>"><?php echo $u['user_type']; ?></span>
                                </div>
                            </div>
                            <div style="font-weight: 800; font-size: 14px; color: var(--admin-blue);">MWK
                                <?php echo number_format($u['wallet_balance'], 0); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        </div>

        <div id="section-merchants" style="display: none;">
            <div class="data-card full-card">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--admin-dark); margin: 0 0 25px;">Merchant Applications</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Business Name</th>
                            <th>Fee Paid</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $apps = $pdo->query("
                            SELECT u.user_id, u.full_name, m.business_name, m.fee_paid, m.is_approved 
                            FROM merchants m 
                            JOIN users u ON m.user_id = u.user_id 
                            ORDER BY m.is_approved ASC, u.created_at DESC
                        ")->fetchAll();
                        
                        if (empty($apps)) {
                            echo "<tr><td colspan='5' style='text-align: center; color: #64748b; padding: 40px;'>No applications found</td></tr>";
                        }
                        
                        foreach ($apps as $a):
                        ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($a['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($a['business_name']); ?></td>
                            <td>
                                <?php if ($a['fee_paid']): ?>
                                    <span style="color: #16a34a; font-weight: 700;"><i class="fas fa-check"></i> Paid</span>
                                <?php else: ?>
                                    <span style="color: #ef4444; font-weight: 700;"><i class="fas fa-clock"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($a['is_approved']): ?>
                                    <span class="badge badge-student">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-admin">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$a['is_approved']): ?>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="approveMerchant(<?php echo $a['user_id']; ?>, 'approve')" style="background: #2563eb; color: white; border: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer;">Approve</button>
                                        <button onclick="approveMerchant(<?php echo $a['user_id']; ?>, 'reject')" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer;">Reject</button>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #64748b; font-size: 12px;">No Actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="section-users" style="display: none;">
            <div class="data-card full-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--admin-dark); margin: 0;">System Users</h3>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearch" placeholder="Search by name, email or type..." onkeyup="filterUsers()">
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Wallet</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $user): 
                            $badgeStyle = '';
                            if ($user['user_type'] === 'Student') $badgeStyle = 'badge-student';
                            elseif ($user['user_type'] === 'Merchant') $badgeStyle = 'badge-merchant';
                            elseif ($user['user_type'] === 'Admin') $badgeStyle = 'badge-admin';
                        ?>
                        <tr class="user-row">
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=random" style="width: 30px; height: 30px; border-radius: 8px;">
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge <?php echo $badgeStyle; ?>"><?php echo $user['user_type']; ?></span></td>
                            <td style="font-weight: 700; color: var(--admin-blue);">MWK <?php echo number_format($user['wallet_balance'], 2); ?></td>
                            <td style="color: #6C7378; font-size: 13px;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="section-transactions" style="display: none;">
            <div class="data-card full-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--admin-dark); margin: 0;">Transaction Records</h3>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; flex: 1; justify-content: flex-end;">
                        <input type="date" id="dateSearch" onchange="filterTransactions()" style="padding: 10px; border-radius: 10px; border: 1px solid #DDE1E3; font-size: 14px; outline: none;">
                        <div class="search-box" style="max-width: 300px;">
                            <i class="fas fa-search"></i>
                            <input type="text" id="txnSearch" placeholder="Search ID or Status..." onkeyup="filterTransactions()">
                        </div>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference Code</th>
                            <th>User Name</th>
                            <th>Transaction ID</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTransactions as $t): ?>
                        <tr class="txn-row" data-date="<?php echo date('Y-m-d', strtotime($t['created_at'])); ?>">
                            <td style="font-family: monospace; font-weight: 600;"><?php echo $t['reference_code']; ?></td>
                            <td><?php echo htmlspecialchars($t['user_name'] ?? 'System'); ?></td>
                            <td>#TXN-<?php echo $t['txn_id']; ?></td>
                            <td><?php echo $t['txn_type']; ?></td>
                            <td style="font-weight: 700; color: var(--admin-dark);">MWK <?php echo number_format($t['amount'], 2); ?></td>
                            <td style="color: #6C7378; font-size: 13px;">
                                <?php echo date('M d, Y H:i', strtotime($t['created_at'])); ?>
                            </td>
                            <td><span style="color: #10b981; font-weight: 700; font-size: 12px;"><i class="fas fa-check-circle"></i> Completed</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="section-events" style="display: none;">
            <div class="data-card full-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--admin-dark); margin: 0;">Ticket Events</h3>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button class="admin-btn" onclick="jumpToEventForm()"><i class="fas fa-plus-circle"></i> Add Ticket Event</button>
                        <button class="admin-btn" style="background:#0ea5e9;" onclick="loadAdminEvents()"><i class="fas fa-sync-alt"></i> Refresh</button>
                    </div>
                </div>

                <div id="eventFormCard" class="event-form-card">
                    <h4 id="eventFormTitle" style="margin: 0 0 15px; font-weight: 800; color: var(--admin-dark);">Create New Event</h4>
                    <form id="eventForm" enctype="multipart/form-data">
                        <input type="hidden" id="eventId">
                        <div class="admin-form-grid">
                            <div>
                                <label style="display:block; font-weight:700; margin-bottom:8px;">Event Name *</label>
                                <input type="text" id="eventName" class="admin-input" required>
                            </div>
                            <div>
                                <label style="display:block; font-weight:700; margin-bottom:8px;">Ticket Price (MWK) *</label>
                                <input type="number" id="eventPrice" class="admin-input" min="0" step="0.01" required>
                            </div>
                            <div>
                                <label style="display:block; font-weight:700; margin-bottom:8px;">Event Date & Time</label>
                                <input type="datetime-local" id="eventDate" class="admin-input">
                            </div>
                            <div>
                                <label style="display:block; font-weight:700; margin-bottom:8px;">Event Location</label>
                                <input type="text" id="eventLocation" class="admin-input">
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <label style="display:block; font-weight:700; margin-bottom:8px;">Event Description</label>
                            <textarea id="eventDescription" class="admin-textarea"></textarea>
                        </div>

                        <div class="admin-form-grid" style="margin-top: 15px;">
                            <div>
                                <label style="display:block; font-weight:700; margin-bottom:8px;">Airtel Money Code</label>
                                <input type="text" id="airtelMoneyCode" class="admin-input" placeholder="e.g. *211*12# or paycode">
                            </div>
                            <div>
                                <label style="display:block; font-weight:700; margin-bottom:8px;">Airtel Money ID</label>
                                <input type="text" id="airtelMoneyId" class="admin-input" placeholder="Merchant / Airtel ID">
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <label style="display:block; font-weight:700; margin-bottom:8px;">QR Preview</label>
                            <div class="qr-preview-box" id="qrPreviewContainer">
                                <span style="color:#6C7378; font-size: 13px;">Enter an Airtel Money code to auto-generate the QR.</span>
                            </div>
                        </div>

                        <div class="admin-form-grid" style="margin-top: 15px;">
                            <div>
                                <label style="display:block; font-weight:700; margin-bottom:8px;">Maximum Tickets (Optional)</label>
                                <input type="number" id="maxTickets" class="admin-input" min="1">
                            </div>
                            <div>
                                <label style="display:block; font-weight:700; margin-bottom:8px;">Event Picture</label>
                                <input type="file" id="eventPicture" class="admin-input" accept="image/*" onchange="previewEventImage(this)">
                                <img id="eventPicturePreview" class="event-image" style="margin-top: 10px; display: none;">
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <label style="display:block; font-weight:700; margin-bottom:8px;">Ticket Template (HTML/Text)</label>
                            <textarea id="ticketTemplate" class="admin-textarea" placeholder="Optional ticket template"></textarea>
                        </div>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                            <button type="submit" id="eventSubmitBtn" class="admin-btn"><i class="fas fa-save"></i> Create Event</button>
                            <button type="button" id="eventCancelBtn" class="admin-btn" style="background:#f59e0b; display:none;" onclick="resetEventForm()"><i class="fas fa-undo"></i> Cancel Edit</button>
                        </div>
                    </form>
                </div>

                <h4 style="margin: 10px 0 10px; font-weight: 800; color: var(--admin-dark);">Existing Events</h4>
                <div id="eventsList" class="event-grid"></div>
            </div>
        </div>

        <div id="section-cafes" style="display: none;">
            <div class="data-card full-card">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--admin-dark); margin: 0 0 20px;">Campus Cafe Meals</h3>
                
                <form id="addCafeMealForm" style="margin-bottom: 30px;">
                    <div class="admin-form-grid">
                        <?php if (empty($cafesList)): ?>
                            <input type="hidden" id="mealCafe" value="">
                        <?php else: ?>
                            <input type="hidden" id="mealCafe" value="<?php echo $cafesList[0]['cafe_id']; ?>">
                        <?php endif; ?>
                        <div>
                            <label style="display:block; font-weight:700; margin-bottom:8px;">Meal Name *</label>
                            <input type="text" id="mealName" class="admin-input" required>
                        </div>
                        <div>
                            <label style="display:block; font-weight:700; margin-bottom:8px;">Price (MWK) *</label>
                            <input type="number" id="mealPrice" class="admin-input" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <label style="display:block; font-weight:700; margin-bottom:8px;">Description</label>
                        <textarea id="mealDescription" class="admin-textarea"></textarea>
                    </div>
                    <button type="submit" class="admin-btn" style="margin-top: 15px;">Add Meal</button>
                </form>

                <h3 style="font-size: 16px; font-weight: 800; color: var(--admin-dark); margin: 0 0 15px;">Existing Meals</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cafe</th>
                            <th>Meal</th>
                            <th>Price</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($cafeMeals)) {
                            echo "<tr><td colspan='6' style='text-align: center; color: #64748b; padding: 40px;'>No meals added yet</td></tr>";
                        } else {
                            foreach ($cafeMeals as $meal):
                                $statusLabel = ($meal['is_active'] ?? 1) ? 'Active' : 'Inactive';
                        ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($meal['cafe_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($meal['meal_name']); ?></td>
                            <td style="font-weight: 700; color: var(--admin-blue);">MWK <?php echo number_format($meal['price'], 2); ?></td>
                            <td class="report-message"><?php echo nl2br(htmlspecialchars($meal['description'] ?? '')); ?></td>
                            <td><span class="badge <?php echo ($statusLabel === 'Active') ? 'badge-report-resolved' : 'badge-report-viewed'; ?>"><?php echo $statusLabel; ?></span></td>
                            <td style="color: #6C7378; font-size: 13px;"><?php echo date('M d, Y', strtotime($meal['created_at'])); ?></td>
                        </tr>
                        <?php
                            endforeach;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="section-reports" style="display: none;">
            <div class="data-card full-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--admin-dark); margin: 0;">Help Desk Reports</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($helpReports)) {
                            echo "<tr><td colspan='6' style='text-align: center; color: #64748b; padding: 40px;'>No reports submitted yet</td></tr>";
                        } else {
                            foreach ($helpReports as $r):
                                $status = $r['status'] ?? 'NEW';
                                $badgeClass = 'badge-report-new';
                                if ($status === 'VIEWED') $badgeClass = 'badge-report-viewed';
                                if ($status === 'RESOLVED') $badgeClass = 'badge-report-resolved';
                                $userName = $r['full_name'] ?: 'Unknown';
                                $phone = $r['phone_number'] ? ' - ' . $r['phone_number'] : '';
                        ?>
                        <tr>
                            <td style="color: #6C7378; font-size: 13px;"><?php echo date('M d, Y H:i', strtotime($r['created_at'])); ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($userName . $phone); ?></td>
                            <td><?php echo htmlspecialchars($r['user_type']); ?></td>
                            <td><?php echo htmlspecialchars($r['subject']); ?></td>
                            <td class="report-message"><?php echo nl2br(htmlspecialchars($r['message'])); ?></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                        </tr>
                        <?php
                            endforeach;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="section-settings" style="display: none;">
            <div class="data-card full-card">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--admin-dark); margin: 0 0 25px;">System Settings</h3>
                <p style="color: #6C7378;">Configuration options will appear here.</p>
            </div>
<script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.sidebar-overlay').classList.toggle('active');
        }

        // Admin Section Toggling
        function showAdminSection(section) {
            if (window.innerWidth <= 768 && document.querySelector('.sidebar').classList.contains('active')) toggleSidebar();
            
            // Hide all sections
            document.querySelectorAll('[id^="section-"]').forEach(s => s.style.display = 'none');
            
            const target = document.getElementById('section-' + section);
            if (target) {
                target.style.display = 'block';
                const titleMap = {
                    'dashboard': 'Admin Overview',
                    'merchants': 'Merchant Applications',
                    'users': 'User Management',
                    'transactions': 'Transaction Records',
                    'events': 'Ticket Events',
                    'cafes': 'Campus Cafe Meals',
                    'reports': 'Help Desk Reports',
                    'settings': 'System Settings'
                };
                if (titleMap[section]) {
                    document.querySelector('.header h1').innerText = titleMap[section];
                }
                if (section === 'events') {
                    loadAdminEvents();
                }
            }
            
            document.querySelectorAll('.side-link').forEach(l => l.classList.remove('active'));
            const activeLink = document.getElementById('link-' + section);
            if (activeLink) activeLink.classList.add('active');
        }

        // Event Management (in-panel)
        let editingEventId = null;
        let eventsCache = {};

        function resolveEventAsset(path) {
            if (!path) return '';
            if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:')) return path;
            if (path.startsWith('../')) return path;
            return '../' + path.replace(/^\.?\//, '');
        }

        function jumpToEventForm() {
            const card = document.getElementById('eventFormCard');
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function previewEventImage(input) {
            const preview = document.getElementById('eventPicturePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function updateEventQrPreview(code, fallbackImage = '') {
            const container = document.getElementById('qrPreviewContainer');
            if (!container) return;
            const trimmed = (code || '').trim();
            let qrUrl = '';
            if (trimmed) {
                qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(trimmed);
            } else if (fallbackImage) {
                qrUrl = resolveEventAsset(fallbackImage);
            }

            if (qrUrl) {
                container.innerHTML = `<img src="${qrUrl}" class="qr-preview" alt="Airtel QR">`;
            } else {
                container.innerHTML = '<span style="color:#6C7378; font-size: 13px;">Enter an Airtel Money code to auto-generate the QR.</span>';
            }
        }

        function resetEventForm() {
            editingEventId = null;
            const form = document.getElementById('eventForm');
            if (form) form.reset();
            document.getElementById('eventId').value = '';
            document.getElementById('eventFormTitle').innerText = 'Create New Event';
            document.getElementById('eventSubmitBtn').innerHTML = '<i class="fas fa-save"></i> Create Event';
            document.getElementById('eventCancelBtn').style.display = 'none';
            document.getElementById('eventPicturePreview').style.display = 'none';
            updateEventQrPreview('');
        }

        function startEditEvent(eventId) {
            const event = eventsCache[eventId];
            if (!event) return;
            editingEventId = eventId;
            document.getElementById('eventId').value = eventId;
            document.getElementById('eventName').value = event.event_name || '';
            document.getElementById('eventDescription').value = event.event_description || '';
            document.getElementById('eventPrice').value = event.ticket_price || '';
            document.getElementById('eventDate').value = event.event_date ? event.event_date.replace(' ', 'T').slice(0, 16) : '';
            document.getElementById('eventLocation').value = event.event_location || '';
            document.getElementById('airtelMoneyCode').value = event.airtel_money_code || '';
            document.getElementById('airtelMoneyId').value = event.airtel_money_id || '';
            document.getElementById('maxTickets').value = event.max_tickets || '';
            document.getElementById('ticketTemplate').value = event.ticket_template || '';

            const preview = document.getElementById('eventPicturePreview');
            if (event.event_picture) {
                preview.src = resolveEventAsset(event.event_picture);
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }

            updateEventQrPreview(event.airtel_money_code, event.airtel_qr_image);

            document.getElementById('eventFormTitle').innerText = 'Edit Event';
            document.getElementById('eventSubmitBtn').innerHTML = '<i class="fas fa-save"></i> Save Changes';
            document.getElementById('eventCancelBtn').style.display = 'inline-block';
            jumpToEventForm();
        }

        async function loadAdminEvents() {
            const container = document.getElementById('eventsList');
            if (!container) return;
            try {
                const res = await fetch('../../backend/api/get_events.php?admin=true');
                const data = await res.json();
                if (!data.success) {
                    container.innerHTML = '<div class="event-card">Unable to load events.</div>';
                    return;
                }

                if (data.events.length === 0) {
                    container.innerHTML = '<div class="event-card"><strong>No events created yet.</strong></div>';
                    return;
                }

                eventsCache = {};
                container.innerHTML = data.events.map(event => {
                    eventsCache[event.event_id] = event;
                    const eventImage = event.event_picture ? `<img src="${resolveEventAsset(event.event_picture)}" class="event-image" alt="${event.event_name}">` : '';
                    const qrUrl = event.airtel_qr_image ? resolveEventAsset(event.airtel_qr_image) : (event.airtel_money_code ? `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(event.airtel_money_code)}` : '');
                    return `
                        <div class="event-card">
                            ${eventImage}
                            <div class="event-title">${event.event_name}</div>
                            <div class="event-price">MWK ${parseFloat(event.ticket_price).toLocaleString()}</div>
                            <div class="event-meta">
                                ${event.event_date ? `<span><i class="fas fa-calendar"></i> ${new Date(event.event_date).toLocaleDateString()}</span>` : ''}
                                ${event.event_location ? `<span><i class="fas fa-map-marker-alt"></i> ${event.event_location}</span>` : ''}
                                <span><i class="fas fa-ticket-alt"></i> Sold: ${event.tickets_sold || 0}${event.max_tickets ? ' / ' + event.max_tickets : ''}</span>
                                ${event.airtel_money_id ? `<span><i class="fas fa-id-badge"></i> ${event.airtel_money_id}</span>` : ''}
                                ${event.airtel_money_code ? `<span><i class="fas fa-mobile-alt"></i> ${event.airtel_money_code}</span>` : ''}
                            </div>
                            ${qrUrl ? `<img src="${qrUrl}" class="qr-preview" alt="QR Code">` : ''}
                            <div class="event-actions">
                                <button class="admin-btn" style="background:#0ea5e9;" onclick="startEditEvent(${event.event_id})"><i class="fas fa-edit"></i> Edit</button>
                                <button class="admin-btn" onclick="toggleEventStatus(${event.event_id}, ${event.is_active})">${event.is_active ? '<i class="fas fa-pause"></i> Deactivate' : '<i class="fas fa-play"></i> Activate'}</button>
                                <button class="admin-btn" style="background:#8b5cf6;" onclick="generateOwnerLink(${event.event_id})"><i class="fas fa-link"></i> Owner Link</button>
                                <button class="admin-btn" style="background:#ef4444;" onclick="deleteEvent(${event.event_id})"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (e) {
                container.innerHTML = '<div class="event-card">Network error loading events.</div>';
            }
        }

        async function toggleEventStatus(eventId, currentStatus) {
            if (!confirm('Are you sure you want to ' + (currentStatus ? 'deactivate' : 'activate') + ' this event?')) return;
            try {
                const res = await fetch('../../backend/api/admin_toggle_event.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ event_id: eventId, is_active: !currentStatus })
                });
                const data = await res.json();
                if (data.success) {
                    loadAdminEvents();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Network error');
            }
        }

        async function deleteEvent(eventId) {
            if (!confirm('Are you sure you want to delete this event? This action cannot be undone.')) return;
            try {
                const res = await fetch('../../backend/api/admin_delete_event.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ event_id: eventId })
                });
                const data = await res.json();
                if (data.success) {
                    if (editingEventId === eventId) resetEventForm();
                    loadAdminEvents();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Network error');
            }
        }

        async function generateOwnerLink(eventId) {
            try {
                const res = await fetch('../../backend/api/admin_generate_owner_link.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ event_id: eventId })
                });
                const data = await res.json();
                if (data.success) {
                    const fullLink = window.location.origin + '/kweza-app/' + data.link;
                    // Copy to clipboard
                    navigator.clipboard.writeText(fullLink).then(() => {
                        alert('Owner Link generated and copied to clipboard!\n\nLink: ' + fullLink);
                    });
                } else {
                    alert('Error generating link: ' + data.error);
                }
            } catch (e) {
                alert('Network error');
            }
        }

        const airtelInput = document.getElementById('airtelMoneyCode');
        if (airtelInput) {
            airtelInput.addEventListener('input', (e) => updateEventQrPreview(e.target.value));
        }

        const eventForm = document.getElementById('eventForm');
        if (eventForm) {
            eventForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData();
                formData.append('event_name', document.getElementById('eventName').value);
                formData.append('event_description', document.getElementById('eventDescription').value);
                formData.append('ticket_price', document.getElementById('eventPrice').value);
                formData.append('event_date', document.getElementById('eventDate').value);
                formData.append('event_location', document.getElementById('eventLocation').value);
                formData.append('airtel_money_code', document.getElementById('airtelMoneyCode').value);
                formData.append('airtel_money_id', document.getElementById('airtelMoneyId').value);
                formData.append('max_tickets', document.getElementById('maxTickets').value);
                formData.append('ticket_template', document.getElementById('ticketTemplate').value);

                const pictureFile = document.getElementById('eventPicture').files[0];
                if (pictureFile) {
                    formData.append('event_picture', pictureFile);
                }

                const isEditing = !!editingEventId;
                if (isEditing) {
                    formData.append('event_id', editingEventId);
                }

                try {
                    const res = await fetch(isEditing ? '../../backend/api/admin_update_event.php' : '../../backend/api/admin_create_event.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        resetEventForm();
                        loadAdminEvents();
                    } else {
                        alert('Error: ' + data.error);
                    }
                } catch (e) {
                    alert('Network error');
                }
            });
        }

        if (document.getElementById('eventForm')) {
            resetEventForm();
        }

        function filterUsers() {
            const query = document.getElementById('userSearch').value.toLowerCase();
            document.querySelectorAll('.user-row').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
        }

        function filterTransactions() {
            const query = document.getElementById('txnSearch').value.toLowerCase();
            const date = document.getElementById('dateSearch').value;
            
            document.querySelectorAll('.txn-row').forEach(row => {
                const text = row.innerText.toLowerCase();
                const rowDate = row.getAttribute('data-date');
                const matchesSearch = text.includes(query);
                const matchesDate = !date || rowDate === date;
                row.style.display = (matchesSearch && matchesDate) ? '' : 'none';
            });
        }

        async function approveMerchant(userId, action) {
            if (!confirm('Are you sure you want to ' + action + ' this merchant application?')) return;
            
            try {
                const res = await fetch('../../backend/api/admin_approve_merchant.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, action: action })
                });
                const data = await res.json();
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.error);
                }
            } catch (e) {
                alert('Connection error');
            }
        }

        const cafeMealForm = document.getElementById('addCafeMealForm');
        if (cafeMealForm) {
            cafeMealForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const cafeId = document.getElementById('mealCafe').value;
                const mealName = document.getElementById('mealName').value.trim();
                const mealPrice = document.getElementById('mealPrice').value;
                const mealDescription = document.getElementById('mealDescription').value.trim();

                if (!mealName || !mealPrice) {
                    alert('Please enter meal name and price.');
                    return;
                }

                try {
                    const res = await fetch('../../backend/api/admin_add_cafe_meal.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            cafe_id: cafeId,
                            meal_name: mealName,
                            price: mealPrice,
                            description: mealDescription
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert('Meal added successfully.');
                        location.reload();
                    } else {
                        alert(data.error || 'Unable to add meal.');
                    }
                } catch (e) {
                    alert('Connection error');
                }
            });
        }

        async function uploadAdminPic(input) {
            if (!input.files || !input.files[0]) return;
            const formData = new FormData();
            formData.append('profile_pic', input.files[0]);
            try {
                const res = await fetch('../../backend/api/upload_profile_pic.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error);
                }
            } catch (e) {
                alert('Upload failed');
            }
        }

        const chartData = <?php echo json_encode($chartData); ?>;
        const labels = chartData.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        const volumes = chartData.map(d => parseFloat(d.volume));
        const counts = chartData.map(d => parseInt(d.count));

        const ctx = document.getElementById('growthChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Volume',
                    data: volumes,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#2563eb'
                }, {
                    label: 'Transactions',
                    data: counts,
                    borderColor: '#10b981',
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                    borderWidth: 2,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#64748b' }
                    },
                    y1: {
                        position: 'right',
                        grid: { display: false },
                        ticks: { color: '#64748b' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b' }
                    }
                }
            }
        });
    </script>
</body>

</html>
