<?php
require_once '../backend/api/session.php';
if (isLoggedIn()) {
    $user_type = $_SESSION['user_type'] ?? '';
    $redirect = '';
    
    if ($user_type === 'Student') $redirect = 'student.php';
    elseif ($user_type === 'Merchant') $redirect = 'merchant.php';
    elseif ($user_type === 'StudentUnion') $redirect = 'student_union.php';
    elseif ($user_type === 'Admin') $redirect = 'admin/index.php';
    elseif ($user_type === 'Person') $redirect = 'person.php';
    
    if ($redirect) {
        header('Location: ' . $redirect);
        exit;
    } else {
        // If type is invalid, clear session to stop loop
        session_destroy();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Kweza Pay - Login &amp; Registration</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&amp;display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#265D82",
                        "background-light": "#F8FAFC",
                    },
                    fontFamily: {
                        display: ["Plus Jakarta Sans", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.75rem",
                    },
                },
            },
        };
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
        }
        function switchTab(tab) {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const loginTab = document.getElementById('login-tab');
            const signupTab = document.getElementById('signup-tab');
            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                signupForm.classList.add('hidden');
                loginTab.classList.add('border-primary', 'text-primary');
                loginTab.classList.remove('border-transparent', 'text-gray-500');
                signupTab.classList.remove('border-primary', 'text-primary');
                signupTab.classList.add('border-transparent', 'text-gray-500');
            } else {
                loginForm.classList.add('hidden');
                signupForm.classList.remove('hidden');
                signupTab.classList.add('border-primary', 'text-primary');
                signupTab.classList.remove('border-transparent', 'text-gray-500');
                loginTab.classList.remove('border-primary', 'text-primary');
                loginTab.classList.add('border-transparent', 'text-gray-500');
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass-effect {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
    </style>
</head>

<body class="kweza-pattern-bg bg-background-light text-slate-900 min-h-screen flex flex-col">

    <main class="flex-grow flex items-center justify-center px-4 pt-12 pb-4 relative overflow-hidden">
        <div
            class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[100px] pointer-events-none">
        </div>
        <div
            class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[100px] pointer-events-none">
        </div>
        <div class="w-full max-w-md relative">
            <div class="flex flex-col items-center mb-4 relative pt-12">
                <img alt="Kweza Pay Logo"
                    class="kweza-logo-login"
                    src="assets/img/logo.png" onerror="this.src='https://ui-avatars.com/api/?name=KP&size=128'" />
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 text-center">Welcome to </h1>
                <h2 class="text-lg font-extrabold text-slate-700 text-center mt-1">Kweza Pay</h2>
                <p class="text-slate-500 text-center">Secure payments for your community</p>
            </div>
            <div
                class="bg-white/80 glass-effect border border-slate-200 rounded-2xl shadow-2xl overflow-hidden transition-colors duration-300">
                <div class="flex border-b border-slate-200">
                    <button
                        class="flex-1 py-4 text-sm font-semibold border-b-2 border-primary text-primary transition-all"
                        id="login-tab" onclick="switchTab('login')">
                        Login
                    </button>
                    <button
                        class="flex-1 py-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-slate-700 transition-all"
                        id="signup-tab" onclick="window.location.href='register.php'">
                        Sign Up
                    </button>
                </div>
                <div class="p-8">
                    <form class="space-y-6" id="login-form">
                        <div id="login-fields">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="login-phone">Phone
                                    Number</label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <span class="material-symbols-rounded text-[20px]">phone_iphone</span>
                                    </span>
                                    <input
                                        class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none"
                                        id="login-phone" name="phone" placeholder="e.g. 0712 345 678" type="tel" required />
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-sm font-medium text-slate-700" for="login-pin">PIN
                                        entry</label>
                                    <a class="text-xs font-semibold text-primary hover:underline" href="#"
                                        onclick="alert('Contact Admin to reset PIN')">Forgot PIN?</a>
                                </div>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <span class="material-symbols-rounded text-[20px]">lock</span>
                                    </span>
                                    <input
                                        class="block w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none tracking-widest"
                                        id="login-pin" maxlength="4" name="pin" placeholder="••••" type="password"
                                        required autocomplete="off" />
                                </div>
                            </div>
                            <div class="flex items-center mt-4">
                                <input class="h-4 w-4 text-primary focus:ring-primary border-slate-300 rounded bg-white"
                                    id="remember-me" name="remember-me" type="checkbox" />
                                <label class="ml-2 block text-sm text-slate-600" for="remember-me">Keep
                                    me logged in</label>
                            </div>
                        </div>

                        <div id="role-selection" class="hidden space-y-4">
                            <div class="text-center mb-4">
                                <h3 class="text-lg font-bold text-slate-800">Select Account</h3>
                                <p class="text-sm text-slate-500">Choose the account you want to enter</p>
                            </div>
                            <div id="roles-grid" class="grid grid-cols-1 gap-3">
                                <!-- Roles will be injected here -->
                            </div>
                            <button type="button" onclick="resetLogin()" class="w-full py-2 text-sm font-semibold text-slate-500 hover:text-primary transition-all">
                                Back to login
                            </button>
                        </div>

                        <div id="login-error" class="hidden text-red-500 text-sm text-center"></div>
                        <button
                            id="login-btn"
                            class="w-full flex justify-center items-center py-3.5 px-4 bg-primary hover:bg-[#1D4A69] text-white text-sm font-bold rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-[0.98]"
                            type="submit">
                            Login to Account
                        </button>
                    </form>

                    <!-- Fallback Signup Form (Hidden in favor of redirect) -->
                    <form class="space-y-5 hidden" id="signup-form">
                        <div class="text-center p-4">
                            <p class="text-slate-600">Redirecting to registration...</p>
                            <a href="register.php" class="text-primary font-bold">Click here if not redirected</a>
                        </div>
                    </form>

                </div>
            </div>
            <div class="mt-2 flex justify-center space-x-4 text-sm font-medium text-slate-500">
                <a class="hover:text-primary transition-colors" href="#">Help Center</a>
                <a class="hover:text-primary transition-colors" href="#">Contact Support</a>
                <a class="hover:text-primary transition-colors" href="#">Security</a>
            </div>
        </div>
    </main>
    <footer class="pt-1 pb-2 text-center text-xs text-slate-400">
        © 2026 Kweza Pay. All rights reserved. Professional financial services for education.
    </footer>

    <script>
        async function handleLogin(e, selectedRole = null) {
            if (e) e.preventDefault();
            
            const phone = document.getElementById('login-phone').value;
            const pin = document.getElementById('login-pin').value;
            const errDiv = document.getElementById('login-error');
            const btn = document.getElementById('login-btn');
            const loginFields = document.getElementById('login-fields');
            const roleSelection = document.getElementById('role-selection');
            const rolesGrid = document.getElementById('roles-grid');

            errDiv.classList.add('hidden');
            btn.disabled = true;
            btn.textContent = selectedRole ? 'Entering...' : 'Logging in...';

            try {
                const res = await fetch('../backend/api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ phone, pin, selected_role: selectedRole })
                });
                const data = await res.json();
                
                if (data.success) {
                    if (data.requires_selection) {
                        // Show role selection
                        loginFields.classList.add('hidden');
                        roleSelection.classList.remove('hidden');
                        btn.classList.add('hidden'); // Hide main login button
                        
                        rolesGrid.innerHTML = '';
                        data.roles.forEach(role => {
                            const roleBtn = document.createElement('button');
                            roleBtn.type = 'button';
                            roleBtn.className = 'w-full p-4 bg-slate-50 border border-slate-200 rounded-xl hover:border-primary hover:bg-primary/5 transition-all text-left flex items-center space-x-3';
                            
                            let icon = 'person';
                            if (role === 'Student') icon = 'school';
                            if (role === 'Merchant') icon = 'storefront';
                            if (role === 'Admin') icon = 'admin_panel_settings';
                            if (role === 'StudentUnion') icon = 'group';

                            roleBtn.innerHTML = `
                                <span class="material-symbols-rounded text-primary">${icon}</span>
                                <span class="font-bold text-slate-700">${role} Account</span>
                            `;
                            roleBtn.onclick = () => handleLogin(null, role);
                            rolesGrid.appendChild(roleBtn);
                        });
                    } else {
                        window.location.href = data.redirect;
                    }
                } else {
                    errDiv.textContent = data.error || 'Login failed';
                    errDiv.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = 'Login to Account';
                }
            } catch (e) {
                errDiv.textContent = 'Network error. Try again.';
                errDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Login to Account';
            }
        }

        function resetLogin() {
            document.getElementById('login-fields').classList.remove('hidden');
            document.getElementById('role-selection').classList.add('hidden');
            document.getElementById('login-btn').classList.remove('hidden');
            document.getElementById('login-btn').disabled = false;
            document.getElementById('login-btn').textContent = 'Login to Account';
        }

        document.getElementById('login-form').addEventListener('submit', handleLogin);
    </script>
</body>

</html>
