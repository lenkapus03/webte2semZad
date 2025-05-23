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
    <title>Merge PDF Files</title>
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
<div class="navigation">
    <a id="back" href="/myapp/index.php">← Back to Dashboard</a>
</div>

<div class="language-switcher">
    <a href="#" id="lang-en">English</a> | <a href="#" id="lang-sk">Slovensky</a>
</div>

<h1 id="title">Merge PDF Files</h1>

<div class="dropzone" id="dropzone">
    <p id="dropText">Drag and drop PDF files here, or click to select files</p>
    <input type="file" id="fileInput" multiple accept=".pdf">
    <button class="btn btn-secondary" id="selectFilesBtn">Select Files</button>
</div>

<ul id="fileList"></ul>

<div id="actions">
    <button class="btn" id="mergeBtn" disabled>Merge PDF Files</button>
</div>

<div id="messageContainer" class="hidden"></div>
<div id="resultContainer" class="hidden">
    <a href="#" id="downloadLink" class="btn btn-secondary" target="_blank">Download Merged PDF</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Translation object
        const translations = {
            'en': {
                'title': 'Merge PDF Files',
                'dropText': 'Drag and drop PDF files here, or click to select files',
                'selectFiles': 'Select Files',
                'mergeBtn': 'Merge PDF Files',
                'downloadLink': 'Download Merged PDF',
                'merging': 'Merging PDF files...',
                'success': 'PDF files were successfully merged!',
                'errorNoFiles': 'Please select at least 2 PDF files to merge.',
                'errorUpload': 'Error uploading files: ',
                'errorMerge': 'Error merging PDF files: ',
                'remove': 'Remove',
                'back': '← Back to Dashboard'
            },
            'sk': {
                'title': 'Spájanie PDF súborov',
                'dropText': 'Pretiahnite PDF súbory sem, alebo kliknite pre výber súborov',
                'selectFiles': 'Vybrať súbory',
                'mergeBtn': 'Spojiť PDF súbory',
                'downloadLink': 'Stiahnuť spojený PDF',
                'merging': 'Spájanie PDF súborov...',
                'success': 'PDF súbory boli úspešne spojené!',
                'errorNoFiles': 'Vyberte aspoň 2 PDF súbory na spojenie.',
                'errorUpload': 'Chyba pri nahrávaní súborov: ',
                'errorMerge': 'Chyba pri spájaní PDF súborov: ',
                'remove': 'Odstrániť',
                'back': '← Späť na prehľad'
            }
        };

        // Set default language
        let currentLang = 'en';

        // DOM elements
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        const selectFilesBtn = document.getElementById('selectFilesBtn');
        const fileList = document.getElementById('fileList');
        const mergeBtn = document.getElementById('mergeBtn');
        const messageContainer = document.getElementById('messageContainer');
        const resultContainer = document.getElementById('resultContainer');
        const downloadLink = document.getElementById('downloadLink');
        const title = document.getElementById('title');
        const dropText = document.getElementById('dropText');
        const langEn = document.getElementById('lang-en');
        const langSk = document.getElementById('lang-sk');
        const back = document.getElementById('back');

        // Files array to store selected files
        let selectedFiles = [];

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
            back.textContent = translations[lang].back;
            title.textContent = translations[lang].title;
            dropText.textContent = translations[lang].dropText;
            selectFilesBtn.textContent = translations[lang].selectFiles;
            mergeBtn.textContent = translations[lang].mergeBtn;
            downloadLink.textContent = translations[lang].downloadLink;

            // Update remove buttons
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.textContent = translations[lang].remove;
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
            handleFiles(files);
        });

        // Click events for file selection
        // Improved event handling
        dropzone.addEventListener('click', function(e) {
            if (e.target !== selectFilesBtn && !selectFilesBtn.contains(e.target)) {
                fileInput.click();
            }
        });

        selectFilesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                handleFiles(this.files);
                this.value = ''; // Reset to allow selecting the same file again
            }
        });

        // Handle the selected files
        function handleFiles(files) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];

                // Check if file is a PDF
                if (file.type !== 'application/pdf') {
                    showMessage('error', `File "${file.name}" is not a PDF. Skipping.`);
                    continue;
                }

                // Add file to the array
                selectedFiles.push(file);

                // Add file to the list
                addFileToList(file, selectedFiles.length - 1);
            }

            updateMergeButton();
        }

        // Add a file to the list
        function addFileToList(file, index) {
            const li = document.createElement('li');

            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';

            const dragHandle = document.createElement('span');
            dragHandle.className = 'drag-handle';
            dragHandle.innerHTML = '&#9776;'; // Hamburger icon
            fileItem.appendChild(dragHandle);

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
                // Remove from array
                selectedFiles.splice(index, 1);
                // Remove from list
                fileList.removeChild(li);
                // Update button state
                updateMergeButton();
                // Update indices for remaining files
                updateFileIndices();
            });

            li.appendChild(removeBtn);

            // Set data attribute for dragging
            li.setAttribute('draggable', 'true');
            li.setAttribute('data-index', index);

            // Add drag event listeners
            li.addEventListener('dragstart', handleDragStart);
            li.addEventListener('dragover', handleDragOver);
            li.addEventListener('drop', handleDrop);
            li.addEventListener('dragend', handleDragEnd);

            fileList.appendChild(li);
        }

        // Update the merge button state
        function updateMergeButton() {
            mergeBtn.disabled = selectedFiles.length < 2;
        }

        // Update file indices after removal
        function updateFileIndices() {
            const items = fileList.querySelectorAll('li');
            items.forEach((item, index) => {
                item.setAttribute('data-index', index);
            });
        }

        // Drag and drop reordering
        let dragSrcEl = null;

        function handleDragStart(e) {
            this.style.opacity = '0.4';
            dragSrcEl = this;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            return false;
        }

        function handleDrop(e) {
            e.stopPropagation();
            e.preventDefault();

            if (dragSrcEl !== this) {
                // Get indices
                const srcIndex = parseInt(dragSrcEl.getAttribute('data-index'));
                const destIndex = parseInt(this.getAttribute('data-index'));

                // Reorder the files array
                const temp = selectedFiles[srcIndex];

                if (srcIndex < destIndex) {
                    // Moving down
                    for (let i = srcIndex; i < destIndex; i++) {
                        selectedFiles[i] = selectedFiles[i + 1];
                    }
                } else {
                    // Moving up
                    for (let i = srcIndex; i > destIndex; i--) {
                        selectedFiles[i] = selectedFiles[i - 1];
                    }
                }

                selectedFiles[destIndex] = temp;

                // Refresh the list
                refreshFileList();
            }

            return false;
        }

        function handleDragEnd() {
            this.style.opacity = '1';
        }

        // Refresh the file list after reordering
        function refreshFileList() {
            fileList.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                addFileToList(file, index);
            });
        }

        // Merge button click event
        mergeBtn.addEventListener('click', mergePDFs);

        async function mergePDFs() {
            if (selectedFiles.length < 2) {
                showMessage('error', translations[currentLang].errorNoFiles);
                return;
            }

            showMessage('info', translations[currentLang].merging);

            // Disable merge button
            mergeBtn.disabled = true;

            // Create a FormData instance
            const formData = new FormData();

            // Add files to FormData
            for (let i = 0; i < selectedFiles.length; i++) {
                formData.append('files[]', selectedFiles[i]);
            }

            // Log for debugging
            console.log('Sending request to merge PDFs...');
            console.log('Number of files:', selectedFiles.length);

            try {
                // First, get the API key
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
                // Now proceed with the merge operation using the API key
                fetch('/myapp/backend/api/api_merge_pdfs.php', {
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

                        return response.text().then(text => {
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('Failed to parse JSON response:', text);
                                throw new Error('Server returned invalid JSON response');
                            }
                        });
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
                                    downloadLink.setAttribute('download', 'merged.pdf');
                                    resultContainer.classList.remove('hidden');
                                })
                                .catch(error => {
                                    console.error('Download error:', error);
                                    showMessage('error', translations[currentLang].errorDownloading + error.message);
                                });

                            console.log('PDF merge successful. Result ID:', data.result_id);
                        } else {
                            throw new Error(data.error || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        console.error('PDF Merge Error:', error);
                        showMessage('error', translations[currentLang].errorMerge + error.message);
                    })
            } catch (error) {
                console.error('PDF Merge Error:', error);
                showMessage('error', translations[currentLang].errorMerge + error.message);
            } finally {
                // Re-enable merge button
                updateMergeButton();
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