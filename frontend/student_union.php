<?php
require_once '../backend/api/session.php';
require_once '../backend/api/db.php';
requireLogin();

// Ensure only SU or Admin can access
if ($_SESSION['user_type'] !== 'StudentUnion' && $_SESSION['user_type'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

$user = getCurrentUser($pdo);

// Determine last reset timestamp for this student union (if any)
// Ensure the `collection_resets` table exists (create if missing), then fetch latest reset
$lastReset = null;
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS collection_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reset_at DATETIME NOT NULL,
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $pdo->prepare("SELECT reset_at FROM collection_resets WHERE user_id = ? ORDER BY reset_at DESC LIMIT 1");
    $stmt->execute([$user['user_id']]);
    $lastResetRow = $stmt->fetch();
    $lastReset = $lastResetRow ? $lastResetRow['reset_at'] : null;
} catch (PDOException $e) {
    $lastReset = null;
}

// Fetch total collected funds since the last reset (or all time if never reset)
$sql = "SELECT SUM(amount) FROM transactions WHERE receiver_id IN (SELECT user_id FROM users WHERE user_type = 'StudentUnion') AND txn_type = 'SU_FEE'";
if ($lastReset) {
    $sql .= " AND created_at >= ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$lastReset]);
} else {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
$totalBalance = $stmt->fetchColumn() ?: 0.00;

// Fetch Merchant Details for SU
$stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = ?");
$stmt->execute([$user['user_id']]);
$merchant = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kweza Pay - Student Union Dashboard</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/paypal_ui.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

        .btn-outline {
            background: transparent;
            border: 1px solid var(--pp-dark-blue);
            color: var(--pp-dark-blue);
        }

        .hidden {
            display: none !important;
        }

        body.dashboard-union {
            background: #f4f7fb;
            position: relative;
            overflow-x: hidden;
        }

        body.dashboard-union::before,
        body.dashboard-union::after {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        body.dashboard-union::before {
            background:
                radial-gradient(circle at 15% 20%, rgba(17, 41, 94, 0.18), rgba(17, 41, 94, 0) 55%),
                radial-gradient(circle at 85% 80%, rgba(14, 116, 144, 0.16), rgba(14, 116, 144, 0) 60%),
                linear-gradient(135deg, rgba(17, 41, 94, 0.06), rgba(255, 255, 255, 0));
            opacity: 0.26;
        }

        body.dashboard-union::after {
            background-image: url("data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22360%22%20height%3D%22360%22%20viewBox%3D%220%200%20360%20360%22%3E%0A%20%20%3Crect%20width%3D%22360%22%20height%3D%22360%22%20fill%3D%22none%22%2F%3E%0A%0A%20%20%3Crect%20x%3D%220%22%20y%3D%220%22%20width%3D%2270%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%2270%22%20y%3D%220%22%20width%3D%2250%22%20height%3D%22360%22%20fill%3D%22%23ece7d8%22%2F%3E%0A%20%20%3Crect%20x%3D%22120%22%20y%3D%220%22%20width%3D%22120%22%20height%3D%22360%22%20fill%3D%22%23ece7d8%22%2F%3E%0A%20%20%3Crect%20x%3D%22240%22%20y%3D%220%22%20width%3D%2250%22%20height%3D%22360%22%20fill%3D%22%23ece7d8%22%2F%3E%0A%20%20%3Crect%20x%3D%22290%22%20y%3D%220%22%20width%3D%2270%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%0A%20%20%3Crect%20x%3D%2268%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%22118%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%22238%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%22288%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%0A%20%20%3Cg%20fill%3D%22%239aa200%22%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%200%20L70%2015%20L0%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%2030%20L70%2045%20L0%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%2060%20L70%2075%20L0%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%2090%20L70%20105%20L0%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20120%20L70%20135%20L0%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20150%20L70%20165%20L0%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20180%20L70%20195%20L0%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20210%20L70%20225%20L0%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20240%20L70%20255%20L0%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20270%20L70%20285%20L0%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20300%20L70%20315%20L0%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20330%20L70%20345%20L0%20360%20Z%22%2F%3E%0A%0A%20%20%20%20%3Cpath%20d%3D%22M360%200%20L290%2015%20L360%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%2030%20L290%2045%20L360%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%2060%20L290%2075%20L360%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%2090%20L290%20105%20L360%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20120%20L290%20135%20L360%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20150%20L290%20165%20L360%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20180%20L290%20195%20L360%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20210%20L290%20225%20L360%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20240%20L290%20255%20L360%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20270%20L290%20285%20L360%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20300%20L290%20315%20L360%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20330%20L290%20345%20L360%20360%20Z%22%2F%3E%0A%20%20%3C%2Fg%3E%0A%0A%20%20%3Cg%20fill%3D%22%23c86b1d%22%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%200%20L120%2015%20L70%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%2030%20L120%2045%20L70%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%2060%20L120%2075%20L70%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%2090%20L120%20105%20L70%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20120%20L120%20135%20L70%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20150%20L120%20165%20L70%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20180%20L120%20195%20L70%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20210%20L120%20225%20L70%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20240%20L120%20255%20L70%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20270%20L120%20285%20L70%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20300%20L120%20315%20L70%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20330%20L120%20345%20L70%20360%20Z%22%2F%3E%0A%0A%20%20%20%20%3Cpath%20d%3D%22M290%200%20L240%2015%20L290%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%2030%20L240%2045%20L290%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%2060%20L240%2075%20L290%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%2090%20L240%20105%20L290%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20120%20L240%20135%20L290%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20150%20L240%20165%20L290%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20180%20L240%20195%20L290%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20210%20L240%20225%20L290%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20240%20L240%20255%20L290%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20270%20L240%20285%20L290%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20300%20L240%20315%20L290%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20330%20L240%20345%20L290%20360%20Z%22%2F%3E%0A%20%20%3C%2Fg%3E%0A%0A%20%20%3Cg%20fill%3D%22none%22%20stroke%3D%22%230b0b0b%22%20stroke-linecap%3D%22square%22%20stroke-linejoin%3D%22round%22%3E%0A%20%20%20%20%3Cg%20transform%3D%22translate(0%2C0)%22%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%200%20L220%2060%20L180%20120%20L140%2060%20Z%22%20stroke-width%3D%226%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L206%2060%20L180%2096%20L154%2060%20Z%22%20stroke-width%3D%224%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L180%2096%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M154%2060%20L206%2060%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%3C%2Fg%3E%0A%20%20%20%20%3Cg%20transform%3D%22translate(0%2C120)%22%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%200%20L220%2060%20L180%20120%20L140%2060%20Z%22%20stroke-width%3D%226%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L206%2060%20L180%2096%20L154%2060%20Z%22%20stroke-width%3D%224%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L180%2096%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M154%2060%20L206%2060%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%3C%2Fg%3E%0A%20%20%20%20%3Cg%20transform%3D%22translate(0%2C240)%22%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%200%20L220%2060%20L180%20120%20L140%2060%20Z%22%20stroke-width%3D%226%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L206%2060%20L180%2096%20L154%2060%20Z%22%20stroke-width%3D%224%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L180%2096%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M154%2060%20L206%2060%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%3C%2Fg%3E%0A%20%20%3C%2Fg%3E%0A%0A%20%20%3Crect%20x%3D%22178%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%20opacity%3D%220.7%22%2F%3E%0A%3C%2Fsvg%3E");
            background-repeat: repeat;
            background-size: 360px 360px;
            opacity: 0.03;
            filter: grayscale(1);
        }

        body.dashboard-union .pp-main {
            position: relative;
            z-index: 1;
        }

        #collectionGrowthCard {
            background: #265D82;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        #collectionGrowthCard .pp-balance-title,
        #collectionGrowthCard .pp-balance-amount,
        #collectionGrowthCard .pp-balance-available {
            color: #ffffff;
        }

        #collectionGrowthCard .pp-btn-pill {
            background: #ffffff;
            color: #265D82;
        }

        #collectionGrowthCard .pp-btn-pill:hover {
            background: #e6f0ff;
        }

        #collectionGrowthCard::before,
        #collectionGrowthCard::after {
            content: "";
            position: absolute;
            pointer-events: none;
            z-index: 0;
            opacity: 0.35;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0) 60%),
                radial-gradient(circle at 70% 70%, rgba(17, 41, 94, 0.18), rgba(17, 41, 94, 0) 60%);
        }

        #collectionGrowthCard::before {
            width: 220px;
            height: 220px;
            top: -60px;
            right: -70px;
        }

        #collectionGrowthCard::after {
            width: 180px;
            height: 180px;
            bottom: -50px;
            left: -60px;
        }

        #collectionGrowthCard > * {
            position: relative;
            z-index: 1;
        }

        .portal-text {
            color: white;
            font-weight: 700;
            font-size: 18px;
            margin-left: 20px;
        }

        .search-hero {
            background: #f8fafc;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
        }

        /* Collections Table Styles */
        .collections-table-container {
            overflow-x: auto;
            background: white;
            border-radius: 12px;
            border: 1px solid var(--pp-border);
        }

        .collections-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .collections-table th {
            background: #f8fafc;
            padding: 15px;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 2px solid #f1f5f9;
            white-space: nowrap;
        }

        .collections-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            font-size: 14px;
        }

        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-input {
            flex: 1;
            min-width: 200px;
        }

        .report-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-mini-card {
            background: #f0f9ff;
            padding: 15px 20px;
            border-radius: 12px;
            border: 1px solid #bae6fd;
            flex: 1;
        }

        /* Student Union dashboard spacing tweaks */
        .dashboard-union #collectionGrowthCard {
            margin-bottom: 10px;
        }

        .dashboard-union #actionSidebar .pp-actions-grid {
            margin-bottom: 12px;
        }

        .dashboard-union #homeScreen > div > .pp-card {
            margin-bottom: 10px;
        }

        .dashboard-union #homeScreen > div > .pp-card:last-of-type {
            margin-bottom: 14px;
        }

        @media (min-width: 901px) {
            .dashboard-union #collectionGrowthCard .pp-balance-title {
                font-size: 16px;
            }

            .dashboard-union #collectionGrowthCard .pp-balance-amount {
                font-size: 24px !important;
                line-height: 1.15;
            }

            .dashboard-union #collectionGrowthCard .pp-balance-available {
                font-size: 14px;
                margin-bottom: 0;
            }
        }

        @media (max-width: 900px) {
            .dashboard-union #collectionGrowthCard {
                margin-bottom: 8px !important;
            }

            .dashboard-union #actionSidebar .pp-actions-grid {
                margin-bottom: 10px !important;
            }

            .dashboard-union #homeScreen > div > .pp-card {
                margin-bottom: 8px !important;
            }

            .dashboard-union #homeScreen > div > .pp-card:last-of-type {
                margin-bottom: 10px !important;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/css/student_union_mobile.css?v=<?php echo time(); ?>">
    <!-- PDF & QR Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
