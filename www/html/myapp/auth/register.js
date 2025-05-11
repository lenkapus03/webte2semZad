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
                body: formData
            });

            const data = await handleResponse(response);

            if (data.success) {
                if (data.api_key) {
                    apiKeyElement.textContent = data.api_key;
                    apiKeyDisplay.style.display = 'block';
                }
                if (data.user) {
                    window.location.href = '/myapp/index.php';
                }
            } else if (data.error) {
                displayErrors(errorMessagesDiv, errorList, Array.isArray(data.error) ? data.error : [data.error]); // Updated function call
            }
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

    function displayErrors(containerDiv, listElement, messages) {
        listElement.innerHTML = messages.map(msg => `<li>${msg}</li>`).join('');
        containerDiv.style.display = 'block';
    }

    async function handleResponse(response) {
        const contentType = response.headers.get('content-type');

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Server error: ${response.status} - ${errorText}`);
        }

        if (!contentType || !contentType.includes('application/json')) {
            throw new Error(`Expected JSON but got ${contentType}`);
        }

        return response.json();
    }
});
