<?php
// email_functions.php - Funciones para envío de emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php'; // Para PHPMailer

function sendConfirmationEmail($email, $name, $registration_number, $qr_url)
{
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.zoho.com'; // Cambiar por tu servidor SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hackathon@tecnocleveland.com'; // Tu email
        $mail->Password   = 'xyvRc3zav239'; // Contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('hackathon@tecnocleveland.com', 'Hackathon Cursos Cleveland');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Confirmación de Registro - Hackathon 2025';
        $mail->Body = getConfirmationEmailTemplate($name, $registration_number, $qr_url);

        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando email: {$mail->ErrorInfo}");
    }
}

function sendGuardianNotificationEmail($email, $guardian_name, $student_name, $registration_number)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.zoho.com'; // Cambiar por tu servidor SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hackathon@tecnocleveland.com'; // Tu email
        $mail->Password   = 'xyvRc3zav239'; // Contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('hackathon@tecnocleveland.com', 'Hackathon Cursos Cleveland');
        $mail->addAddress($email, $guardian_name);

        $mail->isHTML(true);
        $mail->Subject = 'Confirmación de Registro de Menor - Hackathon 2025';
        $mail->Body = getGuardianEmailTemplate($guardian_name, $student_name, $registration_number);

        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando email: {$mail->ErrorInfo}");
    }
}

function getConfirmationEmailTemplate($name, $registration_number, $qr_url)
{
    return "
    <html>
    <head>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .registration-info { background: #f0f9ff; padding: 15px; border-radius: 8px; margin: 20px 0; }
            .qr-code { text-align: center; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>¡Registro Confirmado! 🚀</h1>
            <p>Hackathon Cursos Cleveland 2025</p>
        </div>
        <div class='content'>
            <p>Estimado/a <strong>$name</strong>,</p>
            
            <p>¡Felicidades! Tu registro para el Hackathon 2025 ha sido confirmado exitosamente.</p>
            
            <div class='registration-info'>
                <h3>Información de tu registro:</h3>
                <p><strong>Número de registro:</strong> $registration_number</p>
                <p><strong>Fecha de registro:</strong> " . date('d/m/Y H:i') . "</p>
            </div>
            
            <div class='qr-code'>
                <h3>Tu código QR:</h3>
                <img src='$qr_url' alt='Código QR' />
                <p><small>Presenta este código QR el día del evento</small></p>
            </div>
            
            <h3>Próximos pasos:</h3>
            <ul>
                <li>Guarda este email como confirmación</li>
                <li>Prepara tu documento de identidad</li>
                <li>¡Ven listo para aprender y crear!</li>
            </ul>
            
            <p>¡Nos vemos en el hackathon!</p>
            
            <p>Saludos cordiales,<br>
            <strong>Equipo Cursos Cleveland</strong></p>
        </div>
    </body>
    </html>";
}

function getGuardianEmailTemplate($guardian_name, $student_name, $registration_number)
{
    return "
    <html>
    <head>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Registro de Menor Confirmado</h1>
            <p>Hackathon Cursos Cleveland 2025</p>
        </div>
        <div class='content'>
            <p>Estimado/a <strong>$guardian_name</strong>,</p>
            
            <p>Le informamos que el registro de <strong>$student_name</strong> para el Hackathon 2025 ha sido confirmado.</p>
            
            <p><strong>Número de registro:</strong> $registration_number</p>
            <p><strong>Fecha de registro:</strong> " . date('d/m/Y H:i') . "</p>
            
            <p>Como representante legal, usted ha autorizado la participación del menor en este evento educativo.</p>
            
            <p>Si tiene alguna pregunta, no dude en contactarnos.</p>
            
            <p>Saludos cordiales,<br>
            <strong>Equipo Cursos Cleveland</strong></p>
        </div>
    </body>
    </html>";
}
