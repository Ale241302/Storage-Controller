<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Controller - Panel de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .container-main {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 30px;
        }
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .quota-bar {
            height: 25px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }
        .quota-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        .file-item {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-size {
            color: #666;
            font-size: 14px;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f8f9ff;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            background: #efefff;
            border-color: #764ba2;
        }
        .upload-area.dragover {
            background: #e0e0ff;
            border-color: #764ba2;
        }
        .alert-custom {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            display: none;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-custom.show {
            display: block;
        }
        .btn-admin {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 10px;
        }
        .btn-admin:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="container-main">
            <!-- Header con opciones -->
            <div class="header-section">
                <div>
                    <h1>📁 Storage Controller</h1>
                    <p class="text-muted">Gestiona tus archivos de forma segura</p>
                </div>
                <div>
                    
                    <?php if ($role == 'admin') { ?>
                        <a href="/admin" class="btn-admin">⚙️ Panel Admin</a>
                    <?php } ?>
                    <button class="btn btn-outline-danger" onclick="logout()">Logout</button>
                </div>
            </div>

            <!-- Cuota de almacenamiento -->
            <div class="quota-section">
                <h5>Cuota de Almacenamiento</h5>
                <div class="quota-bar">
                    <div class="quota-fill" id="quotaFill" style="width: 0%">
                        <span id="quotaPercentage">0%</span>
                    </div>
                </div>
                <p class="text-muted small">
                    <span id="quotaUsed">0</span> MB de <span id="quotaTotal">10</span> MB usados
                </p>
            </div>

            <!-- Contenedor de alertas dinámicas -->
            <div id="alertContainer"></div>

            <!-- Área de carga de archivos -->
            <div class="upload-area" id="uploadArea">
                <h5>📤 Arrastra archivos aquí o haz clic</h5>
                <p class="text-muted">Máximo: <span id="maxSize">100 MB</span></p>
                <input type="file" id="fileInput" style="display: none;" accept="*/*">
            </div>

            <!-- Lista de archivos del usuario -->
            <div class="files-section">
                <h5>Tus Archivos</h5>
                <div id="filesList">
                    <p class="text-muted">No hay archivos aún</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Estado global de la aplicación
        let appState = {
            quotaInfo: {},
            files: []
        };

        // Inicializar aplicación cuando DOM está listo
        document.addEventListener('DOMContentLoaded', function() {
            loadQuotaInfo();
            loadUserFiles();
            setupUploadArea();
        });

        // Cargar información de cuota actual del usuario
        function loadQuotaInfo() {
            fetch('/file/quota')
                .then(res => res.json())
                .then(data => {
                    appState.quotaInfo = data;
                    updateQuotaDisplay();
                })
                .catch(err => showAlert('Error al cargar cuota', 'error'));
        }

        // Actualizar visualización gráfica de cuota
        function updateQuotaDisplay() {
            const { quota_mb, used_mb, percentage_used } = appState.quotaInfo;
            document.getElementById('quotaFill').style.width = percentage_used + '%';
            document.getElementById('quotaPercentage').textContent = Math.round(percentage_used) + '%';
            document.getElementById('quotaUsed').textContent = used_mb.toFixed(2);
            document.getElementById('quotaTotal').textContent = quota_mb;
        }

        // Cargar lista de archivos del usuario
        function loadUserFiles() {
            const filesList = document.getElementById('filesList');
            const template = `<div class="file-item" data-file-id="<?= $file->id ?>">
                <div>
                    <strong><?= $file->original_filename ?></strong><br>
                    <span class="file-size"><?= $this->callMacro('formatBytes', [$file->file_size]) ?></span>
                </div>
                <button class="btn-delete" onclick="deleteFile(<?= $file->id ?>)">🗑️ Eliminar</button>
            </div>`;
            
            // Aquí la lógica será manejada por AJAX
        }

        // Configurar área de carga mediante drag-drop
        function setupUploadArea() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');

            uploadArea.addEventListener('click', () => fileInput.click());
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                handleFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
        }

        // Procesar archivos seleccionados o arrastrados
        function handleFiles(files) {
            if (files.length === 0) return;
            Array.from(files).forEach(file => uploadFile(file));
        }

        // Subir archivo individual al servidor
        function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);

            fetch('/file/upload', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showAlert('✅ ' + data.message, 'success');
                        appState.quotaInfo = data.quotaInfo;
                        updateQuotaDisplay();
                        loadUserFiles();
                    } else {
                        showAlert('❌ ' + data.error, 'error');
                    }
                })
                .catch(err => showAlert('Error en la subida', 'error'));
        }

        // Eliminar archivo por ID del servidor
        function deleteFile(fileId) {
            if (!confirm('¿Está seguro de que desea eliminar este archivo?')) return;

            const formData = new FormData();
            formData.append('file_id', fileId);

            fetch('/file/delete', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Archivo eliminado', 'success');
                        appState.quotaInfo = data.quotaInfo;
                        updateQuotaDisplay();
                        loadUserFiles();
                    } else {
                        showAlert(data.error, 'error');
                    }
                })
                .catch(err => showAlert('Error al eliminar', 'error'));
        }

        // Mostrar alerta temporal en la interfaz
        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert-custom alert-${type} show`;
            alert.textContent = message;
            container.appendChild(alert);

            setTimeout(() => alert.remove(), 4000);
        }

        // Convertir bytes a formato legible (KB, MB, GB)
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Cerrar sesión del usuario actual
        function logout() {
            window.location.href = '/auth/logout';
        }
    </script>
</body>
</html>
