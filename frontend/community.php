<?php
require_once '../backend/api/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - Kweza Pay</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <a href="student.php" style="color: white; text-decoration: none;"><i class="fas fa-arrow-left"></i></a>
                <div style="font-weight: bold; margin-left: 10px;">Community</div>
            </div>
        </div>

        <div class="card mt-20">
            <h3 class="section-title">Latest Updates</h3>

            <div
                style="padding: 15px; background: var(--bg-secondary); border-radius: 12px; margin-bottom: 15px; border-left: 4px solid var(--kweza-blue);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <div style="font-weight: 700; color: var(--kweza-blue);">System Update</div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary);">Today</div>
                </div>
                <p style="font-size: 14px; line-height: 1.5; color: var(--text-primary);">
                    🎉 Dark Mode is here! You can now switch between light and dark themes from your profile settings.
                </p>
            </div>

            <div
                style="padding: 15px; background: var(--bg-secondary); border-radius: 12px; margin-bottom: 15px; border-left: 4px solid var(--service-green);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <div style="font-weight: 700; color: var(--service-green);">Student Union</div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary);">Yesterday
                    </div>
                </div>
                <p style="font-size: 14px; line-height: 1.5; color: var(--text-primary);">
                    Elections are coming up next week. Make sure your SU fees are up to date to be eligible to vote!
                </p>
            </div>

            <div
                style="padding: 15px; background: var(--bg-secondary); border-radius: 12px; margin-bottom: 15px; border-left: 4px solid var(--service-orange);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <div style="font-weight: 700; color: var(--service-orange);">Maintenance</div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--text-secondary);">2 Days Ago
                    </div>
                </div>
                <p style="font-size: 14px; line-height: 1.5; color: var(--text-primary);">
                    Scheduled maintenance on Saturday 2 AM - 4 AM. Services might be intermittent.
                </p>
            </div>
        </div>
    </div>
</body>

</html>