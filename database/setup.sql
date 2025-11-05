-- ========================================
-- STORAGE CONTROLLER - DATABASE SETUP
-- PostgreSQL 12+
-- ========================================
-- ========================================
-- TABLA: users
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('user', 'admin')),
    quota_mb INTEGER DEFAULT NULL,  -- NULL = usar global, si tiene valor = personalizada
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);

COMMENT ON TABLE users IS 'Usuarios del sistema';
COMMENT ON COLUMN users.quota_mb IS 'Cuota personalizada del usuario (NULL = usar global/grupo)';

-- ========================================
-- TABLA: groups
-- ========================================
CREATE TABLE IF NOT EXISTS groups (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE groups IS 'Grupos de usuarios (ej: Marketing, Desarrolladores)';

-- ========================================
-- TABLA: user_groups
-- ========================================
CREATE TABLE IF NOT EXISTS user_groups (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, group_id)
);

-- Índices
CREATE INDEX idx_user_groups_user_id ON user_groups(user_id);
CREATE INDEX idx_user_groups_group_id ON user_groups(group_id);

COMMENT ON TABLE user_groups IS 'Relación muchos a muchos: usuarios y grupos';

-- ========================================
-- TABLA: group_quotas
-- ========================================
CREATE TABLE IF NOT EXISTS group_quotas (
    id SERIAL PRIMARY KEY,
    group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    quota_mb INTEGER NOT NULL DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(group_id)
);

COMMENT ON TABLE group_quotas IS 'Cuota de almacenamiento por grupo';

-- ========================================
-- ========================================
-- TABLA: quota_settings
-- ========================================
CREATE TABLE IF NOT EXISTS quota_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE quota_settings IS 'Configuración global del sistema (cuota por defecto, etc)';

-- ========================================
-- DATOS INICIALES
-- ========================================

-- Configuración global de cuota (300 MB por defecto)
INSERT INTO quota_settings (setting_key, setting_value, description)
VALUES ('default_quota_mb', '300', 'Cuota por defecto en MB para nuevos usuarios')
ON CONFLICT (setting_key) DO UPDATE SET setting_value = '300';

