<?php
require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function enviarEmail($destinatario, $assunto, $mensagem)
{
    
    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'borsatole@gmail.com';
        $mail->Password = 'tidn kinn hkjn nahx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('borsatole@gmail.com', 'ConectFacil');
        $mail->addAddress($destinatario, 'ConectFacilUser');

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagem;
        $mail->AltBody = 'Esse é um email enviado com PHPMailer';

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
        // echo "Erro ao enviar o email: {$mail->ErrorInfo}";
    }

}

?>