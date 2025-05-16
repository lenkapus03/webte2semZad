<?php
session_start();
if (!isset($_SESSION['username'])) {
    // Use an absolute path starting with / to ensure proper redirection
    header("Location: /myapp/auth/login.php");
    exit;
}
$langCode = $_GET['lang'] ?? $_SESSION['lang'] ?? 'sk';
$_SESSION['lang'] = $langCode;
/** @var array $lang */
require_once __DIR__ . '/lang.php';
$t = $lang[$langCode] ?? $lang['sk'];

$isAdmin = $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>PDF_app</title>
    <?php if (!isset($isPdf)): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.95em;
        }

        .topbar a {
            color: #1a73e8;
            text-decoration: none;
        }

        .topbar a:hover {
            text-decoration: underline;
        }
        :root {
            --primary-color: #4285F4;
            --secondary-color: #34A853;
            --danger-color: #EA4335;
            --warning-color: #FBBC05;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f7;
            color: #333;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .header h1 {
            margin: 0;
            color: var(--primary-color);
            font-size: 2.2em;
        }

        .user-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--box-shadow);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            margin: 0;
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: #333;
        }

        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .tools-section {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin: 30px 0;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--primary-color);
        }

        .tools-section h2 {
            margin-top: 0;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .tool-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .tool-card h3 {
            color: var(--dark-color);
            margin-top: 0;
            font-size: 1.5em;
            display: flex;
            align-items: center;
        }

        .tool-card h3 i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .tool-card p {
            color: #666;
            margin-bottom: 20px;
            min-height: 50px;
        }

        .tool-card .btn-div {
            text-align: center;
        }
        .tool-card .btn {
            width: 80%;
            margin: auto;
            box-sizing: border-box;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .admin-section {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin: 30px 0;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--warning-color);
        }

        .admin-section h3 {
            margin-top: 0;
            color: var(--dark-color);
        }

        .api-section {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin: 10px 0;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--secondary-color);
        }

        .api-section h3 {
            margin-top: 0;
            color: var(--dark-color);
        }

        #apiKeyDisplay {
            background-color: var(--light-color);
            padding: 0;
            border-radius: var(--border-radius);
            margin: 10px 0;
            font-family: monospace;
            word-break: break-all;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: var(--border-radius);
            animation: fadeIn 0.5s;
        }

        .success {
            background-color: rgba(52, 168, 83, 0.1);
            color: var(--secondary-color);
            border: 1px solid rgba(52, 168, 83, 0.2);
        }

        .error {
            background-color: rgba(234, 67, 53, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(234, 67, 53, 0.2);
        }

        .info {
            background-color: rgba(66, 133, 244, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(66, 133, 244, 0.2);
        }

        .hidden {
            display: none;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            color: #666;
            font-size: 0.9em;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .documentation-link {
            display: inline-block;
            margin-top: 30px;
            text-align: center;
            width: 100%;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .user-controls {
                margin-top: 15px;
            }

            .tools-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php endif; ?>
</head>
<body>
<?php if (!isset($isPdf)): ?>
<div class="topbar">
    <div class="left">
        <a href="index.php">&larr; <?= $t['back_dashboard']?></a>
    </div>
    <div class="right">
        <a href="?lang=en">English</a> | <a href="?lang=sk">Slovensky</a>
    </div>
</div>
<?php endif; ?>

<div class="header">
    <h1><?= $t['title'] ?></h1>

</div>


<div class="api-section">
<h3><i class="fas fa-key"></i> <?= $t['api_title'] ?></h3>
<p><?= $t['api_desc'] ?></p>
</div>

<?php if ($isAdmin): ?>
    <div class="admin-section">
        <h3><i class="fas fa-user-shield"></i> <?= $t['admin_panel'] ?></h3>
        <p><?= $t['admin_panel_desc'] ?></p>
    </div>
<?php endif; ?>



<div class="tools-section">
    <h2><i class="fas fa-tools"></i> <?= $t['tools_title'] ?></h2>
    <div class="tools-grid">
        <div class="tool-card">
            <h3><i class="fas fa-object-group"></i> <?= $t['merge'] ?></h3>
            <p><?= $t['merge_desc'] ?></p>
        </div>
        <div class="tool-card">
            <h3><i class="fas fa-file-alt"></i> <?= $t['split'] ?></h3>
            <p><?= $t['split_desc'] ?></p>
        </div>
        <div class="tool-card">
            <h3><i class="fas fa-sync-alt"></i> <?= $t['rotate'] ?></h3>
            <p><?= $t['rotate_desc'] ?></p>
        </div>
        <div class="tool-card">
            <h3><i class="fas fa-trash-alt"></i> <?= $t['remove'] ?></h3>
            <p><?= $t['remove_desc'] ?></p>
        </div>
        <div class="tool-card">
            <h3><i class="fas fa-sort-amount-down"></i> <?= $t['reorder'] ?></h3>
            <p><?= $t['reorder_desc'] ?></p>
        </div>
        <div class="tool-card">
            <h3><i class="fas fa-lock"></i> <?= $t['encrypt'] ?></h3>
            <p><?= $t['encrypt_desc'] ?></p>
        </div>
        <div class="tool-card">
            <h3><i class="fas fa-file-export"></i> <?= $t['extract'] ?></h3>
            <p><?= $t['extract_desc'] ?></p>
        </div>
        <div class="tool-card">
            <h3><i class="fas fa-unlock"></i> <?= $t['unlock'] ?></h3>
            <p><?= $t['unlock_desc'] ?></p>
        </div>
        <div class="tool-card">
            <h3><i class="fas fa-stamp"></i> <?= $t['watermark'] ?></h3>
            <p><?= $t['watermark_desc'] ?></p>

        </div>
    </div>
</div>
<?php if (!isset($isPdf)): ?>
    <div class="documentation-link">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-book"></i> <?= $t['back'] ?>
        </a>
        <a href="domPdfScript.php" class="btn btn-primary">
            <i class="fas fa-download"></i> <?= $t['download_manual'] ?>
        </a>
    </div>
<?php endif; ?>
</body>
</html>