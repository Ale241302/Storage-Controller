<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Storage Controller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 30px;
            margin: 20px;
        }
        .header-admin {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .header-admin h1 {
            color: #667eea;
            margin: 0;
        }
        .header-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-back {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
        }
        .btn-back:hover {
            background: #218838;
        }
        .btn-logout {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-logout:hover {
            background: #c82333;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .tabs-container {
            display: flex;
            gap: 10px;
            margin: 30px 0 20px 0;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
        }
        .tab-button {
            color: #666;
            cursor: pointer;
            padding: 12px 20px;
            border: none;
            background: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .tab-button:hover {
            color: #667eea;
        }
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .tab-content.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .tab-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0 15px 0;
        }
        .tab-header h5 {
            margin: 0;
            color: #333;
        }
        .btn-primary {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-primary:hover {
            background: #764ba2;
        }
        .btn-edit {
            background: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }
        .btn-edit:hover {
            background: #0056b3;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        .alert-custom {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            display: none;
        }
        .alert-custom.show {
            display: block;
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
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        .modal-header {
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }
        .modal-header h5 {
            margin: 0;
            color: #667eea;
            font-weight: bold;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .form-group {
            margin: 15px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            border-top: 2px solid #f0f0f0;
            padding-top: 15px;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-cancel:hover {
            background: #5a6268;
        }
        .btn-submit {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-submit:hover {
            background: #764ba2;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .user-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .user-item:hover {
            background: #f0f0f0;
        }
        .user-item.selected {
            background: #e3f2fd;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="admin-container">
            <!-- Header -->
            <div class="header-admin">
                <div>
                    <h1>⚙️ Panel de Administración</h1>
                    <p class="text-muted">Gestión centralizada del sistema</p>
                </div>
                <div class="header-buttons">
                    <a href="/" class="btn-back">← Volver al Panel</a>
                    <button class="btn-logout" onclick="logout()">Logout</button>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalUsers">0</div>
                    <div class="stat-label">Usuarios Totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalGroups">0</div>
                    <div class="stat-label">Grupos Creados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalFiles">0</div>
                    <div class="stat-label">Archivos en Sistema</div>
                </div>
            </div>

            <!-- Alertas -->
            <div id="alertContainer"></div>

            <!-- Tabs Horizontales -->
            <div class="tabs-container">
                <button class="tab-button active" onclick="switchTab(event, 'users')">👥 Usuarios</button>
                <button class="tab-button" onclick="switchTab(event, 'groups')">📁 Grupos</button>
                <button class="tab-button" onclick="switchTab(event, 'assignments')">🔗 Asignaciones</button>
                <button class="tab-button" onclick="switchTab(event, 'extensions')">🚫 Extensiones</button>
                <button class="tab-button" onclick="switchTab(event, 'settings')">⚙️ Configuración</button>
            </div>
            <!-- Pestaña: Configuración -->
            <div id="settings" class="tab-content">
                <div class="tab-header">
                    <h5>⚙️ Configuración Global</h5>
                </div>
                <div style="max-width: 500px; background: #f9f9f9; padding: 20px; border-radius: 8px;">
                    <div class="form-group">
                        <label><strong>Límite Global de Cuota (MB)</strong></label>
                        <input type="number" id="globalQuota" class="form-control" min="1" placeholder="10">
                        <small style="color: #666;">Este será el límite por defecto para todos los usuarios</small>
                    </div>
                    <button class="btn-primary" onclick="saveGlobalSettings()">💾 Guardar Configuración</button>
                </div>
            </div>
            <!-- Pestaña: Usuarios -->
            <div id="users" class="tab-content active">
                <div class="tab-header">
                    <h5>Gestión de Usuarios</h5>
                    <button class="btn-primary" onclick="openUserModal(null)">+ Crear Usuario</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usersBody">
                        <tr>
                            <td colspan="5" class="empty-state">
                                <div class="empty-state-icon">👤</div>
                                <p>No hay usuarios registrados</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pestaña: Grupos -->
            <div id="groups" class="tab-content">
                <div class="tab-header">
                    <h5>Gestión de Grupos</h5>
                    <button class="btn-primary" onclick="openGroupModal(null)">+ Crear Grupo</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Cuota (MB)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="groupsBody">
                        <tr>
                            <td colspan="5" class="empty-state">
                                <div class="empty-state-icon">📁</div>
                                <p>No hay grupos creados</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pestaña: Asignaciones -->
            <div id="assignments" class="tab-content">
                <div class="tab-header">
                    <h5>🔗 Asignar Usuarios a Grupos</h5>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Lista de usuarios -->
                    <div>
                        <h6 style="color: #667eea; font-weight: bold; margin-bottom: 15px;">👥 Usuarios</h6>
                        <div id="usersList" style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; max-height: 500px; overflow-y: auto;">
                            <p style="text-align: center; color: #999;">Cargando usuarios...</p>
                        </div>
                    </div>
                    <!-- Lista de grupos -->
                    <div>
                        <h6 style="color: #667eea; font-weight: bold; margin-bottom: 15px;">📁 Grupos Disponibles</h6>
                        <div id="groupsList" style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; max-height: 500px; overflow-y: auto;">
                            <p style="text-align: center; color: #999;">Selecciona un usuario primero</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestaña: Extensiones -->
            <div id="extensions" class="tab-content">
                <div class="tab-header">
                    <h5>Extensiones Prohibidas</h5>
                    <button class="btn-primary" onclick="openExtensionModal(null)">+ Añadir Extensión</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Extensión</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="extensionsBody">
                        <tr>
                            <td colspan="4" class="empty-state">
                                <div class="empty-state-icon">🚫</div>
                                <p>No hay extensiones prohibidas</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: Usuario -->
    <div id="userModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('userModal')">×</button>
            <div class="modal-header">
                <h5 id="userModalTitle">Crear Usuario</h5>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId">
                <div class="form-group">
                    <label>Nombre de Usuario</label>
                    <input type="text" id="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" id="userPassword" class="form-control" placeholder="••••••">
                    <small id="passwordHelp" style="color: #666; margin-top: 5px; display: block;">
                        ⭐ Al crear: Dejar vacío generará contraseña aleatoria
                    </small>
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select id="role" class="form-control">
                        <option value="user">Usuario</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cuota de Almacenamiento (MB)</label>
                    <input type="number" id="userQuota" class="form-control" min="1" placeholder="100">
                    <small style="color: #666; margin-top: 5px; display: block;">Dejar vacío para usar la cuota global</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('userModal')">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal: Grupo -->
    <div id="groupModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('groupModal')">×</button>
            <div class="modal-header">
                <h5 id="groupModalTitle">Crear Grupo</h5>
            </div>
            <form id="groupForm">
                <input type="hidden" id="groupId">
                <div class="form-group">
                    <label>Nombre del Grupo</label>
                    <input type="text" id="groupName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" id="groupDescription" class="form-control">
                </div>
                <div class="form-group">
                    <label>Cuota (MB)</label>
                    <input type="number" id="groupQuota" class="form-control" min="0" value="100">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('groupModal')">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Extensión -->
    <div id="extensionModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('extensionModal')">×</button>
            <div class="modal-header">
                <h5 id="extensionModalTitle">Añadir Extensión</h5>
            </div>
            <form id="extensionForm">
                <input type="hidden" id="extensionId">
                <div class="form-group">
                    <label>Extensión (ej: exe, bat, php)</label>
                    <input type="text" id="extension" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" id="extDescription" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('extensionModal')">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Confirmación de Eliminación -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 450px; position: relative;">
            <button class="modal-close" onclick="closeModal('deleteModal')">×</button>
            <div class="modal-header">
                <h5 style="color: #dc3545; display: flex; align-items: center; gap: 10px;">
                    ⚠️ Confirmar Eliminación
                </h5>
            </div>
            <div style="padding: 20px 0;">
                <p id="deleteMessage" style="margin: 0; color: #555; font-size: 15px; line-height: 1.6;"></p>
            </div>
            <div class="modal-footer" style="gap: 15px; justify-content: center;">
                <button class="btn-cancel" onclick="closeModal('deleteModal')" style="min-width: 120px;">
                    Cancelar
                </button>
                <button class="btn-delete" onclick="confirmDelete()" style="min-width: 120px; padding: 12px 30px; font-size: 15px;">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
    <script>
    let currentDeleteAction = null;
    let currentUser = null;

    function switchTab(event, tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
        
        if (tabName === 'users') loadUsers();
        if (tabName === 'groups') loadGroups();
        if (tabName === 'assignments') loadAssignments();
        if (tabName === 'extensions') loadExtensions();
        if (tabName === 'settings') loadGlobalSettings();
    }

    function openModal(modalId) {
        document.getElementById(modalId).classList.add('show');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('show');
        if (modalId === 'userModal') document.getElementById('userForm').reset();
        if (modalId === 'groupModal') document.getElementById('groupForm').reset();
        if (modalId === 'extensionModal') document.getElementById('extensionForm').reset();
    }

    // ========== USUARIOS ==========

    function openUserModal(userId) {
        if (userId === null) {
            document.getElementById('userModalTitle').textContent = 'Crear Usuario';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('userPassword').required = true;
            document.getElementById('userPassword').placeholder = '••••••';
            document.getElementById('passwordHelp').textContent = '⭐ Al crear: Dejar vacío generará contraseña aleatoria';
            document.getElementById('userQuota').value = '';
            openModal('userModal');
        } else {
            fetch(`/admin/users/${userId}`)
                .then(res => res.json())
                .then(response => {
                    const user = response.data || response;
                    document.getElementById('userModalTitle').textContent = 'Editar Usuario';
                    document.getElementById('userId').value = user.id;
                    document.getElementById('username').value = user.username;
                    document.getElementById('email').value = user.email;
                    document.getElementById('role').value = user.role;
                    document.getElementById('userPassword').value = '';
                    document.getElementById('userPassword').required = false;
                    document.getElementById('userPassword').placeholder = 'Dejar vacío para mantener actual';
                    document.getElementById('passwordHelp').textContent = '⭐ Al editar: Dejar vacío mantiene la contraseña actual';
                    document.getElementById('userQuota').value = user.quota_mb || '';
                    openModal('userModal');
                })
                .catch(err => showAlert('Error al cargar usuario: ' + err.message, 'error'));
        }
    }

    function loadUsers() {
        fetch('/admin/users')
            .then(res => res.json())
            .then(response => {
                const users = response.data || response;
                const tbody = document.getElementById('usersBody');
                document.getElementById('totalUsers').textContent = Array.isArray(users) ? users.length : 0;
                
                if (!Array.isArray(users) || users.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><div class="empty-state-icon">👤</div><p>No hay usuarios registrados</p></td></tr>';
                    return;
                }
                
                tbody.innerHTML = users.map(user => {
                    const groupInfo = user.group_ids && user.group_ids.length > 0 
                        ? `En ${user.group_ids.length} grupo(s)` 
                        : 'Sin grupos';
                    
                    const quotaDisplay = user.quota_mb 
                        ? `${user.quota_mb} MB (personal)`
                        : (user.group_ids?.length > 0 ? 'Múltiples grupos' : 'Global');
                    
                    return `
                        <tr>
                            <td>${user.id}</td>
                            <td>${user.username}</td>
                            <td>${user.email}</td>
                            <td><span style="background: ${user.role === 'admin' ? '#667eea' : '#28a745'}; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">${user.role}</span></td>
                            <td>
                                <button class="btn-edit" onclick="openUserModal(${user.id})">Editar</button>
                                <button class="btn-delete" onclick="showDeleteModal('user', ${user.id}, '${user.username}')">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            })
            .catch(err => showAlert('Error al cargar usuarios: ' + err.message, 'error'));
    }

    document.getElementById('userForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData();
        const isCreating = !document.getElementById('userId').value;
        
        formData.append('username', document.getElementById('username').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('role', document.getElementById('role').value);
        
        const password = document.getElementById('userPassword').value;
        
        if (isCreating) {
            if (!password) {
                formData.append('generate_password', '1');
            } else {
                formData.append('password', password);
            }
        } else {
            if (password) {
                formData.append('password', password);
            }
        }
        
        const quota = document.getElementById('userQuota').value;
        if (quota) {
            formData.append('quota_mb', quota);
        }

        const endpoint = document.getElementById('userId').value ? '/admin/users/update' : '/admin/users/create';
        if (document.getElementById('userId').value) {
            formData.append('user_id', document.getElementById('userId').value);
        }

        fetch(endpoint, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('✅ Usuario guardado', 'success');
                    closeModal('userModal');
                    loadUsers();
                } else {
                    showAlert('❌ ' + (data.error || 'Error desconocido'), 'error');
                }
            })
            .catch(err => showAlert('Error: ' + err.message, 'error'));
    });

    // ========== GRUPOS ==========

    function openGroupModal(groupId) {
        if (groupId === null) {
            document.getElementById('groupModalTitle').textContent = 'Crear Grupo';
            document.getElementById('groupForm').reset();
            document.getElementById('groupId').value = '';
            document.getElementById('groupQuota').value = '100';
            openModal('groupModal');
        } else {
            fetch(`/admin/groups/${groupId}`)
                .then(res => res.json())
                .then(response => {
                    const group = response.data || response;
                    document.getElementById('groupModalTitle').textContent = 'Editar Grupo';
                    document.getElementById('groupId').value = group.id;
                    document.getElementById('groupName').value = group.name;
                    document.getElementById('groupDescription').value = group.description || '';
                    document.getElementById('groupQuota').value = group.quota_mb || 100;
                    openModal('groupModal');
                })
                .catch(err => showAlert('Error al cargar grupo: ' + err.message, 'error'));
        }
    }

    function loadGroups() {
        fetch('/admin/groups')
            .then(res => res.json())
            .then(response => {
                const groups = response.data || response;
                const tbody = document.getElementById('groupsBody');
                document.getElementById('totalGroups').textContent = Array.isArray(groups) ? groups.length : 0;
                
                if (!Array.isArray(groups) || groups.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><div class="empty-state-icon">📁</div><p>No hay grupos creados</p></td></tr>';
                    return;
                }
                
                tbody.innerHTML = groups.map(group => {
                    const quotaText = group.quota_mb > 0 ? `${group.quota_mb} MB` : '0 MB';
                    const quotaBadge = group.quota_mb > 0 
                        ? `<span style="background: #c8e6c9; color: #2e7d32; padding: 3px 8px; border-radius: 3px; font-size: 11px;">${quotaText}</span>`
                        : `<span style="background: #ffe0b2; color: #e65100; padding: 3px 8px; border-radius: 3px; font-size: 11px;">${quotaText}</span>`;
                    
                    return `
                        <tr>
                            <td>${group.id}</td>
                            <td>${group.name}</td>
                            <td>${group.description || 'N/A'}</td>
                            <td>${quotaBadge}</td>
                            <td>
                                <button class="btn-edit" onclick="openGroupModal(${group.id})">Editar</button>
                                <button class="btn-delete" onclick="showDeleteModal('group', ${group.id}, '${group.name}')">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            })
            .catch(err => showAlert('Error al cargar grupos: ' + err.message, 'error'));
    }

    document.getElementById('groupForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('name', document.getElementById('groupName').value);
        formData.append('description', document.getElementById('groupDescription').value);
        formData.append('quota_mb', document.getElementById('groupQuota').value || 0);

        const endpoint = document.getElementById('groupId').value ? '/admin/groups/update' : '/admin/groups/create';
        if (document.getElementById('groupId').value) {
            formData.append('group_id', document.getElementById('groupId').value);
        }

        fetch(endpoint, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('✅ Grupo guardado', 'success');
                    closeModal('groupModal');
                    loadGroups();
                } else {
                    showAlert('❌ ' + (data.error || 'Error'), 'error');
                }
            })
            .catch(err => showAlert('Error: ' + err.message, 'error'));
    });

    // ========== CONFIGURACIÓN GLOBAL ==========

    function loadGlobalSettings() {
        fetch('/admin/settings')
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(response => {
                if (!response.data) {
                    showAlert('❌ Formato de respuesta inválido', 'error');
                    return;
                }
                const settings = response.data || {};
                document.getElementById('globalQuota').value = settings.global_quota_mb || 100;
            })
            .catch(err => {
                console.error('Error cargando configuración:', err);
                showAlert('Error: ' + err.message, 'error');
            });
    }

    function saveGlobalSettings() {
        const quotaValue = document.getElementById('globalQuota').value;
        
        if (!quotaValue || quotaValue <= 0) {
            showAlert('❌ La cuota debe ser mayor a 0', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('global_quota_mb', quotaValue);

        fetch('/admin/settings/update', { 
            method: 'POST', 
            body: formData 
        })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showAlert('✅ Configuración guardada', 'success');
                    loadGlobalSettings();
                } else {
                    showAlert('❌ ' + (data.error || 'Error desconocido'), 'error');
                }
            })
            .catch(err => {
                console.error('Error guardando configuración:', err);
                showAlert('❌ Error: ' + err.message, 'error');
            });
    }

    // ========== ASIGNACIONES ==========

    function loadAssignments() {
        fetch('/admin/assignments')
            .then(r => r.json())
            .then(response => {
                const assignmentsData = response.data || response;
                const users = assignmentsData.users || [];
                const groups = assignmentsData.groups || [];
                
                renderUsersList(users);
                if (!currentUser) {
                    document.getElementById('groupsList').innerHTML = '<p style="text-align: center; color: #999;">Selecciona un usuario primero</p>';
                }
            })
            .catch(err => showAlert('Error al cargar asignaciones: ' + err.message, 'error'));
    }

    function renderUsersList(users) {
        const container = document.getElementById('usersList');
        
        if (!Array.isArray(users) || users.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #999;">No hay usuarios</p>';
            return;
        }

        container.innerHTML = users.map(user => {
            const groupCount = user.group_ids?.length || 0;
            const groupBadge = groupCount > 0 
                ? `<span style="background: #e3f2fd; color: #1565c0; padding: 2px 6px; border-radius: 3px; font-size: 11px;">${groupCount} grupo(s)</span>`
                : '';
            
            return `
                <div class="user-item ${currentUser?.id === user.id ? 'selected' : ''}" 
                    onclick="selectUser(${user.id})" 
                    style="
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    margin-bottom: 8px;
                    cursor: pointer;
                    background: ${currentUser?.id === user.id ? '#e8f1ff' : '#fff'};
                    transition: all 0.2s ease;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${user.username}</strong>
                            <p style="font-size: 12px; color: #666; margin: 5px 0 0 0;">${user.email}</p>
                        </div>
                        ${groupBadge}
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderGroupsList(groups) {
        const container = document.getElementById('groupsList');
        
        if (!currentUser) {
            container.innerHTML = '<p style="text-align: center; color: #999;">Selecciona un usuario primero</p>';
            return;
        }

        if (!Array.isArray(groups) || groups.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #999;">No hay grupos disponibles</p>';
            return;
        }

        container.innerHTML = groups.map(group => {
            const isAssigned = currentUser.group_ids && currentUser.group_ids.includes(group.id);
            const quotaInfo = group.quota_mb > 0 ? `${group.quota_mb} MB` : 'Heredada';
            
            return `
                <div style="
                    background: ${isAssigned ? '#c8e6c9' : '#fff'};
                    border: 1px solid ${isAssigned ? '#4caf50' : '#ddd'};
                    border-radius: 5px;
                    padding: 12px;
                    margin-bottom: 10px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <div>
                        <strong>${group.name}</strong>
                        <p style="font-size: 12px; color: #666; margin: 5px 0 0 0;">
                            ${group.description || ''}<br>
                            <span style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; display: inline-block; margin-top: 3px;">
                                Cuota: ${quotaInfo}
                            </span>
                        </p>
                    </div>
                    <button 
                        class="btn-${isAssigned ? 'delete' : 'edit'}"
                        onclick="toggleUserGroup(${currentUser.id}, ${group.id}, ${isAssigned})">
                        ${isAssigned ? '✕ Remover' : '✓ Asignar'}
                    </button>
                </div>
            `;
        }).join('');
    }

    function selectUser(userId) {
        fetch('/admin/assignments')
            .then(r => r.json())
            .then(response => {
                const assignmentsData = response.data || response;
                const users = assignmentsData.users || [];
                const groups = assignmentsData.groups || [];
                
                currentUser = users.find(u => u.id === userId);
                
                if (!currentUser) {
                    showAlert('❌ Usuario no encontrado', 'error');
                    return;
                }

                renderUsersList(users);
                renderGroupsList(groups);
            })
            .catch(err => showAlert('Error: ' + err.message, 'error'));
    }

    function toggleUserGroup(userId, groupId, isAssigned) {
        const endpoint = isAssigned ? '/admin/remove-user-from-group' : '/admin/assign-user-to-group';
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('group_id', groupId);

        fetch(endpoint, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('✅ ' + data.message, 'success');
                    selectUser(userId);
                } else {
                    showAlert('❌ ' + (data.error || 'Error'), 'error');
                }
            })
            .catch(err => showAlert('Error: ' + err.message, 'error'));
    }

    // ========== EXTENSIONES ==========

    function openExtensionModal(extensionId) {
        if (extensionId === null) {
            document.getElementById('extensionModalTitle').textContent = 'Añadir Extensión Prohibida';
            document.getElementById('extensionForm').reset();
            document.getElementById('extensionId').value = '';
            openModal('extensionModal');
        } else {
            fetch(`/admin/extensions/${extensionId}`)
                .then(res => res.json())
                .then(response => {
                    const ext = response.data || response;
                    document.getElementById('extensionModalTitle').textContent = 'Editar Extensión';
                    document.getElementById('extensionId').value = ext.id;
                    document.getElementById('extension').value = ext.extension;
                    document.getElementById('extDescription').value = ext.description || '';
                    openModal('extensionModal');
                })
                .catch(err => showAlert('Error: ' + err.message, 'error'));
        }
    }

    function loadExtensions() {
        fetch('/admin/extensions')
            .then(res => res.json())
            .then(response => {
                const extensions = response.data || response;
                const tbody = document.getElementById('extensionsBody');
                
                if (!Array.isArray(extensions) || extensions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="empty-state"><div class="empty-state-icon">🚫</div><p>No hay extensiones prohibidas</p></td></tr>';
                    return;
                }
                tbody.innerHTML = extensions.map(ext => `
                    <tr>
                        <td>${ext.id}</td>
                        <td><code style="background: #f5f5f5; padding: 3px 6px; border-radius: 3px;">.${ext.extension}</code></td>
                        <td>${ext.description || 'N/A'}</td>
                        <td>
                            <button class="btn-edit" onclick="openExtensionModal(${ext.id})">Editar</button>
                            <button class="btn-delete" onclick="showDeleteModal('extension', ${ext.id}, '.${ext.extension}')">Eliminar</button>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(err => showAlert('Error: ' + err.message, 'error'));
    }

    document.getElementById('extensionForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('extension', document.getElementById('extension').value);
        formData.append('description', document.getElementById('extDescription').value);

        const endpoint = document.getElementById('extensionId').value ? '/admin/extensions/update' : '/admin/extensions/add';
        if (document.getElementById('extensionId').value) {
            formData.append('extension_id', document.getElementById('extensionId').value);
        }

        fetch(endpoint, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('✅ Extensión guardada', 'success');
                    closeModal('extensionModal');
                    loadExtensions();
                } else {
                    showAlert('❌ ' + (data.error || 'Error'), 'error');
                }
            })
            .catch(err => showAlert('Error: ' + err.message, 'error'));
    });

    // ========== ELIMINACIÓN ==========

    function showDeleteModal(type, id, name) {
        currentDeleteAction = { type, id, name };
        document.getElementById('deleteMessage').textContent = `¿Estás seguro de que deseas eliminar "${name}"? Esta acción no se puede deshacer.`;
        openModal('deleteModal');
    }

    function confirmDelete() {
        if (!currentDeleteAction) return;

        const { type, id } = currentDeleteAction;
        const endpoint = `/admin/${type}s/delete`;
        const formData = new FormData();
        formData.append(`${type}_id`, id);

        fetch(endpoint, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('✅ Eliminado correctamente', 'success');
                    closeModal('deleteModal');
                    if (type === 'user') loadUsers();
                    if (type === 'group') loadGroups();
                    if (type === 'extension') loadExtensions();
                } else {
                    showAlert('❌ ' + (data.error || 'Error'), 'error');
                }
            })
            .catch(err => showAlert('Error: ' + err.message, 'error'));
    }

    // ========== UTILIDADES ==========

    function showAlert(message, type) {
        const container = document.getElementById('alertContainer');
        if (!container) return;
        const alert = document.createElement('div');
        alert.className = `alert-custom alert-${type} show`;
        alert.textContent = message;
        container.appendChild(alert);
        setTimeout(() => alert.remove(), 4000);
    }

    function logout() {
        window.location.href = '/auth/logout';
    }

    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        loadUsers();
        loadGroups();
    });
</script>

</body>
</html>
