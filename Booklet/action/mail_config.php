<?php
//PHPMailer files
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendResetEmail($toEmail, $resetLink)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'booklet8415@gmail.com'; //email
        $mail->Password = 'mshb satz sgub kazi'; //app password

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Sender info
        $mail->setFrom('booklet8415@gmail.com', 'Booklet');

        // Receiver
        $mail->addAddress($toEmail);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password';

        $mail->Body = "
            <h3>Password Reset Request</h3>
            <p>Hello,</p>
            <p>Click the link below to reset your Boolet account password:</p>
            <p><a href='$resetLink'>$resetLink</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>Please ignore this email if you did not send a request.</p>
        ";

        $mail->AltBody = "Reset your password using this link: $resetLink";

        $mail->send();
        return true;
    } 
    catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}
?>