<?php
// 1. Iniciar sesión obligatoriamente al principio del archivo
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once 'database.php';

$error = '';

// Si el usuario ya inició sesión previamente, redirigirlo a su panel correspondiente
if (isset($_SESSION['user_id'])) {
  if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Juez') {
    header("Location: judge_dashboard.php"); // Cambia esto por el nombre de tu vista de juez
  } else {
    header("Location: dashboard.php");
  }
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $password = $_POST['password'];

  // 2. Consultar el usuario y hacer JOIN para obtener el nombre del rol
  $stmt = $pdo->prepare("
        SELECT u.*, r.name AS role_name 
        FROM users u
        INNER JOIN roles r ON u.role_id = r.id
        WHERE u.username = :username 
        LIMIT 1
    ");
  $stmt->execute(['username' => $username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && password_verify($password, $user['password'])) {
    // Login exitoso: iniciar sesión y guardar variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role_name'] = $user['role_name']; // <- Esto soluciona tu problema de permisos

    // 3. Redirección dinámica basada en el rol
    if ($user['role_name'] === 'Admin') {
      header("Location: dashboard.php");
    } else if ($user['role_name'] === 'Juez') {
      header("Location: judge_dashboard.php"); // Asegúrate de crear este archivo para los jueces
    } else {
      header("Location: dashboard.php"); // Fallback general
    }
    exit();
  } else {
    $error = "Usuario o contraseña incorrectos";
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="bg-light">
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card shadow">
          <div class="card-body">
            <h3 class="card-title mb-3 text-center">Iniciar Sesión</h3>

            <?php if ($error): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="mb-3">
                <input type="text" class="form-control" name="username" placeholder="Usuario" required>
              </div>
              <div class="mb-3">
                <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
              </div>
              <button class="btn btn-primary w-100">Iniciar Sesión</button>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>