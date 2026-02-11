<?php
require_once '../backend/api/session.php';
require_once '../backend/api/db.php';
requireLogin();

// Use session type for consistency during role switching
if ($_SESSION['user_type'] !== 'Merchant') {
    header('Location: index.php');
    exit;
}

$user = getCurrentUser($pdo);

// Fetch Merchant Details
$stmt = $pdo->prepare("SELECT * FROM merchants WHERE user_id = ?");
$stmt->execute([$user['user_id']]);
$merchant = $stmt->fetch();

if (!$merchant)
    die("Merchant Profile Not Found");

if (!$merchant['is_approved']) {
    // Show pending message
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Application Pending - Kweza Pay</title>
        <link rel="icon" type="image/png" href="assets/img/favicon.png">
        <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
        <link rel="stylesheet" href="assets/css/paypal_ui.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>
            .pending-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                text-align: center;
                padding: 20px;
                background: #f5f7fa;
            }
            .pending-card {
                background: white;
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.05);
                max-width: 500px;
                width: 100%;
            }
            .icon-box {
                width: 80px;
                height: 80px;
                background: #fff7ed;
                color: #f59e0b;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 40px;
                margin: 0 auto 20px;
            }
        </style>
    </head>
    <body>
        <div class="pending-container">
            <div class="pending-card">
                <div class="icon-box"><i class="fas fa-clock"></i></div>
                <h1 style="font-size: 24px; font-weight: 800; color: #1e293b; margin-bottom: 15px;">Application Pending</h1>
                <p style="color: #64748b; line-height: 1.6; margin-bottom: 30px;">
                    Thank you for applying to become a Kweza Pay merchant! Your application is currently being reviewed by our team.
                    <br><br>
                    Once approved, you will have full access to your merchant dashboard and QR payment features.
                </p>
                <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 30px; text-align: left; border: 1px solid #e2e8f0;">
                    <p style="font-size: 12px; font-weight: 800; color: #94a3b8; margin-bottom: 5px;">APPLICATION STATUS</p>
                    <p style="font-size: 14px; color: #475569; font-weight: 600;">Fee Status: <span style="color: #16a34a;">Paid</span></p>
                    <p style="font-size: 14px; color: #475569; font-weight: 600;">Review Status: <span style="color: #f59e0b;">In Progress</span></p>
                </div>
                <a href="logout.php" style="color: #64748b; font-weight: 600; text-decoration: none;">Log Out</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// TODAY'S SALES 
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE receiver_id = ? AND DATE(created_at) = ? AND txn_type IN ('QR_PAY', 'PAYMENT')");
$stmt->execute([$user['user_id'], $today]);
$dailySales = $stmt->fetchColumn() ?: 0.00;

