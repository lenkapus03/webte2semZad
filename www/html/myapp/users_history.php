<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin')  {
    header("Location: /myapp/auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User History – Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            margin: 4px 5px;
            cursor: pointer;
            border-radius: var(--border-radius);
            transition: var(--transition);
            box-shadow: var(--box-shadow);
            text-decoration: none;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
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
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-top: 20px;
        }

        thead {
            background-color: var(--primary-color);
            color: white;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .delete-btn:hover {
            opacity: 0.85;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            border-radius: 4px;
        }

        .pagination button.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination button:hover:not(.active) {
            background-color: #f1f1f1;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .per-page-selector {
            margin-top: 20px;
            text-align: center;
        }

        .per-page-selector select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

<div class="header">
    <h1><i class="fas fa-history"></i> User History</h1>
    <div>
        <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Back</a>
        <button id="exportCsvBtn" class="btn btn-secondary"><i class="fas fa-file-csv"></i> Export to CSV</button>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>Username</th>
        <th>Time</th>
        <th>Action</th>
        <th>Source</th>
        <th>Location</th>
        <th>Delete</th>
    </tr>
    </thead>
    <tbody id="historyBody">
    </tbody>
</table>

<div class="per-page-selector">
    <label for="perPage">Items per page:</label>
    <select id="perPage">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
    </select>
</div>

<div class="pagination" id="pagination"></div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const tbody = document.querySelector("#historyBody");
        const paginationDiv = document.getElementById("pagination");
        const perPageSelect = document.getElementById("perPage");

        let currentPage = 1;
        let perPage = 10;
        let totalPages = 1;

        // Initialize the page
        perPageSelect.value = perPage;
        loadUserHistory();

        function loadUserHistory() {
            const url = `/myapp/backend/users_history.php?page=${currentPage}&per_page=${perPage}`;

            fetch(url, {
                credentials: "include"
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(errorText => {
                            throw new Error(errorText || 'Network response was not ok');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.data && Array.isArray(data.data)) {
                        renderTable(data.data);
                        updatePagination(data.pagination);
                    } else {
                        showError(data.error || 'Unknown error');
                    }
                })
                .catch(error => {
                    showError(error.message);
                    console.error('Fetch error:', error);
                });
        }

        function renderTable(entries) {
            tbody.innerHTML = "";
            entries.forEach(entry => {
                const tr = document.createElement("tr");

                tr.innerHTML = `
                <td>${entry.users_username}</td>
                <td>${entry.time}</td>
                <td>${entry.action_type}</td>
                <td>${entry.source}</td>
                <td>${entry.location}</td>
                <td>
                    <button class="delete-btn" data-username="${entry.users_username}" data-time="${entry.time}">🗑️</button>
                </td>
            `;

                tbody.appendChild(tr);
            });

            // Attach delete event listeners
            document.querySelectorAll(".delete-btn").forEach(button => {
                button.addEventListener("click", function() {
                    const username = this.dataset.username;
                    const time = this.dataset.time;

                    if (!confirm(`Are you sure you want to delete the record for ${username} at ${time}?`)) return;

                    deleteHistoryEntry(username, time, this.closest('tr'));
                });
            });
        }

        function updatePagination(pagination) {
            totalPages = pagination.last_page;
            paginationDiv.innerHTML = '';

            // Previous button
            const prevButton = document.createElement("button");
            prevButton.textContent = "«";
            prevButton.disabled = currentPage === 1;
            prevButton.addEventListener("click", () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadUserHistory();
                }
            });
            paginationDiv.appendChild(prevButton);

            // Page buttons
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);

            for (let i = startPage; i <= endPage; i++) {
                const pageButton = document.createElement("button");
                pageButton.textContent = i;
                if (i === currentPage) {
                    pageButton.classList.add("active");
                }
                pageButton.addEventListener("click", () => {
                    currentPage = i;
                    loadUserHistory();
                });
                paginationDiv.appendChild(pageButton);
            }

            // Next button
            const nextButton = document.createElement("button");
            nextButton.textContent = "»";
            nextButton.disabled = currentPage === totalPages;
            nextButton.addEventListener("click", () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    loadUserHistory();
                }
            });
            paginationDiv.appendChild(nextButton);
        }

        function deleteHistoryEntry(username, time, rowElement) {
            fetch("/myapp/backend/users_history.php", {
                method: "DELETE",
                credentials: "include",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ username, time })
            })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(errorText => {
                            throw new Error(errorText || 'Network response was not ok');
                        });
                    }
                    return res.json();
                })
                .then(response => {
                    if (response.success) {
                        // Reload current page after deletion to maintain pagination state
                        loadUserHistory();
                    } else {
                        alert(response.error || "Failed to delete entry.");
                    }
                })
                .catch(error => {
                    alert("Error: " + error.message);
                    console.error('Delete error:', error);
                });
        }

        function showError(message) {
            tbody.innerHTML = `<tr><td colspan="6" style="color:red;">Error: ${message}</td></tr>`;
            paginationDiv.innerHTML = '';
        }

        // Items per page change handler
        perPageSelect.addEventListener("change", () => {
            perPage = parseInt(perPageSelect.value);
            currentPage = 1;
            loadUserHistory();
        });

        // CSV Export button
        document.getElementById("exportCsvBtn").addEventListener("click", () => {
            window.location.href = "/myapp/backend/users_history.php?export=1";
        });
    });
</script>
</body>
</html>
