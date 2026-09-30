<?php
// Opcional: Validar sesión de administrador/staff aquí
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'header.php'; // Tu header si usas uno
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escanear Asistencia</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #reader video {
            object-fit: cover;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 text-center">
                <h2 class="mb-4 text-primary"><i class="bi bi-qr-code-scan"></i> Control de Acceso</h2>

                <!-- Contenedor de la cámara -->
                <div id="reader" class="mb-4"></div>

                <div id="result-container" class="card shadow-sm d-none mt-3">
                    <div class="card-body" id="result-body">
                        <!-- Aquí se mostrarán los datos del participante -->
                    </div>
                </div>

                <button id="btn-restart" class="btn btn-secondary mt-3 d-none" onclick="startScanner()">
                    <i class="bi bi-arrow-clockwise"></i> Escanear Otro
                </button>
            </div>
        </div>
    </div>

    <!-- Librería del Escáner -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        let html5QrcodeScanner;
        let isProcessing = false; // Bandera para evitar escaneos duplicados rápidos

        // Inicializar el escáner
        function startScanner() {
            document.getElementById('result-container').classList.add('d-none');
            document.getElementById('btn-restart').classList.add('d-none');
            isProcessing = false;

            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", {
                    fps: 10,
                    qrbox: {
                        width: 250,
                        height: 250
                    },
                    supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
                    // Forzar cámara trasera en móviles
                    videoConstraints: {
                        facingMode: {
                            ideal: "environment"
                        }
                    }
                },
                false
            );
            html5QrcodeScanner.render(onScanSuccess, onScanFailure)
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo acceder a la cámara',
                        text: 'Verifica los permisos y que uses HTTPS. Error: ' + err,
                    });
                    console.error('Error al iniciar cámara:', err);
                });
        }

        // Función que se ejecuta al detectar un QR
        function onScanSuccess(decodedText, decodedResult) {
            if (isProcessing) return; // Evita enviar la petición varias veces
            isProcessing = true;

            // Detener la cámara temporalmente
            html5QrcodeScanner.clear().then(() => {
                procesarAsistencia(decodedText);
            });
        }

        // Función si falla el escaneo (sólo logs, no alertar al usuario porque falla hasta que enfoca bien)
        function onScanFailure(error) {
            // console.warn(`Error escaneando: ${error}`);
        }

        // Enviar código por AJAX a PHP
        async function procesarAsistencia(registration_number) {
            const formData = new FormData();
            formData.append('registration_number', registration_number);

            try {
                // Muestra mensaje de carga
                Swal.fire({
                    title: 'Verificando...',
                    text: 'Comprobando registro en la base de datos',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const response = await fetch('procesar_asistencia.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Éxito: Participante marcado
                    Swal.fire({
                        icon: 'success',
                        title: '¡Acceso Permitido!',
                        html: `<b>${result.data.full_name} ${result.data.last_name}</b><br>
                           Categoría: ${result.data.category}<br>
                           Cédula: ${result.data.document_number}`,
                        confirmButtonText: 'Aceptar'
                    });
                    mostrarDatosEnPantalla(result.data, true);
                } else {
                    // Error: Ya registrado o no existe
                    Swal.fire({
                        icon: 'error',
                        title: 'Problema con el acceso',
                        text: result.message,
                        confirmButtonText: 'Entendido'
                    });
                    if (result.data) {
                        mostrarDatosEnPantalla(result.data, false);
                    }
                }

            } catch (error) {
                Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
                console.error(error);
            } finally {
                document.getElementById('btn-restart').classList.remove('d-none');
            }
        }

        // Función auxiliar para mostrar datos en la tarjeta
        function mostrarDatosEnPantalla(data, isSuccess) {
            const container = document.getElementById('result-container');
            const body = document.getElementById('result-body');

            let statusBadge = isSuccess ?
                '<span class="badge bg-success">Entrada Registrada</span>' :
                '<span class="badge bg-warning text-dark">Ya había registrado entrada</span>';

            body.innerHTML = `
            <h4 class="mb-1">${data.full_name} ${data.last_name}</h4>
            <p class="text-muted mb-2">C.I: ${data.document_number} | Categoría: <strong>${data.category}</strong></p>
            ${statusBadge}
        `;
            container.classList.remove('d-none');
        }

        // Iniciar al cargar la página
        document.addEventListener("DOMContentLoaded", function() {
            startScanner();
        });
    </script>

</body>

</html>