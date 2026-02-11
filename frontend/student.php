<?php
require_once '../backend/api/session.php';
require_once '../backend/api/db.php';
requireLogin();
$user = getCurrentUser($pdo);

// Calculate total money transferred (cumulative volume)
$stmt_total = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE sender_id = ?");
$stmt_total->execute([$user['user_id']]);
$total_transferred = $stmt_total->fetch()['total'] ?: 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kweza Pay - Home</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/paypal_ui.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 400px;
            width: 90%;
            border: 1px solid var(--pp-border);
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .modal-content::-webkit-scrollbar {
            display: none;
        }

        .input-field {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--pp-border);
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--pp-dark-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--pp-blue);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--pp-dark-blue);
            color: var(--pp-dark-blue);
        }

        .btn-outline:hover {
            background: #f0f0f0;
        }

        .hidden {
            display: none !important;
        }

        body.dashboard-student {
            background: #f4f7fb;
            position: relative;
            overflow-x: hidden;
        }

        body.dashboard-student::before,
        body.dashboard-student::after {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        body.dashboard-student::before {
            background:
                radial-gradient(circle at 15% 20%, rgba(17, 41, 94, 0.18), rgba(17, 41, 94, 0) 55%),
                radial-gradient(circle at 85% 80%, rgba(14, 116, 144, 0.16), rgba(14, 116, 144, 0) 60%),
                linear-gradient(135deg, rgba(17, 41, 94, 0.06), rgba(255, 255, 255, 0));
            opacity: 0.26;
        }

        body.dashboard-student::after {
            background-image: url("data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22360%22%20height%3D%22360%22%20viewBox%3D%220%200%20360%20360%22%3E%0A%20%20%3Crect%20width%3D%22360%22%20height%3D%22360%22%20fill%3D%22none%22%2F%3E%0A%0A%20%20%3Crect%20x%3D%220%22%20y%3D%220%22%20width%3D%2270%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%2270%22%20y%3D%220%22%20width%3D%2250%22%20height%3D%22360%22%20fill%3D%22%23ece7d8%22%2F%3E%0A%20%20%3Crect%20x%3D%22120%22%20y%3D%220%22%20width%3D%22120%22%20height%3D%22360%22%20fill%3D%22%23ece7d8%22%2F%3E%0A%20%20%3Crect%20x%3D%22240%22%20y%3D%220%22%20width%3D%2250%22%20height%3D%22360%22%20fill%3D%22%23ece7d8%22%2F%3E%0A%20%20%3Crect%20x%3D%22290%22%20y%3D%220%22%20width%3D%2270%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%0A%20%20%3Crect%20x%3D%2268%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%22118%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%22238%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%22288%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%0A%20%20%3Cg%20fill%3D%22%239aa200%22%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%200%20L70%2015%20L0%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%2030%20L70%2045%20L0%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%2060%20L70%2075%20L0%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%2090%20L70%20105%20L0%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20120%20L70%20135%20L0%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20150%20L70%20165%20L0%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20180%20L70%20195%20L0%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20210%20L70%20225%20L0%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20240%20L70%20255%20L0%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20270%20L70%20285%20L0%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20300%20L70%20315%20L0%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20330%20L70%20345%20L0%20360%20Z%22%2F%3E%0A%0A%20%20%20%20%3Cpath%20d%3D%22M360%200%20L290%2015%20L360%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%2030%20L290%2045%20L360%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%2060%20L290%2075%20L360%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%2090%20L290%20105%20L360%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20120%20L290%20135%20L360%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20150%20L290%20165%20L360%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20180%20L290%20195%20L360%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20210%20L290%20225%20L360%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20240%20L290%20255%20L360%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20270%20L290%20285%20L360%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20300%20L290%20315%20L360%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20330%20L290%20345%20L360%20360%20Z%22%2F%3E%0A%20%20%3C%2Fg%3E%0A%0A%20%20%3Cg%20fill%3D%22%23c86b1d%22%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%200%20L120%2015%20L70%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%2030%20L120%2045%20L70%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%2060%20L120%2075%20L70%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%2090%20L120%20105%20L70%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20120%20L120%20135%20L70%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20150%20L120%20165%20L70%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20180%20L120%20195%20L70%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20210%20L120%20225%20L70%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20240%20L120%20255%20L70%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20270%20L120%20285%20L70%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20300%20L120%20315%20L70%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20330%20L120%20345%20L70%20360%20Z%22%2F%3E%0A%0A%20%20%20%20%3Cpath%20d%3D%22M290%200%20L240%2015%20L290%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%2030%20L240%2045%20L290%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%2060%20L240%2075%20L290%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%2090%20L240%20105%20L290%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20120%20L240%20135%20L290%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20150%20L240%20165%20L290%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20180%20L240%20195%20L290%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20210%20L240%20225%20L290%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20240%20L240%20255%20L290%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20270%20L240%20285%20L290%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20300%20L240%20315%20L290%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20330%20L240%20345%20L290%20360%20Z%22%2F%3E%0A%20%20%3C%2Fg%3E%0A%0A%20%20%3Cg%20fill%3D%22none%22%20stroke%3D%22%230b0b0b%22%20stroke-linecap%3D%22square%22%20stroke-linejoin%3D%22round%22%3E%0A%20%20%20%20%3Cg%20transform%3D%22translate(0%2C0)%22%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%200%20L220%2060%20L180%20120%20L140%2060%20Z%22%20stroke-width%3D%226%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L206%2060%20L180%2096%20L154%2060%20Z%22%20stroke-width%3D%224%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L180%2096%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M154%2060%20L206%2060%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%3C%2Fg%3E%0A%20%20%20%20%3Cg%20transform%3D%22translate(0%2C120)%22%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%200%20L220%2060%20L180%20120%20L140%2060%20Z%22%20stroke-width%3D%226%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L206%2060%20L180%2096%20L154%2060%20Z%22%20stroke-width%3D%224%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L180%2096%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M154%2060%20L206%2060%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%3C%2Fg%3E%0A%20%20%20%20%3Cg%20transform%3D%22translate(0%2C240)%22%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%200%20L220%2060%20L180%20120%20L140%2060%20Z%22%20stroke-width%3D%226%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L206%2060%20L180%2096%20L154%2060%20Z%22%20stroke-width%3D%224%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L180%2096%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M154%2060%20L206%2060%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%3C%2Fg%3E%0A%20%20%3C%2Fg%3E%0A%0A%20%20%3Crect%20x%3D%22178%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%20opacity%3D%220.7%22%2F%3E%0A%3C%2Fsvg%3E");
            background-repeat: repeat;
            background-size: 360px 360px;
            opacity: 0.03;
            filter: grayscale(1);
        }

        body.dashboard-student .pp-main {
            position: relative;
            z-index: 1;
        }

        #balanceCard {
            background: #265D82;
            color: #ffffff;
        }

        #balanceCard .pp-balance-available {
            color: #ffffff;
        }

        /* Receipt Styles */
        .receipt-container {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            background: white;
            padding: 20px;
            border-radius: 0;
            position: relative;
            overflow: hidden;
        }

        .receipt-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 15px;
        }

        .receipt-logo {
            width: 60px;
            height: 60px;
        }

        .receipt-title-box {
            display: flex;
            flex-direction: column;
        }

        .receipt-main-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--pp-blue);
            margin: 0;
        }

        .receipt-tagline {
            font-size: 12px;
            color: #64748b;
        }

        .receipt-info-line {
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .receipt-info-label {
            color: #333;
            text-transform: uppercase;
        }

        .receipt-section-title {
            font-size: 16px;
            font-weight: 800;
            margin: 20px 0 10px;
            text-transform: uppercase;
            color: #1a1a1a;
        }

        .receipt-details-grid {
            margin-bottom: 20px;
        }

        .receipt-detail-item {
            font-size: 14px;
            margin-bottom: 5px;
            color: #4b5563;
        }

        .receipt-status {
            font-size: 18px;
            font-weight: 800;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .receipt-status.success {
            color: #16a34a;
        }

        .receipt-disclaimer {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            margin-top: 15px;
        }

        .receipt-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            width: 80%;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
        }

        .receipt-footer {
            margin-top: 30px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        @media (max-width: 900px) {
            .pp-nav {
                margin-left: 0;
            }
        }

        /* Student dashboard spacing tweaks */
        .dashboard-student #balanceCard {
            margin-bottom: 10px;
        }

        @media (min-width: 901px) {
            .dashboard-student .pp-main.is-home {
                grid-template-columns: 480px 1fr;
            }

            .dashboard-student #recentActivityCard {
                width: 410px;
                max-width: 410px;
                justify-self: start;
            }

            .dashboard-student #dashboardActions {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 20px;
                justify-items: center;
                align-content: start;
            }

            .dashboard-student #dashboardActions .kweza-save-action {
                grid-column: 1;
                grid-row: 2;
            }
        }

        .dashboard-student #homeScreen .pp-card {
            position: relative;
            overflow: hidden;
        }

        .dashboard-student #homeScreen .pp-card::before,
        .dashboard-student #homeScreen .pp-card::after {
            content: "";
            position: absolute;
            pointer-events: none;
            z-index: 0;
            opacity: 0.35;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0) 60%),
                radial-gradient(circle at 70% 70%, rgba(17, 41, 94, 0.18), rgba(17, 41, 94, 0) 60%);
        }

        .dashboard-student #homeScreen .pp-card::before {
            width: 220px;
            height: 220px;
            top: -60px;
            right: -70px;
        }

        .dashboard-student #homeScreen .pp-card::after {
            width: 180px;
            height: 180px;
            bottom: -50px;
            left: -60px;
        }

        .dashboard-student #homeScreen .pp-card > * {
            position: relative;
            z-index: 1;
        }

        .dashboard-student .pp-actions-grid {
            margin-bottom: 12px;
        }

        .dashboard-student #recentActivityCard {
            margin-bottom: 14px;
        }

        @media (max-width: 900px) {
            .dashboard-student #balanceCard {
                margin-bottom: 8px;
            }

            .dashboard-student .pp-actions-grid {
                margin-bottom: 10px;
            }

            .dashboard-student #recentActivityCard {
                margin-bottom: 10px;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/css/student_mobile.css?v=<?php echo time(); ?>">
