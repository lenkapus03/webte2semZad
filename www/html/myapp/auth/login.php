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
    <title>Login – PDF App</title>
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

        .login-card {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 400px;
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

        input {
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
            margin-bottom: 10px;
            text-align: center;
            font-size: 14px;
        }

        .error-message {
            margin: 5px 0;
            padding: 5px;
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
<div class="login-card">
    <h2><i class="fas fa-lock"></i> Login</h2>
    <div id="errorMessages" class="error"></div>
    <form id="loginForm">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required />
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required />
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
    <div class="link">
        Don't have an account? <a href="register.php">Register</a>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const loginForm = document.querySelector("#loginForm");
        const errorMessagesDiv = document.querySelector("#errorMessages");

        if (!loginForm || !errorMessagesDiv) {
            console.error("Required elements not found in the DOM");
            return;
        }

        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            errorMessagesDiv.innerHTML = '';
            errorMessagesDiv.style.display = 'none';

            const formData = new FormData(loginForm);
            const username = formData.get("username")?.toString().trim() || '';
            const password = formData.get("password")?.toString().trim() || '';

            const validationErrors = validateCredentials(username, password);
            if (validationErrors.length > 0) {
                errorMessagesDiv.style.display = 'block';
                displayErrors(errorMessagesDiv, validationErrors);
                return;
            }

            try {
                const response = await fetch("/myapp/backend/auth/login.php", {
                    method: "POST",
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await handleResponse(response);

                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    errorMessagesDiv.style.display = 'block';
                    const errors = data.error ?
                        (Array.isArray(data.error) ? data.error : [data.error]) :
                        ["Login failed. Please try again."];
                    displayErrors(errorMessagesDiv, errors);
                }
            } catch (error) {
                console.error('Login error:', error);
                errorMessagesDiv.style.display = 'block';
                // Parse error message to get the actual server error if available
                let errorMsg = "An unexpected error occurred. Please try again.";
                try {
                    const errorData = JSON.parse(error.message);
                    if (errorData.error) {
                        errorMsg = Array.isArray(errorData.error) ? errorData.error : [errorData.error];
                    } else {
                        errorMsg = error.message;
                    }
                } catch (e) {
                    errorMsg = error.message;
                }

                displayErrors(errorMessagesDiv, Array.isArray(errorMsg) ? errorMsg : [errorMsg]);
            }
        });

        function validateCredentials(username, password) {
            const errors = [];
            const usernameRegex = /^[A-Za-z0-9_]{3,30}$/;
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{6,}$/;

            if (!username) errors.push("Username is required");
            else if (!usernameRegex.test(username)) {
                errors.push("Username must be 3-30 characters (letters, numbers, underscores)");
            }

            if (!password) errors.push("Password is required");
            else if (!passwordRegex.test(password)) {
                errors.push("Password must be 6+ characters with uppercase, lowercase, and number");
            }

            return errors;
        }

        function displayErrors(container, messages) {
            container.innerHTML = messages.map(msg =>
                `<div class="error-message">${msg}</div>`
            ).join('');
        }

        async function handleResponse(response) {
            const contentType = response.headers.get('content-type');

            if (!response.ok) {
                let errorMessage = `Server error: ${response.status}`;
                if (contentType && contentType.includes('application/json')) {
                    try {
                        const errorData = await response.json();
                        if (errorData.error) {
                            errorMessage = Array.isArray(errorData.error) ? errorData.error : [errorData.error];
                        }
                    } catch (jsonError) {
                        const errorText = await response.text();
                        errorMessage += ` - ${errorText}`;
                    }
                    return { error: errorMessage }; // Return the error as part of the data
                } else {
                    const errorText = await response.text();
                    return { error: `Server error: ${response.status} - ${errorText}` };
                }
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
