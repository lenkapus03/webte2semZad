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
    <title>Watermark PDF File</title>
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
            overflow: hidden;
        }
        .file-name {
            flex-grow: 1;
            margin-right: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: calc(100% - 120px);
        }
        .file-item {
            display: flex;
            align-items: center;
            min-width: 0;
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
            flex-shrink: 0;
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
        .watermark-options {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .watermark-options label {
            display: block;
            margin-bottom: 10px;
        }
        .watermark-options input,
        .watermark-options select {
            margin-bottom: 15px;
            padding: 8px;
            width: 100%;
            box-sizing: border-box;
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
        .color-preview {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 1px solid #ddd;
            border-radius: 3px;
            vertical-align: middle;
            margin-left: 10px;
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

<h1 id="title">Watermark PDF File</h1>

<div class="dropzone" id="dropzone">
    <p id="dropText">Drag and drop a PDF file here, or click to select file</p>
    <input type="file" id="fileInput" accept=".pdf">
    <button class="btn btn-secondary" id="selectFileBtn">Select File</button>
</div>

<ul id="fileList"></ul>

<div class="watermark-options">
    <h3>Watermark Settings</h3>
    <label for="watermarkText">Watermark Text:</label>
    <input type="text" id="watermarkText" placeholder="Enter watermark text" value="CONFIDENTIAL">

    <label for="watermarkPosition">Position:</label>
    <select id="watermarkPosition">
        <option value="center">Center</option>
        <option value="top-left">Top Left</option>
        <option value="top-right">Top Right</option>
        <option value="bottom-left">Bottom Left</option>
        <option value="bottom-right">Bottom Right</option>
    </select>

    <label for="watermarkOpacity">Opacity (0-100%):</label>
    <input type="range" id="watermarkOpacity" min="0" max="100" value="50">
    <span id="opacityValue">50%</span>

    <label for="watermarkColor">Color:</label>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input type="color" id="watermarkColor" value="#000000">
        <span id="colorHexValue">#000000</span>
        <div class="color-preview" id="colorPreview" style="background-color: #000000;"></div>
    </div>

    <label for="watermarkFontSize">Font Size:</label>
    <input type="number" id="watermarkFontSize" min="10" max="72" value="36">

    <label for="watermarkRotation">Rotation Angle (0-360°):</label>
    <input type="range" id="watermarkRotation" min="0" max="360" value="45">
    <span id="rotationValue">45°</span>
</div>

<div id="actions">
    <button class="btn" id="watermarkBtn" disabled>Add Watermark to PDF</button>
</div>

<div id="messageContainer" class="hidden"></div>
<div id="resultContainer" class="hidden">
    <a href="#" id="downloadLink" class="btn btn-secondary" target="_blank">Download Watermarked PDF</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Translation object
        const translations = {
            'en': {
                'title': 'Watermark PDF File',
                'dropText': 'Drag and drop a PDF file here, or click to select file',
                'selectFile': 'Select File',
                'watermarkBtn': 'Add Watermark to PDF',
                'downloadLink': 'Download Watermarked PDF',
                'processing': 'Adding watermark to PDF file...',
                'success': 'Watermark was successfully added to PDF!',
                'errorNoFile': 'Please select a PDF file to watermark.',
                'errorUpload': 'Error uploading file: ',
                'errorWatermark': 'Error adding watermark to PDF: ',
                'remove': 'Remove',
                'watermarkSettings': 'Watermark Settings',
                'watermarkText': 'Watermark Text',
                'position': 'Position',
                'opacity': 'Opacity',
                'color': 'Color',
                'fontSize': 'Font Size',
                'rotation': 'Rotation Angle',
                'back': '← Back to Dashboard'
            },
            'sk': {
                'title': 'Pridať vodoznak do PDF',
                'dropText': 'Pretiahnite PDF súbor sem, alebo kliknite pre výber súboru',
                'selectFile': 'Vybrať súbor',
                'watermarkBtn': 'Pridať vodoznak do PDF',
                'downloadLink': 'Stiahnuť PDF s vodoznakom',
                'processing': 'Pridávanie vodoznaku do PDF súboru...',
                'success': 'Vodoznak bol úspešne pridaný do PDF!',
                'errorNoFile': 'Vyberte PDF súbor pre pridanie vodoznaku.',
                'errorUpload': 'Chyba pri nahrávaní súboru: ',
                'errorWatermark': 'Chyba pri pridávaní vodoznaku do PDF: ',
                'remove': 'Odstrániť',
                'watermarkSettings': 'Nastavenia vodoznaku',
                'watermarkText': 'Text vodoznaku',
                'position': 'Pozícia',
                'opacity': 'Priehľadnosť',
                'color': 'Farba',
                'fontSize': 'Veľkosť písma',
                'rotation': 'Uhol otočenia',
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
        const watermarkBtn = document.getElementById('watermarkBtn');
        const messageContainer = document.getElementById('messageContainer');
        const resultContainer = document.getElementById('resultContainer');
        const downloadLink = document.getElementById('downloadLink');
        const title = document.getElementById('title');
        const dropText = document.getElementById('dropText');
        const langEn = document.getElementById('lang-en');
        const langSk = document.getElementById('lang-sk');
        const watermarkText = document.getElementById('watermarkText');
        const watermarkPosition = document.getElementById('watermarkPosition');
        const watermarkOpacity = document.getElementById('watermarkOpacity');
        const opacityValue = document.getElementById('opacityValue');
        const watermarkColor = document.getElementById('watermarkColor');
        const watermarkFontSize = document.getElementById('watermarkFontSize');
        const colorHexValue = document.getElementById('colorHexValue');
        const colorPreview = document.getElementById('colorPreview');
        const watermarkRotation = document.getElementById('watermarkRotation');
        const rotationValue = document.getElementById('rotationValue');
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

        // Update opacity value display
        watermarkOpacity.addEventListener('input', function() {
            opacityValue.textContent = `${this.value}%`;
        });

        watermarkColor.addEventListener('input', function() {
            colorHexValue.textContent = this.value;
            colorPreview.style.backgroundColor = this.value;
        });

        watermarkRotation.addEventListener('input', function() {
            rotationValue.textContent = `${this.value}°`;
        });

        // Function to set the language
        function setLanguage(lang) {
            currentLang = lang;
            title.textContent = translations[lang].title;
            dropText.textContent = translations[lang].dropText;
            selectFileBtn.textContent = translations[lang].selectFile;
            watermarkBtn.textContent = translations[lang].watermarkBtn;
            downloadLink.textContent = translations[lang].downloadLink;
            back.textContent = translations[lang].back;

            // Update watermark settings labels
            document.querySelector('.watermark-options h3').textContent = translations[lang].watermarkSettings;
            document.querySelector('label[for="watermarkText"]').textContent = translations[lang].watermarkText;
            document.querySelector('label[for="watermarkPosition"]').textContent = translations[lang].position;
            document.querySelector('label[for="watermarkOpacity"]').textContent = translations[lang].opacity;
            document.querySelector('label[for="watermarkColor"]').textContent = translations[lang].color;
            document.querySelector('label[for="watermarkFontSize"]').textContent = translations[lang].fontSize;

            // Update remove buttons
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.textContent = translations[lang].remove;
            });

            document.querySelector('label[for="watermarkRotation"]').textContent = translations[lang].rotation;
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
            updateWatermarkButton();

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
            fileIcon.innerHTML = '📄';
            fileItem.appendChild(fileIcon);

            const fileName = document.createElement('div');
            fileName.className = 'file-name';
            fileName.textContent = file.name;
            fileName.title = file.name; // Show full name on hover
            fileItem.appendChild(fileName);

            li.appendChild(fileItem);

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-btn';
            removeBtn.textContent = translations[currentLang].remove;
            removeBtn.addEventListener('click', function() {
                selectedFile = null;
                fileList.removeChild(li);
                updateWatermarkButton();
            });

            li.appendChild(removeBtn);
            fileList.appendChild(li);
        }

        // Update the watermark button state
        function updateWatermarkButton() {
            watermarkBtn.disabled = !selectedFile || !watermarkText.value.trim();
        }

        // Update button when watermark text changes
        watermarkText.addEventListener('input', updateWatermarkButton);

        // Watermark button click event
        watermarkBtn.addEventListener('click', addWatermark);

        async function addWatermark() {
            if (!selectedFile) {
                showMessage('error', translations[currentLang].errorNoFile);
                return;
            }

            if (!watermarkText.value.trim()) {
                showMessage('error', translations[currentLang].watermarkText + ' is required');
                return;
            }

            showMessage('info', translations[currentLang].processing);

            // Disable watermark button
            watermarkBtn.disabled = true;

            // Create a FormData instance
            const formData = new FormData();

            // Add file to FormData
            formData.append('file', selectedFile);

            // Add watermark settings
            formData.append('watermark_text', watermarkText.value);
            formData.append('position', watermarkPosition.value);
            formData.append('opacity', watermarkOpacity.value / 100); // Convert to 0-1 range
            formData.append('color', watermarkColor.value);
            formData.append('font_size', watermarkFontSize.value);
            formData.append('rotation', watermarkRotation.value);

            // Log for debugging
            console.log('Sending request to add watermark to PDF...');

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

                // Send request to the server
                fetch('/myapp/backend/api/api_watermark_pdf.php', {
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
                                    downloadLink.setAttribute('download', 'watermarked.pdf');
                                    resultContainer.classList.remove('hidden');
                                })
                                .catch(error => {
                                    console.error('Download error:', error);
                                    showMessage('error', translations[currentLang].errorDownloading + error.message);
                                });

                            console.log('Watermark added successfully. Result ID:', data.result_id);
                        } else {
                            throw new Error(data.error || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        console.error('Watermark Error:', error);
                        showMessage('error', translations[currentLang].errorWatermark + error.message);
                    })
            } catch (error) {
                console.error('PDF Processing Error:', error);
                showMessage('error', translations[currentLang].errorMerge + error.message);
            } finally {
                watermarkBtn.disabled = false;
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