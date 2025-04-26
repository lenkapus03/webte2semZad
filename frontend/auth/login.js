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
        errorMessagesDiv.className = 'error-messages';

        const formData = new FormData(loginForm);
        const username = formData.get("username")?.toString().trim() || '';
        const password = formData.get("password")?.toString().trim() || '';

        const validationErrors = validateCredentials(username, password);
        if (validationErrors.length > 0) {
            displayErrors(errorMessagesDiv, validationErrors);
            return;
        }

        try {
            const formDataToSend = new FormData();
            formDataToSend.append("username", username);
            formDataToSend.append("password", password);

            const response = await fetch("../../backend/auth/login.php", {
                method: "POST",
                body: formDataToSend
            });

            const data = await handleResponse(response);

            if (data.redirect) {
                window.location.href = data.redirect;
            } else if (data.error) {
                displayErrors(errorMessagesDiv, Array.isArray(data.error) ? data.error : [data.error]);
            } else if (!response.ok) {
                // Fallback for non-JSON errors or unexpected non-ok responses
                const errorText = await response.text();
                displayErrors(errorMessagesDiv, [`Server error: ${response.status} - ${errorText}`]);
            }
        } catch (error) {
            console.error('Login error:', error);
            displayErrors(errorMessagesDiv, ["An unexpected error occurred. Please try again."]);
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
        container.innerHTML = messages.map(msg => `<p>${msg}</p>`).join('');
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
