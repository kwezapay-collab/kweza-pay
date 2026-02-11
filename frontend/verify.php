<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kweza Pay - Verify Account</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="assets/css/verify.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="verify-card">
        <div class="logo-container">
            <img src="assets/img/logo.png" alt="Kweza Pay" onerror="this.src='https://ui-avatars.com/api/?name=KP&background=0D8ABC&color=fff'">
        </div>

        <h1 class="verify-title">Verify your email</h1>
        <p class="verify-subtitle">
            Enter the 6-digit code we sent to your email to activate your account.
        </p>

        <div id="errorMessage" class="alert alert-error hidden"></div>
        <div id="successMessage" class="alert alert-success hidden"></div>

        <form id="verifyForm">
            <div class="code-input-container">
                <input type="text" class="code-digit" maxlength="1" id="digit1" autocomplete="off" autofocus>
                <input type="text" class="code-digit" maxlength="1" id="digit2" autocomplete="off">
                <input type="text" class="code-digit" maxlength="1" id="digit3" autocomplete="off">
                <input type="text" class="code-digit" maxlength="1" id="digit4" autocomplete="off">
                <input type="text" class="code-digit" maxlength="1" id="digit5" autocomplete="off">
                <input type="text" class="code-digit" maxlength="1" id="digit6" autocomplete="off">
            </div>

            <button type="submit" class="btn-verify" id="verifyBtn">Verify Account</button>
        </form>

        <div class="resend-box">
            Didn't receive a code? <a href="#" class="resend-link" onclick="resendCode(); return false;">Resend it</a>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const phone = urlParams.get('phone');
        const userType = urlParams.get('type'); // Get user type from URL
        if (!phone) window.location.href = 'register.php';

        const codeInputs = document.querySelectorAll('.code-digit');
        codeInputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                if (input.value.length === 1 && index < codeInputs.length - 1) codeInputs[index + 1].focus();
            });
            input.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !input.value && index > 0) codeInputs[index - 1].focus();
            });
            input.addEventListener('beforeinput', e => {
                if (e.data && !/^\d$/.test(e.data)) e.preventDefault();
            });
        });

        function showError(msg) {
            const div = document.getElementById('errorMessage');
            div.textContent = msg;
            div.classList.remove('hidden');
            document.getElementById('successMessage').classList.add('hidden');
        }

        function showSuccess(msg) {
            const div = document.getElementById('successMessage');
            div.textContent = msg;
            div.classList.remove('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
        }

        document.getElementById('verifyForm').addEventListener('submit', async e => {
            e.preventDefault();
            const code = Array.from(codeInputs).map(i => i.value).join('');
            if (code.length !== 6) { showError('Please enter the complete 6-digit code'); return; }
            const btn = document.getElementById('verifyBtn');
            btn.disabled = true;
            btn.textContent = 'Verifying...';
            try {
                const res = await fetch('../backend/api/verify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ phone, verification_code: code, user_type: userType })
                });
                const data = await res.json();
                if (data.success) {
                    showSuccess(data.message || 'Account verified successfully! Redirecting...');
                    setTimeout(() => window.location.href = data.redirect || 'index.php', 2000);
                } else {
                    showError(data.error || 'Verification failed');
                    btn.disabled = false;
                    btn.textContent = 'Verify Account';
                    codeInputs.forEach(i => i.value = '');
                    codeInputs[0].focus();
                }
            } catch (err) {
                showError('Network error. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Verify Account';
            }
        });

        async function resendCode() {
            try {
                const res = await fetch('../backend/api/resend_code.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ phone })
                });
                const data = await res.json();
                if (data.success) showSuccess('Verification code resent! Check your email.');
                else showError(data.error || 'Failed to resend code');
            } catch (err) { showError('Network error. Please try again.'); }
        }
    </script>
</body>

</html>