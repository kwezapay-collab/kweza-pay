# Local Testing Guide

This guide will help you set up and run the Kweza Pay application on your local machine.

## Prerequisites
- **PHP 7.4+** (You have PHP 8.1.17 installed)
- **MySQL Database** (e.g., via XAMPP, WAMP, or standalone)
- **Git** (Optional, for version control)

## 🚀 Automated Setup (Recommended)
We have included a script to automate the setup process.

1.  Open your terminal/command prompt in the project folder.
2.  Run the following command:
    ```cmd
    start_local.bat
    ```
3.  Follow the on-screen prompts.
4.  The application will be accessible at: `http://localhost:8000/frontend/index.php`

## 🛠 Manual Setup
If you prefer to set up manually, follow these steps:

### 1. Database Configuration
Ensure your local MySQL server is running.
The default configuration assumes:
- Host: `127.0.0.1`
- User: `root`
- Password: `(empty)`

If your setup differs, update:
- `backend/init_db.php`
- `backend/api/db.php`

### 2. Initialize Database
Navigate to the `backend` folder and run:
```bash
cd backend
php init_db.php
cd ..
```

### 3. Create Upload Directories
Run the setup script from the root folder:
```bash
php setup_directories.php
```

### 4. Run Migrations
Apply the latest database changes:
```bash
php run_migration.php
```

### 5. Start Local Server
Start the built-in PHP development server from the root folder:
```bash
php -S localhost:8000
```

## 🧪 Testing Credentials
Use these accounts to test different roles:

| Role | Phone Number | Default PIN |
|------|--------------|-------------|
| **Admin** | `00000` | `1234` |
| **Student** | `07001` | `1234` |
| **Merchant** | `07777` | `1234` |
| **Union** | `09999` | `1234` |
