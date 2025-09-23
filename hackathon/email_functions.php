<?php
// email_functions.php - Funciones para envío de emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php'; // Para PHPMailer

function sendConfirmationEmail($email, $name, $registration_number, $qr_url, $category = 'General')
{
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.zoho.com'; // Cambiar por tu servidor SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@cursoscleveland.com'; // Email actualizado
        $mail->Password   = 'xyvRc3zav239'; // Contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('info@cursoscleveland.com', 'Hackathon de Micro:bit');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = '🚀 ¡Registro Confirmado para el Hackathon 2025!';
        $mail->Body = getConfirmationEmailTemplate($name, $registration_number, $qr_url, $category);

        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando email: {$mail->ErrorInfo}");
    }
}

function sendGuardianNotificationEmail($email, $guardian_name, $student_name, $registration_number, $category = 'General')
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host       = 'smtp.zoho.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@cursoscleveland.com'; // Email actualizado
        $mail->Password   = 'xyvRc3zav239';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('info@cursoscleveland.com', 'Hackathon de Micro:bit');
        $mail->addAddress($email, $guardian_name);

        $mail->isHTML(true);
        $mail->Subject = '📩 Confirmación de Registro de Menor - Hackathon 2025';
        $mail->Body = getGuardianEmailTemplate($guardian_name, $student_name, $registration_number, $category);

        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando email: {$mail->ErrorInfo}");
    }
}

