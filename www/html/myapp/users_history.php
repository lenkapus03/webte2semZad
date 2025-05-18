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
<script>

    document.addEventListener("DOMContentLoaded", () => {

        const tbody = document.querySelector("#historyBody");

        loadUserHistory();

        function loadUserHistory() {
            fetch("/myapp/backend/users_history.php", {
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
                    if (Array.isArray(data)) {
                        renderTable(data);
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

            // Attach delete event listeners after rendering
            document.querySelectorAll(".delete-btn").forEach(button => {
                button.addEventListener("click", function () {
                    const username = this.dataset.username;
                    const time = this.dataset.time;

                    if (!confirm(`Are you sure you want to delete the record for ${username} at ${time}?`)) return;

                    deleteHistoryEntry(username, time, this.closest('tr'));
                });
            });
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
                        rowElement.remove();
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
        }

        document.getElementById("exportCsvBtn").addEventListener("click", () => {
            window.location.href = "/myapp/backend/users_history.php?export=1";
        });

        const exportBtn = document.getElementById("exportCsvBtn");
        exportBtn.addEventListener("click", () => {
            window.location.href = "/myapp/backend/users_history.php?export=1";
        });
    });
</script>
</body>
</html>