</head>

<body class="dashboard-student">
    <!-- PayPal Styled Header -->
    <header class="pp-header">
        <div class="pp-nav">
            <a href="#" onclick="switchScreen('home')">
                <img src="assets/img/logo.png" alt="Kweza Pay" class="pp-logo"
                    onerror="this.src='https://ui-avatars.com/api/?name=Kweza+Pay&background=0D8ABC&color=fff'">
            </a>
            <span id="nav-greeting" style="color: white; font-weight: 700; font-size: 18px; transition: all 10.0s ease; position: absolute; left: 50%; transform: translateX(-50%);">Student Account</span>
        </div>
        <div class="pp-mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars" id="menuIcon"></i>
        </div>
        <div class="pp-header-actions" id="headerActions">
            <i class="far fa-bell pp-header-icon" data-label="Notifications" onclick="showNotifications()"></i>
            <div onclick="switchScreen('me')" class="pp-header-icon" data-label="My Profile" style="cursor: pointer;">
                <div class="profile-menu-icon">
                    <?php if (!empty($user['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </div>
            </div>
            <a href="logout.php" class="pp-logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>
    </header>

    <main class="pp-main is-home">
        <!-- Dashboard Content -->
        <div id="homeScreen">
            <!-- Balance Card -->
            <div class="pp-card" id="balanceCard">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="pp-balance-title">Total transaction Student  history </span>
                    <i class="fas fa-ellipsis-v" style="color: #ffffff; cursor: pointer;"></i>
                </div>
                <div class="pp-balance-amount">
                    <span id="balanceDisplay">MWK
                        <?php echo number_format($total_transferred, 2); ?></span>
                </div>
                <div class="pp-balance-available">Processed volume</div>
            </div>

            <!-- Dashboard Actions (Moved here for mobile ordering) -->
            <div class="pp-actions-grid" id="dashboardActions">
                <div class="pp-action-item" onclick="showScanner()">
                    <div class="pp-action-icon"><i class="fas fa-qrcode"></i></div>
                    <span class="pp-action-label">Pay merchant</span>
                </div>
                <div class="pp-action-item" onclick="openSendMoneyModal()">
                    <div class="pp-action-icon"><i class="fas fa-paper-plane"></i></div>
                    <span class="pp-action-label">Send money</span>
                </div>
                <div class="pp-action-item" onclick="paySUFee()">
                    <div class="pp-action-icon"><i class="fas fa-graduation-cap"></i></div>
                    <span class="pp-action-label">Pay student union fee</span>
                </div>
                <div class="pp-action-item" onclick="openHelpDesk()">
                    <div class="pp-action-icon"><i class="fas fa-headset"></i></div>
                    <span class="pp-action-label">Help desk</span>
                </div>
                <div class="pp-action-item" onclick="openEventTickets()">
                    <div class="pp-action-icon"><i class="fas fa-ticket-alt"></i></div>
                    <span class="pp-action-label">Event tickets</span>
                </div>
                <div class="pp-action-item" onclick="openCampusCafe()">
                    <div class="pp-action-icon"><i class="fas fa-utensils"></i></div>
                    <span class="pp-action-label">Campus cafe</span>
                </div>
                <div class="pp-action-item kweza-save-action" onclick="alert('Kweza Save website coming soon!')">
                    <div class="pp-action-icon"><i class="fas fa-piggy-bank"></i></div>
                    <span class="pp-action-label">Kweza Save</span>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="pp-card" id="recentActivityCard">
                <h3 class="pp-card-title">Recent activity</h3>
                <div class="pp-activity-list">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE sender_id = ? OR receiver_id = ? ORDER BY created_at DESC LIMIT 2");
                    $stmt->execute([$user['user_id'], $user['user_id']]);
                    $results = $stmt->fetchAll();
                    if (count($results) > 0):
                        foreach ($results as $txn):
                            $isDebit = $txn['sender_id'] == $user['user_id'];
                            ?>
                            <div class="pp-activity-item" onclick="viewTransactionReceipt(<?php echo htmlspecialchars(json_encode($txn)); ?>)" style="cursor: pointer;">
                                <div class="pp-activity-info">
                                    <span
                                        class="pp-activity-desc"><?php echo htmlspecialchars($txn['description'] ?: $txn['txn_type']); ?></span>
                                    <span
                                        class="pp-activity-date"><?php echo date('M d', strtotime($txn['created_at'])); ?></span>
                                </div>
                                <span
                                    class="pp-activity-amount <?php echo $isDebit ? 'amount-debit' : 'amount-credit'; ?>">
                                    <?php echo $isDebit ? '-' : '+'; ?>MWK
                                    <?php echo number_format($txn['amount'], 2); ?>
                                </span>
                            </div>
                            <?php
                        endforeach;
                    else:
                        ?>
                        <p style="color: var(--pp-text-secondary); line-height: 1.6;">See when money comes in, and
                            when it goes out. You'll find your recent Kweza Pay activity here.</p>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 25px;">
                    <a href="#" onclick="switchScreen('transactions')" class="pp-show-all">Show all</a>
                </div>
            </div>
        </div>

        <!-- Transitions & Sub-screens -->
        <div id="transactionsScreen" class="hidden" style="grid-column: 1 / -1;">
            <div class="pp-card">
                <h3 class="pp-card-title">Activity Details</h3>
                <div class="pp-activity-list">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE sender_id = ? OR receiver_id = ? ORDER BY created_at DESC LIMIT 50");
                    $stmt->execute([$user['user_id'], $user['user_id']]);
                    while ($txn = $stmt->fetch()):
                        $isDebit = $txn['sender_id'] == $user['user_id'];
                        ?>
                        <div class="pp-activity-item" onclick="viewTransactionReceipt(<?php echo htmlspecialchars(json_encode($txn)); ?>)" style="cursor: pointer;">
                            <div class="pp-activity-info">
                                <span
                                    class="pp-activity-desc"><?php echo htmlspecialchars($txn['description'] ?: $txn['txn_type']); ?></span>
                                <span
                                    class="pp-activity-date"><?php echo date('M d, Y H:i', strtotime($txn['created_at'])); ?></span>
                                <span
                                    style="font-size: 11px; font-family: monospace; color: var(--pp-text-secondary);"><?php echo $txn['reference_code']; ?></span>
                            </div>
                            <span class="pp-activity-amount <?php echo $isDebit ? 'amount-debit' : 'amount-credit'; ?>">
                                <?php echo $isDebit ? '-' : '+'; ?>MWK <?php echo number_format($txn['amount'], 2); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button class="btn btn-outline" style="margin-top: 20px;" onclick="switchScreen('home')">Back to
                    Dashboard</button>
            </div>
        </div>

        <div id="meScreen" class="hidden" style="grid-column: 1 / -1;">
            <div class="pp-card" style="max-width: 500px; margin: 0 auto;">
                <h3 class="pp-card-title">Profile Settings</h3>
                <div style="text-align: center; margin-bottom: 30px;">
                    <div id="profileImageContainer" onclick="document.getElementById('profilePicInput').click()"
                        style="width: 100px; height: 100px; background: var(--pp-blue); color: white; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: bold; cursor: pointer; overflow: hidden; position: relative; border: 3px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <?php if (!empty($user['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        <?php endif; ?>
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(17, 41, 94, 0.8); font-size: 10px; padding: 4px 0; color: white;">CHANGE</div>
                    </div>
                    <input type="file" id="profilePicInput" style="display: none;" accept="image/*" onchange="uploadProfilePic(this)">
                    <div style="font-size: 24px; font-weight: 700; margin-bottom: 5px;">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </div>
                    <div style="color: var(--pp-text-secondary);"><?php echo htmlspecialchars($user['phone_number']); ?>
                    </div>
                </div>

                <div style="padding: 15px; border-bottom: 1px solid var(--pp-border); cursor: pointer;"
                    onclick="switchScreen('transactions')">
                    <div style="font-weight: 600;"> Transaction History</div>
                </div>
                <div style="padding: 15px; border-bottom: 1px solid var(--pp-border); cursor: pointer;"
                    onclick="changePin()">
                    <div style="font-weight: 600;"> Update Security PIN</div>
                </div>
                <div style="padding: 15px; border-bottom: 1px solid var(--pp-border); cursor: pointer;"
                    onclick="document.getElementById('profilePicInput').click()">
                    <div style="font-weight: 600;"> Update Profile Picture</div>
                </div>
                <div style="padding: 15px; border-bottom: 1px solid var(--pp-border); cursor: pointer;"
                    onclick="window.location.href='camera_help.html'">
                    <div style="font-weight: 600;"> Help & Support</div>
                </div>
                <div style="padding: 15px; border-bottom: 2px solid var(--pp-blue); cursor: pointer; background: #f0f7ff;"
                    onclick="openMerchantApplication()">
                    <div style="font-weight: 700; color: var(--pp-blue);"> Become a Merchant</div>
                </div>
                <button class="btn btn-outline" style="margin-top: 30px;" onclick="logout()">Log Out</button>
                <button class="btn btn-outline" style="margin-top: 15px;" onclick="switchScreen('home')">Back</button>
            </div>
        </div>
    </main>

    <!-- MODALS -->

    <!-- QR Scanner Modal -->
    <div id="scannerModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px;">Scan Merchant QR</h3>
            <div id="qr-reader" style="width: 100%;"></div>
            <p style="text-align: center; margin: 15px 0; color: #6C7378;">OR enter manually:</p>
            <input type="text" id="manualMerchantId" class="input-field" placeholder="Enter Merchant ID">
            <button class="btn btn-primary" onclick="manualMerchantPay()">Continue</button>
            <button class="btn" style="margin-top: 10px; color: #6C7378;" onclick="stopScanner()">Close</button>
        </div>
    </div>

    <!-- Pay Modal -->
    <div id="payModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px;">Pay Merchant</h3>
            <input type="text" id="payMerchantId" class="input-field" placeholder="Merchant ID">
            <input type="number" id="payAmount" class="input-field" placeholder="Amount (MWK)">
            <input type="password" id="payPin" class="input-field" placeholder="Enter Security PIN" maxlength="4">
            <button class="btn btn-primary" onclick="processPayment()">Confirm Payment</button>
            <button class="btn" style="margin-top: 10px; color: #6C7378;"
                onclick="closeModal('payModal')">Cancel</button>
        </div>
    </div>

    <!-- Pay SU Fee Modal -->
    <div id="paySUModal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="modal-title">Pay Student Union Fee</h3>
            <form id="suForm">
                <select id="receiptType" class="input-field" required>
                    <option value="Student Union Fee">Student Union Fee</option>
                    <option value="Fee Payment">Fee Payment</option>
                </select>
                <input type="text" id="studentName" class="input-field" placeholder="Student Name" required>
                <input type="text" id="studentId" class="input-field" placeholder="Student ID" required>
                <input type="text" id="program" class="input-field" placeholder="Program" required>
                <input type="number" id="year" class="input-field" placeholder="Year" required>
                <input type="text" id="university" class="input-field" placeholder="University" required
                    value="DMI St. John the Baptist University">
                <input type="number" id="amountPaid" class="input-field" placeholder="Amount (MWK)" value="3000"
                    readonly>
                <button type="submit" class="btn btn-primary">Pay Now</button>
                <button type="button" class="btn" style="margin-top: 10px; color: #6C7378;"
                    onclick="closeModal('paySUModal')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Merchant Application Modal -->
    <div id="merchantApplyModal" class="modal-overlay">
        <div class="modal-content" style="text-align: center;">
            <div style="width: 70px; height: 70px; background: #f0f7ff; color: var(--pp-blue); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 30px;">
                <i class="fas fa-store"></i>
            </div>
            <h3 class="modal-title">Apply as Merchant</h3>
            <p style="color: #6C7378; margin-bottom: 20px;">
                Expand your reach! Apply to become a merchant and start accepting payments via Kweza Pay.
                <br><br>
                <span style="font-weight: 700; color: #11295E;">Fee: MWK 500</span>
            </p>
            
            <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: left; border: 1px solid #e2e8f0;">
                <p style="font-size: 12px; font-weight: 800; color: #64748b; margin-bottom: 5px;">PAYMENT DETAILS</p>
                <p style="font-size: 14px; color: #1e293b;">Please pay MWK 500 to Kweza Pay account.</p>
                <p style="font-size: 11px; color: #94a3b8; margin-top: 5px; font-style: italic;">* Account details will be finalized by admin.</p>
            </div>

            <button class="btn btn-primary" onclick="confirmMerchantApply()">I Have Paid & Want to Apply</button>
            <button class="btn" style="margin-top: 10px; color: #6C7378;" onclick="closeModal('merchantApplyModal')">Cancel</button>
        </div>
    </div>

    <div id="receiptModal" class="modal-overlay">
        <div class="modal-content" style="padding: 0; max-width: 500px; display: flex; flex-direction: column;">
            <div class="receipt-container" id="receiptContent" style="flex: 1;">
                <!-- Populated by JS -->
            </div>
            <div
                style="padding: 20px; background: white; text-align: center; border-top: 1px solid var(--pp-border); display: flex; gap: 10px;">
                <button class="btn btn-outline" onclick="downloadReceipt()">
                    <i class="fas fa-download"></i> Download Receipt
                </button>
                <button class="btn btn-primary" onclick="location.reload()">Finish</button>
            </div>
        </div>
    </div>

    <div id="helpDeskModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 520px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                <h3 class="pp-card-title" style="margin: 0;">Help Desk Report</h3>
                <button class="btn btn-outline" type="button" style="width: auto; padding: 6px 12px;" onclick="closeHelpDesk()">Close</button>
            </div>
            <form id="helpDeskForm" onsubmit="submitHelpReport(event)">
                <input type="text" id="reportSubject" class="input-field" placeholder="Subject" required>
                <textarea id="reportMessage" class="input-field" rows="5" style="min-height: 120px; resize: vertical;" placeholder="Write your report..." required></textarea>
                <button class="btn btn-primary" id="helpDeskSubmitBtn" type="submit">Send Report</button>
            </form>
        </div>
    </div>

    <!-- Event Tickets Modal -->
    <div id="eventTicketsModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">
                <i class="fas fa-ticket-alt"></i> Event Tickets
            </h3>
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary" onclick="viewMyTickets()" style="width: auto; margin-right: 10px;">
                    <i class="fas fa-list"></i> My Tickets
                </button>
            </div>
            <div id="eventsListContainer" style="max-height: 500px; overflow-y: auto;">
                <!-- Events will be loaded here -->
            </div>
            <button class="btn btn-outline" style="margin-top: 20px;" onclick="closeModal('eventTicketsModal')">Close</button>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div id="eventDetailsModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px;">Purchase Ticket</h3>
            <div id="eventDetailsContent" style="margin-bottom: 20px;">
                <!-- Event details will be loaded here -->
            </div>
            <input type="hidden" id="selectedEventId">
            <button class="btn btn-primary" onclick="purchaseTicket()">Confirm Purchase</button>
            <button class="btn" style="margin-top: 10px; color: #6C7378;" onclick="closeModal('eventDetailsModal'); document.getElementById('eventTicketsModal').style.display='flex';">Back</button>
        </div>
    </div>

    <!-- My Tickets Modal -->
    <div id="myTicketsModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">
                <i class="fas fa-ticket-alt"></i> My Tickets
            </h3>
            <div id="myTicketsContainer" style="max-height: 500px; overflow-y: auto;">
                <!-- Tickets will be loaded here -->
            </div>
            <button class="btn btn-outline" style="margin-top: 20px;" onclick="closeModal('myTicketsModal'); document.getElementById('eventTicketsModal').style.display='flex';">Back</button>
        </div>
    </div>

    <!-- Campus Cafe Modal -->
    <div id="campusCafeModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px;">
                <i class="fas fa-utensils"></i> Campus Cafe
            </h3>
            <div id="cafesListContainer" style="max-height: 500px; overflow-y: auto;">
                <!-- Cafes will be loaded here -->
            </div>
            <button class="btn btn-outline" style="margin-top: 20px;" onclick="closeModal('campusCafeModal')">Close</button>
        </div>
    </div>

    <!-- Cafe Payment Modal -->
    <div id="cafePaymentModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px;">Pay at Campus Cafe</h3>
            <div id="cafeDetailsContent" style="margin-bottom: 20px;">
                <!-- Cafe details will be loaded here -->
            </div>
            <div id="selectedMealNote" style="margin-bottom: 10px; font-size: 13px; color: var(--pp-text-secondary);"></div>
            <input type="hidden" id="selectedCafeId">
            <input type="number" id="cafePaymentAmount" class="input-field" placeholder="Amount (MWK)" min="1" step="0.01">
            <input type="text" id="cafePaymentDescription" class="input-field" placeholder="Description (optional)">
            <button class="btn btn-primary" onclick="processCafePayment()">Confirm Payment</button>
            <button class="btn" style="margin-top: 10px; color: #6C7378;" onclick="closeModal('cafePaymentModal'); document.getElementById('campusCafeModal').style.display='flex';">Back</button>
        </div>
    </div>

    <!-- Send Money Modal -->
    <div id="sendMoneyModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px;">Send Money</h3>
            <input type="tel" id="recipientPhone" class="input-field" placeholder="Recipient Phone Number">
            <input type="number" id="sendAmount" class="input-field" placeholder="Amount (MWK)">
            <button class="btn btn-primary" onclick="processSendMoney()">Send Now</button>
            <button class="btn" style="margin-top: 10px; color: #6C7378;"
                onclick="closeModal('sendMoneyModal')">Cancel</button>
        </div>
    </div>

    <!-- Notification Panel -->
    <div id="notificationPanel" class="modal-overlay">
        <div class="modal-content">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px;">Notifications</h3>
            <div style="padding: 20px; text-align: center; color: #6C7378;">
                <i class="far fa-bell" style="font-size: 40px; margin-bottom: 15px; display: block;"></i>
                <p>You're all caught up! No new notifications.</p>
            </div>
            <button class="btn btn-primary" onclick="closeModal('notificationPanel')">Great</button>
        </div>
    </div>

    <style>
        .event-card, .ticket-card, .cafe-card {
            background: white;
            border: 1px solid var(--pp-border);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            gap: 15px;
        }
        
        .event-card:hover, .cafe-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .ticket-card {
            cursor: default;
        }
        
        .event-image, .cafe-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }
        
        .event-info, .cafe-info {
            flex: 1;
        }
        
        .event-title, .cafe-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--pp-dark-blue);
        }
        
        .event-description, .cafe-description {
            font-size: 13px;
            color: var(--pp-text-secondary);
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-clamp: 2;
        }
        
        .event-meta, .cafe-meta {
            font-size: 12px;
            color: var(--pp-text-secondary);
            margin-bottom: 10px;
        }
        
        .event-meta div, .cafe-meta div {
            margin-bottom: 5px;
        }
        
        .event-meta i, .cafe-meta i {
            margin-right: 5px;
            color: var(--pp-blue);
        }
        
        .event-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--pp-blue);
        }
        
        .event-availability {
            font-size: 12px;
            color: var(--pp-text-secondary);
            margin-top: 5px;
        }

        .airtel-block {
            border: 1px dashed var(--pp-border);
            border-radius: 10px;
            padding: 12px;
            display: flex;
            gap: 12px;
            align-items: center;
            background: #f8fafc;
            margin-top: 10px;
        }

        .airtel-block img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            background: white;
            border-radius: 8px;
            border: 1px solid var(--pp-border);
            padding: 6px;
        }

        .airtel-block .airtel-code {
            font-family: monospace;
            font-weight: 700;
            color: var(--pp-blue);
            margin-top: 4px;
        }
        
        .cafe-code {
            font-family: monospace;
            font-weight: 700;
            color: var(--pp-blue);
            background: #f0f7ff;
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 10px;
            display: inline-block;
        }
        
        .cafe-qr {
            max-width: 150px;
            margin-top: 10px;
            border: 2px solid var(--pp-border);
            border-radius: 8px;
        }
    </style>

    <!-- dom-to-image library for receipt download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>

    <script>
        function switchScreen(screen) {
            ['homeScreen', 'transactionsScreen', 'meScreen'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            });
            const main = document.querySelector('.pp-main');
            const target = document.getElementById(screen + 'Screen');

            if (target) {
                target.classList.remove('hidden');
                if (screen === 'home') {
                    main.classList.add('is-home');
                } else {
                    main.classList.remove('is-home');
                }
            }
            closeMobileMenu();
        }

        function closeMobileMenu() {
            const actions = document.getElementById('headerActions');
            const icon = document.getElementById('menuIcon');
            if (actions && actions.classList.contains('active')) {
                actions.classList.remove('active');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        }

        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        function openHelpDesk() {
            const modal = document.getElementById('helpDeskModal');
            if (modal) modal.style.display = 'flex';
        }

        function closeHelpDesk() {
            const modal = document.getElementById('helpDeskModal');
            if (modal) modal.style.display = 'none';
        }

        async function submitHelpReport(e) {
            e.preventDefault();
            const subject = document.getElementById('reportSubject').value.trim();
            const message = document.getElementById('reportMessage').value.trim();
            if (!subject || !message) {
                alert('Please enter a subject and your report.');
                return;
            }

            const btn = document.getElementById('helpDeskSubmitBtn');
            btn.disabled = true;
            const originalText = btn.innerText;
            btn.innerText = 'Sending...';

            try {
                const res = await fetch('../backend/api/submit_report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ subject, message })
                });
                const data = await res.json();
                if (data.success) {
                    alert('Report sent to admin.');
                    document.getElementById('helpDeskForm').reset();
                    closeHelpDesk();
                } else {
                    alert(data.error || 'Unable to send report.');
                }
            } catch (e) {
                alert('Connection error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerText = originalText;
            }
        }

        function paySUFee() {
            document.getElementById('paySUModal').style.display = 'flex';
        }

        function openSendMoneyModal() {
            document.getElementById('sendMoneyModal').style.display = 'flex';
        }

        async function processSendMoney() {
            const phone = document.getElementById('recipientPhone').value;
            const amount = document.getElementById('sendAmount').value;

            if (!phone || !amount) return alert('Please fill all fields');

            try {
                const res = await fetch('../backend/api/send_money.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ recipient_phone: phone, amount })
                });
                const data = await res.json();
                if (data.success) {
                    closeModal('sendMoneyModal');
                    showReceipt({
                        type: 'P2P Transfer',
                        merchantName: data.recipient_name || phone,
                        amount: amount,
                        reference: data.reference_code,
                        date: new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }),
                        description: 'Money transfer to ' + (data.recipient_name || phone)
                    });
                } else {
                    alert('❌ Transfer failed: ' + data.error);
                }
            } catch (e) { alert('Network error'); }
        }

        let html5QrcodeScanner = null;

        function showPayModal() {
            document.getElementById('payModal').style.display = 'flex';
        }

        function showScanner() {
            document.getElementById('scannerModal').style.display = 'flex';
            if (!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: 250 });
            }
            html5QrcodeScanner.render((decodedText) => {
                html5QrcodeScanner.clear();
                document.getElementById('payMerchantId').value = decodedText;
                closeModal('scannerModal');
                showPayModal();
            });
        }

        function stopScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(e => console.error(e));
            }
            closeModal('scannerModal');
        }

        function manualMerchantPay() {
            const mid = document.getElementById('manualMerchantId').value;
            if (!mid) return alert('Please enter Merchant ID');
            stopScanner();
            document.getElementById('payMerchantId').value = mid;
            showPayModal();
        }

        async function processPayment() {
            const mid = document.getElementById('payMerchantId').value;
            const amount = document.getElementById('payAmount').value;
            const pin = document.getElementById('payPin').value;

            if (!mid || !amount || !pin) return alert('Please fill all fields and enter PIN');

            try {
                const res = await fetch('../backend/api/pay_merchant.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ merchant_token: mid, amount, pin })
                });

                if (!res.ok) {
                     const errorText = await res.text();
                     throw new Error(`Server error ${res.status}: ${errorText}`);
                }

                const data = await res.json();
                if (data.success) {
                    closeModal('payModal');
                    showReceipt({
                        type: 'Merchant Payment',
                        merchantName: data.merchant_name || mid,
                        amount: amount,
                        reference: data.reference_code,
                        date: new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }),
                        description: 'Payment for Product/Service'
                    });
                } else {
                    alert('❌ Payment failed: ' + data.error);
                }
            } catch (e) { 
                console.error('Payment Error:', e);
                alert('Connection error: ' + e.message); 
            }
        }

        document.getElementById('suForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = {
                receiptType: document.getElementById('receiptType').value,
                studentName: document.getElementById('studentName').value,
                studentId: document.getElementById('studentId').value,
                program: document.getElementById('program').value,
                year: parseInt(document.getElementById('year').value),
                university: document.getElementById('university').value,
                amountPaid: parseFloat(document.getElementById('amountPaid').value)
            };

            try {
                const res = await fetch('../backend/api/pay_su_fee.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const data = await res.json();
                if (data.success) {
                    closeModal('paySUModal');
                    showReceipt({
                        type: 'Student Union Fee',
                        studentDetails: formData,
                        reference: data.reference_code,
                        date: new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }),
                        merchantName: 'Student Union ' + formData.university
                    });
                } else {
                    alert('❌ Payment failed: ' + data.error);
                }
            } catch (e) { alert('Connection error'); }
        });

        function showReceipt(data) {
            const content = document.getElementById('receiptContent');
            const isSU = data.type === 'Student Union Fee';

            let html = `
                <img src="assets/img/logo.png" class="receipt-watermark" onerror="this.style.display='none'">
                <div class="receipt-header">
                    <img src="assets/img/logo.png" class="receipt-logo" onerror="this.src='https://ui-avatars.com/api/?name=KP&background=0D8ABC&color=fff'">
                    <div class="receipt-title-box">
                        <h1 class="receipt-main-title">KWEZA PAY</h1>
                        <span class="receipt-tagline">Payment Facilitation Platform</span>
                    </div>
                </div>
                
                <div class="receipt-info-line"><span class="receipt-info-label">RECEIPT TYPE:</span> ${data.type}</div>
                <div class="receipt-info-line"><span class="receipt-info-label">DATE:</span> ${data.date}</div>
                <div class="receipt-info-line"><span class="receipt-info-label">REFERENCE NO:</span> ${data.reference}</div>
            `;

            if (isSU) {
                html += `
                    <div class="receipt-section-title">STUDENT DETAILS:</div>
                    <div class="receipt-details-grid">
                        <div class="receipt-detail-item">Name: ${data.studentDetails.studentName}</div>
                        <div class="receipt-detail-item">Student ID: ${data.studentDetails.studentId}</div>
                        <div class="receipt-detail-item">Program: ${data.studentDetails.program}</div>
                        <div class="receipt-detail-item">Year: ${data.studentDetails.year}</div>
                        <div class="receipt-detail-item">University: ${data.studentDetails.university}</div>
                    </div>
                    <div class="receipt-section-title">PAYMENT DETAILS:</div>
                    <div class="receipt-details-grid">
                        <div class="receipt-detail-item">Description: Student Union Fee</div>
                        <div class="receipt-detail-item">Amount Paid: MK ${parseFloat(data.studentDetails.amountPaid).toLocaleString()}</div>
                        <div class="receipt-detail-item">Service Fee (Provider): (Handled by Airtel/TNM)</div>
                        <div class="receipt-detail-item">Total Amount Debited: MK ${parseFloat(data.studentDetails.amountPaid).toLocaleString()}</div>
                        <div class="receipt-detail-item">Payment Method: Airtel Money</div>
                        <div class="receipt-detail-item">Merchant/Recipient: ${data.merchantName}</div>
                    </div>
                `;
            } else {
                html += `
                    <div class="receipt-section-title">PAYMENT DETAILS:</div>
                    <div class="receipt-details-grid">
                        <div class="receipt-detail-item">Description: ${data.description || 'Printing Services'}</div>
                        <div class="receipt-detail-item">Amount Paid: MK ${parseFloat(data.amount).toLocaleString()}</div>
                        <div class="receipt-detail-item">Service Fee (Provider): (Handled by Airtel/TNM)</div>
                        <div class="receipt-detail-item">Total Amount Debited: MK ${parseFloat(data.amount).toLocaleString()}</div>
                        <div class="receipt-detail-item">Payment Method: Airtel Money</div>
                        <div class="receipt-detail-item">Merchant/Recipient: ${data.merchantName}</div>
                    </div>
                `;
            }

            html += `
                <div class="receipt-status success">STATUS: SUCCESSFUL <i class="fas fa-check-circle"></i></div>
                <div class="receipt-disclaimer">
                     This is a verification receipt for the transaction facilitated by Kweza Pay.
                     <br><br>
                     All funds were transferred directly via the service provider (Airtel/TNM).
                     <br><br>
                     Kweza Pay does not hold or manage funds.
                </div>
                <div class="receipt-footer">Thank you for using Kweza Pay</div>
            `;

            content.innerHTML = html;
            document.getElementById('receiptModal').style.display = 'flex';
        }

        async function downloadReceipt() {
            const node = document.getElementById('receiptContent');
            const ref = node.querySelector('.receipt-info-line:last-of-type').innerText.split(': ')[1] || 'receipt';

            // Adjust styles temporarily for optimal capture if needed
            try {
                const dataUrl = await domtoimage.toPng(node, {
                    bgcolor: '#ffffff',
                    quality: 0.95
                });
                const link = document.createElement('a');
                link.download = `Kweza-Receipt-${ref}.png`;
                link.href = dataUrl;
                link.click();
            } catch (error) {
                console.error('oops, something went wrong!', error);
                alert('Could not generate image. Try taking a screenshot instead.');
            }
        }

        function logout() { if (confirm('Logout from Kweza Pay?')) window.location.href = 'logout.php'; }
        
        async function uploadProfilePic(input) {
            if (!input.files || !input.files[0]) return;
            
            const formData = new FormData();
            formData.append('profile_pic', input.files[0]);
            
            try {
                const res = await fetch('../backend/api/upload_profile_pic.php', {
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
                alert('Upload failed. Connection error.');
            }
        }

        function viewTransactionReceipt(txn) {
            // Adapt txn object for showReceipt
            const receiptData = {
                merchantName: txn.description || (txn.sender_id == <?php echo $user['user_id']; ?> ? 'Transfer Out' : 'Transfer In'),
                amount: txn.amount,
                reference: txn.reference_code,
                date: txn.created_at,
                description: txn.description
            };
            showReceipt(receiptData);
        }

        function clearCache() { if (confirm('Clear all settings?')) { localStorage.clear(); location.reload(); } }
        function changePin() { alert('PIN can be reset via Admin or Email Security Settings'); }
        function showNotifications() { 
            closeMobileMenu();
            document.getElementById('notificationPanel').style.display = 'flex'; 
        }
        function showMoreActions() { document.getElementById('moreActionsMenu').style.display = 'flex'; }

        function toggleMobileMenu() {
            const actions = document.getElementById('headerActions');
            const icon = document.getElementById('menuIcon');
            actions.classList.toggle('active');
            
            if (actions.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }

        // Merchant Application Functions
        function openMerchantApplication() {
            document.getElementById('merchantApplyModal').style.display = 'flex';
        }

        async function confirmMerchantApply() {
            try {
                const res = await fetch('../backend/api/apply_merchant.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ business_name: '<?php echo addslashes($user['full_name']); ?> Shop' })
                });
                const data = await res.json();
                if (data.success) {
                    alert('✅ ' + data.message);
                    closeModal('merchantApplyModal');
                    location.reload();
                } else {
                    alert('❌ ' + data.error);
                }
            } catch (e) {
                alert('Connection error');
            }
        }
        
        // Event Tickets Functions
        function openEventTickets() {
            document.getElementById('eventTicketsModal').style.display = 'flex';
            loadAvailableEvents();
        }

        async function loadAvailableEvents() {
            try {
                const res = await fetch('../backend/api/get_events.php');
                const data = await res.json();
                
                if (data.success) {
                    const container = document.getElementById('eventsListContainer');
                    if (data.events.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: var(--pp-text-secondary);">No events available at the moment.</p>';
                        return;
                    }
                    
                    container.innerHTML = data.events.map(event => `
                        <div class="event-card" onclick="viewEventDetails(${event.event_id})">
                            ${event.event_picture ? `<img src="${event.event_picture}" class="event-image" alt="${event.event_name}">` : ''}
                            <div class="event-info">
                                <h4 class="event-title">${event.event_name}</h4>
                                <p class="event-description">${event.event_description || ''}</p>
                                <div class="event-meta">
                                    ${event.event_date ? `<div><i class="fas fa-calendar"></i> ${new Date(event.event_date).toLocaleDateString()}</div>` : ''}
                                    ${event.event_location ? `<div><i class="fas fa-map-marker-alt"></i> ${event.event_location}</div>` : ''}
                                </div>
                                <div class="event-price">MWK ${parseFloat(event.ticket_price).toLocaleString()}</div>
                                ${event.max_tickets ? `<div class="event-availability">${event.tickets_sold || 0} / ${event.max_tickets} sold</div>` : ''}
                                ${event.airtel_money_id ? `<div class="event-availability"><i class="fas fa-id-badge"></i> Airtel ID: ${event.airtel_money_id}</div>` : ''}
                                ${event.airtel_money_code ? `<div class="event-availability"><i class="fas fa-mobile-alt"></i> ${event.airtel_money_code}</div>` : ''}
                            </div>
                        </div>
                    `).join('');
                }
            } catch (e) {
                console.error('Error loading events:', e);
            }
        }

        async function viewEventDetails(eventId) {
            try {
                const res = await fetch('../backend/api/get_events.php');
                const data = await res.json();
                
                if (data.success) {
                    const event = data.events.find(e => e.event_id == eventId);
                    if (event) {
                        const qrUrl = event.airtel_qr_image || (event.airtel_money_code ? `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(event.airtel_money_code)}` : '');
                        document.getElementById('selectedEventId').value = eventId;
                        document.getElementById('eventDetailsContent').innerHTML = `
                            ${event.event_picture ? `<img src="${event.event_picture}" style="width: 100%; border-radius: 12px; margin-bottom: 15px;">` : ''}
                            <h3 style="margin-bottom: 10px;">${event.event_name}</h3>
                            <p style="color: var(--pp-text-secondary); margin-bottom: 15px;">${event.event_description || ''}</p>
                            <div style="margin-bottom: 10px;"><strong>Price:</strong> MWK ${parseFloat(event.ticket_price).toLocaleString()}</div>
                            ${event.event_date ? `<div style="margin-bottom: 10px;"><strong>Date:</strong> ${new Date(event.event_date).toLocaleString()}</div>` : ''}
                            ${event.event_location ? `<div style="margin-bottom: 10px;"><strong>Location:</strong> ${event.event_location}</div>` : ''}
                            ${event.max_tickets ? `<div style="margin-bottom: 10px;"><strong>Availability:</strong> ${event.max_tickets - (event.tickets_sold || 0)} tickets remaining</div>` : ''}
                            ${(event.airtel_money_code || event.airtel_money_id) ? `
                                <div class="airtel-block">
                                    ${qrUrl ? `<img src="${qrUrl}" alt="Airtel QR">` : ''}
                                    <div>
                                        <div style="font-weight: 700; color: var(--pp-dark-blue);">Airtel Money</div>
                                        ${event.airtel_money_id ? `<div><strong>ID:</strong> ${event.airtel_money_id}</div>` : ''}
                                        ${event.airtel_money_code ? `<div class="airtel-code">${event.airtel_money_code}</div>` : '<div style="color: var(--pp-text-secondary);">No code provided.</div>'}
                                        <div style="font-size: 12px; color: var(--pp-text-secondary); margin-top: 6px;">Scan or use the code to pay for this event.</div>
                                    </div>
                                </div>
                            ` : ''}
                        `;
                        closeModal('eventTicketsModal');
                        document.getElementById('eventDetailsModal').style.display = 'flex';
                    }
                }
            } catch (e) {
                alert('Error loading event details');
            }
        }

        async function purchaseTicket() {
            const eventId = document.getElementById('selectedEventId').value;
            
            try {
                const res = await fetch('../backend/api/purchase_ticket.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ event_id: eventId })
                });
                const data = await res.json();
                
                if (data.success) {
                    closeModal('eventDetailsModal');
                    showTicketReceipt(data);
                } else {
                    alert('❌ ' + data.error);
                }
            } catch (e) {
                alert('Network error');
            }
        }

        function showTicketReceipt(data) {
            const content = document.getElementById('receiptContent');
            content.innerHTML = `
                <img src="assets/img/logo.png" class="receipt-watermark" onerror="this.style.display='none'">
                <div class="receipt-header">
                    <img src="assets/img/logo.png" class="receipt-logo" onerror="this.src='https://ui-avatars.com/api/?name=KP&background=0D8ABC&color=fff'">
                    <div class="receipt-title-box">
                        <h1 class="receipt-main-title">KWEZA PAY</h1>
                        <span class="receipt-tagline">Event Ticket</span>
                    </div>
                </div>
                
                <div class="receipt-info-line"><span class="receipt-info-label">EVENT:</span> ${data.event_name}</div>
                <div class="receipt-info-line"><span class="receipt-info-label">TICKET CODE:</span> ${data.ticket_code}</div>
                ${data.serial_number ? `<div class="receipt-info-line" style="color: var(--pp-blue); font-weight: 800;"><span class="receipt-info-label">SERIAL ID:</span> ${data.serial_number}</div>` : ''}
                <div class="receipt-info-line"><span class="receipt-info-label">REFERENCE:</span> ${data.reference_code}</div>
                
                <div class="receipt-section-title">TICKET DETAILS:</div>
                <div class="receipt-details-grid">
                    <div class="receipt-detail-item">Amount Paid: MK ${parseFloat(data.amount).toLocaleString()}</div>
                    ${data.event_date ? `<div class="receipt-detail-item">Event Date: ${new Date(data.event_date).toLocaleString()}</div>` : ''}
                    ${data.event_location ? `<div class="receipt-detail-item">Location: ${data.event_location}</div>` : ''}
                </div>

                <div class="receipt-status success">STATUS: CONFIRMED <i class="fas fa-check-circle"></i></div>
                <div class="receipt-disclaimer">
                     This is your official event ticket. Please present this ticket at the event entrance.
                     <br><br>
                     Keep this ticket safe and do not share your ticket code.
                </div>
                <div class="receipt-footer">Thank you for using Kweza Pay</div>
            `;
            document.getElementById('receiptModal').style.display = 'flex';
        }

        function viewMyTickets() {
            closeModal('eventTicketsModal');
            loadMyTickets();
            document.getElementById('myTicketsModal').style.display = 'flex';
        }

        async function loadMyTickets() {
            try {
                const res = await fetch('../backend/api/get_my_tickets.php');
                const data = await res.json();
                
                if (data.success) {
                    const container = document.getElementById('myTicketsContainer');
                    if (data.tickets.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: var(--pp-text-secondary);">You haven\'t purchased any tickets yet.</p>';
                        return;
                    }
                    
                    container.innerHTML = data.tickets.map(ticket => `
                        <div class="ticket-card">
                            ${ticket.event_picture ? `<img src="${ticket.event_picture}" class="event-image" alt="${ticket.event_name}">` : ''}
                            <div class="event-info">
                                <h4 class="event-title">${ticket.event_name}</h4>
                                <p style="font-family: monospace; font-weight: 700; color: var(--pp-blue);">${ticket.ticket_code}</p>
                                ${ticket.serial_number ? `<p style="font-size: 14px; font-weight: 800; color: #11295E; margin: 4px 0;">Serial: ${ticket.serial_number}</p>` : ''}
                                <div class="event-meta">
                                    ${ticket.event_date ? `<div><i class="fas fa-calendar"></i> ${new Date(ticket.event_date).toLocaleDateString()}</div>` : ''}
                                    ${ticket.event_location ? `<div><i class="fas fa-map-marker-alt"></i> ${ticket.event_location}</div>` : ''}
                                </div>
                                <div class="event-price">MWK ${parseFloat(ticket.purchase_amount).toLocaleString()}</div>
                                <div style="margin-top: 10px;">
                                    <span style="padding: 5px 10px; background: ${ticket.ticket_status === 'valid' ? '#10b981' : '#6b7280'}; color: white; border-radius: 5px; font-size: 12px; font-weight: 700;">
                                        ${ticket.ticket_status.toUpperCase()}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (e) {
                console.error('Error loading tickets:', e);
            }
        }

        // Campus Cafe Functions
        function openCampusCafe() {
            document.getElementById('campusCafeModal').style.display = 'flex';
            loadAvailableCafes();
        }

        async function loadAvailableCafes() {
            try {
                const res = await fetch('../backend/api/get_cafes.php');
                const data = await res.json();
                
                if (data.success) {
                    const container = document.getElementById('cafesListContainer');
                    if (data.cafes.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: var(--pp-text-secondary);">No campus cafes available at the moment.</p>';
                        return;
                    }
                    
                    container.innerHTML = data.cafes.map(cafe => `
                        <div class="cafe-card" onclick="viewCafeDetails(${cafe.cafe_id})">
                            ${cafe.cafe_logo ? `<img src="${cafe.cafe_logo}" class="cafe-image" alt="${cafe.cafe_name}">` : ''}
                            <div class="cafe-info">
                                <h4 class="cafe-title">${cafe.cafe_name}</h4>
                                <p class="cafe-description">${cafe.cafe_description || ''}</p>
                                <div class="cafe-code">
                                    <i class="fas fa-mobile-alt"></i> ${cafe.airtel_money_code}
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (e) {
                console.error('Error loading cafes:', e);
            }
        }

        async function viewCafeDetails(cafeId) {
            try {
                const res = await fetch('../backend/api/get_cafes.php');
                const data = await res.json();
                
                if (data.success) {
                    const cafe = data.cafes.find(c => c.cafe_id == cafeId);
                    if (cafe) {
                        document.getElementById('selectedCafeId').value = cafeId;
                        document.getElementById('cafePaymentAmount').value = '';
                        document.getElementById('cafePaymentDescription').value = '';
                        const selectedNote = document.getElementById('selectedMealNote');
                        if (selectedNote) selectedNote.textContent = '';
                        document.getElementById('cafeDetailsContent').innerHTML = `
                            ${cafe.cafe_logo ? `<img src="${cafe.cafe_logo}" style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 15px;">` : ''}
                            <h3 style="margin-bottom: 10px;">${cafe.cafe_name}</h3>
                            <p style="color: var(--pp-text-secondary); margin-bottom: 15px;">${cafe.cafe_description || ''}</p>
                            <div id="cafeMealsSection" style="margin-bottom: 15px;"></div>
                            <div style="margin-bottom: 15px;">
                                <strong>Airtel Money Code:</strong>
                                <div style="font-family: monospace; font-size: 18px; font-weight: 700; color: var(--pp-blue); background: #f0f7ff; padding: 12px; border-radius: 8px; margin-top: 5px;">
                                    ${cafe.airtel_money_code}
                                </div>
                            </div>
                            ${cafe.qr_code_image ? `<div style="margin-bottom: 15px;"><strong>QR Code:</strong><br><img src="${cafe.qr_code_image}" class="cafe-qr"></div>` : ''}
                        `;
                        loadCafeMeals(cafeId);
                        closeModal('campusCafeModal');
                        document.getElementById('cafePaymentModal').style.display = 'flex';
                    }
                }
            } catch (e) {
                alert('Error loading cafe details');
            }
        }

        async function loadCafeMeals(cafeId) {
            const container = document.getElementById('cafeMealsSection');
            if (!container) return;
            container.innerHTML = '<p style="color: var(--pp-text-secondary);">Loading meals...</p>';
            
            try {
                const res = await fetch(`../backend/api/get_cafe_meals.php?cafe_id=${cafeId}`);
                const data = await res.json();
                
                if (!data.success || data.meals.length === 0) {
                    container.innerHTML = '<p style="color: var(--pp-text-secondary);">No meals listed yet.</p>';
                    return;
                }

                container.innerHTML = `
                    <h4 style="margin: 0 0 10px;">Menu</h4>
                    <div style="display: grid; gap: 10px;">
                        ${data.meals.map(meal => `
                            <div style="border: 1px solid var(--pp-border); border-radius: 10px; padding: 12px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                <div>
                                    <div style="font-weight: 700;">${meal.meal_name}</div>
                                    ${meal.description ? `<div style="font-size: 12px; color: var(--pp-text-secondary);">${meal.description}</div>` : ''}
                                </div>
                                <button class="btn btn-primary" type="button" style="width: auto; padding: 8px 12px; font-size: 12px;" onclick="selectCafeMeal('${encodeURIComponent(meal.meal_name)}', ${parseFloat(meal.price)})">
                                    MWK ${parseFloat(meal.price).toLocaleString()}
                                </button>
                            </div>
                        `).join('')}
                    </div>
                `;
            } catch (e) {
                container.innerHTML = '<p style="color: var(--pp-text-secondary);">Failed to load meals.</p>';
            }
        }

        function selectCafeMeal(encodedMealName, price) {
            const mealName = decodeURIComponent(encodedMealName);
            const amountInput = document.getElementById('cafePaymentAmount');
            const descInput = document.getElementById('cafePaymentDescription');
            if (amountInput) amountInput.value = price;
            if (descInput) descInput.value = `Meal: ${mealName}`;

            const selectedNote = document.getElementById('selectedMealNote');
            if (selectedNote) {
                selectedNote.textContent = `Selected: ${mealName} (MWK ${Number(price).toLocaleString()})`;
            }
        }

        async function processCafePayment() {
            const cafeId = document.getElementById('selectedCafeId').value;
            const amount = document.getElementById('cafePaymentAmount').value;
            const description = document.getElementById('cafePaymentDescription').value;
            
            if (!amount || amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }
            
            try {
                const res = await fetch('../backend/api/pay_cafe.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        cafe_id: cafeId, 
                        amount: amount,
                        description: description
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    closeModal('cafePaymentModal');
                    showCafeReceipt(data);
                } else {
                    alert('❌ ' + data.error);
                }
            } catch (e) {
                alert('Network error');
            }
        }

        function showCafeReceipt(data) {
            const content = document.getElementById('receiptContent');
            content.innerHTML = `
                <img src="assets/img/logo.png" class="receipt-watermark" onerror="this.style.display='none'">
                <div class="receipt-header">
                    <img src="assets/img/logo.png" class="receipt-logo" onerror="this.src='https://ui-avatars.com/api/?name=KP&background=0D8ABC&color=fff'">
                    <div class="receipt-title-box">
                        <h1 class="receipt-main-title">KWEZA PAY</h1>
                        <span class="receipt-tagline">Campus Cafe Payment</span>
                    </div>
                </div>
                
                <div class="receipt-info-line"><span class="receipt-info-label">CAFE:</span> ${data.cafe_name}</div>
                <div class="receipt-info-line"><span class="receipt-info-label">REFERENCE:</span> ${data.reference_code}</div>
                <div class="receipt-info-line"><span class="receipt-info-label">DATE:</span> ${new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</div>
                
                <div class="receipt-section-title">PAYMENT DETAILS:</div>
                <div class="receipt-details-grid">
                    <div class="receipt-detail-item">Amount Paid: MK ${parseFloat(data.amount).toLocaleString()}</div>
                    <div class="receipt-detail-item">Payment Method: ${data.airtel_code}</div>
                </div>

                <div class="receipt-status success">STATUS: SUCCESSFUL <i class="fas fa-check-circle"></i></div>
                <div class="receipt-disclaimer">
                     This is your payment receipt for campus cafe transaction.
                     <br><br>
                     Payment was processed via Airtel Money.
                </div>
                <div class="receipt-footer">Thank you for using Kweza Pay</div>
            `;
            document.getElementById('receiptModal').style.display = 'flex';
        }
        
        // Dynamic Greeting Transition
        window.addEventListener('load', function() {
            const greeting = document.getElementById('nav-greeting');
            setTimeout(() => {
                greeting.style.opacity = '0';
                setTimeout(() => {
                    greeting.textContent = 'Student Account';
                    greeting.style.opacity = '1';
                }, 300);
            }, 1500);
        });
    </script>
</body>

</html>
