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
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .language-switcher {
            text-align: right;
            margin-bottom: 20px;
        }
        .dropzone {
            border: 2px dashed #ccc;
            border-radius: 5px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }
        .dropzone.active {
            border-color: #4CAF50;
            background-color: #e8f5e9;
        }
        #fileList {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        #fileList li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            border-radius: 4px;
            background-color: #f5f5f5;
        }
        #fileList .file-name {
            flex-grow: 1;
            margin-right: 10px;
        }
        .file-item {
            display: flex;
            align-items: center;
        }
        .file-icon {
            margin-right: 10px;
        }
        .remove-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .drag-handle {
            cursor: move;
            padding: 0 10px;
            color: #aaa;
        }
        #fileInput {
            display: none;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .btn-secondary {
            background-color: #2196F3;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .info {
            background-color: #d9edf7;
            color: #31708f;
            border: 1px solid #bce8f1;
        }
        .hidden {
            display: none;
        }
        .navigation {
            margin-bottom: 20px;
        }
        .navigation a {
            margin-right: 15px;
            color: #2196F3;
            text-decoration: none;
        }
        .navigation a:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .dropzone {
                padding: 15px;
            }
            #fileList li {
                flex-direction: column;
                align-items: flex-start;
            }
            .file-buttons {
                margin-top: 10px;
                align-self: flex-end;
            }
        }
    </style>
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


<h2>Available PDF Tools</h2>

<div class="features">


    <div class="feature-box">
        <h3>Advanced PDF Merger</h3>
        <p>Merge PDFs with additional options like reordering pages.</p>
        <a href="/myapp/frontend/merge_pdfs.html" class="btn">Use Tool</a>
    </div>

    <!-- Add more feature boxes for future tools -->
</div>

<script src="index.js" defer></script>
</body>
</html>