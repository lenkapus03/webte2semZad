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
  <title>Unlock PDF</title>
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
    .file-info {
      padding: 10px;
      border: 1px solid #ddd;
      margin-bottom: 10px;
      border-radius: 4px;
      background-color: #f5f5f5;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .file-name {
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
    .form-group {
      margin-bottom: 15px;
    }
    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }
    .options-container {
      background-color: #f8f8f8;
      border: 1px solid #e0e0e0;
      border-radius: 5px;
      padding: 20px 15px 10px 15px;
      margin: 15px 0;
    }
    .password-container {
      position: relative;
      width: 100%;
    }
    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      box-sizing: border-box;
    }
    .password-info {
      font-size: 0.9em;
      color: #666;
      margin: 5px 0 0 0;
    }
    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #666;
      width: 20px;
      height: 20px;
    }
    @media (max-width: 600px) {
      .dropzone {
        padding: 15px;
      }
      .file-info {
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

<h1 id="title">Unlock PDF</h1>

<div class="dropzone" id="dropzone">
  <p id="dropText">Drag and drop a PDF file here, or click to select a file</p>
  <input type="file" id="fileInput" accept=".pdf">
  <button class="btn btn-secondary" id="selectFileBtn">Select File</button>
</div>

<div id="fileInfo" class="hidden"></div>

<div class="options-container hidden" id="optionsContainer">
  <div class="form-group">
    <label for="password" id="passwordLabel">Enter PDF Password:</label>
    <div class="password-container">
      <input type="password" id="password">
      <div class="password-toggle" id="togglePassword">
        <!-- Eye Icon (visible when password is hidden) -->
        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        </svg>
        <!-- Crossed Eye Icon (visible when password is shown) -->
        <svg id="eyeIconCrossed" class="hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
          <line x1="1" y1="1" x2="23" y2="23"></line>
        </svg>
      </div>
    </div>
    <p class="password-info" id="passwordInfo">This password will be required to open the PDF file</p>
  </div>
</div>

<div id="actions" class="hidden">
  <button class="btn" id="decryptPdfBtn">Unlock PDF</button>
</div>

<div id="messageContainer" class="hidden"></div>
<div id="resultContainer" class="hidden">
  <a href="#" id="downloadLink" class="btn btn-secondary" target="_blank">Download Unprotected PDF</a>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Translation object
    const translations = {
      'en': {
        'title': 'Password Protect PDF',
        'dropText': 'Drag and drop a PDF file here, or click to select a file',
        'selectFile': 'Select File',
        'decryptPdfBtn': 'Unlock PDF',
        'downloadLink': 'Download Unprotected PDF',
        'processing': 'Removing password from PDF...',
        'success': 'PDF password was successfully removed!',
        'errorNoFile': 'Please select a PDF file first.',
        'errorNoPassword': 'Please enter a password.',
        'errorUpload': 'Error uploading file: ',
        'errorProcessing': 'Error removing password from PDF:  ',
        'remove': 'Remove',
        'optionsTitle': 'Set Password',
        'passwordLabel': 'PDF Password:',
        'passwordInfo': 'This password is required to unlock the PDF before removing it',
        'fileSelected': 'Selected file:',
        'back': '← Back to Dashboard'

      },
      'sk': {
        'title': 'Odstrániť heslo z PDF',
        'dropText': 'Pretiahnite PDF súbor sem, alebo kliknite pre výber súboru',
        'selectFile': 'Vybrať súbor',
        'decryptPdfBtn': 'Odomknúť PDF',
        'downloadLink': 'Stiahnuť odomknutý PDF',
        'processing': 'Odstraňujem heslo z PDF...',
        'success': 'Heslo z PDF bolo úspešne odstránené!',
        'errorNoFile': 'Najskôr vyberte PDF súbor.',
        'errorNoPassword': 'Zadajte heslo.',
        'errorUpload': 'Chyba pri nahrávaní súboru: ',
        'errorProcessing': 'Chyba pri odstraňovaní hesla z PDF: ',
        'remove': 'Odstrániť',
        'optionsTitle': 'Zadajte heslo',
        'passwordLabel': 'Heslo PDF:',
        'passwordInfo': 'Toto heslo je potrebné na odomknutie PDF pred jeho spracovaním',
        'fileSelected': 'Vybraný súbor:',
        'back': '← Späť na prehľad'
      }
    };

    // Set default language
    let currentLang = 'en';

    // DOM elements
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const selectFileBtn = document.getElementById('selectFileBtn');
    const fileInfo = document.getElementById('fileInfo');
    const optionsContainer = document.getElementById('optionsContainer');
    const actions = document.getElementById('actions');
    const decryptPdfBtn = document.getElementById('decryptPdfBtn');
    const messageContainer = document.getElementById('messageContainer');
    const resultContainer = document.getElementById('resultContainer');
    const downloadLink = document.getElementById('downloadLink');
    const title = document.getElementById('title');
    const dropText = document.getElementById('dropText');
    const langEn = document.getElementById('lang-en');
    const langSk = document.getElementById('lang-sk');
    const passwordLabel = document.getElementById('passwordLabel');
    const passwordInfo = document.getElementById('passwordInfo');
    const togglePassword = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeIconCrossed = document.getElementById('eyeIconCrossed');
    const back = document.getElementById('back');

    // File variable to store the selected file
    let selectedFile = null;

    // Password toggle functionality
    togglePassword.addEventListener('click', function() {
      const passwordField = document.getElementById('password');
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeIconCrossed.classList.remove('hidden');
      } else {
        passwordField.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeIconCrossed.classList.add('hidden');
      }
    });

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
      decryptPdfBtn.textContent = translations[lang].decryptPdfBtn;
      downloadLink.textContent = translations[lang].downloadLink;
      passwordLabel.textContent = translations[lang].passwordLabel;
      passwordInfo.textContent = translations[lang].passwordInfo;
      back.textContent = translations[lang].back;

      // Update any dynamic content that might exist
      updateFileInfo();
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

      // Store the file
      selectedFile = file;

      // Update UI
      updateFileInfo();
      optionsContainer.classList.remove('hidden');
      actions.classList.remove('hidden');
    }

    // Update file info display
    function updateFileInfo() {
      if (selectedFile) {
        fileInfo.innerHTML = `
                    <div class="file-info">
                        <div class="file-item">
                            <div class="file-icon">📄</div>
                            <div class="file-name">${translations[currentLang].fileSelected} ${selectedFile.name}</div>
                        </div>
                        <button class="remove-btn">${translations[currentLang].remove}</button>
                    </div>
                `;
        fileInfo.classList.remove('hidden');

        // Add remove button event
        document.querySelector('.remove-btn').addEventListener('click', function() {
          selectedFile = null;
          fileInfo.classList.add('hidden');
          optionsContainer.classList.add('hidden');
          actions.classList.add('hidden');
          resultContainer.classList.add('hidden');
        });
      } else {
        fileInfo.classList.add('hidden');
      }
    }

    // Encrypt PDF button click event
    decryptPdfBtn.addEventListener('click', decryptPdf);

    async function decryptPdf() {
      if (!selectedFile) {
        showMessage('error', translations[currentLang].errorNoFile);
        return;
      }

      const password = document.getElementById('password').value;
      if (!password) {
        showMessage('error', translations[currentLang].errorNoPassword);
        return;
      }

      showMessage('info', translations[currentLang].processing);

      // Disable button while processing
      decryptPdfBtn.disabled = true;

      // Create a FormData instance
      const formData = new FormData();

      // Add file to FormData
      formData.append('file', selectedFile);

      // Add password to FormData
      formData.append('password', password);

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
        fetch('/myapp/backend/api/api_decrypt_pdf.php', {
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
                      try {

                        if ( text.error.includes("Incorrect password")) {
                          throw new Error("Incorrect password. Please try again.");
                        } else {
                          throw new Error(text.error || `Server error: ${response.status}`);
                        }
                      } catch (e) {
                        throw new Error(`Server error: ${response.status}`);
                      }
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
                              downloadLink.setAttribute('download', 'decrypted.pdf');
                              resultContainer.classList.remove('hidden');
                          })
                          .catch(error => {
                              console.error('Download error:', error);
                              showMessage('error', translations[currentLang].errorDownloading + error.message);
                          });

                      console.log('PDF decryption successful. Result ID:', data.result_id);
                  } else {
                      throw new Error(data.error || 'Unknown error');
                  }
                })
            .catch(error => {
                console.error('PDF Decryption Error:', error);
                showMessage('error', translations[currentLang].errorProcessing + error.message);
            })
            .finally(() => {
                // Re-enable button
                decryptPdfBtn.disabled = false;
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