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
  <title>Extract PDF Pages</title>
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
      position: sticky;
      top: 0;
      background-color: white;
      z-index: 100;
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
    .select-all-btn {
      background-color: #2196F3;
    }
    .apply-btn {
      background-color: #4CAF50;
    }
    .deselect-all-btn {
      background-color: #607D8B;
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
      transition: opacity 0.3s;
    }
    .page-container.selected-for-keep {
      opacity: 0.5;
      border: 1px solid green;
    }
    .page-container.selected-for-keep::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(76, 175, 80, 0.2);
      z-index: 2;
      pointer-events: none;
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
    .selection-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background-color: green;
      color: white;
      border-radius: 50%;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 18px;
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
    .remove-page-btn {
      background-color: green;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 8px 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    .remove-page-btn:hover {
      background-color: green;
    }
    .keep-page-btn {
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 8px 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    .keep-page-btn:hover {
      background-color: #388E3C;
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

<h1 id="title">Extract PDF Pages</h1>

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
      <button class="btn select-all-btn" id="selectAllBtn">Select All Pages</button>
      <button class="btn deselect-all-btn" id="deselectAllBtn">Deselect All</button>
      <button class="btn apply-btn" id="applyBtn">Extract Selected Pages</button>
    </div>
  </div>

  <div id="summaryBox" class="summary-box hidden">
    <p><strong id="selectedCount">0</strong> pages selected for extraction out of <strong id="totalCount">0</strong> total pages.</p>
  </div>

  <div id="pdfViewer" class="pdf-viewer">
    <!-- Page previews will be dynamically inserted here -->
  </div>
</div>

<div id="messageContainer" class="hidden"></div>
<div id="resultContainer" class="hidden">
  <a href="#" id="downloadLink" class="btn btn-secondary" target="_blank">Download Modified PDF</a>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
<script>
  // Initialize the PDF.js worker
  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

  document.addEventListener('DOMContentLoaded', function() {
    const translations = {
      'en': {
        'title': 'Extract PDF Pages',
        'dropText': 'Drag and drop a PDF file here, or click to select file',
        'selectFile': 'Select File',
        'selectAllBtn': 'Select All Pages',
        'deselectAllBtn': 'Deselect All',
        'applyBtn': 'Extract Selected Pages',
        'downloadLink': 'Download Extracted PDF',
        'processing': 'Processing PDF...',
        'success': 'PDF pages were successfully extracted!',
        'errorNoFile': 'Please select a PDF file.',
        'errorUpload': 'Error uploading file: ',
        'errorProcess': 'Error processing PDF file: ',
        'remove': 'Remove',
        'removePage': 'Extract Page',
        'keepPage': 'Unselect',
        'page': 'Page',
        'of': 'of',
        'pagesSelected': 'pages selected for extraction out of',
        'totalPages': 'total pages',
        'noSelection': 'No pages selected for extraction.',
        'back': '← Back to Dashboard'

      },
      'sk': {
        'title': 'Extrahovanie PDF stránok',
        'dropText': 'Pretiahnite PDF súbor sem, alebo kliknite pre výber súboru',
        'selectFile': 'Vybrať súbor',
        'selectAllBtn': 'Vybrať všetky stránky',
        'deselectAllBtn': 'Zrušiť výber',
        'applyBtn': 'Extrahovať vybrané stránky',
        'downloadLink': 'Stiahnuť extrahovaný PDF',
        'processing': 'Spracovávam PDF...',
        'success': 'PDF stránky boli úspešne extrahované!',
        'errorNoFile': 'Vyberte PDF súbor.',
        'errorUpload': 'Chyba pri nahrávaní súboru: ',
        'errorProcess': 'Chyba pri spracovaní PDF súboru: ',
        'remove': 'Odstrániť',
        'removePage': 'Extrahovať stránku',
        'keepPage': 'Zrušiť výber',
        'page': 'Strana',
        'of': 'z',
        'pagesSelected': 'stránok vybraných na extrakciu z',
        'totalPages': 'všetkých stránok',
        'noSelection': 'Nie sú vybrané žiadne stránky na extrakciu.',
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
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
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
    const summaryBox = document.getElementById('summaryBox');
    const selectedCount = document.getElementById('selectedCount');
    const totalCount = document.getElementById('totalCount');
    const back = document.getElementById('back');

    // PDF viewer variables
    let pdfDoc = null;
    let currentPage = 1;
    let pagesPerView = 3;
    let pagesToKeep = [];

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
      selectAllBtn.textContent = translations[lang].selectAllBtn;
      deselectAllBtn.textContent = translations[lang].deselectAllBtn;
      applyBtn.textContent = translations[lang].applyBtn;
      downloadLink.textContent = translations[lang].downloadLink;
      back.textContent = translations[lang].back;

      // Update page indicator
      updatePageIndicator();

      // Update remove buttons
      document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.textContent = translations[lang].remove;
      });

      // Update page buttons
      document.querySelectorAll('.keep-page-btn').forEach(btn => {
        btn.textContent = translations[lang].removePage;
      });
      document.querySelectorAll('.remove-page-btn').forEach(btn => {
        btn.textContent = translations[lang].keepPage;
      });

      // Update page numbers
      document.querySelectorAll('.page-number').forEach((el, index) => {
        el.textContent = `${translations[lang].page} ${index + currentPage}`;
      });

      // Update summary box
      updateSummaryBox();
    }

    // Update page indicator text
    function updatePageIndicator() {
      if (pdfDoc) {
        const lastPage = Math.min(currentPage + pagesPerView - 1, pdfDoc.numPages);
        pageIndicator.textContent = `${translations[currentLang].page} ${currentPage}-${lastPage} ${translations[currentLang].of} ${pdfDoc.numPages}`;
      }
    }

    // Update summary box
    function updateSummaryBox() {
      if (pdfDoc) {
        selectedCount.textContent = pagesToKeep.length;
        totalCount.textContent = pdfDoc.numPages;

        if (pagesToKeep.length > 0) {
          summaryBox.classList.remove('hidden');
        } else {
          summaryBox.classList.add('hidden');
        }
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
      pagesToKeep = [];

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
        pagesToKeep = [];
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

        // Init pages to remove array
        pagesToKeep = [];

        // Show the PDF viewer
        viewerContainer.classList.remove('hidden');

        // Render the first set of pages
        currentPage = 1;
        renderPages();

        // Update page indicator
        updatePageIndicator();

        // Update summary box
        updateSummaryBox();

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
          pageContainer.dataset.pageNum = i;

          // Check if this page is marked for removal
          if (pagesToKeep.includes(i)) {
            pageContainer.classList.add('selected-for-keep');

            // Add X badge
            const selectionBadge = document.createElement('div');
            selectionBadge.className = 'selection-badge';
            selectionBadge.textContent = '✔';
            pageContainer.appendChild(selectionBadge);
          }

          // Create page content container
          const pageContent = document.createElement('div');
          pageContent.className = 'page-content';

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

          // Add page action buttons
          const pageTools = document.createElement('div');
          pageTools.className = 'page-tools';

          if (pagesToKeep.includes(i)) {
            // Keep button
            const keepBtn = document.createElement('button');
            keepBtn.className = 'keep-page-btn';
            keepBtn.textContent = translations[currentLang].keepPage;
            keepBtn.addEventListener('click', function(e) {
              e.preventDefault();

              // Get the current scroll position
              const scrollPosition = window.scrollY;

              // Keep the page (remove from pagesToKeep)
              togglePageSelection(i);

              // Restore scroll position after a small delay to allow DOM updates
              setTimeout(() => {
                window.scrollTo({
                  top: scrollPosition,
                  behavior: 'auto'
                });
              }, 10);
            });
            pageTools.appendChild(keepBtn);
          } else {
            // Remove button
            const removeBtn = document.createElement('button');
            removeBtn.className = 'keep-page-btn';
            removeBtn.textContent = translations[currentLang].removePage;
            removeBtn.addEventListener('click', function(e) {
              e.preventDefault();

              // Get the current scroll position
              const scrollPosition = window.scrollY;

              // Mark the page for removal
              togglePageSelection(i);

              // Restore scroll position after a small delay to allow DOM updates
              setTimeout(() => {
                window.scrollTo({
                  top: scrollPosition,
                  behavior: 'auto'
                });
              }, 10);
            });
            pageTools.appendChild(removeBtn);
          }

          pageContainer.appendChild(pageTools);
          pdfViewer.appendChild(pageContainer);

        } catch (error) {
          console.error(`Error rendering page ${i}:`, error);
        }
      }
    }

    // Toggle page removal selection
    function togglePageSelection(pageNum) {
      const index = pagesToKeep.indexOf(pageNum);
      if (index > -1) {
        // Remove page from the array
        pagesToKeep .splice(index, 1);
      } else {
        // Add page to the array
        pagesToKeep.push(pageNum);
        // Sort array to maintain page order
        pagesToKeep.sort((a, b) => a - b);
      }

      // Update the current page view
      const pageContainer = document.querySelector(`.page-container[data-page-num="${pageNum}"]`);
      if (pageContainer) {
        if (pagesToKeep.includes(pageNum)) {
          pageContainer.classList.add('selected-for-keep');

          // Add X badge if not exists
          if (!pageContainer.querySelector('.selection-badge')) {
            const selectionBadge = document.createElement('div');
            selectionBadge.className = 'selection-badge';
            selectionBadge.textContent = '✔';
            pageContainer.appendChild(selectionBadge);
          }

          // Replace buttons
          const pageTools = pageContainer.querySelector('.page-tools');
          pageTools.innerHTML = '';

          const keepBtn = document.createElement('button');
          keepBtn.className = 'keep-page-btn';
          keepBtn.textContent = translations[currentLang].keepPage;
          keepBtn.addEventListener('click', function(e) {
            e.preventDefault();
            togglePageSelection(pageNum);
          });
          pageTools.appendChild(keepBtn);

        } else {
          pageContainer.classList.remove('selected-for-keep');

          // Remove X badge if exists
          const selectionBadge = pageContainer.querySelector('.selection-badge');
          if (selectionBadge) {
            pageContainer.removeChild(selectionBadge);
          }

          // Replace buttons
          const pageTools = pageContainer.querySelector('.page-tools');
          pageTools.innerHTML = '';

          const removeBtn = document.createElement('button');
          removeBtn.className = 'remove-page-btn';
          removeBtn.textContent = translations[currentLang].removePage;
          removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            togglePageSelection(pageNum);
          });
          pageTools.appendChild(removeBtn);
        }
      }

      // Update summary
      updateSummaryBox();
    }

    // Pagination handlers
    prevPage.addEventListener('click', function(e) {
      e.preventDefault();
      if (currentPage > 1) {
        currentPage = Math.max(1, currentPage - pagesPerView);
        renderPages();
        updatePageIndicator();
        // Scroll to the top of the viewer
        viewerContainer.scrollIntoView({ behavior: 'smooth' });
      }
    });

    nextPage.addEventListener('click', function(e) {
      e.preventDefault();
      if (currentPage + pagesPerView <= pdfDoc.numPages) {
        currentPage += pagesPerView;
        renderPages();
        updatePageIndicator();
        // Scroll to the top of the viewer
        viewerContainer.scrollIntoView({ behavior: 'smooth' });
      }
    });

    // Select all pages
    selectAllBtn.addEventListener('click', function(e) {
      e.preventDefault();

      // Mark all pages for removal
      pagesToKeep = [];
      for (let i = 1; i <= pdfDoc.numPages; i++) {
        pagesToKeep.push(i);
      }

      // Re-render current view
      renderPages();

      // Update summary
      updateSummaryBox();
    });

    // Deselect all pages
    deselectAllBtn.addEventListener('click', function(e) {
      e.preventDefault();

      // Clear pages to remove
      pagesToKeep = [];

      // Re-render current view
      renderPages();

      // Update summary
      updateSummaryBox();
    });


    applyBtn.addEventListener('click', function(e) {
      e.preventDefault();

      if (!selectedFile) {
        showMessage('error', translations[currentLang].errorNoFile);
        return;
      }

      // Check if any pages are selected for extract
      if (pagesToKeep.length === 0) {
        showMessage('info', translations[currentLang].noSelection);
        return;
      }


      // Process the PDF to extract pages
      processExtraction();
    });

    // Process removal of pages
    async function processExtraction() {
      showMessage('info', translations[currentLang].processing);
      applyBtn.disabled = true;

      const formData = new FormData();
      formData.append('file', selectedFile);
      formData.append('pages_to_keep', JSON.stringify(pagesToKeep));

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

          fetch('/myapp/backend/api/api_extract_pdf.php', {
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

                      // Modified download link handling - now with headers
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
                              downloadLink.setAttribute('download', 'extracted.pdf');
                              resultContainer.classList.remove('hidden');
                          })
                          .catch(error => {
                              console.error('Download error:', error);
                              showMessage('error', translations[currentLang].errorDownloading + error.message);
                          });

                  } else {
                      throw new Error(data.error || 'Unknown error');
                  }
              })
              .catch(error => {
                  console.error('PDF Processing Error:', error);
                  showMessage('error', translations[currentLang].errorProcess + error.message);
              })
              .finally(() => {
                  applyBtn.disabled = false;
              });
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