// TOTAL VOLUME RECEIVED
$stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE receiver_id = ?");
$stmt->execute([$user['user_id']]);
$totalVolume = $stmt->fetchColumn() ?: 0.00;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kweza Pay - Merchant Dashboard</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/paypal_ui.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
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

        body.dashboard-merchant {
            background: #f4f7fb;
            position: relative;
            overflow-x: hidden;
        }

        body.dashboard-merchant::before,
        body.dashboard-merchant::after {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        body.dashboard-merchant::before {
            background:
                radial-gradient(circle at 15% 20%, rgba(17, 41, 94, 0.18), rgba(17, 41, 94, 0) 55%),
                radial-gradient(circle at 85% 80%, rgba(14, 116, 144, 0.16), rgba(14, 116, 144, 0) 60%),
                linear-gradient(135deg, rgba(17, 41, 94, 0.06), rgba(255, 255, 255, 0));
            opacity: 0.26;
        }

        body.dashboard-merchant::after {
            background-image: url("data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22360%22%20height%3D%22360%22%20viewBox%3D%220%200%20360%20360%22%3E%0A%20%20%3Crect%20width%3D%22360%22%20height%3D%22360%22%20fill%3D%22none%22%2F%3E%0A%0A%20%20%3Crect%20x%3D%220%22%20y%3D%220%22%20width%3D%2270%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%2270%22%20y%3D%220%22%20width%3D%2250%22%20height%3D%22360%22%20fill%3D%22%23ece7d8%22%2F%3E%0A%20%20%3Crect%20x%3D%22120%22%20y%3D%220%22%20width%3D%22120%22%20height%3D%22360%22%20fill%3D%22%23ece7d8%22%2F%3E%0A%20%20%3Crect%20x%3D%22240%22%20y%3D%220%22%20width%3D%2250%22%20height%3D%22360%22%20fill%3D%22%23ece7d8%22%2F%3E%0A%20%20%3Crect%20x%3D%22290%22%20y%3D%220%22%20width%3D%2270%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%0A%20%20%3Crect%20x%3D%2268%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%22118%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%22238%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%20%20%3Crect%20x%3D%22288%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%2F%3E%0A%0A%20%20%3Cg%20fill%3D%22%239aa200%22%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%200%20L70%2015%20L0%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%2030%20L70%2045%20L0%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%2060%20L70%2075%20L0%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%2090%20L70%20105%20L0%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20120%20L70%20135%20L0%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20150%20L70%20165%20L0%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20180%20L70%20195%20L0%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20210%20L70%20225%20L0%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20240%20L70%20255%20L0%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20270%20L70%20285%20L0%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20300%20L70%20315%20L0%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M0%20330%20L70%20345%20L0%20360%20Z%22%2F%3E%0A%0A%20%20%20%20%3Cpath%20d%3D%22M360%200%20L290%2015%20L360%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%2030%20L290%2045%20L360%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%2060%20L290%2075%20L360%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%2090%20L290%20105%20L360%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20120%20L290%20135%20L360%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20150%20L290%20165%20L360%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20180%20L290%20195%20L360%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20210%20L290%20225%20L360%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20240%20L290%20255%20L360%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20270%20L290%20285%20L360%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20300%20L290%20315%20L360%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M360%20330%20L290%20345%20L360%20360%20Z%22%2F%3E%0A%20%20%3C%2Fg%3E%0A%0A%20%20%3Cg%20fill%3D%22%23c86b1d%22%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%200%20L120%2015%20L70%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%2030%20L120%2045%20L70%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%2060%20L120%2075%20L70%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%2090%20L120%20105%20L70%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20120%20L120%20135%20L70%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20150%20L120%20165%20L70%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20180%20L120%20195%20L70%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20210%20L120%20225%20L70%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20240%20L120%20255%20L70%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20270%20L120%20285%20L70%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20300%20L120%20315%20L70%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M70%20330%20L120%20345%20L70%20360%20Z%22%2F%3E%0A%0A%20%20%20%20%3Cpath%20d%3D%22M290%200%20L240%2015%20L290%2030%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%2030%20L240%2045%20L290%2060%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%2060%20L240%2075%20L290%2090%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%2090%20L240%20105%20L290%20120%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20120%20L240%20135%20L290%20150%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20150%20L240%20165%20L290%20180%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20180%20L240%20195%20L290%20210%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20210%20L240%20225%20L290%20240%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20240%20L240%20255%20L290%20270%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20270%20L240%20285%20L290%20300%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20300%20L240%20315%20L290%20330%20Z%22%2F%3E%0A%20%20%20%20%3Cpath%20d%3D%22M290%20330%20L240%20345%20L290%20360%20Z%22%2F%3E%0A%20%20%3C%2Fg%3E%0A%0A%20%20%3Cg%20fill%3D%22none%22%20stroke%3D%22%230b0b0b%22%20stroke-linecap%3D%22square%22%20stroke-linejoin%3D%22round%22%3E%0A%20%20%20%20%3Cg%20transform%3D%22translate(0%2C0)%22%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%200%20L220%2060%20L180%20120%20L140%2060%20Z%22%20stroke-width%3D%226%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L206%2060%20L180%2096%20L154%2060%20Z%22%20stroke-width%3D%224%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L180%2096%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M154%2060%20L206%2060%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%3C%2Fg%3E%0A%20%20%20%20%3Cg%20transform%3D%22translate(0%2C120)%22%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%200%20L220%2060%20L180%20120%20L140%2060%20Z%22%20stroke-width%3D%226%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L206%2060%20L180%2096%20L154%2060%20Z%22%20stroke-width%3D%224%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L180%2096%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M154%2060%20L206%2060%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%3C%2Fg%3E%0A%20%20%20%20%3Cg%20transform%3D%22translate(0%2C240)%22%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%200%20L220%2060%20L180%20120%20L140%2060%20Z%22%20stroke-width%3D%226%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L206%2060%20L180%2096%20L154%2060%20Z%22%20stroke-width%3D%224%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M180%2024%20L180%2096%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%20%20%3Cpath%20d%3D%22M154%2060%20L206%2060%22%20stroke-width%3D%223%22%2F%3E%0A%20%20%20%20%3C%2Fg%3E%0A%20%20%3C%2Fg%3E%0A%0A%20%20%3Crect%20x%3D%22178%22%20y%3D%220%22%20width%3D%224%22%20height%3D%22360%22%20fill%3D%22%230b0b0b%22%20opacity%3D%220.7%22%2F%3E%0A%3C%2Fsvg%3E");
            background-repeat: repeat;
            background-size: 360px 360px;
            opacity: 0.03;
            filter: grayscale(1);
        }

        body.dashboard-merchant .pp-main {
            position: relative;
            z-index: 1;
        }

        #processedVolumeCard {
            background: #265D82;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        #processedVolumeCard .pp-balance-available {
            color: #ffffff;
        }

        #processedVolumeCard::before,
        #processedVolumeCard::after {
            content: "";
            position: absolute;
            pointer-events: none;
            z-index: 0;
            opacity: 0.35;
            background: radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0) 60%),
                radial-gradient(circle at 70% 70%, rgba(17, 41, 94, 0.18), rgba(17, 41, 94, 0) 60%);
        }

        #processedVolumeCard::before {
            width: 220px;
            height: 220px;
            top: -60px;
            right: -70px;
        }

        #processedVolumeCard::after {
            width: 180px;
            height: 180px;
            bottom: -50px;
            left: -60px;
        }

        #processedVolumeCard > * {
            position: relative;
            z-index: 1;
        }

        .qr-hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .qr-image {
            width: 180px;
            height: 180px;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
        }

        .pp-header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.2);
            cursor: pointer;
        }

        .portal-text {
            color: white;
            font-weight: 700;
            font-size: 18px;
            margin-left: 20px;
        }

        .receipt-container {
            padding: 40px;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .receipt-header {
            text-align: left;
            margin-bottom: 30px;
            border-bottom: 2px solid #11295E;
            padding-bottom: 20px;
        }

        .receipt-logo {
            height: 40px;
            margin-bottom: 15px;
        }

        .receipt-title {
            font-size: 24px;
            font-weight: 800;
            color: #11295E;
            margin: 0;
        }

        .receipt-info-line {
            font-size: 13px;
            color: #6C7378;
            margin-bottom: 4px;
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

        .receipt-footer {
            margin-top: 30px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        /* Merchant dashboard spacing tweaks */
        .dashboard-merchant #processedVolumeCard {
            margin-bottom: 10px;
        }

        .dashboard-merchant #actionSidebar .pp-actions-grid {
            margin-bottom: 12px;
        }

        .dashboard-merchant #homeScreen > div > .pp-card:first-of-type {
            margin-bottom: 10px;
        }

        .dashboard-merchant #homeScreen > div > .pp-card:last-of-type {
            margin-bottom: 14px;
        }

        @media (max-width: 900px) {
            .dashboard-merchant #processedVolumeCard {
                margin-bottom: 8px !important;
            }

            .dashboard-merchant #actionSidebar .pp-actions-grid {
                margin-bottom: 10px !important;
            }

            .dashboard-merchant #homeScreen > div > .pp-card:first-of-type {
                margin-bottom: 8px !important;
            }

            .dashboard-merchant #homeScreen > div > .pp-card:last-of-type {
                margin-bottom: 10px !important;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/css/merchant_mobile.css?v=<?php echo time(); ?>">
