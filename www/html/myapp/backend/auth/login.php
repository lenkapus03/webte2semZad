<?php
header('Content-Type: application/json');
session_start();

//require_once "/var/www/configs/config.php";
//require_once "utilities.php";
require_once "api_key.php";

$response = [];

try {
    $pdo = getPDO();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $validation = validateUsernamePassword($username, $password);
    if (!$validation['valid']) {
        throw new Exception(implode("\n", $validation['errors']), 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception('Invalid username or password', 401);
    }

    $_SESSION = [
        'username' => $user['username'],
        'role' => $user['role'],
        'api_key' => $user['api_key']
    ];

    logUserAction($user['username'], 'login');

    $response = [
        'success' => true,
        'redirect' => '/myapp/index.php',
        'user' => [
            'username' => $user['username']
        ]
    ];

} catch (PDOException $e) {
    http_response_code(500);
    $response = ['error' => 'Database error occurred'];
    error_log("Login PDO error: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 400);
    $response = ['error' => $e->getMessage()];
    if ($e->getCode() >= 500) {
        error_log("Login error: " . $e->getMessage());
    }
}

echo json_encode($response);
