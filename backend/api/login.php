<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$phone = $data['phone'] ?? '';
$pin = $data['pin'] ?? '';
$selectedRole = $data['selected_role'] ?? null;

if (!$phone || !$pin) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Phone and PIN are required']);
    exit;
}

try {
    // 1. Authenticate user
    $stmt = $pdo->prepare("SELECT user_id, full_name, pin_hash, is_verified FROM users WHERE phone_number = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pin, $user['pin_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid Phone or PIN']);
        exit;
    }

    // 2. Check verification
    if (!$user['is_verified']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Please verify your account first. Check your email for the verification code.']);
        exit;
    }

    // 3. Get all available roles for this user
    $stmtRoles = $pdo->prepare("SELECT role FROM user_roles WHERE user_id = ?");
    $stmtRoles->execute([$user['user_id']]);
    $roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);

    // Backward compatibility: if no roles found in user_roles, check users table
    if (empty($roles)) {
        $stmtOld = $pdo->prepare("SELECT user_type FROM users WHERE user_id = ?");
        $stmtOld->execute([$user['user_id']]);
        $oldType = $stmtOld->fetchColumn();
        if ($oldType) {
            $roles = [$oldType];
            // Fix: Insert this role into user_roles for future
            $pdo->prepare("INSERT IGNORE INTO user_roles (user_id, role) VALUES (?, ?)")->execute([$user['user_id'], $oldType]);
        }
    }

    // 4. Handle role selection
    if (count($roles) > 1 && !$selectedRole) {
        // More than one role and none selected - send roles back to frontend
        echo json_encode([
            'success' => true,
            'requires_selection' => true,
            'roles' => $roles,
            'user' => [
                'name' => $user['full_name']
            ]
        ]);
        exit;
    }

    // 5. Finalize Login
    $finalRole = $selectedRole ?: $roles[0];
    
    // Ensure selected role is valid for this user
    if (!in_array($finalRole, $roles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid role selected for this account.']);
        exit;
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_type'] = $finalRole;
    $_SESSION['full_name'] = $user['full_name'];

    // Determine Redirect
    $redirect = 'student.php';
    if ($finalRole === 'Merchant') $redirect = 'merchant.php';
    if ($finalRole === 'Admin') $redirect = 'admin/index.php';
    if ($finalRole === 'StudentUnion') $redirect = 'student_union.php';
    if ($finalRole === 'Person') $redirect = 'person.php';

    echo json_encode([
        'success' => true,
        'redirect' => $redirect,
        'user' => [
            'name' => $user['full_name'],
            'type' => $finalRole
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server Error: ' . $e->getMessage()]);
}
