<?php
// install.php - Script de instalación automática
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = $_POST;
    
    try {
        // Crear config.php
        createConfigFile($config);
        
        // Crear base de datos
        createDatabase($config);
        
        // Crear carpetas necesarias
        createFolders();
        
        // Verificar permisos
        checkPermissions();
        
        echo "<div class='success'>✅ Instalación completada exitosamente!</div>";
        echo "<p><a href='index.html' class='btn'>Ir al formulario</a></p>";
        echo "<p><a href='admin_panel.php' class='btn'>Panel de administración</a></p>";
        
        // Eliminar archivo de instalación por seguridad
        // unlink(__FILE__);
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    }
    
    exit;
}

function createConfigFile($config) {
    $configContent = "<?php\n";
    $configContent .= "// Configuración generada automáticamente\n";
    $configContent .= "\$servername = '{$config['db_host']}';\n";
    $configContent .= "\$username = '{$config['db_user']}';\n";
    $configContent .= "\$password = '{$config['db_pass']}';\n";
    $configContent .= "\$dbname = '{$config['db_name']}';\n\n";
    
    $configContent .= "try {\n";
    $configContent .= "    \$pdo = new PDO(\"mysql:host=\$servername;dbname=\$dbname;charset=utf8\", \$username, \$password);\n";
    $configContent .= "    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";
    $configContent .= "} catch(PDOException \$e) {\n";
    $configContent .= "    die(\"Error de conexión: \" . \$e->getMessage());\n";
    $configContent .= "}\n\n";
    
    $configContent .= "// Configuración de email\n";
    $configContent .= "define('SMTP_HOST', '{$config['smtp_host']}');\n";
    $configContent .= "define('SMTP_USERNAME', '{$config['smtp_user']}');\n";
    $configContent .= "define('SMTP_PASSWORD', '{$config['smtp_pass']}');\n";
    $configContent .= "define('FROM_EMAIL', '{$config['from_email']}');\n";
    $configContent .= "define('FROM_NAME', 'Hackathon Cursos Cleveland');\n";
    $configContent .= "?>";
    
    if (file_put_contents('config.php', $configContent) === false) {
        throw new Exception('No se pudo crear config.php');
    }
}

function createDatabase($config) {
    $pdo = new PDO("mysql:host={$config['db_host']}", $config['db_user'], $config['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear base de datos
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$config['db_name']}`");
    
    // Crear tabla
    $sql = "CREATE TABLE IF NOT EXISTS registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        registration_number VARCHAR(50) UNIQUE NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        document_type ENUM('cedula', 'pasaporte', 'cedula_escolar') NOT NULL,
        document_number VARCHAR(50) NOT NULL UNIQUE,
        nationality VARCHAR(1),
        birth_date DATE NOT NULL,
        age INT NOT NULL,
        gender ENUM('masculino', 'femenino') NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        state VARCHAR(100) NOT NULL,
        city VARCHAR(100) NOT NULL,
        institution VARCHAR(255) NOT NULL,
        education_level ENUM('primaria', 'bachillerato', 'universidad') NOT NULL,
        grade VARCHAR(100),
        category ENUM('Junior', 'Senior') NOT NULL,
        microbit_experience ENUM('ninguna', 'basica', 'intermedia', 'avanzada') NOT NULL,
        expectations TEXT,
        shirt_size ENUM('S', 'M', 'L', 'XL') NOT NULL,
        document_photo_path VARCHAR(500),
        is_minor BOOLEAN DEFAULT FALSE,
        guardian_name VARCHAR(255),
        guardian_doc_type VARCHAR(50),
        guardian_document VARCHAR(50),
        guardian_email VARCHAR(255),
        guardian_phone VARCHAR(20),
        authorization_doc_path VARCHAR(500),
        image_rights_accepted BOOLEAN DEFAULT TRUE,
        data_verified BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    
    // Crear índices
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_document_number ON registrations(document_number)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_registration_number ON registrations(registration_number)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_email ON registrations(email)");
}

function createFolders() {
    $folders = [
        'uploads',
        'uploads/documents',
        'uploads/authorizations',
        'logs'
    ];
    
    foreach ($folders as $folder) {
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }
    }
}

function checkPermissions() {
    $folders = ['uploads', 'uploads/documents', 'uploads/authorizations'];
    
    foreach ($folders as $folder) {
        if (!is_writable($folder)) {
            throw new Exception("La carpeta $folder no tiene permisos de escritura");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Formulario Hackathon</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #4f46e5;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 5px;
            font-size: 16px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #4f46e5;
        }
        .btn {
            background: #4f46e5;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-top: 10px;
        }
        .btn:hover {
            background: #3730a3;
        }
        .success {
            background: #10b981;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background: #ef4444;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info {
            background: #3b82f6;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Instalación del Formulario Hackathon</h1>
        
        <div class="info">
            <strong>Antes de continuar, asegúrate de tener:</strong>
            <ul>
                <li>Servidor web con PHP 7.4+</li>
                <li>Base de datos MySQL</li>
                <li>Cuenta de email SMTP configurada</li>
                <li>PHPMailer instalado (composer require phpmailer/phpmailer)</li>
            </ul>
        </div>

        <form method="POST">
            <h2>📊 Configuración de Base de Datos</h2>
            
            <div class="form-group">
                <label for="db_host">Host de la Base de Datos:</label>
                <input type="text" id="db_host" name="db_host" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label for="db_user">Usuario de la Base de Datos:</label>
                <input type="text" id="db_user" name="db_user" value="root" required>
            </div>
            
            <div class="form-group">
                <label for="db_pass">Contraseña de la Base de Datos:</label>
                <input type="password" id="db_pass" name="db_pass">
            </div>
            
            <div class="form-group">
                <label for="db_name">Nombre de la Base de Datos:</label>
                <input type="text" id="db_name" name="db_name" value="hackathon_db" required>
            </div>

            <h2>📧 Configuración de Email</h2>
            
            <div class="form-group">
                <label for="smtp_host">Servidor SMTP:</label>
                <input type="text" id="smtp_host" name="smtp_host" value="smtp.gmail.com" required>
            </div>
            
            <div class="form-group">
                <label for="smtp_user">Usuario SMTP (Email):</label>
                <input type="email" id="smtp_user" name="smtp_user" required>
            </div>
            
            <div class="form-group">
                <label for="smtp_pass">Contraseña SMTP (Contraseña de aplicación):</label>
                <input type="password" id="smtp_pass" name="smtp_pass" required>
            </div>
            
            <div class="form-group">
                <label for="from_email">Email remitente:</label>
                <input type="email" id="from_email" name="from_email" placeholder="noreply@cursoscleveland.com" required>
            </div>

            <button type="submit" class="btn">🔧 Instalar Ahora</button>
        </form>
    </div>
</body>
</html>