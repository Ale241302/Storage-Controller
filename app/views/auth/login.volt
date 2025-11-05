<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Storage Controller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .form-control {
            border: 1px solid #ddd;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 5px;
            width: 100%;
            font-weight: bold;
            margin-top: 20px;
            cursor: pointer;
        }
        .btn-login:hover {
            opacity: 0.9;
        }
        .alert-custom {
            padding: 12px;
            border-radius: 5px;
            margin: 15px 0;
            display: none;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-custom.show {
            display: block;
        }
        .credentials-info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 12px;
        }
        .credentials-info strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Storage Controller</h1>
            <p class="text-muted">Acceso seguro a tu almacenamiento</p>
        </div>

        <div id="alertContainer"></div>

        <form id="loginForm">
            <input type="text" id="username" class="form-control" placeholder="Usuario" required>
            <input type="password" id="password" class="form-control" placeholder="Contraseña" required>
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div class="credentials-info">
            <strong>Credenciales de prueba:</strong><br>
            👤 Admin: <code>Admin</code> / <code>Admin213</code><br>
            👤 Usuario: <code>Pruebas</code> / <code>Prueba123</code>
        </div>
    </div>

    <script>
        // Procesar envío de formulario de login
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);

            fetch('/auth/processLogin', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showAlert('✅ Login exitoso', 'success');
                        setTimeout(() => window.location.href = '/', 1500);
                    } else {
                        showAlert('❌ ' + data.error, 'error');
                    }
                })
                .catch(err => showAlert('Error en la conexión', 'error'));
        });

        // Mostrar alerta dinámicamente en formulario
        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert-custom alert-${type} show`;
            alert.textContent = message;
            container.innerHTML = '';
            container.appendChild(alert);

            if (type !== 'success') {
                setTimeout(() => alert.remove(), 4000);
            }
        }
    </script>
</body>
</html>
