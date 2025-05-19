<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register – PDF App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4285F4;
            --danger-color: #EA4335;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f5f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .register-card {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 450px;
        }

        h2 {
            margin-top: 0;
            color: var(--primary-color);
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #3367d6;
        }

        .error {
            color: var(--danger-color);
            margin-bottom: 15px;
            text-align: center;
            padding: 10px;

        }

        .error div {
            margin: 5px 0;
            padding: 0;
        }

        .link {
            text-align: center;
            margin-top: 15px;
        }

        .link a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="register-card">
    <h2><i class="fas fa-user-plus"></i> Register</h2>
    <div id="errorMessages" class="error">
        <div id="errorList"></div>
    </div>
    <form method="post" action="" id="registerForm">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required />
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required />
        </div>
        <div class="form-group">
            <label for="role">Role:</label>
            <select name="role" id="role" required>
                <option value="user" selected>User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn">Register</button>
    </form>

    <div class="link">
        Already have an account? <a href="login.php">Login</a>
    </div>

    <div id="apiKeyDisplay" style="display:none;">
        <p>Your API Key: <strong><span id="apiKey"></span></strong></p>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const registerForm = document.querySelector("#registerForm");
        const errorMessagesDiv = document.querySelector("#errorMessages");
        const errorList = document.querySelector("#errorList"); // Added to target the UL
        const apiKeyDisplay = document.querySelector("#apiKeyDisplay");
        const apiKeyElement = document.querySelector("#apiKey");

        if (!registerForm || !errorMessagesDiv || !apiKeyDisplay || !apiKeyElement || !errorList) {
            console.error("Required elements not found in the DOM");
            return;
        }

        registerForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            errorMessagesDiv.style.display = 'none'; // Hide error div on new submission
            errorList.innerHTML = ''; // Clear previous errors
            apiKeyDisplay.style.display = 'none';

            const formData = new FormData(registerForm);
            const username = formData.get("username")?.toString().trim() || '';
            const password = formData.get("password")?.toString().trim() || '';
            const role = formData.get("role")?.toString().trim() || '';

            const validationErrors = validateInputs(username, password, role);
            if (validationErrors.length > 0) {
                displayErrors(errorMessagesDiv, errorList, validationErrors); // Updated function call
                return;
            }

            try {
                const response = await fetch("/myapp/backend/auth/register.php", {
                    method: "POST",
                    body: formData,
                    headers: {
                        'Accept' : 'application/json'
                    }
                });

                const data = await handleResponse(response);

                if (data.success) {
                    if (data.api_key) {
                        apiKeyElement.textContent = data.api_key;
                        apiKeyDisplay.style.display = 'block';
                    }
                    if (data.user && data.user.username) {
                        window.location.href = '/myapp/index.php';
                    }
                } else {
                    const errors = data.error ?
                        (Array.isArray(data.error) ? data.error : [data.error]) :
                        ["Unknown error occurred"];
                    displayErrors(errorMessagesDiv, errorList, errors);                    }
            } catch (error) {
                console.error('Registration error:', error);
                displayErrors(errorMessagesDiv, errorList, ["An unexpected error occurred. Please try again."]); // Updated function call
            }
        });

        function validateInputs(username, password, role) {
            const errors = [];
            const usernameRegex = /^[A-Za-z0-9_]{3,30}$/;
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{6,}$/;
            const validRoles = ["user", "admin"]; // Adjust if you allow other roles

            if (!username) errors.push("Username is required");
            else if (!usernameRegex.test(username)) {
                errors.push("Username must be 3-30 characters (letters, numbers, underscores)");
            }

            if (!password) errors.push("Password is required");
            else if (!passwordRegex.test(password)) {
                errors.push("Password must be 6+ characters with uppercase, lowercase, and number");
            }

            if (!role) errors.push("Role is required");
            else if (!validRoles.includes(role)) {
                errors.push("Invalid role selected");
            }

            return errors;
        }

        function displayErrors(containerDiv, errorContainer, messages) {
            errorContainer.innerHTML = '';

            messages.forEach(msg => {
                const errorDiv = document.createElement('div');
                errorDiv.textContent = msg;
                errorContainer.appendChild(errorDiv);
            })

            containerDiv.style.display = 'block';
        }

        async function handleResponse(response) {
            const contentType = response.headers.get('content-type');

            if (!response.ok) {
                let errorText = '';
                try {
                    const errorData = await response.json();
                    // If the server provides an error message, use that
                    if (errorData.error) {
                        // Handle both array and string error formats
                        errorText = Array.isArray(errorData.error) ? errorData.error[0] : errorData.error;
                    } else {
                        errorText = 'Registration failed';
                    }
                } catch (e) {
                    // Fallback to text if not JSON
                    errorText = await response.text();
                }
                throw new Error(errorText || `Server error: ${response.status}`);
            }

            if (!contentType || !contentType.includes('application/json')) {
                throw new Error(`Expected JSON but got ${contentType}`);
            }

            return response.json();
        }
    });

</script>
</body>
</html>
