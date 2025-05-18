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
    <title>Split PDF File</title>
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

<h1 id="title">Split PDF File</h1>

<div class="dropzone" id="dropzone">
    <p id="dropText">Drag and drop a PDF file here, or click to select file</p>
    <input type="file" id="fileInput" accept=".pdf">
    <button class="btn btn-secondary" id="selectFileBtn">Select File</button>
</div>

<ul id="fileList"></ul>

<div id="actions">
    <button class="btn" id="splitBtn" disabled>Split PDF File</button>
</div>

<div id="messageContainer" class="hidden"></div>
<div id="resultContainer" class="hidden">
    <a href="#" id="downloadLink" class="btn btn-secondary" target="_blank">Download Split PDF Files (ZIP)</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Translation object
        const translations = {
            'en': {
                'title': 'Split PDF File',
                'dropText': 'Drag and drop a PDF file here, or click to select file',
                'selectFile': 'Select File',
                'splitBtn': 'Split PDF File',
                'downloadLink': 'Download Split PDF Files (ZIP)',
                'splitting': 'Splitting PDF file...',
                'success': 'PDF file was successfully split!',
                'errorNoFile': 'Please select a PDF file to split.',
                'errorUpload': 'Error uploading file: ',
                'errorSplit': 'Error splitting PDF file: ',
                'remove': 'Remove',
                'back': '← Back to Dashboard'
            },
            'sk': {
                'title': 'Rozdelenie PDF súboru',
                'dropText': 'Pretiahnite PDF súbor sem, alebo kliknite pre výber súboru',
                'selectFile': 'Vybrať súbor',
                'splitBtn': 'Rozdeliť PDF súbor',
                'downloadLink': 'Stiahnuť rozdelené PDF súbory (ZIP)',
                'splitting': 'Rozdeľovanie PDF súboru...',
                'success': 'PDF súbor bol úspešne rozdelený!',
                'errorNoFile': 'Vyberte PDF súbor na rozdelenie.',
                'errorUpload': 'Chyba pri nahrávaní súboru: ',
                'errorSplit': 'Chyba pri rozdeľovaní PDF súboru: ',
                'remove': 'Odstrániť',
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
        const splitBtn = document.getElementById('splitBtn');
        const messageContainer = document.getElementById('messageContainer');
        const resultContainer = document.getElementById('resultContainer');
        const downloadLink = document.getElementById('downloadLink');
        const title = document.getElementById('title');
        const dropText = document.getElementById('dropText');
        const langEn = document.getElementById('lang-en');
        const langSk = document.getElementById('lang-sk');
        const back = document.getElementById('back');

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
            splitBtn.textContent = translations[lang].splitBtn;
            downloadLink.textContent = translations[lang].downloadLink;
            back.textContent = translations[lang].back;

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

            // Update button state
            updateSplitButton();

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
                // Update button state
                updateSplitButton();
            });

            li.appendChild(removeBtn);
            fileList.appendChild(li);

        }

        // Update the split button state
        function updateSplitButton() {
            splitBtn.disabled = !selectedFile;
        }

        // Split button click event
        splitBtn.addEventListener('click', splitPDF);

        async function splitPDF() {
            if (!selectedFile) {
                showMessage('error', translations[currentLang].errorNoFile);
                return;
            }

            showMessage('info', translations[currentLang].splitting);

            // Disable split button
            splitBtn.disabled = true;

            // Create a FormData instance
            const formData = new FormData();

            // Add file to FormData
            formData.append('file', selectedFile);

            // Log for debugging
            console.log('Sending request to split PDF...');

            try {
                const keyResponse = await fetch('/myapp/backend/api/api_api_key.php', {
                    credentials: 'include'
                });
                console.log('API key response status:', keyResponse.status);
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
                fetch('/myapp/backend/api/api_split_pdf.php', {
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

                            // Set download link
                            downloadLink.href = `/myapp/backend/pdf/download_zip_pdf.php?id=${data.result_id}`;
                            resultContainer.classList.remove('hidden');

                            console.log('PDF split successful. Result ID:', data.result_id);
                        } else {
                            throw new Error(data.error || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        console.error('PDF Split Error:', error);
                        showMessage('error', translations[currentLang].errorSplit + error.message);
                    })
            } catch (error) {
                console.error('PDF Split Error:', error);
                showMessage('error', translations[currentLang].errorMerge + error.message);
            } finally {
                splitBtn.disabled = false;
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