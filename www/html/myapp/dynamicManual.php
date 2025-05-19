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

        <h2><i class="fas fa-key"></i> <?= $langCode === 'en' ? 'API Key Access' : 'Prístup k API' ?></h2>

        <p>
            <?= $langCode === 'en'
                ? 'This application allows registered users to access PDF tools programmatically through a secure API. Only registered users can access the backend via API, authenticated using a personal API key.'
                : 'Táto aplikácia umožňuje zaregistrovaným používateľom pristupovať k PDF nástrojom programovo prostredníctvom zabezpečeného API. Backend cez API môže používať iba zaregistrovaný používateľ, ktorý je overený pomocou svojho osobného API kľúča.'
            ?>
        </p>

        <p>
            <?= $langCode === 'en'
                ? 'The API key or token is generated within the user interface and can be regenerated at any time.'
                : 'API kľúč alebo token sa generuje priamo v používateľskom rozhraní a je možné ho kedykoľvek pregenerovať.'
            ?>
        </p>

        <p>
            <?= $langCode === 'en'
                ? 'The frontend part of the application is accessible only to logged-in users.'
                : 'Frontend časť aplikácie je prístupná iba pre prihlásených používateľov.'
            ?>
        </p>

        <h3><i class="fas fa-cogs"></i> <?= $langCode === 'en' ? 'API Key Generation and Use' : 'Generovanie a použitie API kľúča' ?></h3>

        <p>
            <?= $langCode === 'en'
                ? 'After logging in, the user can generate a new API key by clicking the green <strong>Generate New API Key</strong> button. The generated key is displayed immediately and can be used in API requests to authorize access to PDF processing tools.'
                : 'Po prihlásení si môže používateľ vygenerovať nový API kľúč kliknutím na zelené tlačidlo <strong>Generuj Nový API Kľúč</strong>. Vygenerovaný kľúč sa okamžite zobrazí a môže sa používať v API požiadavkách na autorizovaný prístup k PDF nástrojom.'
            ?>
        </p>

        <p>
            <?= $langCode === 'en'
                ? 'Each user can have only one active API key at a time. Regenerating the key automatically invalidates the previous one.'
                : 'Každý používateľ môže mať aktívny vždy len jeden API kľúč. Jeho pregenerovanie automaticky zneplatní predchádzajúci kľúč.'
            ?>
        </p>
    </section>

</div>

<?php if ($isAdmin): ?>
    <div class="admin-section">
        <h3><i class="fas fa-user-shield"></i> <?= $t['admin_panel'] ?></h3>
        <p><?= $t['admin_panel_desc'] ?></p>
        <section class="user-history">
            <h2><i class="fas fa-history"></i>
                <?= $langCode === 'en' ? 'User History' : 'História Používania' ?>
            </h2>

            <p>
                <?= $langCode === 'en'
                    ? 'The application automatically stores a history of all user actions. It allows the administrator to track who performed what action, when, from where (city and country), and how (via frontend or API).'
                    : 'Aplikácia automaticky ukladá históriu všetkých používateľských akcií. Táto funkcia umožňuje administrátorom sledovať, kto vykonal akciu, kedy k nej došlo, akú akciu vykonal, odkiaľ sa prihlásil (mesto a štát) a akým spôsobom bola akcia spustená (cez frontend alebo API).'
                ?>
            </p>

            <h3><i class="fas fa-info-circle"></i>
                <?= $langCode === 'en' ? 'Tracked Information' : 'Uchovávané údaje' ?>
            </h3>
            <ul>
                <?php if ($langCode === 'en'): ?>
                    <li>Username – the name of the logged-in user.</li>
                    <li>Time – exact date and time of the action.</li>
                    <li>Action – type of operation performed (e.g., login, logout, merge_pdf, regenerate_api_key, etc.).</li>
                    <li>Source – how the action was triggered (e.g., frontend or api).</li>
                    <li>Location – login location (city and country), derived from the user’s IP address.</li>
                <?php else: ?>
                    <li>Používateľ – meno prihláseného používateľa.</li>
                    <li>Čas – presný dátum a čas vykonanej akcie.</li>
                    <li>Akcia – typ vykonanej operácie (napr. login, logout, merge_pdf, regenerate_api_key a pod.).</li>
                    <li>Zdroj – spôsob použitia (napr. frontend alebo api).</li>
                    <li>Lokalita – miesto prihlásenia používateľa (mesto a štát), odvodené podľa IP adresy.</li>
                <?php endif; ?>
            </ul>

            <h3><i class="fas fa-user-shield"></i>
                <?= $langCode === 'en' ? 'Administrator Features' : 'Funkcie pre administrátora' ?>
            </h3>
            <p>
                <?= $langCode === 'en'
                    ? 'The administrator has access to the full usage history and can perform the following actions:'
                    : 'Administrátor má prístup k celej histórii použitia a môže vykonávať nasledujúce operácie:'
                ?>
            </p>
            <ul>
                <?php if ($langCode === 'en'): ?>
                    <li><i class="fas fa-file-csv"></i> Export to CSV – allows exporting all records to a CSV file by clicking the green button.</li>
                    <li><i class="fas fa-trash-alt text-danger"></i> Delete Entry – each record can be deleted individually using the red trash icon button.</li>
                <?php else: ?>
                    <li><i class="fas fa-file-csv"></i> Export do CSV – možnosť exportovať všetky záznamy do CSV súboru kliknutím na zelené tlačidlo.</li>
                    <li><i class="fas fa-trash-alt text-danger"></i> Odstránenie záznamu – každý záznam je možné jednotlivo odstrániť pomocou červeného tlačidla s ikonou koša.</li>
                <?php endif; ?>
            </ul>
    </div>
