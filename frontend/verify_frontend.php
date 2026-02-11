<?php
echo "=== KWEZA PAY FRONTEND CHECK ===\n\n";

function check_page($page) {
    $url = 'http://localhost/kweza-app/frontend/' . $page;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false); // Get body to check content
    $content = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code == 200 && strlen($content) > 0) {
        echo "[OK] $page (Size: " . strlen($content) . " bytes)\n";
        return true;
    } else {
        echo "[FAIL] $page (Code: $code)\n";
        return false;
    }
}

check_page('index.php');
check_page('student.php');
check_page('merchant.php');
check_page('assets/css/style.css');

echo "\n=== CHECK COMPLETE ===\n";
?>
