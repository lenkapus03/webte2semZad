<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin')  {
    header("Location: /myapp/auth/login.php");
    exit;
}

$defaultLang = 'sk'; 
$selectedLang = $_GET['lang'] ?? $_SESSION['lang'] ?? $defaultLang;

$_SESSION['lang'] = $selectedLang;

require_once 'lang.php'; 

$t = $lang[$selectedLang]; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User History – Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    :root {
      --primary-color: #4285F4;
      --secondary-color: #34A853;
      --danger-color: #EA4335;
      --light-color: #f8f9fa;
      --dark-color: #343a40;
      --border-radius: 8px;
      --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s ease;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

    .header {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
    }

    .header h1 {
      margin: 0;
      color: var(--primary-color);
      font-size: 2em;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn {
      background-color: var(--primary-color);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: var(--border-radius);
      cursor: pointer;
      box-shadow: var(--box-shadow);
      text-decoration: none;
      font-size: 14px;
      margin: 5px;
      display: inline-block;
    }

    .btn-secondary {
      background-color: var(--secondary-color);
    }

    .btn-danger {
      background-color: var(--danger-color);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      overflow: hidden;
    }

    thead {
      background-color: var(--primary-color);
      color: white;
    }

    th, td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
      word-break: break-word;
    }

    tr:hover {
      background-color: #f1f1f1;
    }

    .delete-btn {
      background-color: var(--danger-color);
      color: white;
      border: none;
      padding: 6px 10px;
      border-radius: 4px;
      cursor: pointer;
    }

    .per-page-selector, .pagination {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 20px;
    }

    .pagination button {
      background-color: var(--primary-color);
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: var(--border-radius);
      cursor: pointer;
      transition: var(--transition);
    }

    .pagination button.active {
      background-color: var(--secondary-color);
    }

    @media (max-width: 768px) {
      table, thead, tbody, th, td, tr {
        display: block;
      }

      thead {
        display: none;
      }

      tr {
        margin-bottom: 15px;
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 10px;
      }

      td {
        border: none;
        padding: 8px 10px;
        position: relative;
        text-align: left;
      }

      td::before {
        content: attr(data-label);
        font-weight: bold;
        display: block;
        margin-bottom: 4px;
        color: var(--primary-color);
      }

      .delete-btn {
        margin-top: 8px;
        width: 100%;
      }
    }
  </style>
</head>
<body>
<div class="container">
  <div class="header">
     <h1><i class="fas fa-history"></i> <?= $t['viewuserhistory'] ?></h1>
    <div>
    <a href="index.php?lang=<?= $selectedLang ?>" class="btn"><i class="fas fa-arrow-left"></i> <?= $t['back'] ?></a>
    <button id="exportCsvBtn" class="btn btn-secondary"><i class="fas fa-file-csv"></i> <?= $t['download_CSV'] ?></button>
    </div>
    <div class="language-switcher" style="text-align:right; margin:10px 0;">
  <a href="?lang=en">English</a> | <a href="?lang=sk">Slovensky</a>
</div>
  </div>

  <table>
    <thead>
    <tr>
    <th><?= $t['username'] ?? 'Username' ?></th>
    <th><?= $t['time'] ?? 'Time' ?></th>
    <th><?= $t['action'] ?? 'Action' ?></th>
    <th><?= $t['source'] ?? 'Source' ?></th>
    <th><?= $t['location'] ?? 'Location' ?></th>
    <th><?= $t['delete'] ?? 'Delete' ?></th>

    </tr>
    </thead>
    <tbody id="historyBody"></tbody>
  </table>

  <div class="per-page-selector">
  <label for="perPage"><?= $t['items_per_page'] ?>:</label>
    <select id="perPage">
      <option value="10">10</option>
      <option value="25">25</option>
      <option value="50">50</option>
      <option value="100">100</option>
    </select>
  </div>

  <div class="pagination" id="pagination"></div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.querySelector("#historyBody");
    const paginationDiv = document.getElementById("pagination");
    const perPageSelect = document.getElementById("perPage");

    let currentPage = 1;
    let perPage = 10;
    let totalPages = 1;

    perPageSelect.value = perPage;
    loadUserHistory();

    function loadUserHistory() {
      const url = `/myapp/backend/users_history.php?page=${currentPage}&per_page=${perPage}`;

      fetch(url, { credentials: "include" })
        .then(response => {
          if (!response.ok) {
            return response.text().then(text => { throw new Error(text); });
          }
          return response.json();
        })
        .then(data => {
          if (Array.isArray(data.data)) {
            renderTable(data.data);
            updatePagination(data.pagination);
          } else {
            showError(data.error || "Unknown error");
          }
        })
        .catch(error => {
          showError(error.message);
          console.error(error);
        });
    }

    function renderTable(entries) {
      tbody.innerHTML = "";
      entries.forEach(entry => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td data-label="Username">${entry.users_username}</td>
          <td data-label="Time">${entry.time}</td>
          <td data-label="Action">${entry.action_type}</td>
          <td data-label="Source">${entry.source}</td>
          <td data-label="Location">${entry.location}</td>
          <td data-label="Delete">
            <button class="delete-btn" data-username="${entry.users_username}" data-time="${entry.time}">🗑️</button>
          </td>
        `;
        tbody.appendChild(tr);
      });

      document.querySelectorAll(".delete-btn").forEach(button => {
        button.addEventListener("click", function () {
          const username = this.dataset.username;
          const time = this.dataset.time;
          if (confirm(`Are you sure you want to delete the record for ${username} at ${time}?`)) {
            deleteHistoryEntry(username, time, this.closest("tr"));
          }
        });
      });
    }

    function updatePagination(pagination) {
      totalPages = pagination.last_page;
      paginationDiv.innerHTML = "";

      const prevBtn = document.createElement("button");
      prevBtn.textContent = "«";
      prevBtn.disabled = currentPage === 1;
      prevBtn.addEventListener("click", () => {
        currentPage--;
        loadUserHistory();
      });
      paginationDiv.appendChild(prevBtn);

      for (let i = 1; i <= totalPages; i++) {
        const pageBtn = document.createElement("button");
        pageBtn.textContent = i;
        if (i === currentPage) pageBtn.classList.add("active");
        pageBtn.addEventListener("click", () => {
          currentPage = i;
          loadUserHistory();
        });
        paginationDiv.appendChild(pageBtn);
      }

      const nextBtn = document.createElement("button");
      nextBtn.textContent = "»";
      nextBtn.disabled = currentPage === totalPages;
      nextBtn.addEventListener("click", () => {
        currentPage++;
        loadUserHistory();
      });
      paginationDiv.appendChild(nextBtn);
    }

    function deleteHistoryEntry(username, time, row) {
      fetch("/myapp/backend/users_history.php", {
        method: "DELETE",
        credentials: "include",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ username, time })
      })
        .then(res => res.json())
        .then(response => {
          if (response.success) {
            loadUserHistory();
          } else {
            alert(response.error || "Failed to delete entry.");
          }
        })
        .catch(error => {
          alert("Error: " + error.message);
        });
    }

    function showError(msg) {
      tbody.innerHTML = `<tr><td colspan="6" style="color:red;">${msg}</td></tr>`;
      paginationDiv.innerHTML = '';
    }

    perPageSelect.addEventListener("change", () => {
      perPage = parseInt(perPageSelect.value);
      currentPage = 1;
      loadUserHistory();
    });

    document.getElementById("exportCsvBtn").addEventListener("click", () => {
      window.location.href = "/myapp/backend/users_history.php?export=1";
    });
  });
</script>
</body>
</html>
