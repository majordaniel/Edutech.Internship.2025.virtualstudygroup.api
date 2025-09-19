<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Register</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:2rem}
        form{max-width:480px}
        input,button{display:block;width:100%;padding:0.5rem;margin-bottom:0.75rem}
        .error{color:#a00}
        .success{color:#080}
    </style>
</head>
<body>
    <h1>Register</h1>
    <form id="registerForm">
        <label for="first_name">First name</label>
        <input id="first_name" name="first_name" type="text" required />

        <label for="last_name">Last name</label>
        <input id="last_name" name="last_name" type="text" required />

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required />

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required />

        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required />

        <button type="submit">Create account</button>
    </form>
    <div id="message"></div>

    <script>
    const form = document.getElementById('registerForm');
    const message = document.getElementById('message');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        message.textContent = '';
        message.className = '';

        const data = {
            first_name: document.getElementById('first_name').value,
            last_name: document.getElementById('last_name').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            password_confirmation: document.getElementById('password_confirmation').value,
        };

        try {
            const res = await fetch('/api/auth/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
            const json = await res.json();
            if (!res.ok) {
                const err = (json && (json.message || (json.errors && Object.values(json.errors).flat().join(', ')))) || 'Registration failed';
                message.textContent = err;
                message.className = 'error';
                return;
            }
            message.textContent = json.message || 'Registered successfully';
            message.className = 'success';
        } catch (err) {
            message.textContent = err.message || 'Network error';
            message.className = 'error';
        }
    });
    </script>
</body>
</html>
