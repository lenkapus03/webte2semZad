<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/auth/api_key.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        $validation = validateUsernamePassword($username, $password);
        if (!$validation['valid']) {
            throw new Exception(implode("<br>", $validation['errors']));
        }

        $pdo = getPDO();

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('Invalid username or password.');
        }

        $_SESSION = [
            'username' => $user['username'],
            'role' => $user['role'],
            'api_key' => $user['api_key']
        ];

        logUserAction($user['username'], 'login');

        header("Location: /myapp/index.php");
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login – PDF App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4285F4;
            --danger-color: #EA4335;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f5f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            margin-top: 0;
            color: var(--primary-color);
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #3367d6;
        }

        .error {
            color: var(--danger-color);
            margin-bottom: 10px;
            text-align: center;
            font-size: 14px;
        }

        .link {
            text-align: center;
            margin-top: 15px;
        }

        .link a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2><i class="fas fa-lock"></i> Login</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required />
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required />
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="link">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>
</body>
</html>
