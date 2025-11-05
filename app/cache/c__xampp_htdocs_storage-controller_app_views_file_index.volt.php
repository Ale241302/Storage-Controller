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
            padding: 8px 12px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.2s;
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
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-admin:hover {
            background: #218838;
            color: white;
        }
        .btn-logout {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-logout:hover {
            background: #c82333;
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .modal-footer .btn-secondary {
            background: #6c757d;
        }
        .modal-footer .btn-secondary:hover {
            background: #5a6268;
        }
        .modal-footer .btn-danger {
            background: #dc3545;
        }
        .modal-footer .btn-danger:hover {
            background: #c82333;
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
                    <button class="btn-logout" onclick="logout()">Logout</button>
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

    <!-- ⭐ MODAL DE CONFIRMACIÓN DE ELIMINACIÓN -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">⚠️ Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar el archivo?</p>
                    <p class="text-muted" id="deleteFileName" style="font-weight: bold;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    let appState = { quotaInfo: {}, files: [], deleteFileId: null };

    document.addEventListener('DOMContentLoaded', function() {
        loadQuotaInfo();
        loadUserFiles();
        setupUploadArea();
        setupDeleteModal();
    });

    /**
     * Cargar información de cuota y archivos
     */
    function loadQuotaInfo() {
        fetch('/file/quota')
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                console.log('📊 Respuesta completa:', data);
                
                if (!data.success) {
                    showAlert(data.error || 'Error al cargar cuota', 'error');
                    return;
                }

                const quotaData = data.data?.quota || {};
                const filesData = data.data?.files || [];

                console.log('📊 Cuota:', quotaData);
                console.log('📁 Archivos:', filesData);

                appState.quotaInfo = {
                    quota_mb: quotaData.quota_mb || 100,
                    used_mb: quotaData.used_mb || 0,
                    available_mb: quotaData.available_mb || 100,
                    percentage_used: quotaData.percentage_used || 0,
                    quota_type: quotaData.quota_type || 'global'
                };

                appState.files = Array.isArray(filesData) ? filesData : [];

                updateQuotaDisplay();
                renderFilesList();
            })
            .catch(err => {
                console.error('❌ Error al cargar cuota:', err);
                showAlert('Error al cargar cuota: ' + err.message, 'error');
            });
    }

    /**
     * Actualizar visualización de cuota
     */
    function updateQuotaDisplay() {
        const { quota_mb, used_mb, percentage_used } = appState.quotaInfo;
        
        if (document.getElementById('quotaFill')) {
            document.getElementById('quotaFill').style.width = percentage_used + '%';
        }
        if (document.getElementById('quotaPercentage')) {
            document.getElementById('quotaPercentage').textContent = Math.round(percentage_used) + '%';
        }
        if (document.getElementById('quotaUsed')) {
            document.getElementById('quotaUsed').textContent = (used_mb || 0).toFixed(2);
        }
        if (document.getElementById('quotaTotal')) {
            document.getElementById('quotaTotal').textContent = quota_mb || 10;
        }
    }

    /**
     * Renderizar lista de archivos
     */
    function renderFilesList() {
        const filesList = document.getElementById('filesList');
        
        if (!filesList) return;
        
        if (!Array.isArray(appState.files) || appState.files.length === 0) {
            filesList.innerHTML = '<p style="text-align: center; color: #999;">No hay archivos aún</p>';
            return;
        }

        filesList.innerHTML = appState.files.map(file => `
            <div class="file-item">
                <div>
                    <strong>${escapeHtml(file.filename)}</strong>
                    <p style="font-size: 12px; color: #666; margin: 5px 0 0 0;">
                        ${formatFileSize(file.file_size)} • ${new Date(file.created_at).toLocaleDateString()}
                    </p>
                </div>
                <button class="btn-delete" onclick="showDeleteModal(${file.id}, '${escapeHtml(file.filename)}')">
                    Eliminar
                </button>
            </div>
        `).join('');
    }

    /**
     * Cargar archivos del usuario
     */
    function loadUserFiles() {
        // Los archivos se cargan en loadQuotaInfo()
    }

    /**
     * Configurar área de carga
     */
    function setupUploadArea() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        
        if (!uploadArea || !fileInput) return;

        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
    }

    /**
     * Manejar archivos a subir
     */
    function handleFiles(files) {
        if (files.length === 0) return;
        
        Array.from(files).forEach(file => {
            if (file.size > appState.quotaInfo.available_mb * 1024 * 1024) {
                showAlert(`${file.name} es demasiado grande. Disponible: ${appState.quotaInfo.available_mb} MB`, 'error');
                return;
            }
            uploadFile(file);
        });
    }

    /**
     * Subir archivo
     */
    function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);

        console.log(`📤 Subiendo archivo: ${file.name} (${formatFileSize(file.size)})`);

        fetch('/file/upload', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                console.log('✅ Respuesta de subida:', data);
                
                if (data.success) {
                    showAlert(data.message || 'Archivo subido correctamente', 'success');
                    
                    const quotaData = data.data?.quota || {};
                    appState.quotaInfo = {
                        quota_mb: quotaData.quota_mb || appState.quotaInfo.quota_mb,
                        used_mb: quotaData.used_mb || 0,
                        available_mb: quotaData.available_mb || 0,
                        percentage_used: quotaData.percentage_used || 0
                    };
                    
                    updateQuotaDisplay();
                    loadQuotaInfo();
                } else {
                    showAlert(data.error || 'Error en la subida', 'error');
                }
            })
            .catch(err => {
                console.error('❌ Error en la subida:', err);
                showAlert('Error: ' + err.message, 'error');
            });
    }

    /**
     * ⭐ MOSTRAR MODAL DE CONFIRMACIÓN
     */
    function showDeleteModal(fileId, fileName) {
        appState.deleteFileId = fileId;
        document.getElementById('deleteFileName').textContent = fileName;
        
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }

    /**
     * Configurar evento del botón confirmar eliminación
     */
    function setupDeleteModal() {
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                if (appState.deleteFileId) {
                    confirmDeleteFile(appState.deleteFileId);
                }
            });
        }
    }

    /**
     * Confirmar eliminación de archivo
     */
    function confirmDeleteFile(fileId) {
        const formData = new FormData();
        formData.append('file_id', fileId);

        console.log(`🗑️ Eliminando archivo ID: ${fileId}`);

        fetch('/file/delete', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                console.log('✅ Respuesta de eliminación:', data);
                
                // Cerrar modal
                const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                if (deleteModal) {
                    deleteModal.hide();
                }

                if (data.success) {
                    showAlert('Archivo eliminado', 'success');
                    
                    const quotaData = data.data?.quota || {};
                    appState.quotaInfo = {
                        quota_mb: quotaData.quota_mb || appState.quotaInfo.quota_mb,
                        used_mb: quotaData.used_mb || 0,
                        available_mb: quotaData.available_mb || 0,
                        percentage_used: quotaData.percentage_used || 0
                    };
                    
                    updateQuotaDisplay();
                    loadQuotaInfo();
                } else {
                    showAlert(data.error || 'Error al eliminar', 'error');
                }
            })
            .catch(err => {
                console.error('❌ Error al eliminar:', err);
                showAlert('Error: ' + err.message, 'error');
            });
    }

    /**
     * ⭐ MOSTRAR ALERTA (sin emoji duplicado)
     */
    function showAlert(message, type) {
        const container = document.getElementById('alertContainer');
        if (!container) return;
        
        // ⭐ NO agregar emoji si el mensaje ya lo tiene
        let displayMessage = message;
        if (type === 'error' && !message.startsWith('❌')) {
            displayMessage = '❌ ' + message;
        } else if (type === 'success' && !message.startsWith('✅')) {
            displayMessage = '✅ ' + message;
        }
        
        const alert = document.createElement('div');
        alert.className = `alert-custom alert-${type} show`;
        alert.textContent = displayMessage;
        container.appendChild(alert);

        setTimeout(() => alert.remove(), 4000);
    }

    /**
     * Formatear tamaño de archivo
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Escapar caracteres HTML (seguridad)
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Cerrar sesión
     */
    function logout() {
        window.location.href = '/auth/logout';
    }
    </script>

</body>
</html>
