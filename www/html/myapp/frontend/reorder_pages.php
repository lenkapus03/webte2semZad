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
    <title>Reorder PDF Pages</title>
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
            background-color: white;
            z-index: 100;
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
            cursor: move;
            transition: all 0.2s ease-in-out;
        }
        .page-container.dragging {
            opacity: 0.7;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            transform: scale(1.05);
        }
        .page-container.drag-over {
            border: 2px dashed #2196F3;
        }
        .page-content {
            width: 100%;
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .page-preview {
            max-width: 100%;
            max-height: 250px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: white;
        }
        .page-number {
            font-weight: bold;
            margin: 15px 0;
            font-size: 16px;
            position: relative;
        }
        .original-page-number {
            position: absolute;
            top: -20px;
            right: -10px;
            background-color: #ffc107;
            color: #333;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .reordering-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #2196F3;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .summary-box {
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
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

<h1 id="title">Reorder PDF Pages</h1>

<div class="dropzone" id="dropzone">
    <p id="dropText">Drag and drop a PDF file here, or click to select file</p>
    <input type="file" id="fileInput" accept=".pdf">
    <button class="btn btn-secondary" id="selectFileBtn">Select File</button>
</div>

<ul id="fileList"></ul>

<div id="viewerContainer" class="hidden">
    <div class="controls-container">
        <div class="action-buttons">
            <button class="btn btn-secondary" id="resetOrderBtn">Reset Order</button>
            <button class="btn" id="applyBtn">Apply Page Reordering</button>
        </div>
    </div>

    <div id="summaryBox" class="summary-box hidden">
        <span id="reorderInfo">Drag and drop pages to reorder them. </span><span style="background-color: #ffc107; padding: 2px 5px; border-radius: 3px; font-weight: bold;">The original page number is shown in yellow.</span>
    </div>

    <div id="pdfViewer" class="pdf-viewer">
        <!-- Page previews will be dynamically inserted here -->
    </div>
</div>

<div id="messageContainer" class="hidden"></div>
<div id="resultContainer" class="hidden">
    <a href="#" id="downloadLink" class="btn btn-secondary" target="_blank">Download Reorded PDF</a>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
<script>
    // Initialize the PDF.js worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

    document.addEventListener('DOMContentLoaded', function() {
        // Translation object
        const translations = {
            'en': {
                'title': 'Reorder PDF Pages',
                'dropText': 'Drag and drop a PDF file here, or click to select file',
                'selectFile': 'Select File',
                'resetOrderBtn': 'Reset Order',
                'applyBtn': 'Apply Page Reordering',
                'downloadLink': 'Download Modified PDF',
                'processing': 'Processing PDF...',
                'success': 'PDF pages were successfully reordered!',
                'errorNoFile': 'Please select a PDF file.',
                'errorUpload': 'Error uploading file: ',
                'errorProcess': 'Error processing PDF file: ',
                'remove': 'Remove',
                'page': 'Page',
                'of': 'of',
                'reorderInfo': 'Drag and drop pages to reorder them. The original page number is shown in yellow.',
                'originalPage': 'Original:',
                'noChange': 'The page order has not changed. Make some changes first.',
                'back': '← Back to Dashboard'
            },
            'sk': {
                'title': 'Preusporiadanie PDF stránok',
                'dropText': 'Pretiahnite PDF súbor sem, alebo kliknite pre výber súboru',
                'selectFile': 'Vybrať súbor',
                'resetOrderBtn': 'Obnoviť poradie',
                'applyBtn': 'Použiť preusporiadanie',
                'downloadLink': 'Stiahnuť upravený PDF',
                'processing': 'Spracovávam PDF...',
                'success': 'PDF stránky boli úspešne preusporiadané!',
                'errorNoFile': 'Vyberte PDF súbor.',
                'errorUpload': 'Chyba pri nahrávaní súboru: ',
                'errorProcess': 'Chyba pri spracovaní PDF súboru: ',
                'remove': 'Odstrániť',
                'page': 'Strana',
                'of': 'z',
                'reorderInfo': 'Presuňte stránky pretiahnutím myšou. Pôvodné číslo strany je zobrazené žltou.',
                'originalPage': 'Pôvodná:',
                'noChange': 'Poradie strán sa nezmenilo. Najprv vykonajte nejaké zmeny.',
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
        const resetOrderBtn = document.getElementById('resetOrderBtn');
        const applyBtn = document.getElementById('applyBtn');
        const messageContainer = document.getElementById('messageContainer');
        const resultContainer = document.getElementById('resultContainer');
        const downloadLink = document.getElementById('downloadLink');
        const title = document.getElementById('title');
        const dropText = document.getElementById('dropText');
        const langEn = document.getElementById('lang-en');
        const langSk = document.getElementById('lang-sk');
        const summaryBox = document.getElementById('summaryBox');
        const reorderInfo = document.getElementById('reorderInfo');
        const back = document.getElementById('back');

        // PDF viewer variables
        let pdfDoc = null;
        let pageOrder = []; // Array to track current page order [1, 2, 3, 4, ...]
        let originalPageOrder = []; // Array to track original page order for reference

        // Selected file
        let selectedFile = null;

        // Element being dragged
        let draggedElement = null;

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
            resetOrderBtn.textContent = translations[lang].resetOrderBtn;
            applyBtn.textContent = translations[lang].applyBtn;
            downloadLink.textContent = translations[lang].downloadLink;
            reorderInfo.textContent = translations[lang].reorderInfo;
            back.textContent = translations[lang].back;

            // Update remove buttons
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.textContent = translations[lang].remove;
            });

            // Update page numbers
            document.querySelectorAll('.page-number').forEach((el, index) => {
                const pageNum = pageOrder[index];
                el.textContent = `${translations[lang].page} ${index + 1}`;

                // Update tooltips for original page numbers
                const badge = el.querySelector('.original-page-number');
                if (badge) {
                    badge.title = `${translations[lang].originalPage} ${pageNum}`;
                }
            });
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
            pageOrder = [];
            originalPageOrder = [];

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
                summaryBox.classList.add('hidden');
                // Reset PDF viewer
                pdfDoc = null;
                pdfViewer.innerHTML = '';
                pageOrder = [];
                originalPageOrder = [];
            });

            li.appendChild(removeBtn);
            fileList.appendChild(li);
        }

        // Load PDF file and render previews
        async function loadPdfFile(file) {
            try {
                const fileURL = URL.createObjectURL(file);
                const loadingTask = pdfjsLib.getDocument(fileURL);

                pdfDoc = await loadingTask.promise;
                console.log(`PDF loaded with ${pdfDoc.numPages} pages`);

                // Init page order array (1-based, like PDF page numbers)
                pageOrder = Array.from({ length: pdfDoc.numPages }, (_, i) => i + 1);
                originalPageOrder = [...pageOrder]; // Clone for reset functionality

                // Show the PDF viewer
                viewerContainer.classList.remove('hidden');
                summaryBox.classList.remove('hidden');

                // Render all pages
                renderPages();

            } catch (error) {
                console.error('Error loading PDF:', error);
                showMessage('error', `Error loading PDF: ${error.message}`);
            }
        }

        // Render all pages
        async function renderPages() {
            // Clear current previews
            pdfViewer.innerHTML = '';

            // Set grid layout based on number of pages
            let columns = 3; // Default
            if (pdfDoc.numPages > 12) {
                columns = 4;
            } else if (pdfDoc.numPages > 24) {
                columns = 5;
            }
            pdfViewer.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;

            for (let i = 0; i < pageOrder.length; i++) {
                const pageNum = pageOrder[i]; // Get the actual page number from the order array

                try {
                    const pageContainer = document.createElement('div');
                    pageContainer.className = 'page-container';
                    pageContainer.dataset.pageNum = pageNum;
                    pageContainer.dataset.orderIndex = i;
                    pageContainer.draggable = true;

                    // Add drag and drop event listeners
                    pageContainer.addEventListener('dragstart', handleDragStart);
                    pageContainer.addEventListener('dragend', handleDragEnd);
                    pageContainer.addEventListener('dragover', handleDragOver);
                    pageContainer.addEventListener('dragleave', handleDragLeave);
                    pageContainer.addEventListener('drop', handleDrop);

                    // Create page content container
                    const pageContent = document.createElement('div');
                    pageContent.className = 'page-content';

                    const page = await pdfDoc.getPage(pageNum);
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
                    pageNumber.textContent = `${translations[currentLang].page} ${i + 1}`;

                    // Add original page number badge (if reordered)
                    if (i + 1 !== pageNum) {
                        const originalNumBadge = document.createElement('div');
                        originalNumBadge.className = 'original-page-number';
                        originalNumBadge.title = `${translations[currentLang].originalPage} ${pageNum}`;
                        originalNumBadge.textContent = pageNum;
                        pageNumber.appendChild(originalNumBadge);
                    }

                    pageContainer.appendChild(pageNumber);

                    pdfViewer.appendChild(pageContainer);

                } catch (error) {
                    console.error(`Error rendering page ${pageNum}:`, error);
                }
            }
        }

        // Drag and Drop Handlers
        function handleDragStart(e) {
            draggedElement = this;
            this.classList.add('dragging');

            // Set data for drag operation
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.orderIndex);

            // Use a timeout to change opacity (workaround for drag image)
            setTimeout(() => {
                this.style.opacity = '0.4';
            }, 0);
        }

        function handleDragEnd(e) {
            this.classList.remove('dragging');
            this.style.opacity = '1';

            // Reset all containers
            document.querySelectorAll('.page-container').forEach(container => {
                container.classList.remove('drag-over');
            });
        }

        function handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault(); // Necessary to allow drop
            }

            e.dataTransfer.dropEffect = 'move';
            this.classList.add('drag-over');

            return false;
        }

        function handleDragLeave(e) {
            this.classList.remove('drag-over');
        }

        function handleDrop(e) {
            e.stopPropagation(); // Stops browser from redirecting

            if (draggedElement !== this) {
                // Get the source and target indices
                const sourceIndex = parseInt(draggedElement.dataset.orderIndex);
                const targetIndex = parseInt(this.dataset.orderIndex);

                // Reorder the pages
                const pageToMove = pageOrder[sourceIndex];

                // Remove the page from its original position
                pageOrder.splice(sourceIndex, 1);

                // Insert it at the new position
                pageOrder.splice(targetIndex, 0, pageToMove);

                // Re-render the pages
                renderPages();
            }

            this.classList.remove('drag-over');
            return false;
        }

        // Reset order button handler
        resetOrderBtn.addEventListener('click', function(e) {
            e.preventDefault();
            pageOrder = [...originalPageOrder]; // Reset to original order
            renderPages();
        });

        // Apply changes button handler
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();

            if (!selectedFile) {
                showMessage('error', translations[currentLang].errorNoFile);
                return;
            }

            // Check if the order has actually changed
            const hasChanged = !pageOrder.every((page, index) => page === index + 1);

            if (!hasChanged) {
                showMessage('info', translations[currentLang].noChange);
                return;
            }

            // Process the PDF to reorder pages
            processReordering();
        });

        // Process reordering of pages
        async function processReordering() {
            showMessage('info', translations[currentLang].processing);
            applyBtn.disabled = true;

            // Create FormData
            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('page_order', JSON.stringify(pageOrder));

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

                // Send to backend
                fetch('/myapp/backend/api/api_reorder_pages.php', {
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
                            const downloadUrl = `/myapp/backend/api/api_download_pdf.php?id=${data.result_id}`;
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
                                    downloadLink.setAttribute('download', 'reordered.pdf');
                                    resultContainer.classList.remove('hidden');
                                })
                                .catch(error => {
                                    console.error('Download error:', error);
                                    showMessage('error', translations[currentLang].errorDownloading + error.message);
                                });

                            console.log('PDF pages reordering successful. Result ID:', data.result_id);
                        } else {
                            throw new Error(data.error || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        console.error('PDF Processing Error:', error);
                        showMessage('error', translations[currentLang].errorProcess + error.message);
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