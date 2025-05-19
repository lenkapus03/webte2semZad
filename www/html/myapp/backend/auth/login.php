<?php
header('Content-Type: application/json');
session_start();

require_once "api_key.php";

$response = ['success' => false];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    // Get form data directly from $_POST
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic validation
    if (empty($username)) {
        throw new Exception('Username is required', 400);
    }
    if (empty($password)) {
        throw new Exception('Password is required', 400);
    }

    $validation = validateUsernamePassword($username, $password);
    if (!$validation['valid']) {
        $response['error'] = $validation['errors'];
        throw new Exception('Validation failed', 400);
    }

    $pdo = getPDO();
    if (!$pdo) {
        throw new Exception('Database connection failed', 500);
    }

    $stmt = $pdo->prepare("SELECT username, password, role, api_key FROM users WHERE username = ?");
    if (!$stmt->execute([$username])) {
        throw new Exception('Database query failed', 500);
    }

    $user = $stmt->fetch();
    if (!$user) {
        throw new Exception('Invalid username or password', 401);
    }

    if (!password_verify($password, $user['password'])) {
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
    $response = ['error' => 'Login failed due to database error'];
    error_log("Login PDO error: " . $e->getMessage());
} catch (Exception $e) {
    $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
    http_response_code($code);
    $response['error'] = (array)($response['error'] ?? $e->getMessage());
    if ($code >= 500) {
        error_log("Login error: " . $e->getMessage());
    }
}

echo json_encode($response);
exit;