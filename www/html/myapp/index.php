<?php
session_start();
if (!isset($_SESSION['username'])) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
</head>
<body>
<div class="header">
    <h1><?= $t['titleApp'] ?></h1>
    <div class="user-controls">
        <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
        <a href="/myapp/backend/auth/logout.php" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i><?= $t['logout'] ?></a>
        <div class="topbar">
            <div class="right">
                <a href="?lang=en">English</a> | <a href="?lang=sk">Slovensky</a>
            </div>
        </div>
    </div>
</div>

<div class="api-section">
    <h3><i class="fas fa-key"></i> <?= $t['api_title'] ?></h3>
    <p><?= $t['api_desc'] ?></p>
    <button id="regenApiKey" class="btn btn-secondary"><i class="fas fa-sync"></i> <?= $t['generateApiKey'] ?></button>
    <p id="message" class="hidden"></p>
    <div id="apiKeyDisplay"></div>
</div>
<?php if ($isAdmin): ?>
    <div class="admin-section">
        <h3><i class="fas fa-user-shield"></i> <?= $t['admin_panel'] ?></h3>
        <p><?= $t['admin_panel_desc'] ?></p>
        <a href="/myapp/users_history.php" class="btn btn-warning"><i class="fas fa-history"> </i> <?= $t['viewuserhistory'] ?></a>
    </div>
<?php endif; ?>


<div class="tools-section">
    <h2><i class="fas fa-tools"></i> <?= $t['tools_title'] ?></h2>
    <div class="tools-grid">
        <div class="tool-card">
            <h3><i class="fas fa-object-group"></i> <?= $t['merge'] ?></h3>
            <p><?= $t['merge_desc'] ?></p>
            <div class="btn-div">
                <a href="/myapp/frontend/merge_pdfs.php" class="btn"><i class="fas fa-link"> </i><?= $t['use_tool'] ?></a>
            </div>
        </div>

        <div class="tool-card">
            <h3><i class="fas fa-file-alt"></i> <?= $t['split'] ?></h3>
            <p><?= $t['split_desc'] ?></p>
            <div class="btn-div">
                <a href="/myapp/frontend/split_pdf.php" class="btn"><i class="fas fa-cut"></i> <?= $t['use_tool'] ?></a>
            </div>
        </div>


        <div class="tool-card">
            <h3><i class="fas fa-sync-alt"></i> <?= $t['rotate'] ?></h3>
            <p><?= $t['rotate_desc'] ?></p>
            <div class="btn-div">
                <a href="/myapp/frontend/rotate_pdf.php" class="btn"><i class="fas fa-redo"></i> <?= $t['use_tool'] ?></a>
            </div>
        </div>

        <div class="tool-card">
            <h3><i class="fas fa-trash-alt"></i> <?= $t['remove'] ?></h3>
            <p><?= $t['remove_desc'] ?></p>
            <div class="btn-div">
                <a href="/myapp/frontend/remove_pages.php" class="btn"><i class="fas fa-minus-circle"></i> <?= $t['use_tool'] ?></a>
            </div>
        </div>

        <div class="tool-card">
            <h3><i class="fas fa-sort-amount-down"></i> <?= $t['reorder'] ?></h3>
            <p><?= $t['reorder_desc'] ?></p>
            <div class="btn-div">
                <a href="/myapp/frontend/reorder_pages.php" class="btn"><i class="fas fa-exchange-alt"></i> <?= $t['use_tool'] ?></a>
            </div>
        </div>

        <div class="tool-card">
            <h3><i class="fas fa-lock"></i> <?= $t['encrypt'] ?></h3>
            <p><?= $t['encrypt_desc'] ?></p>
            <div class="btn-div">
                <a href="/myapp/frontend/encrypt_pdf.php" class="btn"><i class="fas fa-key"></i> <?= $t['use_tool'] ?></a>
            </div>
        </div>

        <div class="tool-card">
            <h3><i class="fas fa-file-export"></i> <?= $t['extract'] ?></h3>
            <p><?= $t['extract_desc'] ?></p>
            <div class="btn-div">
                <a href="/myapp/frontend/extract_pages.php" class="btn"><i class="fas fa-file-export"></i> <?= $t['use_tool'] ?></a>
            </div>
        </div>
        <div class="tool-card">
            <h3><i class="fas fa-unlock"></i> <?= $t['unlock'] ?></h3>
            <p><?= $t['unlock_desc'] ?></p>
            <div class="btn-div">
                <a href="/myapp/frontend/decrypt_pdf.php" class="btn"><i class="fas fa-unlock-alt"></i> <?= $t['use_tool'] ?></a>
            </div>
        </div>

        <div class="tool-card">
            <h3><i class="fas fa-stamp"></i> <?= $t['watermark'] ?></h3>
            <p><?= $t['watermark_desc'] ?></p>
            <div class="btn-div">
                <a href="/myapp/frontend/watermark_pdf.php" class="btn"><i class="fas fa-water"></i> <?= $t['use_tool'] ?></a>
            </div>
        </div>

        <div class="tool-card">
            <h3><i class="fas fa-balance-scale"></i> <?= $t['compare'] ?></h3>
            <p><?= $t['compare_desc'] ?></p>
            <div class="btn-div">
                <a href="/myapp/frontend/compare_pdfs.php" class="btn"><i class="fas fa-not-equal"></i> <?= $t['use_tool'] ?></a>
            </div>
        </div>


    </div>
</div>

<div class="documentation-link">
    <a href="dynamicManual.php" class="btn btn-secondary">
        <i class="fas fa-book"></i> <?= $t['manual'] ?>
    </a>
    <a href="/myapp/frontend/api-docs.php" class="btn btn-secondary">
        <i class="fas fa-book"></i> <?= $t['OpenAPI'] ?>
    </a>
</div>

<div class="footer">
    <p>&copy; <?= date('Y') ?> <?= $t['footer'] ?></p>
</div>

<script>
    document.getElementById('regenApiKey').addEventListener('click', async () => {
        const msg = document.getElementById('message');
        const apiKeyDisplay = document.getElementById('apiKeyDisplay');

        if (!msg || !apiKeyDisplay) {
            console.error("Required elements not found");
            return;
        }

        msg.textContent = 'Regenerating API key...';
        msg.style.color = 'orange';
        apiKeyDisplay.style.display = 'none';

        try {
            const response = await fetch('/myapp/backend/regenerate_api_key.php', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Accept': 'application/json' }
            });

            const contentLength = response.headers.get('Content-Length');
            if (contentLength === '0') {
                console.error("empty response");
            }

            let data;
            const contentType = response.headers.get('Content-Type');

            if (response.ok && contentType?.includes('application/json')) {
                data = await response.json();
                if (data?.success && data?.api_key) {
                    msg.style.color = 'green';
                    msg.textContent = 'API key successfully regenerated.';
                    apiKeyDisplay.style.display = 'block';
                    apiKeyDisplay.textContent = `Your new API key is: ${data.api_key}`;
                } else {
                    throw new Error(data?.error || 'Failed to regenerate API key');
                }
            } else {
                const errorText = await response.text();
                console.error('Server error:', errorText);
                throw new Error(`Server error: ${response.status} - ${errorText}`);
            }

        } catch (error) {
            console.error('Error:', error);
            msg.style.color = 'red';
            msg.textContent = error.message || 'Error communicating with server.';
            apiKeyDisplay.style.display = 'none';
        }
    });

</script>
</body>
</html>