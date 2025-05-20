<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /myapp/auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Processing API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4/swagger-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f7;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .header-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        h1 {
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .navigation {
            margin-bottom: 20px;
        }

        .navigation a {
            display: inline-flex;
            align-items: center;
            margin-right: 15px;
            color: #2196F3;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .navigation a:hover {
            text-decoration: underline;
            color: #0D47A1;
        }

        .navigation i {
            margin-right: 8px;
        }

        .swagger-ui {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 5px 20px;
        }

        .swagger-ui .topbar {
            background-color: #4285F4;
            border-radius: 8px 8px 0 0;
        }

        .swagger-ui .btn {
            border-radius: 4px;
        }

        .swagger-ui .opblock-tag {
            border-bottom: 1px solid #ddd;
        }

        .swagger-ui .opblock {
            margin: 16px 0;
            border-radius: 8px;
        }

        .swagger-ui .opblock-summary-method {
            border-radius: 4px;
        }

        .language-switcher {
            text-align: right;
            margin-bottom: 20px;
        }

        .language-switcher a {
            color: #2196F3;
            text-decoration: none;
            margin-left: 10px;
            transition: all 0.3s ease;
        }

        .language-switcher a:hover {
            text-decoration: underline;
            color: #0D47A1;
        }

        /* Custom override for better mobile display */
        @media (max-width: 768px) {
            .swagger-ui .wrapper {
                padding: 0;
            }

            .swagger-ui .opblock-summary-method {
                min-width: 80px;
            }
        }
    </style>
</head>
<body>
<div class="container">
        <div class="navigation">
            <a href="/myapp/index.php" id="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <div class="language-switcher">
            <a href="#" id="lang-en">English</a> | <a href="#" id="lang-sk">Slovensky</a>
        </div>

        <h1 id="title" ><i class="fas fa-book"></i> PDF Processing App API Documentation</h1>
        <p id="description">This documentation provides an overview of the PDF processing API. It is intended for registered users and ensures efficient and secure access to the application's functionality.</p>


    <div id="swagger-ui"></div>
</div>

<script src="https://unpkg.com/swagger-ui-dist@4/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@4/swagger-ui-standalone-preset.js"></script>
<script>

    const translations = {
        en: {
            backLink: 'Back to Dashboard',
            title: 'PDF Processing App API Documentation',
            description: 'This documentation provides an overview of the PDF processing API. It is intended for registered users and ensures efficient and secure access to the application\'s functionality.'
        },
        sk: {
            backLink: 'Späť na prehľad',
            title: 'Aplikácia na spracovanie PDF API Dokumentácia',
            description: 'Táto dokumentácia poskytuje prehľad o API na spracovanie PDF súborov. Je určená pre registrovaných používateľov a zabezpečuje efektívny a bezpečný prístup k funkcionalitám aplikácie.'
        }
    };

    function translatePage(lang) {
        document.getElementById('back-link').innerHTML = `<i class="fas fa-arrow-left"></i> ${translations[lang].backLink}`;
        document.getElementById('title').textContent = translations[lang].title;
        document.getElementById('description').textContent = translations[lang].description;
    }

    function loadSwaggerUI(lang) {
        const file = lang === 'en' ? '../docs/openapi_en.yaml' : '../docs/openapi.yaml';
        SwaggerUIBundle({
            url: file,
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            layout: "BaseLayout",
            supportedSubmitMethods: ['get', 'post'],
            onComplete: function () {
                const topbarElement = document.querySelector('.swagger-ui .topbar');
                if (topbarElement) {
                    topbarElement.style.display = 'none';
                }
            }
        });
    }

    window.onload = function () {
        // Default language
        loadSwaggerUI('sk');
        translatePage('sk');

        // Language switchers
        document.getElementById('lang-en').addEventListener('click', function (e) {
            e.preventDefault();
            loadSwaggerUI('en');
            translatePage('en');
        });

        document.getElementById('lang-sk').addEventListener('click', function (e) {
            e.preventDefault();
            loadSwaggerUI('sk');
            translatePage('sk');
        });
    };
</script>

</body>
</html>