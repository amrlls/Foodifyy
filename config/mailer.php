<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendResetEmail($toEmail, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'amrllsfyp@gmail.com';   // ← tukar
        $mail->Password   = 'ckhl vnrg aahq ncex';   // ← App Password 16 digit
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('YOURGMAIL@gmail.com', 'Foodify');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Foodify Password';
        $mail->Body    = "
            <div style='font-family:sans-serif; max-width:500px; margin:auto; padding:40px;'>
                <h1 style='font-size:2rem; background:linear-gradient(135deg,#FF6B6B,#FF8E53);
                    -webkit-background-clip:text; -webkit-text-fill-color:transparent;'>
                    foodify.
                </h1>
                <h2 style='color:#1a1a1a;'>Reset Your Password</h2>
                <p style='color:#666;'>Click the button below. Link expires in <b>15 minutes</b>.</p>
                <a href='$resetLink'
                   style='display:inline-block; margin-top:20px; padding:16px 32px;
                          background:linear-gradient(135deg,#FF6B6B,#FF8E53);
                          color:white; border-radius:16px; text-decoration:none;
                          font-weight:800;'>
                    Reset Password →
                </a>
                <p style='color:#999; font-size:0.8rem; margin-top:30px;
                   border-top:1px solid #eee; padding-top:20px;'>
                    If you didn't request this, ignore this email.
                </p>
            </div>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}