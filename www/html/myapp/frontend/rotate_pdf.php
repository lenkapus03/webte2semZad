<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /myapp/auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rotate PDF Pages</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1000px;
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
        .controls-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .pagination {
            display: flex;
            align-items: center;
        }
        .pagination button {
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pagination-text {
            margin: 0 15px;
            font-size: 16px;
            font-weight: bold;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .rotate-all-btn {
            background-color: #2196F3;
        }
        .apply-btn {
            background-color: #4CAF50;
        }

        /* PDF Viewer styles */
        .pdf-viewer {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .page-container {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            min-height: 300px;
        }
        .page-container.rotated-90 .page-content {
            transform: rotate(90deg);
        }
        .page-container.rotated-180 .page-content {
            transform: rotate(180deg);
        }
        .page-container.rotated-270 .page-content {
            transform: rotate(270deg);
        }
        .page-content {
            width: 100%;
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            transition: transform 0.3s ease;
        }
        .page-preview {
            max-width: 100%;
            max-height: 250px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: white;
        }
        .rotation-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0,0,0,0.7);
            color: white;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            z-index: 10;
        }
        .page-number {
            font-weight: bold;
            margin: 15px 0;
            font-size: 16px;
        }
        .page-tools {
            display: flex;
            gap: 10px;
            margin-top: 5px;
        }
        .rotate-btn {
            background-color: #FF9800;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .rotate-btn:hover {
            background-color: #F57C00;
        }
        @media (max-width: 900px) {
            .pdf-viewer {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .pdf-viewer {
                grid-template-columns: 1fr;
            }
            .controls-container {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
<div class="navigation">
    <a id="back" href="/myapp/index.php">← Back to Dashboard</a>
</div>

<div class="language-switcher">
    <a href="#" id="lang-en">English</a> | <a href="#" id="lang-sk">Slovensky</a>
</div>

<h1 id="title">Rotate PDF Pages</h1>

<div class="dropzone" id="dropzone">
    <p id="dropText">Drag and drop a PDF file here, or click to select file</p>
    <input type="file" id="fileInput" accept=".pdf">
    <button class="btn btn-secondary" id="selectFileBtn">Select File</button>
</div>

<ul id="fileList"></ul>

<div id="viewerContainer" class="hidden">
    <div class="controls-container">
        <div class="pagination">
            <button id="prevPage">←</button>
            <span class="pagination-text" id="pageIndicator">Page 1-3 of 3</span>
            <button id="nextPage">→</button>
        </div>
        <div class="action-buttons">
            <button class="btn rotate-all-btn" id="rotateAllBtn">Rotate All 90° CW</button>
            <button class="btn apply-btn" id="applyBtn">Apply Changes</button>
        </div>
    </div>

    <div id="pdfViewer" class="pdf-viewer">
        <!-- Page previews will be dynamically inserted here -->
    </div>
</div>

<div id="messageContainer" class="hidden"></div>
<div id="resultContainer" class="hidden">
    <a href="#" id="downloadLink" class="btn btn-secondary" target="_blank">Download Rotated PDF</a>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
<script>
    // Initialize the PDF.js worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

    document.addEventListener('DOMContentLoaded', function() {
        // Translation object
        const translations = {
            'en': {
                'title': 'Rotate PDF Pages',
                'dropText': 'Drag and drop a PDF file here, or click to select file',
                'selectFile': 'Select File',
                'rotateAllBtn': 'Rotate All 90° CW',
                'applyBtn': 'Apply Changes',
                'downloadLink': 'Download Rotated PDF',
                'rotating': 'Rotating PDF pages...',
                'success': 'PDF pages were successfully rotated!',
                'errorNoFile': 'Please select a PDF file to rotate.',
                'errorUpload': 'Error uploading file: ',
                'errorRotate': 'Error rotating PDF file: ',
                'remove': 'Remove',
                'page': 'Page',
                'of': 'of',
                'back': '← Back to Dashboard'

            },
            'sk': {
                'title': 'Rotácia PDF stránok',
                'dropText': 'Pretiahnite PDF súbor sem, alebo kliknite pre výber súboru',
                'selectFile': 'Vybrať súbor',
                'rotateAllBtn': 'Otočiť všetky o 90° v smere HR',
                'applyBtn': 'Aplikovať zmeny',
                'downloadLink': 'Stiahnuť rotované PDF',
                'rotating': 'Rotácia PDF stránok...',
                'success': 'PDF stránky boli úspešne rotované!',
                'errorNoFile': 'Vyberte PDF súbor na rotáciu.',
                'errorUpload': 'Chyba pri nahrávaní súboru: ',
                'errorRotate': 'Chyba pri rotácii PDF súboru: ',
                'remove': 'Odstrániť',
                'page': 'Strana',
                'of': 'z',
                'back': '← Späť na prehľad'
            }
        };

        // Set default language
        let currentLang = 'en';

        // DOM elements
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        const selectFileBtn = document.getElementById('selectFileBtn');
        const fileList = document.getElementById('fileList');
        const viewerContainer = document.getElementById('viewerContainer');
        const pdfViewer = document.getElementById('pdfViewer');
        const rotateAllBtn = document.getElementById('rotateAllBtn');
        const applyBtn = document.getElementById('applyBtn');
        const prevPage = document.getElementById('prevPage');
        const nextPage = document.getElementById('nextPage');
        const pageIndicator = document.getElementById('pageIndicator');
        const messageContainer = document.getElementById('messageContainer');
        const resultContainer = document.getElementById('resultContainer');
        const downloadLink = document.getElementById('downloadLink');
        const title = document.getElementById('title');
        const dropText = document.getElementById('dropText');
        const langEn = document.getElementById('lang-en');
        const langSk = document.getElementById('lang-sk');
        const back = document.getElementById('back');

        // PDF viewer variables
        let pdfDoc = null;
        let currentPage = 1;
        let pagesPerView = 3;
        let pageRotations = [];

        // Selected file
        let selectedFile = null;

        // Language switcher event listeners
        langEn.addEventListener('click', function(e) {
            e.preventDefault();
            setLanguage('en');
        });

        langSk.addEventListener('click', function(e) {
            e.preventDefault();
            setLanguage('sk');
        });

        // Function to set the language
        function setLanguage(lang) {
            currentLang = lang;
            title.textContent = translations[lang].title;
            dropText.textContent = translations[lang].dropText;
            selectFileBtn.textContent = translations[lang].selectFile;
            rotateAllBtn.textContent = translations[lang].rotateAllBtn;
            applyBtn.textContent = translations[lang].applyBtn;
            downloadLink.textContent = translations[lang].downloadLink;
            back.textContent = translations[lang].back;

            // Update page indicator
            updatePageIndicator();

            // Update remove buttons
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.textContent = translations[lang].remove;
            });

            // Update page numbers
            document.querySelectorAll('.page-number').forEach((el, index) => {
                el.textContent = `${translations[lang].page} ${index + currentPage}`;
            });
        }

        // Update page indicator text
        function updatePageIndicator() {
            if (pdfDoc) {
                const lastPage = Math.min(currentPage + pagesPerView - 1, pdfDoc.numPages);
                pageIndicator.textContent = `${translations[currentLang].page} ${currentPage}-${lastPage} ${translations[currentLang].of} ${pdfDoc.numPages}`;
            }
        }

        // Drag and drop events
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('active');
        });

        dropzone.addEventListener('dragleave', function() {
            dropzone.classList.remove('active');
        });

        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('active');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });

        // Click events for file selection
        dropzone.addEventListener('click', function(e) {
            if (e.target !== selectFileBtn && !selectFileBtn.contains(e.target)) {
                fileInput.click();
            }
        });

        selectFileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                handleFile(this.files[0]);
                this.value = ''; // Reset to allow selecting the same file again
            }
        });

        // Handle the selected file
        function handleFile(file) {
            // Check if file is a PDF
            if (file.type !== 'application/pdf') {
                showMessage('error', `File "${file.name}" is not a PDF.`);
                return;
            }

            // Clear previous file
            selectedFile = file;
            fileList.innerHTML = '';

            // Add file to the list
            addFileToList(file);

            // Load the PDF file
            loadPdfFile(file);

            // Hide any previous messages and results
            messageContainer.classList.add('hidden');
            resultContainer.classList.add('hidden');
        }

        // Add a file to the list
        function addFileToList(file) {
            const li = document.createElement('li');

            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';

            const fileIcon = document.createElement('div');
            fileIcon.className = 'file-icon';
            fileIcon.innerHTML = '📄'; // File icon
            fileItem.appendChild(fileIcon);

            const fileName = document.createElement('div');
            fileName.className = 'file-name';
            fileName.textContent = file.name;
            fileItem.appendChild(fileName);

            li.appendChild(fileItem);

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-btn';
            removeBtn.textContent = translations[currentLang].remove;
            removeBtn.addEventListener('click', function() {
                // Remove file
                selectedFile = null;
                // Remove from list
                fileList.removeChild(li);
                // Hide viewer
                viewerContainer.classList.add('hidden');
                // Reset PDF viewer
                pdfDoc = null;
                pdfViewer.innerHTML = '';
                pageRotations = [];
            });

            li.appendChild(removeBtn);
            fileList.appendChild(li);

            // Add file details
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);

            const details = document.createElement('div');
            details.className = 'file-details';
            details.style.color = '#666';
            details.style.fontSize = '0.9em';
            details.style.marginTop = '5px';
            details.innerHTML = `Size: ${sizeMB} MB`;
            fileItem.appendChild(details);
        }

        // Load PDF file and render previews
        async function loadPdfFile(file) {
            try {
                const fileURL = URL.createObjectURL(file);
                const loadingTask = pdfjsLib.getDocument(fileURL);

                pdfDoc = await loadingTask.promise;
                console.log(`PDF loaded with ${pdfDoc.numPages} pages`);

                // Initialize page rotations array
                pageRotations = new Array(pdfDoc.numPages).fill(0);

                // Show the PDF viewer
                viewerContainer.classList.remove('hidden');

                // Render the first set of pages
                currentPage = 1;
                renderPages();

                // Update page indicator
                updatePageIndicator();

            } catch (error) {
                console.error('Error loading PDF:', error);
                showMessage('error', `Error loading PDF: ${error.message}`);
            }
        }

        // Render a set of pages
        async function renderPages() {
            // Clear current previews
            pdfViewer.innerHTML = '';

            const startPage = currentPage;
            const endPage = Math.min(startPage + pagesPerView - 1, pdfDoc.numPages);

            for (let i = startPage; i <= endPage; i++) {
                try {
                    const page = await pdfDoc.getPage(i);
                    const pageContainer = document.createElement('div');
                    pageContainer.className = 'page-container';

                    if (pageRotations[i-1] !== 0) {
                        pageContainer.classList.add(`rotated-${pageRotations[i-1]}`);
                    }

                    // Create page content container
                    const pageContent = document.createElement('div');
                    pageContent.className = 'page-content';

                    // Create rotation badge if page is rotated
                    if (pageRotations[i-1] !== 0) {
                        const rotationBadge = document.createElement('div');
                        rotationBadge.className = 'rotation-badge';
                        rotationBadge.textContent = `${pageRotations[i-1]}°`;
                        pageContainer.appendChild(rotationBadge);
                    }

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');

                    // Set scale for preview
                    const viewport = page.getViewport({ scale: 0.5 });
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    // Add class to canvas
                    canvas.className = 'page-preview';

                    // Render the page
                    await page.render({
                        canvasContext: context,
                        viewport: viewport
                    }).promise;

                    pageContent.appendChild(canvas);
                    pageContainer.appendChild(pageContent);

                    // Add page number
                    const pageNumber = document.createElement('div');
                    pageNumber.className = 'page-number';
                    pageNumber.textContent = `${translations[currentLang].page} ${i}`;
                    pageContainer.appendChild(pageNumber);

                    // Add rotation buttons
                    const pageTools = document.createElement('div');
                    pageTools.className = 'page-tools';

                    const rotateCCWBtn = document.createElement('button');
                    rotateCCWBtn.className = 'rotate-btn rotate-ccw';
                    rotateCCWBtn.innerHTML = '↺';
                    rotateCCWBtn.title = 'Rotate counterclockwise';
                    rotateCCWBtn.addEventListener('click', function() {
                        rotatePage(i, -90); // Rotate counter-clockwise
                    });

                    const rotateCWBtn = document.createElement('button');
                    rotateCWBtn.className = 'rotate-btn rotate-cw';
                    rotateCWBtn.innerHTML = '↻';
                    rotateCWBtn.title = 'Rotate clockwise';
                    rotateCWBtn.addEventListener('click', function() {
                        rotatePage(i, 90); // Rotate clockwise
                    });

                    pageTools.appendChild(rotateCCWBtn);
                    pageTools.appendChild(rotateCWBtn);

                    pageContainer.appendChild(pageTools);
                    pdfViewer.appendChild(pageContainer);

                } catch (error) {
                    console.error(`Error rendering page ${i}:`, error);
                }
            }
        }

        // Rotate a page
        function rotatePage(pageNum, angle) {
            // Update the rotation value for the page
            pageRotations[pageNum-1] = (pageRotations[pageNum-1] + angle + 360) % 360;

            // Re-render the current page set
            renderPages();
        }

        // Pagination handlers
        prevPage.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage = Math.max(1, currentPage - pagesPerView);
                renderPages();
                updatePageIndicator();
            }
        });

        nextPage.addEventListener('click', function() {
            if (currentPage + pagesPerView <= pdfDoc.numPages) {
                currentPage += pagesPerView;
                renderPages();
                updatePageIndicator();
            }
        });

        // Rotate all pages
        rotateAllBtn.addEventListener('click', function() {
            for (let i = 0; i < pageRotations.length; i++) {
                pageRotations[i] = (pageRotations[i] + 90) % 360;
            }
            renderPages();
        });

        // Apply changes (save rotated PDF)
        applyBtn.addEventListener('click', function() {
            if (!selectedFile) {
                showMessage('error', translations[currentLang].errorNoFile);
                return;
            }

            // Check if any rotations were applied
            const hasRotations = pageRotations.some(rotation => rotation !== 0);
            if (!hasRotations) {
                showMessage('info', 'No rotations were applied. Please rotate at least one page.');
                return;
            }

            // Submit the rotations to the server
            applyRotations();
        });

        // Apply rotations to PDF
        async function applyRotations() {
            showMessage('info', translations[currentLang].rotating);
            applyBtn.disabled = true;

            // Create FormData
            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('rotations', JSON.stringify(pageRotations));

            try {
                const keyResponse = await fetch('/myapp/backend/api/api_api_key.php', {
                    credentials: 'include'
                });
                const responseText = await keyResponse.text();
                let keyData;
                try {
                    keyData = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse API key response:', e);
                    throw new Error('Invalid API key response');
                }
                if (!keyData.success) {
                    throw new Error('Failed to get API key: ' + (keyData.error || 'Unknown error'));
                }
                const apiKey = keyData.api_key;
                if (!apiKey) {
                    throw new Error('No API key returned');
                }

                // Send to server
                fetch('/myapp/backend/api/api_rotate_pdf.php', {
                    method: 'POST',
                    headers: {
                        'X-API-KEY': apiKey,
                        'X-Request-Source': 'frontend'
                    },
                    body: formData,
                    credentials: 'include'
                })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                console.error('Server error response:', text);
                                throw new Error(`Server error: ${response.status}`);
                            });
                        }

                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.result_id) {
                            showMessage('success', translations[currentLang].success);

                            // Secure download implementation with headers
                            const downloadUrl = `/myapp/backend/pdf/download_pdf.php?id=${data.result_id}`;
                            fetch(downloadUrl, {
                                method: 'GET',
                                headers: {
                                    'X-API-KEY': apiKey,
                                    'X-Request-Source': 'frontend'
                                },
                                credentials: 'include'
                            })
                                .then(response => response.blob())
                                .then(blob => {
                                    const url = window.URL.createObjectURL(blob);
                                    downloadLink.href = url;
                                    downloadLink.setAttribute('download', 'rotated.pdf');
                                    resultContainer.classList.remove('hidden');
                                })
                                .catch(error => {
                                    console.error('Download error:', error);
                                    showMessage('error', translations[currentLang].errorDownloading + error.message);
                                });

                            console.log('PDF rotation successful. Result ID:', data.result_id);
                        } else {
                            throw new Error(data.error || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        console.error('PDF Rotation Error:', error);
                        showMessage('error', translations[currentLang].errorRotate + error.message);
                    })
            } catch (error) {
                console.error('PDF Processing Error:', error);
                showMessage('error', translations[currentLang].errorMerge + error.message);
            } finally {
                applyBtn.disabled = false;
            }
        }

        // Show a message to the user
        function showMessage(type, message) {
            messageContainer.textContent = message;
            messageContainer.className = `message ${type}`;
            messageContainer.classList.remove('hidden');

            // Scroll to the message
            messageContainer.scrollIntoView({ behavior: 'smooth' });
        }
    });
</script>
</body>
</html>