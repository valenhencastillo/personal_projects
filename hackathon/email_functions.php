<?php
// email_functions.php - Funciones para envío de emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php'; // Para PHPMailer

function sendConfirmationEmail($email, $name, $registration_number, $qr_url, $category)
{
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.zoho.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hackathon@tecnocleveland.com';
        $mail->Password   = 'xyvRc3zav239';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('hackathon@tecnocleveland.com', 'Hackathon de Micro:Bit');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = '🚀 ¡Registro Confirmado para el Hackathon 2025!';
        $mail->Body = getConfirmationEmailTemplate($name, $registration_number, $qr_url, $category);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error enviando email: {$mail->ErrorInfo}");
        return false;
    }
}

function sendGuardianNotificationEmail($email, $guardian_name, $student_name, $registration_number, $category)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.zoho.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hackathon@tecnocleveland.com';
        $mail->Password   = 'xyvRc3zav239';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('hackathon@tecnocleveland.com', 'Hackathon de Micro:Bit');
        $mail->addAddress($email, $guardian_name);

        $mail->isHTML(true);
        $mail->Subject = 'Registro de Representante - Hackathon 2025';
        $mail->Body = getGuardianEmailTemplate($guardian_name, $student_name, $registration_number, $category);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error enviando email: {$mail->ErrorInfo}");
        return false;
    }
}

function getConfirmationEmailTemplate($name, $registration_number, $qr_url, $category)
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
            .event-details { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
            .requirements { background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745; }
            .important { background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🚀 ¡Registro Confirmado para el Hackathon 2025!</h1>
            <p>Hackathon de Micro:Bit 2025</p>
        </div>
        <div class='content'>
            <p>Estimado/a <strong>$name</strong>,</p>
            
            <p>¡Felicidades! 🎉<br>
            Tu registro para el Hackathon de Micro:Bit 2025 ha sido confirmado exitosamente.</p>
            
            <div class='registration-info'>
                <h3>📌 Información de tu registro</h3>
                <p><strong>Número de registro:</strong> $registration_number</p>
                <p><strong>Fecha de registro:</strong> " . date('d/m/Y H:i') . "</p>
                <p><strong>Categoría:</strong> $category</p>
            </div>
            
            <div class='qr-code'>
                <h3>🎟️ Tu código QR</h3>
                <img src='$qr_url' alt='Código QR' width='200' height='200' />
                <p><small>Este será tu pase de acceso.<br>
                Presenta este código QR el día del evento junto con tu documento de identidad.</small></p>
                <p><strong>📌 Sin este código y tu documento no podrás ingresar.</strong></p>
            </div>
            
            <div class='event-details'>
                <h3>🗓️ Detalles del evento</h3>
                <p><strong>Fecha:</strong> Sábado, 29 de noviembre de 2025</p>
                <p><strong>Hora:</strong> 12:00 p.m. – 9:00 p.m.</p>
                <p><strong>Lugar:</strong> Plaza central de Buenaventura</p>
            </div>
            
            <div class='requirements'>
                <h3>✅ Requisitos para participar</h3>
                <ul>
                    <li>Documento de identidad (obligatorio).</li>
                    <li>Estudiantes menores de edad deben traer la autorización firmada por su representante legal.</li>
                    <li>Conocer el reglamento oficial (adjunto en PDF en este correo).</li>
                </ul>
            </div>
            
            <h3>🚀 Próximos pasos</h3>
            <ul>
                <li>Guarda este correo como comprobante de inscripción.</li>
                <li>Revisa las publicaciones y actualizaciones en nuestras redes sociales. @hackathon.tecno</li>
            </ul>
            
            <div class='important'>
                <h3>⚠️ Importante</h3>
                <ul>
                    <li>La hora máxima de llegada será a las 11:30 a.m. para acreditación.</li>
                    <li>Si llegas tarde, podrías quedar fuera de la competencia.</li>
                </ul>
            </div>
            
            <p><strong>💡 Consejo final:</strong><br>
            ¡Ven con toda tu creatividad y ganas de aprender! Tu esfuerzo será parte de la historia del Primer Hackathon Micro:bit en Venezuela.</p>
            
            <p>Saludos cordiales,<br>
            <strong>Equipo Tecno Cleveland</strong><br>
            📧 info@cursoscleveland.com | ☎️ +58 412-0878674</p>
        </div>
    </body>
    </html>";
}

function getGuardianEmailTemplate($guardian_name, $student_name, $registration_number, $category)
{
    return "
    <html>
    <head>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .event-details { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
            .important { background: #f8d7da; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Registro de Representante</h1>
            <p>Hackathon de Micro:Bit 2025</p>
        </div>
        <div class='content'>
            <p>Estimado/a <strong>$guardian_name</strong>,</p>
            
            <p>Le informamos que el registro de <strong>$student_name</strong> para el Hackathon de Micro:Bit 2025 ha sido confirmado.</p>
            
            <p><strong>Número de registro:</strong> $registration_number</p>
            <p><strong>Fecha de registro:</strong> " . date('d/m/Y H:i') . "</p>
            <p><strong>Categoría:</strong> $category</p>
            
            <div class='event-details'>
                <h3>🗓️ Detalles del evento</h3>
                <p><strong>Fecha:</strong> Sábado, 29 de noviembre de 2025</p>
                <p><strong>Hora:</strong> 12:00 p.m. – 9:00 p.m.</p>
                <p><strong>Lugar:</strong> Plaza central de Buenaventura</p>
            </div>
            
            <div class='important'>
                <h3>⚠️ Importante para el participante</h3>
                <ul>
                    <li>Debe presentar documento de identidad y este correo de confirmación</li>
                    <li>La hora máxima de llegada será a las 11:30 a.m. para acreditación</li>
                    <li>Es obligatorio traer la autorización firmada por usted como representante legal</li>
                </ul>
            </div>
            
            <p>Como representante legal, usted ha autorizado la participación del menor en este evento educativo.</p>
            
            <p>Si tiene alguna pregunta, no dude en contactarnos.</p>
            
            <p>Saludos cordiales,<br>
            <strong>Equipo Tecno Cleveland</strong><br>
            📧 info@cursoscleveland.com | ☎️ +58 412-0878674</p>
        </div>
    </body>
    </html>";
}