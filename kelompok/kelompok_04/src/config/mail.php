<?php
use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/../../vendor/autoload.php';

function sendResetEmail($toEmail, $toName, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // Server SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'siformmas@gmail.com';   // email pengirim
        $mail->Password   = 'mibv hkdt vcyc yhey'; // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Pengirim
        $mail->setFrom('siformmas@gmail.com', 'Puskesmas Digital');
        $mail->addAddress($toEmail, $toName);

        // Isi email
        $mail->isHTML(true);
        $mail->Subject = 'Reset Password Akun Anda';
        $mail->Body    = "
            <h3>Reset Password</h3>
            <p>Klik link berikut untuk mereset password Anda:</p>
            <p><a href='$resetLink' target='_blank'>$resetLink</a></p>
            <p>Link berlaku 1 jam.</p>
        ";

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
