<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Sign Up | Kweza Pay</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" />
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#265D82",
                        "background-light": "#f8fafc",
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.75rem",
                    },
                },
            },
        };
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .input-focus-effect:focus {
            border-color: #265D82;
            box-shadow: 0 0 0 3px rgba(38, 93, 130, 0.15);
        }
    </style>
</head>

<body class="kweza-pattern-bg bg-background-light min-h-screen flex items-start justify-center p-8">
    <div class="w-full max-w-lg relative">
        <div class="flex flex-col items-center mb-4 relative pt-12">
            <img alt="Kweza Pay Logo"
                class="kweza-logo-register"
                src="assets/img/logo.png" onerror="this.src='https://ui-avatars.com/api/?name=KP&size=128'" />
            <h1 class="text-3xl font-bold text-slate-800 text-center">Welcome to </h1>
            <h2 class="text-2xl font-extrabold text-slate-700 text-center mt-1"">kweza Pay</h2>
            <p class="text-slate-500 text-center">Empowering communities through smart financial
                services</p>
        </div>
        <div class="bg-white rounded-2xl shadow-xl p-8 border border-slate-100">
            <form id="signup-form" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700" for="name">Full
                            Name</label>
                        <div class="relative">
                            <span
                                class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">person</span>
                            <input
                                class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none input-focus-effect transition-all"
                                id="name" name="name" placeholder="John Doe" required="" type="text" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700" for="email">Email
                            Address</label>
                        <div class="relative">
                            <span
                                class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">mail</span>
                            <input
                                class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none input-focus-effect transition-all"
                                id="email" name="email" placeholder="john@example.com" required="" type="email" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700" for="university">University (Optional)</label>
                        <div class="relative">
                            <span
                                class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">school</span>
                            <input
                                class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none input-focus-effect transition-all"
                                id="university" name="university" placeholder="Enter University Name"
                                type="text" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700" for="phone">Phone
                            Number</label>
                        <div class="relative">
                            <span
                                class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">phone</span>
                            <input
                                class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none input-focus-effect transition-all"
                                id="phone" name="phone" placeholder="+265 99 123 4567" required="" type="tel" />
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-700" for="pin">Create a
                        PIN</label>
                    <div class="relative">
                        <span
                            class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">lock</span>
                        <input
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none input-focus-effect transition-all"
                            id="pin" inputmode="numeric" maxlength="4" name="pin" placeholder="Enter 4 digit PIN"
                            required="" type="password" />
                    </div>
                    <p class="text-xs text-slate-400">This PIN will be used for transactions.</p>
                </div>

                <div class="space-y-3">
                    <label class="block text-sm font-semibold text-slate-700">I would like to register for:</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label class="flex items-center space-x-3 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-100 transition-all">
                            <input type="checkbox" name="roles" value="Person" class="w-5 h-5 text-primary rounded border-slate-300 focus:ring-primary" checked>
                            <span class="text-sm font-medium text-slate-700">Personal</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-100 transition-all">
                            <input type="checkbox" name="roles" value="Student" class="w-5 h-5 text-primary rounded border-slate-300 focus:ring-primary">
                            <span class="text-sm font-medium text-slate-700">Student</span>
                        </label>
                        <label class="flex items-center space-x-3 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-100 transition-all">
                            <input type="checkbox" id="merchant-role" name="roles" value="Merchant" class="w-5 h-5 text-primary rounded border-slate-300 focus:ring-primary">
                            <span class="text-sm font-medium text-slate-700">Merchant</span>
                        </label>
                    </div>
                </div>

                <div id="error-msg" class="hidden text-red-500 text-sm text-center"></div>
                <div id="success-msg" class="hidden text-green-500 text-sm text-center"></div>

                <button
                    class="w-full py-4 bg-primary hover:bg-[#1D4A69] text-white font-bold rounded-xl shadow-lg shadow-blue-900/10 transition-all transform active:scale-[0.98] mt-4 flex items-center justify-center space-x-2"
                    type="submit">
                    <span>Create Account</span>
                    <span class="material-icons-round text-base">arrow_forward</span>
                </button>
            </form>
            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <p class="text-sm text-slate-500">
                    Already have an account?
                    <a class="text-primary font-bold hover:underline" href="index.php">Log In</a>
                </p>
            </div>
        </div>
        <p class="mt-8 text-center text-xs text-slate-400">
            By signing up, you agree to our Terms of Service and Privacy Policy.<br />
            © <?php echo date('Y'); ?> Kweza Pay. All rights reserved.
        </p>
    </div>

    <!-- Merchant Payment Prompt Modal -->
    <div id="payment-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden flex items-center justify-center p-4" style="z-index: 9999;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 border border-slate-100 animate-in fade-in zoom-in duration-300">
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-50 text-primary rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-icons-round text-3xl">payments</span>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Merchant Application Fee</h3>
                <p class="text-slate-600 mb-6"> To process your application to become a merchant, a one-time non-refundable fee of <span class="font-bold text-primary">MWK 500</span> is required.</p>
                
                <div class="bg-slate-50 rounded-xl p-4 mb-6 text-left border border-slate-200">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Payment Instructions</p>
                    <p class="text-sm text-slate-700">Please pay <span class="font-bold">MWK 500</span> to Kweza Pay.</p>
                    <p class="text-xs text-slate-500 mt-2 italic">* Account details for payment will be provided upon approval or via email.</p>
                </div>

                <div class="flex flex-col gap-3">
                    <button id="confirm-payment-btn" class="w-full py-3 bg-primary text-white font-bold rounded-xl shadow-lg hover:bg-[#1D4A69] transition-all">
                        I Understand & Wish to Apply
                    </button>
                    <button id="cancel-payment-btn" class="w-full py-3 bg-slate-100 text-slate-600 font-semibold rounded-xl hover:bg-slate-200 transition-all">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>


    <script>
        let pendingFormData = null;

        document.getElementById('signup-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            
            const selectedRoles = Array.from(document.querySelectorAll('input[name="roles"]:checked')).map(cb => cb.value);
            
            if (selectedRoles.length === 0) {
                const err = document.getElementById('error-msg');
                err.textContent = 'Please select at least one account type.';
                err.classList.remove('hidden');
                return;
            }

            const isMerchant = selectedRoles.includes('Merchant');
            
            pendingFormData = {
                full_name: document.getElementById('name').value.trim(),
                email: document.getElementById('email').value.trim(),
                university: document.getElementById('university').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                pin: document.getElementById('pin').value.trim(),
                user_types: selectedRoles, // Array of roles
                registration_number: document.getElementById('phone').value.trim() // fallback
            };

            if (isMerchant) {
                pendingFormData.business_name = pendingFormData.university || pendingFormData.full_name;
                document.getElementById('payment-modal').classList.remove('hidden');
            } else {
                submitRegistration();
            }
        });

        document.getElementById('confirm-payment-btn').addEventListener('click', function() {
            document.getElementById('payment-modal').classList.add('hidden');
            submitRegistration();
        });

        document.getElementById('cancel-payment-btn').addEventListener('click', function() {
            document.getElementById('payment-modal').classList.add('hidden');
        });

        async function submitRegistration() {
            if (!pendingFormData) return;

            const btn = document.querySelector('button[type="submit"]');
            const err = document.getElementById('error-msg');
            const succ = document.getElementById('success-msg');

            btn.disabled = true;
            btn.querySelector('span').textContent = 'Processing...';
            err.classList.add('hidden');
            succ.classList.add('hidden');

            try {
                const res = await fetch('../backend/api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(pendingFormData)
                });
                
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (jsonErr) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server returned an invalid response. Please check if MySQL is running.');
                }

                if (data.success) {
                    succ.textContent = 'Application received! Redirecting...';
                    succ.classList.remove('hidden');
                    setTimeout(() => window.location.href = 'verify.php?phone=' + encodeURIComponent(pendingFormData.phone), 1500);
                } else {
                    err.textContent = data.error || 'Registration failed';
                    err.classList.remove('hidden');
                    btn.disabled = false;
                    btn.querySelector('span').textContent = 'Create Account';
                }
            } catch (e) {
                console.error('Registration Error:', e);
                err.textContent = e.message === 'Failed to fetch' ? 'Connection failed. Is the server running?' : e.message;
                err.classList.remove('hidden');
                btn.disabled = false;
                btn.querySelector('span').textContent = 'Create Account';
            }
        }

    </script>
</body>

</html>
