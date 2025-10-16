<?php
// config.php - Configuración de base de datos
$servername = "localhost";
$username = "hackathon_user";
$password = "J30886449-8";
$dbname = "hackathon_db";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hackathon_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>