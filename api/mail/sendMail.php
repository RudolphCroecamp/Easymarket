<?php

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once __DIR__ . '/../../vendor/autoload.php';

    function SendMail($recipient, $subject, $message){
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'rudolphcroecamp@gmail.com';
            $mail->Password   = 'sqfb lulv onfi myet'; // Use App Passwords for Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('easymarket@gmail.com', 'Easymarket');
            $mail->addAddress($recipient);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = "<b>Success!</b> {$message}";

            $mail->send();
            echo 'Notification sent.';

        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    