<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:2rem}
        form{max-width:420px}
        input,button{display:block;width:100%;padding:0.5rem;margin-bottom:0.75rem}
        .error{color:#a00}
        .success{color:#080}
    </style>
</head>
<body>
    <h1>Login</h1>
    <form id="loginForm">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required />

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required />

        <button type="submit">Sign in</button>
    </form>

    <div id="message"></div>

    <script>
    const form = document.getElementById('loginForm');
    const message = document.getElementById('message');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        message.textContent = '';
        message.className = '';

        const data = {
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
        };

        try {
            const res = await fetch('/api/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });

            const json = await res.json();

            if (!res.ok) {
                // show validation or error message
                const err = (json && (json.message || (json.errors && Object.values(json.errors).flat().join(', ')))) || 'Login failed';
                message.textContent = err;
                message.className = 'error';
                return;
            }

            // success: API returns token in `token` field
            const token = json.data && json.data.token ? json.data.token : json.token || null;
            if (token) {
                localStorage.setItem('api_token', token);
                message.textContent = 'Login successful — token saved to localStorage.';
                message.className = 'success';
            } else {
                message.textContent = json.message || 'Login successful';
                message.className = 'success';
            }
        } catch (err) {
            message.textContent = err.message || 'Network error';
            message.className = 'error';
        }
    });
    </script>
</body>
</html>