</head>

<body class="dashboard-merchant">
    <header class="pp-header">
        <div class="pp-nav">
            <a href="#" onclick="switchScreen('home')" id="homeLogo">
                <img src="assets/img/logo.png" alt="Kweza Pay" class="pp-logo"
                    onerror="this.src='https://ui-avatars.com/api/?name=KP&background=0D8ABC&color=fff'">
            </a>
            <span id="nav-greeting" style="color: white; font-weight: 700; font-size: 18px; transition: all 10.0s ease; position: absolute; left: 50%; transform: translateX(-50%);">Merchant Account</span>
        </div>
        <div class="pp-mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars" id="menuIcon"></i>
        </div>
        <div class="pp-header-actions" id="headerActions">
            <div onclick="document.getElementById('merchantPicInput').click()" style="cursor: pointer;" class="pp-header-icon" data-label="Profile">
                <div class="profile-menu-icon">
                    <?php if (!empty($user['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </div>
                <input type="file" id="merchantPicInput" style="display: none;" accept="image/*" onchange="uploadMerchantPic(this)">
            </div>

            <!-- Settings Button -->
            <!-- Settings Button -->
            <div class="pp-header-icon" onclick="switchScreen('settings')" style="cursor: pointer;" title="Settings" data-label="Settings">
                <i class="fas fa-cog" style="color: white; font-size: 20px;"></i>
            </div>
            <a href="logout.php" class="pp-logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>
    </header>

    <main class="pp-main is-home">
        <div id="homeScreen">
            <!-- Left Side Dashboard -->
            <div>

                <!-- Airtel Money Agent QR -->
                <div class="pp-card">
                <h3 class="pp-card-title">Mobile Money Agent QR</h3>
                    
                    <!-- View: Input New Code (Shown if no code exists) -->
                    <div id="agentInputSection" style="padding: 10px; <?php echo !empty($merchant['agent_code']) ? 'display:none;' : ''; ?>">
                        <p style="font-size: 14px; color: #64748b; margin-bottom: 10px;">Enter your Agent Code once. It will be saved for future use.</p>
                        <input type="text" id="agentCodeInput" class="input-field" placeholder="Enter Agent Code" style="margin-bottom: 10px;">
                        <button class="btn btn-primary" onclick="saveAgentQR()">Generate & Save QR</button>
                    </div>

                    <!-- View: Display Saved QR (Shown if code exists) -->
                    <div id="agentQrContainer" class="qr-hero <?php echo empty($merchant['agent_code']) ? 'hidden' : ''; ?>">
                        <canvas id="agentQrCanvas"></canvas>
                        <div id="agentCodeDisplay" class="merchant-id-badge"><?php echo !empty($merchant['agent_code']) ? htmlspecialchars($merchant['agent_code']) : ''; ?></div>
                        <span style="font-size: 12px; color: #64748b; font-weight: 600;">SCAN TO PAY AGENT</span>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="pp-card">
                    <h3 class="pp-card-title">Recent Sales</h3>
                    <div class="pp-activity-list">
                        <?php
                        $stmt = $pdo->prepare("SELECT t.*, u.full_name FROM transactions t JOIN users u ON t.sender_id = u.user_id WHERE t.receiver_id = ? ORDER BY t.created_at DESC LIMIT 5");
                        $stmt->execute([$user['user_id']]);
                        while ($row = $stmt->fetch()):
                            ?>
                            <div class="pp-activity-item" onclick="viewTransactionReceipt(<?php echo htmlspecialchars(json_encode($row)); ?>)" style="cursor: pointer;">
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
        </div>

        <div id="actionSidebar">
            <!-- Merchant Stats -->
            <div class="pp-card" id="processedVolumeCard">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="pp-balance-title">Processed Volume</span>
                </div>
                <div class="pp-balance-amount">MWK <?php echo number_format($totalVolume, 2); ?></div>
                <div class="pp-balance-available">Today's Sales: MWK <?php echo number_format($dailySales, 2); ?></div>
            </div>

            <div class="pp-actions-grid">
                <div class="pp-action-item" onclick="switchScreen('wallet')">
                    <div class="pp-action-icon"><i class="fas fa-history"></i></div>
                    <span class="pp-action-label">History</span>
                </div>
                <div class="pp-action-item" onclick="switchScreen('reports')">
                    <div class="pp-action-icon"><i class="fas fa-chart-line"></i></div>
                    <span class="pp-action-label">Reports</span>
                </div>
                <div class="pp-action-item" onclick="location.reload()">
                    <div class="pp-action-icon"><i class="fas fa-sync"></i></div>
                    <span class="pp-action-label">Refresh</span>
                </div>
            </div>
        </div>

        <!-- Wallet Sub-screen -->
        <div id="walletScreen" class="hidden" style="grid-column: 1 / -1;">
            <div class="pp-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="pp-balance-title">Mercahant total Transaction History</span>
                </div>
                <div class="pp-balance-amount">MWK <?php echo number_format($totalVolume, 2); ?></div>
                <div class="pp-balance-available">Today's Sales: MWK <?php echo number_format($dailySales, 2); ?></div>
                <button class="btn btn-outline" style="margin-top: 20px;" onclick="switchScreen('home')">Back Home</button>
            </div>
        </div>

        <!-- History/Reports Sub-screen -->
        <div id="reportsScreen" class="hidden" style="grid-column: 1 / -1;">
            <div class="pp-card">
                <h3 class="pp-card-title">Comprehensive Sales Report</h3>
                <div class="pp-activity-list">
                    <!-- Full list of transactions -->
                    <?php
                    $stmt = $pdo->prepare("SELECT t.*, u.full_name FROM transactions t JOIN users u ON t.sender_id = u.user_id WHERE t.receiver_id = ? ORDER BY t.created_at DESC LIMIT 50");
                    $stmt->execute([$user['user_id']]);
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

        <!-- Settings Screen -->
        <div id="settingsScreen" class="hidden" style="grid-column: 1 / -1;">
            <div class="pp-card">
                <h3 class="pp-card-title">Settings</h3>
                
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #333; display: block; margin-bottom: 8px;">Agent QR Code</label>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                         <p style="font-size: 14px; color: #64748b; margin-bottom: 10px;">
                            Current Status: 
                            <?php if(!empty($merchant['agent_code'])): ?>
                                <span style="color: #16a34a; font-weight: bold;">Active (<?php echo htmlspecialchars($merchant['agent_code']); ?>)</span>
                            <?php else: ?>
                                <span style="color: #f59e0b; font-weight: bold;">Not Set</span>
                            <?php endif; ?>
                        </p>
                        <p style="font-size: 14px; color: #64748b; margin-bottom: 15px;">Need to change or regenerate your Agent Code?</p>
                        <button class="btn btn-outline" onclick="resetAgentQR()">Reset / Regenerate QR</button>
                    </div>
                </div>

                <div style="margin-bottom: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                     <h3 class="pp-card-title" style="font-size: 16px;">Security</h3>
                     <button class="btn btn-outline" onclick="alert('Please contact admin to reset your PIN.')">Change PIN</button>
                </div>

                <button class="btn btn-primary" style="margin-top: 20px;" onclick="switchScreen('home')">Back to Dashboard</button>
            </div>
        </div>
        </div>
    </main>

    <!-- Receipt Modal -->
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
                <button class="btn btn-primary" onclick="location.reload()">Close</button>
            </div>
        </div>
    </div>



    <script>
        function switchScreen(screen) {
            ['homeScreen', 'reportsScreen', 'walletScreen', 'settingsScreen'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            });
            const sidebar = document.getElementById('actionSidebar');
            const main = document.querySelector('.pp-main');
            if (screen === 'home') {
                document.getElementById('homeScreen').classList.remove('hidden');
                sidebar.classList.remove('hidden');
                main.classList.add('is-home');
            } else {
                const target = document.getElementById(screen + 'Screen');
                if (target) target.classList.remove('hidden');
                sidebar.classList.add('hidden');
                main.classList.remove('is-home');
            }
            document.querySelectorAll('.pp-nav-link').forEach(link => link.classList.remove('active'));
            const nav = document.getElementById('nav-' + screen.substring(0, 4));
            if (nav) nav.classList.add('active');

            // Close mobile menu if open
            const actions = document.getElementById('headerActions');
            if (actions.classList.contains('active')) toggleMobileMenu();
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

        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        async function uploadMerchantPic(input) {
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

        function showReceipt(data) {
            const content = document.getElementById('receiptContent');
            let html = `
                <div class="receipt-header">
                    <img src="assets/img/logo.png" class="receipt-logo" onerror="this.src='https://ui-avatars.com/api/?name=KP&background=012169&color=fff'">
                    <h2 class="receipt-title">Transaction Receipt</h2>
                </div>
                <div class="receipt-info-line">Date: ${new Date(data.date).toLocaleString()}</div>
                <div class="receipt-info-line">Reference: ${data.reference}</div>
                <div class="receipt-section-title">PAYMENT DETAILS:</div>
                <div class="receipt-details-grid">
                    <div class="receipt-detail-item">Description: ${data.description || 'Merchant Payment'}</div>
                    <div class="receipt-detail-item">Amount: MK ${parseFloat(data.amount).toLocaleString()}</div>
                    <div class="receipt-detail-item">Recipient: ${data.merchantName || 'You'}</div>
                </div>
                <div class="receipt-status success">STATUS: SUCCESSFUL <i class="fas fa-check-circle"></i></div>
                <div class="receipt-disclaimer">
                     This is a verification receipt for the transaction facilitated by Kweza Pay.
                </div>
                <div class="receipt-footer">Kweza Pay - Merchant Services</div>
            `;
            content.innerHTML = html;
            document.getElementById('receiptModal').style.display = 'flex';
        }

        async function downloadReceipt() {
            const node = document.getElementById('receiptContent');
            try {
                const dataUrl = await domtoimage.toPng(node, { bgcolor: '#ffffff' });
                const link = document.createElement('a');
                link.download = `Kweza-Receipt.png`;
                link.href = dataUrl;
                link.click();
            } catch (error) {
                alert('Download failed');
            }
        }

        function viewTransactionReceipt(txn) {
            const receiptData = {
                merchantName: 'Your Store',
                amount: txn.amount,
                reference: txn.reference_code,
                date: txn.created_at,
                description: txn.description || 'Sale'
            };
            showReceipt(receiptData);
        }

        function generateAgentQR() {
            const code = document.getElementById('agentCodeInput').value;
            if(!code) return alert('Please enter an agent code');
            
            const canvas = document.getElementById('agentQrCanvas');
            const qr = new QRious({
                element: canvas,
                value: code,
                size: 200
            });
            
            document.getElementById('agentCodeDisplay').innerText = code;
            document.getElementById('agentQrContainer').classList.remove('hidden');
        }
        // Initialize QR if code exists
        window.addEventListener('load', function() {
            const savedCode = "<?php echo $merchant['agent_code'] ?? ''; ?>";
            if (savedCode) {
                renderQR(savedCode);
            }
        });

        function renderQR(code) {
             const canvas = document.getElementById('agentQrCanvas');
             new QRious({
                element: canvas,
                value: code,
                size: 200
            });
        }

        async function saveAgentQR() {
            const code = document.getElementById('agentCodeInput').value;
            if(!code) return alert('Please enter an agent code');
            
            try {
                const res = await fetch('../backend/api/update_agent_code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ agent_code: code })
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('Agent Code Saved Successfully!');
                    location.reload(); // Reload to persist state from server-side PHP session/DB fetch
                } else {
                    alert('Error saving code: ' + (data.error || 'Unknown error'));
                }
            } catch(e) {
                console.error(e);
                alert('Connection error while saving Agent Code');
            }
        }

        async function resetAgentQR() {
            if(!confirm('Are you sure you want to reset your Agent QR Code? This will require you to enter a new code.')) return;

            try {
                const res = await fetch('../backend/api/update_agent_code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ agent_code: null })
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('Agent Code Reset. You can now create a new one.');
                    location.reload();
                } else {
                    alert('Error resetting code: ' + data.error);
                }
            } catch(e) {
                alert('Connection error');
            }
        }


        // Dynamic Greeting Transition
        window.addEventListener('load', function() {
            const greeting = document.getElementById('nav-greeting');
            setTimeout(() => {
                greeting.style.opacity = '0';
                setTimeout(() => {
                    greeting.textContent = 'Merchant Account';
                    greeting.style.opacity = '1';
                }, 300);
            }, 1500);
        });
    </script>
</body>

</html>