function getConfirmationEmailTemplate($name, $registration_number, $qr_url, $category)
{
    $registration_date = date('d/m/Y H:i');
    
    return "
    <html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: white;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }
            .header { 
                background: linear-gradient(135deg, #4f46e5, #7c3aed); 
                color: white; 
                padding: 30px 20px; 
                text-align: center; 
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: bold;
            }
            .content { 
                padding: 30px 20px; 
            }
            .info-section {
                background: #f8fafc;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #4f46e5;
            }
            .info-section h3 {
                margin-top: 0;
                color: #4f46e5;
                font-size: 18px;
            }
            .info-item {
                margin: 10px 0;
                padding: 5px 0;
            }
            .info-item strong {
                color: #1f2937;
            }
            .qr-section { 
                text-align: center; 
                margin: 25px 0;
                background: #fef3c7;
                padding: 20px;
                border-radius: 8px;
                border: 2px solid #f59e0b;
            }
            .qr-section h3 {
                color: #92400e;
                margin-top: 0;
            }
            .requirements {
                background: #ecfdf5;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #10b981;
            }
            .requirements h3 {
                color: #059669;
                margin-top: 0;
            }
            .requirements ul {
                margin: 10px 0;
                padding-left: 20px;
            }
            .requirements li {
                margin: 8px 0;
            }
            .warning {
                background: #fef2f2;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #ef4444;
            }
            .warning h3 {
                color: #dc2626;
                margin-top: 0;
            }
            .footer {
                background: #f8fafc;
                padding: 20px;
                text-align: center;
                border-top: 1px solid #e5e7eb;
                color: #6b7280;
            }
            .footer strong {
                color: #1f2937;
            }
            .emoji {
                font-size: 18px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📩 Correo de Confirmación – Hackathon de Micro:Bit 2025</h1>
                <p style='margin: 10px 0 0 0; font-size: 16px;'>Hackathon de Micro:bit</p>
            </div>
            
            <div class='content'>
                <p>Estimado/a <strong>$name</strong>,</p>
                
                <p>¡Felicidades! 🎉 Tu registro para el <strong>Hackathon de Micro:Bit 2025</strong> ha sido confirmado exitosamente.</p>
                
                <div class='info-section'>
                    <h3>📌 Información de tu registro</h3>
                    <div class='info-item'>
                        <strong>• Número de registro:</strong> $registration_number
                    </div>
                    <div class='info-item'>
                        <strong>• Fecha de registro:</strong> $registration_date
                    </div>
                    <div class='info-item'>
                        <strong>• Categoría:</strong> $category
                    </div>
                </div>
                
                <div class='qr-section'>
                    <h3>🎟️ Tu código QR</h3>
                    <p>Este será tu pase de acceso. <strong>Presenta este código QR el día del evento junto con tu documento de identidad.</strong></p>
                    <div style='margin: 20px 0;'>
                        <img src='$qr_url' alt='Código QR' style='max-width: 200px; height: auto;' />
                    </div>
                    <p style='color: #92400e; font-weight: bold;'>📌 <em>Sin este código y tu documento no podrás ingresar.</em></p>
                </div>
                
                <div class='info-section'>
                    <h3>🗓️ Detalles del evento</h3>
                    <div class='info-item'>
                        <strong>• Fecha:</strong> Sábado, 29 de noviembre de 2025
                    </div>
                    <div class='info-item'>
                        <strong>• Hora:</strong> 12:00 p.m. – 9:00 p.m.
                    </div>
                    <div class='info-item'>
                        <strong>• Lugar:</strong> Plaza central de Buenaventura
                    </div>
                </div>
                
                <div class='requirements'>
                    <h3>✅ Requisitos para participar</h3>
                    <ul>
                        <li>Documento de identidad (obligatorio).</li>
                        <li>Estudiantes menores de edad deben traer la <strong>autorización firmada por su representante legal</strong>.</li>
                        <li>Conocer el reglamento oficial (adjunto en PDF en este correo).</li>
                    </ul>
                </div>
                
                <div class='info-section'>
                    <h3>🚀 Próximos pasos</h3>
                    <div class='info-item'>
                        • Guarda este correo como comprobante de inscripción.
                    </div>
                    <div class='info-item'>
                        • Revisa las publicaciones y actualizaciones en nuestras redes sociales. @hackathon.tecno
                    </div>
                </div>
                
                <div class='warning'>
                    <h3>⚠️ Importante</h3>
                    <p>• La <strong>hora máxima de llegada</strong> será a las 11:30 a.m. para acreditación.</p>
                    <p>• Si llegas tarde, podrías quedar fuera de la competencia.</p>
                </div>
                
                <div style='background: #e0e7ff; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center;'>
                    <p style='margin: 0; font-size: 16px;'><strong>💡 Consejo final:</strong> ¡Ven con toda tu creatividad y ganas de aprender! Tu esfuerzo será parte de la historia del <strong>Primer Hackathon Micro:bit en Venezuela</strong>.</p>
                </div>
            </div>
            
            <div class='footer'>
                <p>Saludos cordiales,</p>
                <p><strong>Equipo Tecno Cleveland</strong></p>
                <p>📧 info@cursoscleveland.com | ☎️ +58 412-0878674</p>
            </div>
        </div>
    </body>
    </html>";
}

function getGuardianEmailTemplate($guardian_name, $student_name, $registration_number, $category)
{
    $registration_date = date('d/m/Y H:i');
    
    return "
    <html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: white;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }
            .header { 
                background: linear-gradient(135deg, #4f46e5, #7c3aed); 
                color: white; 
                padding: 30px 20px; 
                text-align: center; 
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: bold;
            }
            .content { 
                padding: 30px 20px; 
            }
            .info-section {
                background: #f8fafc;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #4f46e5;
            }
            .footer {
                background: #f8fafc;
                padding: 20px;
                text-align: center;
                border-top: 1px solid #e5e7eb;
                color: #6b7280;
            }
            .footer strong {
                color: #1f2937;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📩 Registro de Representante</h1>
                <p style='margin: 10px 0 0 0; font-size: 16px;'>Hackathon de Micro:Bit 2025</p>
            </div>
            
            <div class='content'>
                <p>Estimado/a <strong>$guardian_name</strong>,</p>
                
                <p>Le informamos que el registro de <strong>$student_name</strong> para el <strong>Hackathon de Micro:Bit 2025</strong> ha sido confirmado exitosamente.</p>
                
                <div class='info-section'>
                    <h3>📌 Información del registro</h3>
                    <p><strong>• Número de registro:</strong> $registration_number</p>
                    <p><strong>• Fecha de registro:</strong> $registration_date</p>
                    <p><strong>• Categoría:</strong> $category</p>
                    <p><strong>• Participante:</strong> $student_name</p>
                </div>
                
                <div style='background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;'>
                    <h3 style='color: #92400e; margin-top: 0;'>📋 Detalles del evento</h3>
                    <p><strong>• Fecha:</strong> Sábado, 29 de noviembre de 2025</p>
                    <p><strong>• Hora:</strong> 12:00 p.m. – 9:00 p.m.</p>
                    <p><strong>• Lugar:</strong> Plaza central de Buenaventura</p>
                    <p><strong>• Hora máxima de llegada:</strong> 11:30 a.m.</p>
                </div>
                
                <div style='background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981;'>
                    <h3 style='color: #059669; margin-top: 0;'>⚠️ Recordatorio importante</h3>
                    <p>Como representante legal, usted ha autorizado la participación del menor en este evento educativo.</p>
                    <p><strong>El estudiante debe llevar:</strong></p>
                    <ul>
                        <li>Documento de identidad</li>
                        <li>La autorización firmada por el representante legal</li>
                        <li>El código QR de confirmación (enviado al email del estudiante)</li>
                    </ul>
                </div>
                
                <p>Si tiene alguna pregunta o necesita más información, no dude en contactarnos.</p>
                
                <div style='background: #e0e7ff; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center;'>
                    <p style='margin: 0; font-size: 16px;'><strong>💡 Recuerde:</strong> Este es un evento educativo que formará parte de la historia del <strong>Primer Hackathon Micro:bit en Venezuela</strong>.</p>
                </div>
            </div>
            
            <div class='footer'>
                <p>Saludos cordiales,</p>
                <p><strong>Equipo Tecno Cleveland</strong></p>
                <p>📧 info@cursoscleveland.com | ☎️ +58 412-0878674</p>
            </div>
        </div>
    </body>
    </html>";
}
?>