</head>

<body class="dashboard-union">
    <header class="pp-header">
        <div class="pp-nav">
            <a href="#" onclick="switchScreen('home')" id="homeLogo">
                <img src="assets/img/logo.png" alt="Kweza Pay" class="pp-logo"
                    onerror="this.src='https://ui-avatars.com/api/?name=KP&background=0D8ABC&color=fff'">
            </a>
            <span id="nav-greeting" style="color: white; font-weight: 700; font-size: 18px; transition: all 0.5s ease; position: absolute; left: 50%; transform: translateX(-50%);">Student Union Account</span>
        </div>
        <div class="pp-mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars" id="menuIcon"></i>
        </div>
        <div class="pp-header-actions" id="headerActions">
            <div onclick="document.getElementById('profilePicInput').click()" style="cursor: pointer;" class="pp-header-icon" data-label="Profile">
                <div class="profile-menu-icon" style="display:inline-flex;">
                    <?php if (!empty($user['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="color: white; font-size: 20px;"></i>
                    <?php endif; ?>
                </div>
                <input type="file" id="profilePicInput" style="display: none;" accept="image/*" onchange="uploadProfilePic(this)">
            </div>
            <a href="logout.php" class="pp-logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>
    </header>

    <main class="pp-main is-home">
        <div id="homeScreen">
            <div>
                <!-- Verify Search Hero -->
                <div class="pp-card">
                    <h3 class="pp-card-title">Verify Student Payment</h3>
                    <div class="search-hero">
                        <input type="text" id="searchRef" class="input-field"
                            placeholder="Enter Reference Code (e.g. KWP-SUF-...)">
                        <button class="btn btn-primary" onclick="searchRef()">Verify Reference</button>
                    </div>
                    <div id="searchResult" class="hidden"
                        style="margin-top:15px; padding:15px; background:#f0fdf4; border-radius:12px; border:1px solid #bbf7d0;">
                        <div style="font-weight: 800; color: #166534;" id="resName"></div>
                        <div style="font-size: 14px; color: #15803d; white-space: pre-wrap;" id="resDetails"></div>
                    </div>
                </div>

                <!-- Merchant QR ID (moved up) -->
                <div class="pp-card" style="text-align: center;">
                    <h3 class="pp-card-title">Student Union Merchant ID</h3>
                    
                    <!-- View: Input New Code -->
                    <div id="suAgentInputSection" style="padding: 10px; <?php echo !empty($merchant['agent_code']) ? 'display:none;' : ''; ?>">
                        <p style="font-size: 14px; color: #64748b; margin-bottom: 10px;">Enter your preferred Merchant/Agent ID to generate a QR.</p>
                        <input type="text" id="suAgentCodeInput" class="input-field" placeholder="Enter ID (e.g. SU-999)" style="margin-bottom: 10px; text-align: center;">
                        <button class="btn btn-primary" onclick="saveSUAgentQR()">Generate & Save QR</button>
                    </div>

                    <!-- View: Display Saved QR -->
                    <div id="suQrContainer" class="<?php echo empty($merchant['agent_code']) ? 'hidden' : ''; ?>" style="background: #f8fafc; padding: 20px; border-radius: 12px; display: inline-block;">
                        <canvas id="suQrCanvas"></canvas>
                        <div id="suAgentCodeDisplay" style="margin-top: 10px; font-weight: 800; color: #1e293b; font-family: monospace; font-size: 20px; background: white; padding: 8px 15px; border-radius: 8px; border: 1px solid #e2e8f0; display: inline-block;">
                            <?php echo htmlspecialchars($merchant['agent_code'] ?? ''); ?>
                        </div>
                        <p style="font-size: 12px; color: #64748b; margin-top: 8px; font-weight: 600;">SCAN TO PAY UNION FEE</p>
                        <button class="btn btn-outline" style="margin-top: 15px; font-size: 12px; padding: 8px;" onclick="resetSUAgentQR()">Change ID</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="actionSidebar">
            <!-- SU Collection Stats -->
            <div class="pp-card" id="collectionGrowthCard">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="pp-balance-title">Collection Growth</span>
                </div>
                <div class="pp-balance-amount" style="font-size: 24px;">MWK <?php echo number_format($totalBalance, 2); ?></div>
                <?php if ($lastReset): ?>
                    <div style="font-size:12px; color: white; margin-top:6px;">Last reset: <?php echo date('M d, Y H:i', strtotime($lastReset)); ?></div>
                <?php else: ?>
                    <div style="font-size:12px; color: white; margin-top:6px;">Last reset: Never</div>
                <?php endif; ?>
            </div>

            <div class="pp-actions-grid">
                <div class="pp-action-item" onclick="location.reload()">
                    <div class="pp-action-icon"><i class="fas fa-rotate"></i></div>
                    <span class="pp-action-label">Refresh Data</span>
                </div>
                <div class="pp-action-item" onclick="switchScreen('collections')">
                    <div class="pp-action-icon"><i class="fas fa-file-invoice"></i></div>
                    <span class="pp-action-label">History</span>
                </div>
                <div class="pp-action-item" onclick="resetCollections()">
                    <div class="pp-action-icon"><i class="fas fa-folder-open"></i></div>
                    <span class="pp-action-label">Reset Collections</span>
                </div>
            </div>

            <!-- Recent Collections (moved down and widened) -->
            <div class="pp-card" style="grid-column: 1 / -1;">
                <h3 class="pp-card-title">Recent Fee Payments</h3>
                <div class="pp-activity-list" id="recentPaymentsList">
                    <?php
                    $stmt = $pdo->prepare("SELECT t.*, u.full_name FROM transactions t JOIN users u ON t.sender_id = u.user_id WHERE t.receiver_id IN (SELECT user_id FROM users WHERE user_type = 'StudentUnion') AND t.txn_type = 'SU_FEE' ORDER BY t.created_at DESC LIMIT 5");
                    $stmt->execute();
                    while ($row = $stmt->fetch()):
                        ?>
                        <div class="pp-activity-item">
                            <div class="pp-activity-info">
                                <span class="pp-activity-desc"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                <span
                                    class="pp-activity-date"><?php echo date('M d, H:i', strtotime($row['created_at'])); ?></span>
                            </div>
                            <span class="pp-activity-amount amount-credit">+MWK
                                <?php echo number_format($row['amount'], 2); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Collections Screen -->
        <div id="collectionsScreen" class="hidden" style="grid-column: 1 / -1;">
            <div class="pp-card">
                <h3 class="pp-card-title">Student Fee Collections</h3>
                <?php if ($lastReset): ?>
                    <div style="font-size:13px; color:#64748b; margin-bottom:10px;">Showing collections since last reset: <?php echo date('M d, Y H:i', strtotime($lastReset)); ?></div>
                <?php else: ?>
                    <div style="font-size:13px; color:#64748b; margin-bottom:10px;">Showing all collections (never reset)</div>
                <?php endif; ?>
                
                <div class="filter-bar">
                    <input type="text" id="colSearch" class="input-field filter-input" placeholder="Search Student name, ID, or Ref..." style="margin-bottom:0;" oninput="loadCollections()">
                    <select id="colProgram" class="input-field filter-input" style="margin-bottom:0;" onchange="loadCollections()">
                        <option value="">All Programs</option>
                        <!-- Populated dynamically -->
                    </select>
                    <select id="colYear" class="input-field filter-input" style="margin-bottom:0;" onchange="loadCollections()">
                        <option value="">All Years</option>
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                        <option value="4">Year 4</option>
                    </select>
                    <button class="btn btn-primary" style="width: auto; padding: 12px 25px;" onclick="generatePDF()">
                        <i class="fas fa-file-pdf" style="margin-right: 8px;"></i> Export PDF
                    </button>
                </div>

                <div class="report-stats">
                    <div class="stat-mini-card">
                        <div style="font-size: 12px; font-weight: 700; color: #0369a1; text-transform: uppercase;">Filtered Count</div>
                        <div style="font-size: 20px; font-weight: 800; color: #0c4a6e;" id="filteredCount">0</div>
                    </div>
                    <div class="stat-mini-card">
                        <div style="font-size: 12px; font-weight: 700; color: #0369a1; text-transform: uppercase;">Filtered Total</div>
                        <div style="font-size: 20px; font-weight: 800; color: #0c4a6e;" id="filteredTotal">MWK 0.00</div>
                    </div>
                </div>

                <div class="collections-table-container">
                    <table class="collections-table" id="collTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student Name</th>
                                <th>Student ID</th>
                                <th>Program/Year</th>
                                <th>Ref Code</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody id="colTableBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
                
                <button class="btn btn-outline" style="margin-top: 30px; max-width: 200px;" onclick="switchScreen('home')">
                    <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back home
                </button>
            </div>
        </div>

        <div id="reportsScreen" class="hidden" style="grid-column: 1 / -1;">
            <div class="pp-card">
                <h3 class="pp-card-title">Quick History</h3>
                <div class="pp-activity-list">
                    <?php
                    $stmt = $pdo->prepare("SELECT t.*, u.full_name FROM transactions t JOIN users u ON t.sender_id = u.user_id WHERE t.receiver_id IN (SELECT user_id FROM users WHERE user_type = 'StudentUnion') AND t.txn_type = 'SU_FEE' ORDER BY t.created_at DESC LIMIT 50");
                    $stmt->execute();
                    while ($row = $stmt->fetch()):
                        ?>
                        <div class="pp-activity-item">
                            <div class="pp-activity-info">
                                <span class="pp-activity-desc"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                <span
                                    class="pp-activity-date"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></span>
                                <span style="font-size: 11px; font-family: monospace; color: var(--pp-text-secondary);">Ref:
                                    <?php echo $row['reference_code']; ?></span>
                            </div>
                            <span class="pp-activity-amount amount-credit">+MWK
                                <?php echo number_format($row['amount'], 2); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button class="btn btn-outline" style="margin-top: 20px;" onclick="switchScreen('home')">Back
                    Home</button>
            </div>
        </div>
    </main>

    <script>
        let collectionsData = [];

        function switchScreen(screen) {
            ['homeScreen', 'reportsScreen', 'collectionsScreen'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            });
            const sidebar = document.getElementById('actionSidebar');
            const main = document.querySelector('.pp-main');
            
            if (screen === 'home') {
                document.getElementById('homeScreen').classList.remove('hidden');
                if(sidebar) sidebar.classList.remove('hidden');
                main.classList.add('is-home');
            } else {
                const target = document.getElementById(screen + 'Screen');
                if (target) target.classList.remove('hidden');
                if(sidebar) sidebar.classList.add('hidden');
                main.classList.remove('is-home');
                
                if (screen === 'collections') {
                    loadCollections();
                }
            }
            document.querySelectorAll('.pp-nav-link').forEach(link => link.classList.remove('active'));
            const nav = document.getElementById('nav-' + screen);
            if (nav) nav.classList.add('active');

            const actions = document.getElementById('headerActions');
            if (actions && actions.classList.contains('active')) toggleMobileMenu();
        }

        async function loadCollections() {
            const search = document.getElementById('colSearch').value;
            const program = document.getElementById('colProgram').value;
            const year = document.getElementById('colYear').value;

            try {
                const res = await fetch(`../backend/api/get_collections.php?search=${search}&program=${program}&year=${year}`);
                const data = await res.json();
                
                if (data.success) {
                    collectionsData = data.data;
                    renderTable(data.data);
                    updateStats(data.data);
                    
                    // Populate Program filter one-time
                    if (document.getElementById('colProgram').options.length === 1) {
                        const programs = [...new Set(data.data.map(i => i.program))].sort();
                        programs.forEach(p => {
                            if (p) {
                                const opt = document.createElement('option');
                                opt.value = p;
                                opt.textContent = p;
                                document.getElementById('colProgram').appendChild(opt);
                            }
                        });
                    }
                }
            } catch (e) { console.error('Error loading collections', e); }
        }

        function renderTable(data) {
            const body = document.getElementById('colTableBody');
            body.innerHTML = '';

            if (data.length === 0) {
                body.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">No matching records found.</td></tr>';
                return;
            }

            data.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${new Date(row.date).toLocaleDateString('en-GB')}</td>
                    <td style="font-weight:700; color:#1e293b;">${row.student_name}</td>
                    <td>${row.student_id}</td>
                    <td>${row.program} (Yr ${row.year})</td>
                    <td style="font-family:monospace;">${row.reference_number}</td>
                    <td style="font-weight:700; color:#16a34a;">MK ${parseFloat(row.amount_paid).toLocaleString()}</td>
                `;
                body.appendChild(tr);
            });
        }

        function updateStats(data) {
            const total = data.reduce((sum, row) => sum + parseFloat(row.amount_paid), 0);
            document.getElementById('filteredCount').innerText = data.length;
            document.getElementById('filteredTotal').innerText = 'MWK ' + total.toLocaleString(undefined, {minimumFractionDigits:2});
        }

        async function resetCollections() {
            if (!confirm('Are you sure you want to reset collections? This will set the collection total to zero for the dashboard until new payments arrive.')) return;
            try {
                const res = await fetch('../backend/api/reset_collections.php', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    // Reload to reflect new totals
                    location.reload();
                } else {
                    alert(data.error || 'Reset failed');
                }
            } catch (e) {
                alert('Reset failed');
            }
        }

        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add Title
            doc.setFontSize(18);
            doc.setTextColor(38, 93, 130);
            doc.text('Kweza Pay - Student Union Collection Report', 14, 20);
            
            doc.setFontSize(11);
            doc.setTextColor(100);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 28);
            
            const tableRows = collectionsData.map(row => [
                new Date(row.created_at).toLocaleDateString(),
                row.student_name,
                row.student_id,
                `${row.program} (Yr ${row.year})`,
                row.reference_number,
                `MK ${parseFloat(row.amount_paid).toLocaleString()}`
            ]);

            doc.autoTable({
                head: [['Date', 'Name', 'Student ID', 'Program', 'Reference', 'Amount']],
                body: tableRows,
                startY: 35,
                theme: 'striped',
                headStyles: { fillColor: [38, 93, 130] },
                styles: { fontSize: 9 }
            });

            const total = collectionsData.reduce((sum, row) => sum + parseFloat(row.amount_paid), 0);
            const finalY = doc.lastAutoTable.finalY + 10;
            doc.setFontSize(12);
            doc.setTextColor(0);
            doc.text(`Total Records: ${collectionsData.length}`, 14, finalY);
            doc.text(`Total Amount: MWK ${total.toLocaleString(undefined, {minimumFractionDigits:2})}`, 14, finalY + 7);

            doc.save(`Kweza-SU-Collections-${new Date().getTime()}.pdf`);
        }

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
                alert('Upload failed');
            }
        }

        async function searchRef() {
            const ref = document.getElementById('searchRef').value;
            if (!ref) return;
            try {
                const res = await fetch('../backend/api/search_reference.php?ref=' + ref);
                const data = await res.json();
                if (data.found) {
                    document.getElementById('resName').innerText = '✅ Payment Verified';
                    document.getElementById('resDetails').innerText =
                        'Student: ' + data.student_name +
                        '\nAmount: MWK ' + data.amount +
                        '\nDate: ' + data.date +
                        '\nRef: ' + ref;
                    document.getElementById('searchResult').classList.remove('hidden');
                } else {
                    alert('❌ Reference not found.');
                    document.getElementById('searchResult').classList.add('hidden');
                }
            } catch (e) { alert('Error searching'); }
        }

        // Dynamic Greeting and QR Initialization
        window.addEventListener('load', function() {
            // Initialize QR if code exists
            const suCode = "<?php echo $merchant['agent_code'] ?? ''; ?>";
            if (suCode) {
                renderSUQR(suCode);
            }

            const greeting = document.getElementById('nav-greeting');
            if (greeting) {
                setTimeout(() => {
                    greeting.style.opacity = '0';
                    setTimeout(() => {
                        greeting.textContent = 'Student Union Account';
                        greeting.style.opacity = '1';
                    }, 300);
                }, 1500);
            }
        });

        function renderSUQR(code) {
             const canvas = document.getElementById('suQrCanvas');
             new QRious({
                element: canvas,
                value: code,
                size: 200
            });
        }

        async function saveSUAgentQR() {
            const code = document.getElementById('suAgentCodeInput').value;
            if(!code) return alert('Please enter a Merchant/Agent ID');
            
            try {
                const res = await fetch('../backend/api/update_agent_code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ agent_code: code })
                });
                const data = await res.json();
                if(!data.success) throw new Error(data.error);
            } catch(e) {
                console.error('Error saving code:', e);
                // alert('Error saving code to server. UI will update for now.');
            }

            // Update UI
            document.getElementById('suAgentCodeDisplay').innerText = code;
            document.getElementById('suAgentInputSection').style.display = 'none';
            document.getElementById('suQrContainer').classList.remove('hidden');
            renderSUQR(code);
            location.reload(); // Refresh to ensure backend state is synced
        }

        async function resetSUAgentQR() {
            if(!confirm('Are you sure you want to change your Merchant ID?')) return;

            try {
                await fetch('../backend/api/update_agent_code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ agent_code: null })
                });
                location.reload();
            } catch(e) { console.error(e); }
        }
    </script>
</body>

</html>
