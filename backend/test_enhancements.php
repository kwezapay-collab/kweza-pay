<?php
/**
 * Quick System Test Script
 * Verifies all enhancements are working correctly
 */

echo "Kweza Pay System Verification\n";
echo "==============================\n\n";

require_once 'api/db.php';

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: Check new database columns exist
echo "1. Testing Database Schema...\n";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
    $tests['email_column'] = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'registration_number'");
    $tests['reg_number_column'] = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    $tests['verification_column'] = $stmt->rowCount() > 0;
    
    if ($tests['email_column'] && $tests['reg_number_column'] && $tests['verification_column']) {
        echo "   ✓ All new columns exist\n";
        $passed++;
    } else {
        echo "   ✗ Missing columns\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 2: Verify Student Union account
echo "\n2. Testing Student Union Account...\n";
try {
    $stmt = $pdo->prepare("SELECT user_type, is_verified, full_name FROM users WHERE phone_number = '09999'");
    $stmt->execute();
    $su = $stmt->fetch();
    
    if ($su) {
        $correct_type = ($su['user_type'] === 'StudentUnion');
        $is_verified = ($su['is_verified'] == 1);
        
        echo "   User Type: " . $su['user_type'] . ($correct_type ? " ✓" : " ✗") . "\n";
        echo "   Verified: " . ($is_verified ? "Yes ✓" : "No ✗") . "\n";
        echo "   Name: " . $su['full_name'] . "\n";
        
        if ($correct_type && $is_verified) {
            $passed++;
        } else {
            $failed++;
        }
    } else {
        echo "   ✗ Student Union account not found\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 3: Check test accounts are verified
echo "\n3. Testing Account Verification Status...\n";
try {
    $stmt = $pdo->query("SELECT phone_number, user_type, is_verified FROM users WHERE phone_number IN ('07001', '09999', '07777', '00000')");
    $accounts = $stmt->fetchAll();
    
    $all_verified = true;
    foreach ($accounts as $acc) {
        $status = $acc['is_verified'] ? "✓" : "✗";
        echo "   {$acc['phone_number']} ({$acc['user_type']}): {$status}\n";
        if (!$acc['is_verified']) $all_verified = false;
    }
    
    if ($all_verified && count($accounts) === 4) {
        echo "   ✓ All test accounts verified\n";
        $passed++;
    } else {
        echo "   ✗ Some accounts not verified\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 4: Verify files exist
echo "\n4. Testing New Files...\n";
$files = [
    'register.php' => 'Registration Page',
    'verify.php' => 'Verification Page',
    'api/register.php' => 'Registration API',
    'api/verify.php' => 'Verification API',
    'api/resend_code.php' => 'Resend Code API',
    'db_migration.php' => 'Migration Script'
];

$all_exist = true;
foreach ($files as $file => $name) {
    $exists = file_exists($file);
    echo "   {$name}: " . ($exists ? "✓" : "✗") . "\n";
    if (!$exists) $all_exist = false;
}

if ($all_exist) {
    $passed++;
} else {
    $failed++;
}

// Test 5: Check login page updates
echo "\n5. Testing Login Page Enhancements...\n";
$loginContent = file_get_contents('index.php');
$has_carousel = strpos($loginContent, 'backgroundCarousel') !== false;
$has_signup = strpos($loginContent, 'register.php') !== false;
$has_animation = strpos($loginContent, 'logoFloat') !== false;

echo "   Background Carousel: " . ($has_carousel ? "✓" : "✗") . "\n";
echo "   Signup Link: " . ($has_signup ? "✓" : "✗") . "\n";
echo "   Logo Animation: " . ($has_animation ? "✓" : "✗") . "\n";

if ($has_carousel && $has_signup && $has_animation) {
    $passed++;
} else {
    $failed++;
}

// Summary
echo "\n==============================\n";
echo "Test Summary\n";
echo "==============================\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "\n";

if ($failed === 0) {
    echo "✓ All tests passed! System is ready.\n\n";
    echo "Test Credentials:\n";
    echo "-----------------\n";
    echo "Student Union: 09999 / 1234\n";
    echo "Student: 07001 / 1234\n";
    echo "Merchant: 07777 / 1234\n";
    echo "Admin: 00000 / 1234\n";
    echo "\nNext Steps:\n";
    echo "1. Visit http://localhost/kweza/ to test login\n";
    echo "2. Click 'Create Account' to test registration\n";
    echo "3. Login with Student Union credentials to see the dashboard\n";
} else {
    echo "✗ Some tests failed. Please review the errors above.\n";
}
?>
