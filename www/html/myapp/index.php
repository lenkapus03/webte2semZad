<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Use an absolute path starting with / to ensure proper redirection
    header("Location: /myapp/auth/login.php");
    exit;
}
$isAdmin = $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PDF_app</title>
</head>
<body>
<h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
<a href="/myapp/backend/auth/logout.php">Logout</a>
<button id="regenApiKey">Generate New API Key</button>
<p id="message"></p>
<div id="apiKeyDisplay"></div>
<?php if ($isAdmin): ?>
    <hr>
    <h3>Admin Panel</h3>
    <a href="/myapp/users_history.html">View User History</a>
<?php endif; ?>
<script src="index.js" defer></script>
</body>
</html>