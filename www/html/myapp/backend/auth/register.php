<?php
header('Content-Type: application/json');
session_start();

require_once "api_key.php";

$response = ['success' => false, 'error' => null, 'api_key' => null, 'user' => null];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    // Get form data directly from $_POST
    $username = trim($_POST['username'] ?? '');
    $passwordPlain = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');

    // Basic validation
    if (empty($username)) {
        throw new Exception('Username is required', 400);
    }
    if (empty($passwordPlain)) {
        throw new Exception('Password is required', 400);
    }
    if (empty($role)) {
        throw new Exception('Role is required', 400);
    }

    // Additional validation
    $validation = validateUsernamePassword($username, $passwordPlain);
    if (!$validation['valid']) {
        $response['error'] = $validation['errors'];
        throw new Exception('Validation failed', 400);
    }

    $roleCheck = validateRole($role);
    if (!$roleCheck['valid']) {
        throw new Exception($roleCheck['error'], 400);
    }

    $pdo = getPDO();
    if (!$pdo) {
        throw new Exception('Database connection failed', 500);
    }

    // Check if username exists
    $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
    if (!$stmt->execute([$username])) {
        throw new Exception('Database query failed', 500);
    }

    if ($stmt->fetch()) {
        throw new Exception('Username already taken', 409);
    }

    $hashedPassword = password_hash($passwordPlain, PASSWORD_DEFAULT);
    $api_key = generateApiKey();

    // Insert new user
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, password, role, api_key)
        VALUES (?, ?, ?, ?)"
    );

    if (!$stmt->execute([$username, $hashedPassword, $role, $api_key])) {
        throw new Exception('User registration failed', 500);
    }

    // Set session variables without user_id
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['api_key'] = $api_key;

    logUserAction($username, 'register');

    $response = [
        'success' => true,
        'api_key' => $api_key,
        'user' => [
            'username' => $username
        ]
    ];

} catch (PDOException $e) {
    http_response_code(500);
    $response['error'] = 'Registration failed due to database error';
    error_log("Registration PDO error: " . $e->getMessage());
} catch (Exception $e) {
    $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
    http_response_code($code);
    $response['error'] = (array)($response['error'] ?? $e->getMessage());
}

echo json_encode($response);
exit;