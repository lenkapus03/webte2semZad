<?php
header('Content-Type: application/json');
session_start();

//require "/var/www/configs/config.php";
//require_once "utilities.php";
require_once "api_key.php";

$response = ['success' => false, 'error' => "test", 'api_key' => null, 'user' => null];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    $username = $_POST['username'] ?? '';
    $passwordPlain = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    $validation = validateUsernamePassword($username, $passwordPlain);
    if (!$validation['valid']) {
        throw new Exception(implode("\n", $validation['errors']), 400);
    }

    $roleCheck = validateRole($role);
    if (!$roleCheck['valid']) {
        throw new Exception($roleCheck['error'], 400);
    }
    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception('Username already taken', 409);
    }

    $hashedPassword = password_hash($passwordPlain, PASSWORD_DEFAULT);
    $api_key = generateApiKey();

    $stmt = $pdo->prepare(
        "INSERT INTO users (username, password, role, api_key)
        VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$username, $hashedPassword, $role, $api_key]);

    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['api_key'] = $api_key;

    logUserAction($username, 'register');

    $response = [
        'success' => true,
        'api_key' => $api_key,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $username
        ]
    ];

} catch (PDOException $e) {
    http_response_code(500);
    $response = ['error' => 'Registration failed'];
    error_log("Registration PDO error: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 400);
    $response = ['error' => $e->getMessage()];
    if ($e->getCode() >= 500) {
        error_log("Registration error: " . $e->getMessage());
    }
}

die (json_encode($response));



