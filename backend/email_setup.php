<?php
/**
 * Email Verification Setup Guide & Implementation
 * 
 * This file shows you how to send verification emails using PHPMailer
 */

// STEP 1: Install PHPMailer (run this in terminal from xampp/htdocs/kweza)
// composer require phpmailer/phpmailer

// STEP 2: Configure your email settings
// You can use Gmail, Outlook, or any SMTP service

// OPTION A: Gmail Configuration
$email_config = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'kwezapay@gmail.com',  // Change this to your Gmail
    'smtp_password' => 'nbysgmpyswpkngbz ',     // Get from Google App Passwords
    'from_email' => 'kwezapay@gmail.com',
    'from_name' => 'Kweza Pay'
];

// OPTION B: Custom SMTP (if you have one)
/*
$email_config = [
    'smtp_host' => 'mail.yourdomain.com',
    'smtp_port' => 587,
    'smtp_username' => 'noreply@yourdomain.com',
    'smtp_password' => 'your-password',
    'from_email' => 'noreply@yourdomain.com',
    'from_name' => 'Kweza Pay'
];
*/

/**
 * HOW TO GET GMAIL APP PASSWORD:
 * 
 * 1. Go to your Google Account (myaccount.google.com)
 * 2. Security → 2-Step Verification (turn it on if not enabled)
 * 3. Security → App passwords
 * 4. Create new app password for "Mail"
 * 5. Copy the 16-digit password
 * 6. Use it as smtp_password above
 */

// STEP 3: Example function to send verification email
function sendVerificationEmail($emailConfig, $toEmail, $userName, $verificationCode) {
    // Check if PHPMailer is installed
    if (!file_exists('vendor/autoload.php')) {
        error_log("PHPMailer not installed. Run: composer require phpmailer/phpmailer");
        return false;
    }
    
    require 'vendor/autoload.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailConfig['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['smtp_username'];
        $mail->Password   = $emailConfig['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $emailConfig['smtp_port'];
        
        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($toEmail, $userName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Kweza Pay Account';
        
        // Email body
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #020024 0%, #090979 35%, #00d4ff 100%); padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0;'>Kweza Pay</h1>
            </div>
            <div style='padding: 30px; background: #f8f9fa;'>
                <h2 style='color: #333;'>Hello {$userName},</h2>
                <p style='font-size: 16px; line-height: 1.6; color: #666;'>
                    Thank you for registering with Kweza Pay! Please use the verification code below to activate your account:
                </p>
                <div style='background: white; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0;'>
                    <h1 style='font-size: 36px; letter-spacing: 8px; color: #00d4ff; margin: 0;'>{$verificationCode}</h1>
                </div>
                <p style='font-size: 14px; color: #999;'>
                    This code will expire in 15 minutes. If you didn't request this, please ignore this email.
                </p>
            </div>
            <div style='padding: 20px; text-align: center; background: #333; color: white; font-size: 12px;'>
                © " . date('Y') . " Kweza Pay. All rights reserved.
            </div>
        </div>
        ";
        
        $mail->AltBody = "Your Kweza Pay verification code is: $verificationCode\n\nThis code expires in 15 minutes.";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}

// STEP 4: Test the email function
if (isset($_GET['test'])) {
    $testEmail = 'your-test-email@example.com';  // Change this
    $testCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    if (sendVerificationEmail($email_config, $testEmail, 'Test User', $testCode)) {
        echo "✓ Test email sent successfully to $testEmail<br>";
        echo "Code was: $testCode";
    } else {
        echo "✗ Failed to send email. Check error log.";
    }
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Verification Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #00d4ff;
            padding-bottom: 10px;
        }
        .step {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #00d4ff;
            border-radius: 5px;
        }
        .step h3 {
            margin-top: 0;
            color: #0066cc;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .command {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>📧 Email Verification Setup Guide</h1>
        
        <div class="step">
            <h3>Step 1: Install PHPMailer</h3>
            <p>Open terminal/command prompt in your Kweza directory and run:</p>
            <div class="command">composer require phpmailer/phpmailer</div>
            <p><small>If you don't have Composer installed, download it from <a href="https://getcomposer.org/">getcomposer.org</a></small></p>
        </div>
        
        <div class="step">
            <h3>Step 2: Get Gmail App Password</h3>
            <ol>
                <li>Go to <a href="https://myaccount.google.com" target="_blank">myaccount.google.com</a></li>
                <li>Click Security → Enable 2-Step Verification</li>
                <li>Go back to Security → App passwords</li>
                <li>Generate password for "Mail"</li>
                <li>Copy the 16-digit password</li>
            </ol>
        </div>
        
        <div class="step">
            <h3>Step 3: Configure Email Settings</h3>
            <p>Edit this file (<code>email_setup.php</code>) and update:</p>
            <ul>
                <li><code>smtp_username</code> - Your Gmail address</li>
                <li><code>smtp_password</code> - The app password from Step 2</li>
                <li><code>from_email</code> - Your Gmail address</li>
            </ul>
        </div>
        
        <div class="step">
            <h3>Step 4: Test Email Sending</h3>
            <p>Update the test email address in this file, then visit:</p>
            <div class="command">http://localhost/kweza/email_setup.php?test</div>
        </div>
        
        <div class="success">
            <strong>✓ Once working:</strong> Copy the <code>sendVerificationEmail()</code> function to 
            <code>api/register.php</code> and replace the <code>error_log()</code> line with actual email sending!
        </div>
        
        <div class="warning">
            <strong>⚠️ Alternative:</strong> If you can't use Gmail, you can use:
            <ul>
                <li><strong>SendGrid</strong> - Free tier: 100 emails/day</li>
                <li><strong>Mailgun</strong> - Free tier: 5,000 emails/month</li>
                <li><strong>Mailtrap</strong> - For testing only</li>
            </ul>
        </div>
    </div>
</body>
</html>