-- ========================================
-- TABLA: forbidden_extensions
-- ========================================
CREATE TABLE IF NOT EXISTS forbidden_extensions (
    id SERIAL PRIMARY KEY,
    extension VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE forbidden_extensions IS 'Extensiones de archivo prohibidas (seguridad)';

-- ========================================
-- TABLA: files
-- ========================================
CREATE TABLE IF NOT EXISTS files (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices
CREATE INDEX idx_files_user_id ON files(user_id);
CREATE INDEX idx_files_created_at ON files(created_at DESC);

COMMENT ON TABLE files IS 'Metadatos de archivos subidos por usuarios';
COMMENT ON COLUMN files.file_size IS 'Tamaño en bytes';

-- ========================================
-- DATOS INICIALES
-- ========================================

-- Configuración global de cuota (200 MB por defecto)
INSERT INTO quota_settings (setting_key, setting_value, description)
VALUES ('default_quota_mb', '200', 'Cuota por defecto en MB para nuevos usuarios')
ON CONFLICT (setting_key) DO NOTHING;

-- Extensiones prohibidas
INSERT INTO forbidden_extensions (extension, description) VALUES
    ('exe', 'Ejecutable Windows'),
    ('bat', 'Script batch Windows'),
    ('cmd', 'Comando Windows'),
    ('com', 'Ejecutable MS-DOS'),
    ('js', 'JavaScript (riesgo XSS)'),
    ('php', 'Script PHP'),
    ('php3', 'Script PHP'),
    ('php4', 'Script PHP'),
    ('php5', 'Script PHP'),
    ('phtml', 'Script PHP'),
    ('sh', 'Shell script'),
    ('bash', 'Bash script'),
    ('zsh', 'Zsh script'),
    ('py', 'Script Python'),
    ('jar', 'Ejecutable Java'),
    ('vbs', 'Visual Basic Script'),
    ('ps1', 'PowerShell script'),
    ('app', 'Aplicación macOS'),
    ('deb', 'Paquete Debian'),
    ('rpm', 'Paquete RPM')
ON CONFLICT (extension) DO NOTHING;

-- Usuario administrador
-- Contraseña: Admin213 (bcrypt hash)
INSERT INTO users (username, email, password, role, quota_mb)
VALUES (
    'Admin',
    'admin@example.com',
    '$2y$10$pgoDRCuckKW9zFBGZtqq3ejCah.TVSzj7RUczN/UTvv5LzLdJ8/R2',
    'admin',
    100
)
ON CONFLICT (username) DO NOTHING;

-- Usuario regular de prueba
-- Contraseña: Prueba123 (bcrypt hash)
INSERT INTO users (username, email, password, role, quota_mb)
VALUES (
    'Pruebas',
    'user1@example.com',
    '$2y$10$CF3E5uNSueZXCXDJm3aL9eKzg8LNaN6ekONUEzM.Alwy0bGlbY4a6',
    'user',
    100
)
ON CONFLICT (username) DO NOTHING;

-- Grupos de ejemplo
INSERT INTO groups (name, description) VALUES
    ('Marketing', 'Equipo de marketing y diseño'),
    ('Desarrolladores', 'Equipo de desarrollo de software'),
    ('Ventas', 'Equipo comercial')
ON CONFLICT (name) DO NOTHING;

-- Cuotas de grupos
INSERT INTO group_quotas (group_id, quota_mb)
SELECT id, 100 FROM groups WHERE name = 'Marketing'
ON CONFLICT (group_id) DO NOTHING;

INSERT INTO group_quotas (group_id, quota_mb)
SELECT id, 100 FROM groups WHERE name = 'Desarrolladores'
ON CONFLICT (group_id) DO NOTHING;

INSERT INTO group_quotas (group_id, quota_mb)
SELECT id, 0 FROM groups WHERE name = 'Ventas'
ON CONFLICT (group_id) DO NOTHING;

-- ========================================
-- FUNCIONES AUXILIARES
-- ========================================

-- Función para actualizar updated_at automáticamente
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Triggers para updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_groups_updated_at BEFORE UPDATE ON groups
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_group_quotas_updated_at BEFORE UPDATE ON group_quotas
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_quota_settings_updated_at BEFORE UPDATE ON quota_settings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_forbidden_extensions_updated_at BEFORE UPDATE ON forbidden_extensions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_files_updated_at BEFORE UPDATE ON files
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ========================================
-- VISTAS ÚTILES
-- ========================================

-- Vista: usuarios con información de cuota efectiva
CREATE OR REPLACE VIEW v_users_with_quota AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.role,
    COALESCE(u.quota_mb, 
             (SELECT setting_value::INTEGER FROM quota_settings WHERE setting_key = 'default_quota_mb'),
             100) AS effective_quota_mb,
    u.quota_mb AS personal_quota_mb,
    u.created_at,
    (SELECT COALESCE(SUM(f.file_size), 0) / 1024 / 1024 FROM files f WHERE f.user_id = u.id) AS used_mb
FROM users u;

COMMENT ON VIEW v_users_with_quota IS 'Vista con cuota efectiva y uso actual por usuario';

-- Vista: grupos con capacidad
CREATE OR REPLACE VIEW v_groups_with_capacity AS
SELECT 
    g.id,
    g.name,
    g.description,
    COALESCE(gq.quota_mb, 0) AS total_capacity_mb,
    COALESCE(
        (SELECT SUM(COALESCE(u.quota_mb, 100))
         FROM user_groups ug
         JOIN users u ON u.id = ug.user_id
         WHERE ug.group_id = g.id),
        0
    ) AS used_capacity_mb,
    COALESCE(gq.quota_mb, 0) - COALESCE(
        (SELECT SUM(COALESCE(u.quota_mb, 100))
         FROM user_groups ug
         JOIN users u ON u.id = ug.user_id
         WHERE ug.group_id = g.id),
        0
    ) AS available_capacity_mb
FROM groups g
LEFT JOIN group_quotas gq ON gq.group_id = g.id;

COMMENT ON VIEW v_groups_with_capacity IS 'Vista con capacidad total, usada y disponible por grupo';

-- ========================================
-- PERMISOS
-- ========================================

-- Dar permisos al usuario postgres
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO postgres;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO postgres;

-- ========================================
-- FIN
-- ========================================

-- Verificar estructura
SELECT 
    schemaname,
    tablename,
    tableowner
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY tablename;

-- Mostrar resumen
SELECT 'Database setup complete!' AS status;
SELECT COUNT(*) AS total_users FROM users;
SELECT COUNT(*) AS total_groups FROM groups;
SELECT COUNT(*) AS total_forbidden_extensions FROM forbidden_extensions;
