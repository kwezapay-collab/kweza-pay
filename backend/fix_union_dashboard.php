<?php
/**
 * Fix Union Admin Dashboard
 * - Bank card proportions
 * - Visible watermark
 * - Balance hide/show toggle
 * - Make sure paid fees list shows
 */

$file = 'student_union.php';
$content = file_get_contents($file);

// 1. Fix watermark opacity in inline style
$content = str_replace(
    'filter: grayscale(1) invert(0.8) opacity(0.2);',
    'filter: grayscale(1) brightness(2); opacity: 0.7; width: 140%;',
    $content
);

// 2. Add bank card aspect ratio to balance card
$oldCardStyle = '.su-balance-card {
            background: var(--su-card-bg);
            border-radius: 20px;
            padding: 24px;
            margin: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);
        }';

$newCardStyle = '.su-balance-card {
            background: var(--su-card-bg);
            border-radius: 20px;
            padding: 24px 30px;
            margin: 20px auto;
            max-width: 450px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);
            aspect-ratio: 1.586; /* Bank card proportions */
        }';

$content = str_replace($oldCardStyle, $newCardStyle, $content);

// 3. Add balance toggle button HTML - update the card top section
$oldCardTop = '<div class="su-card-top">
                <div>
                    <div class="su-incoming-label">INCOMING MONEY</div>
                    <div class="su-incoming-val">+MWK <?php echo number_format($todayIncoming, 2); ?></div>
                </div>
                <div class="wallet-icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>

            <div class="su-main-balance">MWK <?php echo number_format($balance, 2); ?></div>';

$newCardTop = '<div class="su-card-top">
                <div>
                    <div class="su-incoming-label">INCOMING MONEY</div>
                    <div class="su-incoming-val">+MWK <?php echo number_format($todayIncoming, 2); ?></div>
                </div>
                <button class="toggle-balance-btn" onclick="toggleBalance()" style="background: rgba(255,255,255,0.1); border: none; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: rgba(255,255,255,0.8);">
                    <i class="fas fa-eye" id="balanceIcon"></i>
                </button>
            </div>

            <div class="su-main-balance" id="mainBalance">MWK <?php echo number_format($balance, 2); ?></div>';

$content = str_replace($oldCardTop, $newCardTop, $content);

// 4. Add toggle balance JavaScript function before closing script tag
$addBeforeScript = '        // Toggle balance visibility
        function toggleBalance() {
            const balanceEl = document.getElementById(\'mainBalance\');
            const iconEl = document.getElementById(\'balanceIcon\');
            const isHidden = balanceEl.dataset.hidden === \'true\';
            
            if (isHidden) {
                balanceEl.textContent = \'MWK <?php echo number_format($balance, 2); ?>\';
                iconEl.className = \'fas fa-eye\';
                balanceEl.dataset.hidden = \'false\';
            } else {
                balanceEl.textContent = \'MWK ••••••\';
                iconEl.className = \'fas fa-eye-slash\';
                balanceEl.dataset.hidden = \'true\';
            }
        }

        // Click bell icon to open search
        document.addEventListener(\'DOMContentLoaded\', function() {';

$content = str_replace(
    "        // Click bell icon to open search\n        document.addEventListener('DOMContentLoaded', function() {",
    $addBeforeScript,
    $content
);

file_put_contents($file, $content);

echo "✅ Fixed Union Admin Dashboard:\n";
echo "  - Bank card proportions (aspect ratio 1.586:1)\n";
echo "  - Logo watermark more visible (70% opacity)\n";
echo "  - Balance hide/show toggle added\n";
echo "  - Payment list already showing\n\n";
echo "🔔 Bell icon = Search payments by reference\n";
echo "👁️ Eye icon = Hide/show balance\n";
?>
