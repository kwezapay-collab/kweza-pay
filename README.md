# Kweza - Student Financial Platform

Kweza is a modern, responsive financial platform designed to facilitate transactions for individuals, students, merchants, and student unions. It provides a seamless experience for managing wallets, making payments via QR codes or direct transfers, and handling student union fees.

![Kweza Logo](frontend/assets/img/logo.png)

## 🚀 Features

- **Multi-User Ecosystem**: Tailored experiences for four distinct user types:
  - **Students**: Manage personal wallets, pay fees, and purchase from merchants.
  - **Merchants**: Accept payments via QR codes and manage business balances.
  - **Student Unions**: Collect and manage union-related fees and documentation.
  - **Personal Accounts**: For general users who need basic wallet functionality.
- **Secure Transactions**: PIN-protected transactions and secure hashing for data integrity.
- **QR Code Payments**: Quick and easy merchant payments using QR code technology.
- **Verification System**: Multi-step verification process to ensure account security.
- **Modern Dashboard**: Responsive UI designed with a clean, premium aesthetic (inspired by modern fintech apps like PayPal).
- **Demo Mode**: Includes built-in simulation for transaction flows (Airtel/TNM mobile money) for testing without real-world banking integration.

## 🛠️ Tech Stack

- **Frontend**: HTML5, Vanilla CSS3, PHP (Templating).
- **Backend**: PHP (7.4+), MySQL.
- **Mail Services**: PHPMailer.
- **Development Tooling**: XAMPP (Local Environment), Composer (Dependency Management).

## 📁 Project Structure

```text
kweza-app/
├── backend/            # API logic and Database management
│   ├── api/            # PHP API endpoints (DB connection in db.php)
│   ├── config/         # Payment and feature configurations
│   ├── services/       # Core business logic (Email, Payments)
│   ├── db_schema.sql   # MySQL Database structure
│   └── init_db.php     # Database initialization script
├── frontend/           # UI Components and Assets
│   ├── assets/         # CSS, Images, and Icons
│   ├── index.php       # Landing Page / Login
│   ├── register.php    # User Registration
│   ├── student.php     # Student Dashboard
│   ├── merchant.php    # Merchant Dashboard
│   └── admin/          # Admin Portal
└── README.md
```

## ⚙️ Installation & Setup

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd kweza-app
   ```

2. **Setup XAMPP**:
   - Move or clone the project into your `xampp/htdocs/` directory.
   - Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. **Database Configuration**:
   - Create a database named `kweza_pay` in phpMyAdmin.
   - Import the schema from `backend/db_schema.sql`.
   - Alternatively, run the initialization script:
     ```bash
     php backend/init_db.php
     ```

4. **Install Dependencies**:
   - Ensure you have [Composer](https://getcomposer.org/) installed.
   - Run the following in the `backend/` directory:
     ```bash
     cd backend
     composer install
     ```

5. **Configure Environment**:
   - Update `backend/api/db.php` with your local database credentials (host, db, user, pass).

## 📱 Usage

- Access the application via `http://localhost/kweza-app/frontend/index.php`.
- **Register**: Choose your user type (Student, Merchant, or Personal).
- **Verify**: Use the simulated verification code system (usually visible in the logs or database for demo) to activate your account.
- **Transact**: Visit the dashboard to top up your wallet (Demo Mode) and start making payments.

## 🧪 Testing

The project includes several debug and test scripts in the `backend/` directory:
- `test_system_ready.php`: Checks if all database tables and columns are correctly initialized.
- `debug_users.php`: Lists current users and their roles for verification.
- `test_accounts.php`: Verifies account balance logic.

## 📄 License

This project is developed for private use. All rights reserved.

---
*Built with ❤️(love haahahahahaha nope) for better financial inclusion.*
