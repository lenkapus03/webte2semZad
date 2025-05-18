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
    <title>Compare PDF Files</title>
    <style>
        /* Previous styles remain, add these new styles */
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
        .file-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            border-radius: 4px;
            background-color: #f5f5f5;
        }
        .file-name {
            flex-grow: 1;
            margin-right: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
        #fileInput1, #fileInput2 {
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
        .options-container {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .file-selector {
            margin-bottom: 15px;
        }
        .file-selector h3 {
            margin-top: 0;
        }
        .comparison-results {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f8f8f8;
        }
        .result-item {
            margin-bottom: 10px;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
            border-left: 4px solid #4CAF50;
        }
        .result-item.different {
            border-left-color: #f44336;
        }
        @media (max-width: 600px) {
            .dropzone {
                padding: 15px;
            }
            .file-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .file-buttons {
                margin-top: 10px;
                align-self: flex-end;
            }
        }
        /* PDF Preview Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 1200px;
            max-height: 80vh;
            overflow: auto;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: black;
        }

        .pdf-preview-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .pdf-preview {
            flex: 1;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            background-color: #f9f9f9;
        }

        .pdf-preview-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        .pdf-preview-frame {
            width: 100%;
            height: 500px;
            border: none;
        }

        .diff-viewer {
            margin-top: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }

        .diff-section {
            margin-bottom: 15px;
        }

        .diff-section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .diff-content {
            background-color: white;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow: auto;
        }

        .diff-line {
            margin: 2px 0;
            padding: 2px;
        }

        .diff-added {
            background-color: #ddffdd;
        }

        .diff-removed {
            background-color: #ffdddd;
        }

        .diff-unchanged {
            color: #999;
        }

        .page-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .page-nav button {
            padding: 8px 16px;
        }
    </style>
</head>
<body>
<div class="navigation">
    <a href="/myapp/index.php">← Back to Dashboard</a>
</div>

<div class="language-switcher">
    <a href="#" id="lang-en">English</a> | <a href="#" id="lang-sk">Slovensky</a>
</div>

<h1 id="title">Compare PDF Files</h1>

<div class="options-container">
    <div class="file-selector">
        <h3 id="file1Title">First PDF File</h3>
        <div class="dropzone" id="dropzone1">
            <p id="dropText1">Drag and drop the first PDF file here, or click to select file</p>
            <input type="file" id="fileInput1" accept=".pdf">
            <button class="btn btn-secondary" id="selectFileBtn1">Select File</button>
        </div>
        <ul class="file-list" id="fileList1"></ul>
    </div>

    <div class="file-selector">
        <h3 id="file2Title">Second PDF File</h3>
        <div class="dropzone" id="dropzone2">
            <p id="dropText2">Drag and drop the second PDF file here, or click to select file</p>
            <input type="file" id="fileInput2" accept=".pdf">
            <button class="btn btn-secondary" id="selectFileBtn2">Select File</button>
        </div>
        <ul class="file-list" id="fileList2"></ul>
    </div>

    <!-- Add comparison options -->
    <div class="comparison-options">
        <h3 id="optionsTitle">Comparison Options</h3>
        <div class="option-checkbox">
            <input type="checkbox" id="compareMetadata" checked>
            <label for="compareMetadata" id="metadataLabel">Compare metadata</label>
        </div>
        <div class="option-checkbox">
            <input type="checkbox" id="compareImages" checked>
            <label for="compareImages" id="imagesLabel">Compare images</label>
        </div>
        <div class="option-checkbox">
            <input type="checkbox" id="detailedResults" checked>
            <label for="detailedResults" id="detailsLabel">Show detailed page-by-page results</label>
        </div>
    </div>
</div>

<div id="actions">
    <button class="btn" id="compareBtn" disabled>Compare PDFs</button>
</div>

<div id="messageContainer" class="hidden"></div>

<div id="resultContainer" class="hidden">
    <h3 id="resultsTitle">Comparison Results</h3>
    <div class="comparison-results" id="comparisonResults"></div>
</div>

<!-- Difference Modal -->
<div id="diffModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" id="closeDiffModal">&times;</span>
        <h2>PDF Differences</h2>
        <div class="page-navigation">
            <button class="btn btn-secondary" id="prevPageBtn">Previous</button>
            <span id="pageCounter">Page 1 of 1</span>
            <button class="btn btn-secondary" id="nextPageBtn">Next</button>
        </div>
        <h3 id="previewTitle1">File 1 - Page 1</h3>
        <div id="textDiffContent" class="diff-viewer"></div>
        <h3 id="previewTitle2">File 2 - Page 1</h3>
        <div id="imageDiffContent" class="diff-viewer"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentComparisonData = null;
        let currentPageIndex = 0;

        // DOM elements
        const dropzone1 = document.getElementById('dropzone1');
        const dropzone2 = document.getElementById('dropzone2');
        const fileInput1 = document.getElementById('fileInput1');
        const fileInput2 = document.getElementById('fileInput2');
        const selectFileBtn1 = document.getElementById('selectFileBtn1');
        const selectFileBtn2 = document.getElementById('selectFileBtn2');
        const fileList1 = document.getElementById('fileList1');
        const fileList2 = document.getElementById('fileList2');
        const compareBtn = document.getElementById('compareBtn');
        const messageContainer = document.getElementById('messageContainer');
        const resultContainer = document.getElementById('resultContainer');
        const comparisonResults = document.getElementById('comparisonResults');
        const title = document.getElementById('title');
        const file1Title = document.getElementById('file1Title');
        const file2Title = document.getElementById('file2Title');
        const dropText1 = document.getElementById('dropText1');
        const dropText2 = document.getElementById('dropText2');
        const resultsTitle = document.getElementById('resultsTitle');
        const langEn = document.getElementById('lang-en');
        const langSk = document.getElementById('lang-sk');
        const compareMetadata = document.getElementById('compareMetadata');
        const compareImages = document.getElementById('compareImages');
        const detailedResults = document.getElementById('detailedResults');
        const metadataLabel = document.getElementById('metadataLabel');
        const imagesLabel = document.getElementById('imagesLabel');
        const detailsLabel = document.getElementById('detailsLabel');
        const optionsTitle = document.getElementById('optionsTitle');

        // Modal elements
        const pdfModal = document.getElementById('diffModal');
        const closePdfModal = document.getElementById('closeDiffModal');
        const previewTitle1 = document.getElementById('previewTitle1');
        const previewTitle2 = document.getElementById('previewTitle2');
        const textDiffContent = document.getElementById('textDiffContent');
        const imageDiffContent = document.getElementById('imageDiffContent');
        const pageCounter = document.getElementById('pageCounter');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');

        // Translation object
        const translations = {
            'en': {
                'title': 'Compare PDF Files',
                'file1Title': 'First PDF File',
                'file2Title': 'Second PDF File',
                'dropText1': 'Drag and drop the first PDF file here, or click to select file',
                'dropText2': 'Drag and drop the second PDF file here, or click to select file',
                'selectFile': 'Select File',
                'compareBtn': 'Compare PDFs',
                'processing': 'Comparing PDF files...',
                'success': 'PDF files were successfully compared!',
                'errorNoFiles': 'Please select two PDF files to compare.',
                'errorSameFile': 'Please select two different PDF files.',
                'errorUpload': 'Error uploading file: ',
                'errorCompare': 'Error comparing PDFs: ',
                'remove': 'Remove',
                'resultsTitle': 'Comparison Results',
                'identical': 'The PDF files are identical.',
                'different': 'The PDF files are different.',
                'pageCount': 'Page count:',
                'file1Pages': 'File 1 has {pages} pages',
                'file2Pages': 'File 2 has {pages} pages',
                'contentMatch': 'Content matches on {matched} of {total} pages',
                'metadataMatch': 'Metadata matches: {match}',
                'yes': 'Yes',
                'no': 'No',
                'optionsTitle': 'Comparison Options',
                'metadataLabel': 'Compare metadata',
                'imagesLabel': 'Compare images',
                'detailsLabel': 'Show detailed page-by-page results',
                'pageHeader': 'Page {page}',
                'textMatch': 'Text matches',
                'textDiff': 'Text differences found',
                'imagesMatch': 'Images match',
                'imagesDiff': 'Image differences found',
                'showDiff': 'Show differences',
                'hideDiff': 'Hide differences',
                'noDifferences': 'No differences found',
                'previous': 'Previous',
                'next': 'Next',
                'downloadReport': 'Download Report'
            },
            'sk': {
                'title': 'Porovnať PDF súbory',
                'file1Title': 'Prvý PDF súbor',
                'file2Title': 'Druhý PDF súbor',
                'dropText1': 'Pretiahnite prvý PDF súbor sem, alebo kliknite pre výber súboru',
                'dropText2': 'Pretiahnite druhý PDF súbor sem, alebo kliknite pre výber súboru',
                'selectFile': 'Vybrať súbor',
                'compareBtn': 'Porovnať PDF',
                'processing': 'Porovnávanie PDF súborov...',
                'success': 'PDF súbory boli úspešne porovnané!',
                'errorNoFiles': 'Vyberte dva PDF súbory na porovnanie.',
                'errorSameFile': 'Vyberte dva rôzne PDF súbory.',
                'errorUpload': 'Chyba pri nahrávaní súboru: ',
                'errorCompare': 'Chyba pri porovnávaní PDF: ',
                'remove': 'Odstrániť',
                'resultsTitle': 'Výsledky porovnania',
                'identical': 'PDF súbory sú identické.',
                'different': 'PDF súbory sú rozdielne.',
                'pageCount': 'Počet strán:',
                'file1Pages': 'Súbor 1 má {pages} strán',
                'file2Pages': 'Súbor 2 má {pages} strán',
                'contentMatch': 'Obsah sa zhoduje na {matched} zo {total} strán',
                'metadataMatch': 'Metadáta sa zhodujú: {match}',
                'yes': 'Áno',
                'no': 'Nie',
                'optionsTitle': 'Možnosti porovnania',
                'metadataLabel': 'Porovnať metadáta',
                'imagesLabel': 'Porovnať obrázky',
                'detailsLabel': 'Zobraziť podrobné výsledky po stránkach',
                'pageHeader': 'Strana {page}',
                'textMatch': 'Text sa zhoduje',
                'textDiff': 'Nájdené rozdiely v texte',
                'imagesMatch': 'Obrázky sa zhodujú',
                'imagesDiff': 'Nájdené rozdiely v obrázkoch',
                'showDiff': 'Zobraziť rozdiely',
                'hideDiff': 'Skryť rozdiely',
                'noDifferences': 'Žiadne rozdiely neboli nájdené',
                'previous': 'Predchádzajúca',
                'next': 'Nasledujúca',
                'downloadReport': 'Stiahnuť správu'
            }
        };

        // Set default language
        let currentLang = 'en';

        // Selected files
        let selectedFile1 = null;
        let selectedFile2 = null;

        // Language switcher event listeners
        langEn.addEventListener('click', function(e) {
            e.preventDefault();
            setLanguage('en');
        });

        langSk.addEventListener('click', function(e) {
            e.preventDefault();
            setLanguage('sk');
        });

        // Close modal button
        closePdfModal.addEventListener('click', function() {
            pdfModal.style.display = 'none';
        });

        // Page navigation buttons
        prevPageBtn.addEventListener('click', function() {
            if (currentPageIndex > 0) {
                currentPageIndex--;
                updatePagePreview();
            }
        });

        nextPageBtn.addEventListener('click', function() {
            if (currentComparisonData && currentPageIndex < currentComparisonData.page_comparisons.length - 1) {
                currentPageIndex++;
                updatePagePreview();
            }
        });

        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target === pdfModal) {
                pdfModal.style.display = 'none';
            }
        });

        // Function to set the language
        function setLanguage(lang) {
            currentLang = lang;
            title.textContent = translations[lang].title;
            file1Title.textContent = translations[lang].file1Title;
            file2Title.textContent = translations[lang].file2Title;
            dropText1.textContent = translations[lang].dropText1;
            dropText2.textContent = translations[lang].dropText2;
            selectFileBtn1.textContent = translations[lang].selectFile;
            selectFileBtn2.textContent = translations[lang].selectFile;
            compareBtn.textContent = translations[lang].compareBtn;
            resultsTitle.textContent = translations[lang].resultsTitle;

            // Update remove buttons
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.textContent = translations[lang].remove;
            });

            document.querySelectorAll('.download-btn').forEach(btn => {
                btn.textContent = translations[lang].downloadReport;
            });

            optionsTitle.textContent = translations[lang].optionsTitle;
            metadataLabel.textContent = translations[lang].metadataLabel;
            imagesLabel.textContent = translations[lang].imagesLabel;
            detailsLabel.textContent = translations[lang].detailsLabel;

            // Update navigation buttons
            prevPageBtn.textContent = translations[lang].previous;
            nextPageBtn.textContent = translations[lang].next;

            // If there are results displayed, refresh them to update language
            if (currentComparisonData && !resultContainer.classList.contains('hidden')) {
                displayResults(currentComparisonData);
            }
        }

        // Initialize dropzones
        initDropzone(dropzone1, fileInput1, selectFileBtn1, fileList1, (file) => {
            selectedFile1 = file;
            updateCompareButton();
        });

        initDropzone(dropzone2, fileInput2, selectFileBtn2, fileList2, (file) => {
            selectedFile2 = file;
            updateCompareButton();
        });

        function initDropzone(dropzone, fileInput, selectBtn, fileList, callback) {
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
                    handleFile(files[0], fileList, callback);
                }
            });

            // Click events for file selection
            dropzone.addEventListener('click', function(e) {
                if (e.target !== selectBtn && !selectBtn.contains(e.target)) {
                    fileInput.click();
                }
            });

            selectBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.click();
            });

            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    handleFile(this.files[0], fileList, callback);
                    this.value = ''; // Reset to allow selecting the same file again
                }
            });
        }

        // Handle the selected file
        function handleFile(file, fileList, callback) {
            // Check if file is a PDF
            if (file.type !== 'application/pdf') {
                showMessage('error', `File "${file.name}" is not a PDF.`);
                return;
            }

            // Clear previous file
            fileList.innerHTML = '';

            // Add file to the list
            addFileToList(file, fileList);

            // Call the callback with the file
            callback(file);

            // Hide any previous messages and results
            messageContainer.classList.add('hidden');
            resultContainer.classList.add('hidden');
        }

        // Add a file to the list
        function addFileToList(file, fileList) {
            const li = document.createElement('li');
            li.className = 'file-item';

            const fileIcon = document.createElement('div');
            fileIcon.className = 'file-icon';
            fileIcon.innerHTML = '📄';
            li.appendChild(fileIcon);

            const fileName = document.createElement('div');
            fileName.className = 'file-name';
            fileName.textContent = file.name;
            fileName.title = file.name; // Show full name on hover
            li.appendChild(fileName);

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-btn';
            removeBtn.textContent = translations[currentLang].remove;
            removeBtn.addEventListener('click', function() {
                fileList.removeChild(li);
                if (fileList === fileList1) {
                    selectedFile1 = null;
                } else {
                    selectedFile2 = null;
                }
                updateCompareButton();
            });

            li.appendChild(removeBtn);
            fileList.appendChild(li);
        }

        // Update the compare button state
        function updateCompareButton() {
            compareBtn.disabled = !(selectedFile1 && selectedFile2);
        }

        // Compare button click event
        compareBtn.addEventListener('click', comparePDFs);

        async function comparePDFs() {
            if (!selectedFile1 || !selectedFile2) {
                showMessage('error', translations[currentLang].errorNoFiles);
                return;
            }

            if (selectedFile1.name === selectedFile2.name && selectedFile1.size === selectedFile2.size) {
                showMessage('error', translations[currentLang].errorSameFile);
                return;
            }

            showMessage('info', translations[currentLang].processing);
            compareBtn.disabled = true;

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

                const formData = new FormData();
                formData.append('file1', selectedFile1);
                formData.append('file2', selectedFile2);
                formData.append('compare_metadata', compareMetadata.checked ? '1' : '0');
                formData.append('compare_images', compareImages.checked ? '1' : '0');
                formData.append('detailed_results', detailedResults.checked ? '1' : '0');

                const response = await fetch('/myapp/backend/api/api_compare_pdfs.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-API-KEY': apiKey,
                        'X-Request-Source': 'frontend'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('success', translations[currentLang].success);
                    currentComparisonData = data;

                    const downloadLink = document.createElement('a');
                    downloadLink.className = 'btn download-btn';
                    downloadLink.textContent = translations[currentLang].downloadReport;
                    downloadLink.download = 'pdf_comparison_report.pdf';
                    downloadLink.target = '_blank';

                    // Construct the download URL for the fetch request
                    const downloadUrl = `/myapp/backend/api/api_download_comparison.php?id=${data.result_id}`;

                    downloadLink.addEventListener('click', async (event) => {
                        event.preventDefault(); // Prevent the default link behavior

                        try {
                            const downloadResponse = await fetch(downloadUrl, {
                                method: 'GET',
                                headers: {
                                    'X-API-KEY': apiKey // Include API key in the header
                                }
                            });

                            if (downloadResponse.ok) {
                                const blob = await downloadResponse.blob();
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = 'pdf_comparison_report.pdf';
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                window.URL.revokeObjectURL(url);
                            } else {
                                const errorData = await downloadResponse.json();
                                showMessage('error', translations[currentLang].errorDownload + (errorData.error || 'Unknown error'));
                                console.error('Download failed:', errorData);
                            }
                        } catch (error) {
                            showMessage('error', translations[currentLang].errorDownload + error.message);
                            console.error('Download error:', error);
                        }
                    });

                    comparisonResults.innerHTML = '';
                    comparisonResults.appendChild(downloadLink);
                    resultContainer.classList.remove('hidden');

                } else {
                    throw new Error(data.error || 'Unknown error');
                }
            } catch (error) {
                showMessage('error', translations[currentLang].errorCompare + error.message);
            } finally {
                compareBtn.disabled = false;
            }
        }


            // Display comparison results
        function displayResults(data) {
            comparisonResults.innerHTML = '';

            if (data.download_url) {
                const downloadContainer = document.createElement('div');
                downloadContainer.className = 'download-container';

                const downloadBtn = document.createElement('a');
                downloadBtn.className = 'btn download-btn';
                downloadBtn.href = data.download_url;
                downloadBtn.textContent = translations[currentLang].downloadReport;
                downloadBtn.download = 'pdf_comparison_report.pdf';
                downloadBtn.target = '_blank'; // Open in new tab

                downloadContainer.appendChild(downloadBtn);
                comparisonResults.appendChild(downloadContainer);
            }

            if (data.identical) {
                const resultItem = document.createElement('div');
                resultItem.className = 'result-item';
                resultItem.textContent = translations[currentLang].identical;
                comparisonResults.appendChild(resultItem);
                return;
            }

            // Show summary
            const summaryItem = document.createElement('div');
            summaryItem.className = 'result-item different';
            summaryItem.textContent = translations[currentLang].different;
            comparisonResults.appendChild(summaryItem);

            // Page counts
            const pageCount1 = document.createElement('div');
            pageCount1.className = 'result-item';
            pageCount1.textContent = translations[currentLang].file1Pages.replace('{pages}', data.file1_pages);
            comparisonResults.appendChild(pageCount1);

            const pageCount2 = document.createElement('div');
            pageCount2.className = 'result-item';
            pageCount2.textContent = translations[currentLang].file2Pages.replace('{pages}', data.file2_pages);
            comparisonResults.appendChild(pageCount2);

            // Metadata comparison if enabled
            if (data.compare_metadata !== undefined) {
                const metadataResult = document.createElement('div');
                metadataResult.className = 'result-item';
                const matchText = data.metadata_match ? translations[currentLang].yes : translations[currentLang].no;
                metadataResult.textContent = translations[currentLang].metadataMatch.replace('{match}', matchText);
                comparisonResults.appendChild(metadataResult);
            }

            // Count matching pages
            if (data.page_comparisons) {
                let matchingPages = data.matching_pages || 0;
                if (matchingPages === 0) {
                    data.page_comparisons.forEach(page => {
                        if (page.identical) {
                            matchingPages++;
                        }
                    });
                }

                const contentMatchItem = document.createElement('div');
                contentMatchItem.className = 'result-item';
                contentMatchItem.textContent = translations[currentLang].contentMatch
                    .replace('{matched}', matchingPages)
                    .replace('{total}', data.page_comparisons.length);
                comparisonResults.appendChild(contentMatchItem);
            }

            // Detailed page comparison
            if (data.detailed_results && data.page_comparisons) {
                const pageComparisonHeader = document.createElement('h4');
                pageComparisonHeader.textContent = 'Page-by-page comparison:';
                comparisonResults.appendChild(pageComparisonHeader);

                data.page_comparisons.forEach((page, index) => {
                    const pageResult = document.createElement('div');
                    pageResult.className = `result-item ${page.identical ? '' : 'different'}`;

                    const pageHeader = document.createElement('div');
                    pageHeader.className = 'page-result-header';
                    pageHeader.textContent = translations[currentLang].pageHeader.replace('{page}', index + 1);
                    pageHeader.style.fontWeight = 'bold';
                    pageResult.appendChild(pageHeader);

                    // Text comparison
                    const textResult = document.createElement('div');
                    textResult.textContent = page.text_match ?
                        translations[currentLang].textMatch :
                        translations[currentLang].textDiff;
                    pageResult.appendChild(textResult);

                    // Image comparison (if enabled)
                    if (data.compare_images) {
                        const imageResult = document.createElement('div');
                        imageResult.textContent = page.images_match ?
                            translations[currentLang].imagesMatch :
                            translations[currentLang].imagesDiff;
                        pageResult.appendChild(imageResult);
                    }

                    // Show differences button
                    if (!page.identical) {
                        const showDiffBtn = document.createElement('button');
                        showDiffBtn.className = 'btn-secondary';
                        showDiffBtn.textContent = translations[currentLang].showDiff;
                        showDiffBtn.style.marginTop = '10px';
                        showDiffBtn.onclick = function() {
                            showPageDifferences(index);
                        };
                        pageResult.appendChild(showDiffBtn);
                    }

                    comparisonResults.appendChild(pageResult);
                });
            }
        }

        // Show page differences in modal
        function showPageDifferences(pageIndex) {
            if (!currentComparisonData || !currentComparisonData.page_comparisons) {
                return;
            }

            currentPageIndex = pageIndex;
            updatePagePreview();
            pdfModal.style.display = 'block';
        }

        // Update page preview in modal
        function updatePagePreview() {
            if (!currentComparisonData || !currentComparisonData.page_comparisons) {
                return;
            }

            const pageData = currentComparisonData.page_comparisons[currentPageIndex];

            // Update text differences
            if (pageData.text_match) {
                textDiffContent.innerHTML = `<div class="diff-line">${translations[currentLang].textMatch}</div>`;
            } else {
                let diffHTML = '';
                if (pageData.text_diff) {
                    // Handle UTF-8 text directly
                    const diffLines = pageData.text_diff.split('\n');
                    diffHTML = diffLines.map(line => {
                        // Preserve leading +/- for diff display
                        const firstChar = line.charAt(0);
                        const content = escapeHTML(line.substring(1));

                        if (firstChar === '+') {
                            return `<div class="diff-line diff-added">+${content}</div>`;
                        } else if (firstChar === '-') {
                            return `<div class="diff-line diff-removed">-${content}</div>`;
                        } else {
                            return `<div class="diff-line diff-unchanged">${escapeHTML(line)}</div>`;
                        }
                    }).join('');
                } else {
                    diffHTML = `<div class="diff-line">${translations[currentLang].textDiff}</div>`;
                }
                textDiffContent.innerHTML = diffHTML;
            }

            // Update image differences
            if (currentComparisonData.compare_images) {
                if (pageData.images_match) {
                    imageDiffContent.innerHTML = `<div class="diff-line">${translations[currentLang].imagesMatch}</div>`;
                } else {
                    // Format the image differences
                    if (pageData.image_diff) {
                        imageDiffContent.innerHTML = `<div class="diff-line">${escapeHTML(pageData.image_diff)}</div>`;
                    } else {
                        imageDiffContent.innerHTML = `<div class="diff-line">${translations[currentLang].imagesDiff}</div>`;
                    }
                }
            } else {
                imageDiffContent.innerHTML = `<div class="diff-line">Image comparison disabled</div>`;
            }

            // Update page counter
            pageCounter.textContent = `Page ${currentPageIndex + 1} of ${currentComparisonData.page_comparisons.length}`;

            // Update navigation buttons
            prevPageBtn.disabled = currentPageIndex === 0;
            nextPageBtn.disabled = currentPageIndex === currentComparisonData.page_comparisons.length - 1;
        }

        // Helper function to escape HTML
        function escapeHTML(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML
                .replace(/ /g, '&nbsp;')
                .replace(/\n/g, '<br>');
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