<?php endif; ?>



<div class="tools-section">
    <h2><i class="fas fa-tools"></i> <?= $t['tools_title'] ?></h2>

        <h2><i class="fas fa-tools"></i> <?= $langCode === 'en' ? 'PDF Tools' : 'PDF Nástroje' ?></h2>

        <p>
            <?= $langCode === 'en'
                ? 'The application provides users with a complete set of PDF tools that allow them to process uploaded PDF files directly in the system. All tools are available via a user-friendly interface with drag & drop support where applicable.'
                : 'Aplikácia poskytuje používateľovi kompletnú sadu PDF nástrojov, ktoré mu umožňujú spracovať ním uploadnuté PDF súbory priamo v systéme. Všetky nástroje sú dostupné cez prehľadné používateľské rozhranie s podporou drag & drop.'
            ?>
        </p>
    <h3><i class="fas fa-book"></i> <?= $langCode === 'en' ? 'OpenAPI Documentation' : 'OpenAPI Dokumentácia' ?></h3>

    <p>
        <?= $langCode === 'en'
            ? 'The application offers full OpenAPI 3.0 (Swagger) documentation. It describes all available endpoints, required parameters, response formats, and authentication methods. The documentation is accessible via the "OpenAPI Documentation" button at the bottom of the main page. It also allows try out individual tools and test the web service directly in the browser.'
            : 'Aplikácia ponúka k dispozícii dokumentáciu podľa špecifikácie OpenAPI 3.0 (Swagger). Popisuje všetky dostupné API endpointy, požadované parametre, formáty odpovedí a metódy autentifikácie.  Dokumentácia je dostupná cez tlačidlo "OpenAPI Dokumentácia" v spodnej časti hlavnej stránky. Umožňuje tiež priamo v prehliadači vyskúšať jednotlivé nástroje a otestovať webovú službu.'
        ?>
    </p>


    <div class="tools-grid">

        <div class="tool-card">
            <?php if (!isset($isPdf)): ?>
            <h3><i class="fas fa-object-group"></i> <?= $t['merge'] ?></h3>
            <p><?= $t['merge_desc'] ?></p>
            <?php endif; ?>
            <h3> <?= $langCode === 'en' ? 'Merge PDF Files' : 'Spájanie PDF súborov' ?></h3>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool allows users to merge multiple PDF files into one. The merging process will preserve the order of the files as selected by the user.'
                    : 'Tento nástroj umožňuje používateľovi spojiť viacero PDF súborov do jedného. Poradie súborov je zachované podľa poradia, v akom si ich používateľ vybral.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'Users can upload files by dragging them into the drop area or by clicking the "Select Files" button. Each added file is shown in a list with the ability to reorder or remove it before merging.'
                    : 'Súbory je možné nahrať potiahnutím do označenej zóny alebo kliknutím na tlačidlo „Vybrať súbory“. Každý súbor sa zobrazí v zozname, kde ho možno presúvať alebo odstrániť pred spojením.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'The merge process starts by clicking the "Merge PDF Files" button. The result is generated and available for download once completed.'
                    : 'Proces spájania sa spustí kliknutím na tlačidlo „Spojiť PDF súbory“. Výsledok sa vygeneruje a po dokončení je dostupný na stiahnutie.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'The tool supports drag & drop file reordering and automatically disables the merge button until at least two PDF files are selected.'
                    : 'Nástroj podporuje presúvanie súborov pomocou drag & drop a automaticky deaktivuje tlačidlo spájania, pokiaľ nie sú vybrané aspoň dva PDF súbory.'
                ?>
            </p>
        </div>
        <div class="tool-card">
            <?php if (!isset($isPdf)): ?>
            <h3><i class="fas fa-file-alt"></i> <?= $t['split'] ?></h3>
            <p><?= $t['split_desc'] ?></p>
            <?php endif; ?>
            <h3> <?= $langCode === 'en' ? 'Split PDF File' : 'Rozdelenie PDF súboru' ?></h3>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool allows users to split a PDF file into individual pages. It is ideal for separating documents or removing blank pages.'
                    : 'Tento nástroj umožňuje používateľovi rozdeliť PDF súbor na jednotlivé strany. Je vhodný napríklad na oddelenie dokumentov alebo odstránenie prázdnych strán.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'The user uploads a single PDF file using drag & drop or the "Select File" button. The file is shown in the list and can be removed before proceeding.'
                    : 'Používateľ nahrá jeden PDF súbor potiahnutím do označenej oblasti alebo kliknutím na tlačidlo „Vybrať súbor“. Súbor sa zobrazí v zozname a môže byť pred spracovaním odstránený.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'By clicking the "Split PDF File" button, the server processes the file and returns a ZIP archive containing the separated pages.'
                    : 'Kliknutím na tlačidlo „Rozdeliť PDF súbor“ sa súbor spracuje na serveri a výsledkom je ZIP archív obsahujúci jednotlivé strany.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'The application disables the split button until a valid PDF file is selected and provides immediate download of the result upon success.'
                    : 'Aplikácia deaktivuje tlačidlo rozdelenia, kým nie je vybraný platný PDF súbor, a po úspešnom rozdelení automaticky sprístupní výsledok na stiahnutie.'
                ?>
            </p>
        </div>
        <div class="tool-card">
            <?php if (!isset($isPdf)): ?>
            <h3><i class="fas fa-sync-alt"></i> <?= $t['rotate'] ?></h3>
            <p><?= $t['rotate_desc'] ?></p>
            <?php endif; ?>
            <h3> <?= $langCode === 'en' ? 'Rotate PDF Pages' : 'Rotácia PDF strán' ?></h3>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool provides an intuitive way to rotate individual or all pages in a PDF file. The user can rotate pages by 90°, 180°, or 270°, preview changes in real-time, and download the rotated document.'
                    : 'Tento nástroj ponúka používateľsky prívetivý spôsob rotácie jednotlivých alebo všetkých strán v PDF súbore. Používateľ môže otáčať stránky o 90°, 180° alebo 270°, zmeny priamo vidieť a stiahnuť upravený dokument.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'After uploading a PDF file, a preview is displayed. Each page can be rotated individually using rotate buttons. A batch rotate option ( 90°) is also available.'
                    : 'Po nahratí PDF súboru sa zobrazí náhľad dokumentu. Každú stranu možno otočiť jednotlivo pomocou rotačných tlačidiel. K dispozícii je aj hromadná rotácia ( 90°).'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'When ready, clicking "Apply Changes" sends the rotation data to the server, which returns a downloadable, modified PDF file.'
                    : 'Po vykonaní úprav klikne používateľ na tlačidlo „Aplikovať zmeny“, čím sa zmeny odošlú na server a výsledkom je upravený PDF dokument pripravený na stiahnutie.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool supports pagination and shows three pages at once for better usability, with arrows to navigate.'
                    : 'Nástroj podporuje stránkovanie a zobrazuje tri stránky súčasne, pričom používateľ môže medzi nimi prepínať pomocou šípok.'
                ?>
            </p>
        </div>
        <div class="tool-card">
            <?php if (!isset($isPdf)): ?>
            <h3><i class="fas fa-trash-alt"></i> <?= $t['remove'] ?></h3>
            <p><?= $t['remove_desc'] ?></p>
            <?php endif; ?>
            <h3> <?= $langCode === 'en' ? 'Remove PDF Pages' : 'Odstránenie strán z PDF' ?></h3>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool allows you to remove one or more specific pages from a PDF file. Users can preview all pages, mark pages for deletion, and then download the modified document.'
                    : 'Tento nástroj umožňuje odstrániť jednu alebo viaceré konkrétne stránky z PDF súboru. Používateľ si môže všetky stránky prezrieť, označiť ich na odstránenie a následne si stiahnuť upravený dokument.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'After uploading a PDF, the pages are displayed in a paginated view. You can remove a page using the "Remove Page" button or reverse the action with "Keep Page". A red X badge marks pages selected for removal.'
                    : 'Po nahratí PDF sa stránky zobrazia v stránkovanom náhľade. Stránku možno odstrániť tlačidlom „Odstrániť stránku“ alebo opätovným kliknutím ponechať pomocou „Ponechať stránku“. Vybrané stránky sú označené červeným krížikom.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'You can use "Select All Pages" and "Deselect All" to quickly manage selections. A summary box shows how many pages are selected out of the total.'
                    : 'Pre rýchlejší výber môžete použiť „Vybrať všetky stránky“ alebo „Zrušiť výber“. Súhrnný box zobrazuje počet vybraných strán z celkového počtu.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'After clicking "Remove Selected Pages", the application processes the file and offers a download link to the modified PDF.'
                    : 'Po kliknutí na „Odstrániť vybrané stránky“ sa súbor spracuje a ponúkne sa odkaz na stiahnutie upraveného PDF.'
                ?>
            </p>
        </div>
        <div class="tool-card">
            <?php if (!isset($isPdf)): ?>
            <h3><i class="fas fa-sort-amount-down"></i> <?= $t['reorder'] ?></h3>
            <p><?= $t['reorder_desc'] ?></p>
            <?php endif; ?>
            <h3> <?= $langCode === 'en' ? 'Reorder PDF Pages' : 'Zmena poradia strán PDF' ?></h3>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool allows users to change the order of pages in a PDF document using drag-and-drop.'
                    : 'Tento nástroj umožňuje používateľom zmeniť poradie strán v PDF dokumente pomocou funkcie drag-and-drop.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'After uploading a PDF file, the pages are displayed as thumbnails. Users can grab and drag pages to a new position. Each thumbnail shows its current position as well as its original page number in a yellow badge for reference.'
                    : 'Po nahratí PDF súboru sa zobrazia náhľady strán. Používateľ môže uchopiť a presunúť jednotlivé stránky na nové miesto. Každý náhľad zobrazuje aktuálnu pozíciu aj pôvodné číslo stránky (označené žltou).'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'If needed, the original page order can be restored by clicking "Reset Order". Once the new order is set, click "Apply Page Reordering" to generate the new PDF.'
                    : 'V prípade potreby možno pôvodné poradie obnoviť kliknutím na „Obnoviť poradie“. Po nastavení nového poradia kliknite na „Použiť preusporiadanie“ pre vytvorenie nového PDF súboru.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'The final reordered PDF is available for download after processing is complete.'
                    : 'Po dokončení spracovania je upravený PDF dokument dostupný na stiahnutie.'
                ?>
            </p>
        </div>
        <div class="tool-card">
            <?php if (!isset($isPdf)): ?>
            <h3><i class="fas fa-lock"></i> <?= $t['encrypt'] ?></h3>
            <p><?= $t['encrypt_desc'] ?></p>
            <?php endif; ?>
            <h3><?= $langCode === 'en' ? 'Password Protect PDF' : 'Zabezpečenie PDF heslom' ?></h3>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool allows you to protect a PDF document by setting a password. The password will be required to open and view the PDF.'
                    : 'Tento nástroj umožňuje zabezpečiť PDF dokument nastavením hesla. Heslo bude potrebné na otvorenie a prezeranie dokumentu.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'After uploading the PDF file, you can enter the desired password. You can show or hide the entered password using the eye icon.'
                    : 'Po nahratí PDF súboru môžete zadať požadované heslo. Zadané heslo je možné zobraziť alebo skryť pomocou ikony oka.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'When ready, click "Protect PDF" to apply the password protection. The secured file will be generated and made available for download.'
                    : 'Po zadaní hesla kliknite na „Zabezpečiť PDF“ a ochrana bude aplikovaná. Vznikne nový zabezpečený PDF súbor, ktorý si môžete stiahnuť.'
                ?>
            </p>
        </div>
        <div class="tool-card">
            <?php if (!isset($isPdf)): ?>
            <h3><i class="fas fa-file-export"></i> <?= $t['extract'] ?></h3>
            <p><?= $t['extract_desc'] ?></p>
            <?php endif; ?>
            <h3> <?= $langCode === 'en' ? 'Extract PDF Pages' : 'Extrahovanie PDF strán' ?></h3>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool allows you to extract only selected pages from a PDF document. The resulting PDF will contain only the pages you choose to keep.'
                    : 'Tento nástroj umožňuje extrahovať len vybrané stránky z PDF dokumentu. Výsledný PDF bude obsahovať iba stránky, ktoré ste si zvolili ponechať.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'After uploading a PDF file, you can navigate through pages, preview them, and select those you wish to extract. You can use the "Select All" or "Deselect All" buttons for convenience.'
                    : 'Po nahratí PDF súboru môžete prehliadať jednotlivé strany, prezerať ich a vyberať tie, ktoré chcete extrahovať. Pre uľahčenie môžete použiť tlačidlá „Vybrať všetky“ alebo „Zrušiť výber“.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'Once you have selected the pages, click "Extract Selected Pages" to create a new PDF file containing only those pages.'
                    : 'Po výbere požadovaných strán kliknite na „Extrahovať vybrané stránky“ a vygeneruje sa nový PDF súbor len s týmito stránkami.'
                ?>
            </p>
        </div>
        <div class="tool-card">
            <?php if (!isset($isPdf)): ?>
            <h3><i class="fas fa-unlock"></i> <?= $t['unlock'] ?></h3>
            <p><?= $t['unlock_desc'] ?></p>
            <?php endif; ?>
            <h3><?= $langCode === 'en' ? 'Unlock PDF' : 'Odomknutie PDF' ?></h3>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool allows you to remove password protection from a PDF file, provided you know the current password.'
                    : 'Tento nástroj umožňuje odstrániť ochranu PDF súboru heslom, ak poznáte aktuálne heslo.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'After uploading a locked PDF file, enter the correct password to unlock the document. Once verified, the password will be removed, and you can download an unprotected version of the file.'
                    : 'Po nahratí uzamknutého PDF súboru zadajte správne heslo. Po jeho overení bude z dokumentu odstránená ochrana heslom a môžete si stiahnuť jeho voľne dostupnú verziu.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'Note: This tool cannot be used to bypass password protection without knowing the correct password.'
                    : 'Poznámka: Tento nástroj neslúži na obchádzanie ochrany – heslo musíte poznať.'
                ?>
            </p>
        </div>
        <div class="tool-card">
            <?php if (!isset($isPdf)): ?>
            <h3><i class="fas fa-stamp"></i> <?= $t['watermark'] ?></h3>
            <p><?= $t['watermark_desc'] ?></p>
            <?php endif; ?>
            <h3> <?= $langCode === 'en' ? 'Watermark PDF File' : 'Pridať vodoznak do PDF' ?></h3>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool allows you to apply a custom text watermark to every page of a PDF file. You can adjust the position, opacity, color, font size, and rotation angle of the watermark.'
                    : 'Tento nástroj umožňuje pridať vlastný textový vodoznak na každú stranu PDF súboru. Môžete upraviť jeho pozíciu, priehľadnosť, farbu, veľkosť písma a uhol otočenia.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'Once a file is uploaded, enter the desired watermark text and customize its appearance. Then click the button to apply the watermark.'
                    : 'Po nahratí súboru zadajte text vodoznaku a upravte jeho vzhľad podľa potreby. Potom kliknite na tlačidlo pre jeho aplikovanie.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'The resulting PDF with the watermark will be available for download immediately.'
                    : 'Výsledný PDF súbor s vodoznakom bude ihneď dostupný na stiahnutie.'
                ?>
            </p>

        </div>
        <div class="tool-card">
            <?php if (!isset($isPdf)): ?>
                <h3><i class="fas fa-balance-scale"></i> <?= $t['compare'] ?></h3>
                <p><?= $t['compare_desc'] ?></p>
            <?php endif; ?>
            <h3> <?= $langCode === 'en' ? 'Compare PDF Files' : 'Porovnať PDF súbory' ?></h3>

            <p>
                <?= $langCode === 'en'
                    ? 'This tool allows you to compare two PDF files in number of pages, contenf of text, pictures and metadata.'
                    : 'Tento nástroj umožňuje porovnávať dva PDF súbory v počte ich strán, obsahu textu, obrázkov a metadát.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'Once the files are uploaded, click the button to compare the files and view the summary of differences.'
                    : 'Po nahratí súborov stlačte tlačítko na porovnanie a prezrite si súhrn rozdielov medzi súbormi.'
                ?>
            </p>

            <p>
                <?= $langCode === 'en'
                    ? 'The resulting PDF with the report of files differences will be available for download immediately.'
                    : 'Výsledný PDF súbor so záznamom rozdielov bude ihneď dostupný na stiahnutie.'
                ?>
            </p>

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

<div class="footer">
    <p>&copy; <?= date('Y') ?> <?= $t['footer'] ?></p>
</div>
<?php endif; ?>
</body>
</html>