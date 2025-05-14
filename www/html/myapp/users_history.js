document.addEventListener("DOMContentLoaded", function () {
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
    
});
