document.getElementById('regenApiKey').addEventListener('click', async () => {
    const msg = document.getElementById('message');
    const apiKeyDisplay = document.getElementById('apiKeyDisplay');

    if (!msg || !apiKeyDisplay) {
        console.error("Required elements not found");
        return;
    }

    msg.textContent = 'Regenerating API key...';
    msg.style.color = 'orange';
    apiKeyDisplay.style.display = 'none';

    try {
        const response = await fetch('/myapp/backend/regenerate_api_key.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });

        const contentLength = response.headers.get('Content-Length');
        if (contentLength === '0') {
            console.error("empty response");
        }

        let data;
        const contentType = response.headers.get('Content-Type');

        if (response.ok && contentType?.includes('application/json')) {
            data = await response.json();
            if (data?.success && data?.api_key) {
                msg.style.color = 'green';
                msg.textContent = 'API key successfully regenerated.';
                apiKeyDisplay.style.display = 'block';
                apiKeyDisplay.textContent = `Your new API key is: ${data.api_key}`;
            } else {
                throw new Error(data?.error || 'Failed to regenerate API key');
            }
        } else {
            const errorText = await response.text();
            console.error('Server error:', errorText);
            throw new Error(`Server error: ${response.status} - ${errorText}`);
        }

    } catch (error) {
        console.error('Error:', error);
        msg.style.color = 'red';
        msg.textContent = error.message || 'Error communicating with server.';
        apiKeyDisplay.style.display = 'none';
    }
});
