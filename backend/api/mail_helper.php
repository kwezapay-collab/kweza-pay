<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendVerificationEmail($toEmail, $userName, $verificationCode) {
    // Kweza Pay Email Configuration
    $email_config = [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => 'emsilimba07@gmail.com',
        'smtp_password' => 'tzqbpsspltkznzse',
        'from_email' => 'emsilimba07@gmail.com',
        'from_name' => 'Kweza Pay'
    ];

    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $email_config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_config['smtp_username'];
        $mail->Password   = $email_config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $email_config['smtp_port'];
        
        // Recipients
        $mail->setFrom($email_config['from_email'], $email_config['from_name']);
        $mail->addAddress($toEmail, $userName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Kweza Pay Account';
        
        // Email body
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
            <div style='background: #11295E; padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>Kweza Pay</h1>
            </div>
            <div style='padding: 40px; background: white;'>
                <h2 style='color: #11295E; margin-top: 0;'>Hello {$userName},</h2>
                <p style='font-size: 16px; line-height: 1.6; color: #475569;'>
                    Thanks for joining Kweza Pay! To help us keep your account secure, please use the 6-digit verification code below to activate your account:
                </p>
                <div style='background: #f8fafc; padding: 30px; text-align: center; border-radius: 12px; margin: 30px 0; border: 1px dashed #cbd5e1;'>
                    <h1 style='font-size: 42px; letter-spacing: 12px; color: #0070BA; margin: 0; font-family: monospace;'>{$verificationCode}</h1>
                </div>
                <p style='font-size: 14px; color: #64748b; line-height: 1.5;'>
                    This code is valid for <strong>15 minutes</strong>. If you didn't create an account with us, you can safely ignore this email.
                </p>
            </div>
            <div style='padding: 20px; text-align: center; background: #f1f5f9; color: #94a3b8; font-size: 12px;'>
                © " . date('Y') . " Kweza Pay. Professional financial services for education.<br>
                Lilongwe, Malawi
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
