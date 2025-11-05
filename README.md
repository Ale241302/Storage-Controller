# 📁 Storage Controller - Sistema de Gestión de Archivos Seguro

Sistema web de gestión de archivos con control de cuotas, validación de extensiones y análisis de contenido ZIP. Desarrollado con Phalcon PHP y Vanilla JavaScript.

## 🎯 Características Principales

- ✅ **Autenticación y roles** (Usuario/Administrador)
- ✅ **Gestión de usuarios, grupos y cuotas**
- ✅ **Subida de archivos con validaciones de seguridad**
- ✅ **Análisis de archivos ZIP** antes de aprobar
- ✅ **Control de capacidad por grupo**
- ✅ **Panel administrativo completo**
- ✅ **Interfaz responsive con Bootstrap 5**
- ✅ **Validación de extensiones peligrosas**

---

## 🛠️ Stack Tecnológico

- **Backend**: Phalcon 3.4.5+ (PHP 8.1+)
- **Frontend**: Vanilla JavaScript (ES6+) + Bootstrap 5
- **Base de Datos**: PostgreSQL 12+
- **Servidor**: Apache/Nginx o PHP Built-in Server

---

## 📦 Instalación

### **Requisitos Previos**

- PHP 8.1 o superior
- PostgreSQL 12 o superior
- Composer
- Extensión Phalcon instalada
- Git

### **Paso 1: Clonar el repositorio**

git clone https://github.com/tuusuario/storage-controller.git
cd storage-controller

### **Paso 2: Instalar dependencias**

composer install

### **Paso 3: Configurar variables de entorno**

cp .env.example .env

Editar `.env` con tus credenciales:

Base de datos
DB_HOST=localhost
DB_PORT=5432
DB_NAME=storage_controller
DB_USER=postgres
DB_PASSWORD=su_contraseña

Aplicación
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

Cuota global (en MB)
GLOBAL_QUOTA_MB=300

### **Paso 4: Crear la base de datos**

Conectar a PostgreSQL
psql -U postgres

Crear BD
CREATE DATABASE storage_controller;
\q

### **Paso 5: Ejecutar el setup SQL**

psql -U postgres -d storage_controller -f database/setup.sql

**Salida esperada:**
CREATE TABLE
CREATE TABLE
...
INSERT 0 2
Database setup complete!

### **Paso 6: Crear carpetas necesarias**

Crear carpeta de uploads (raíz del proyecto)
mkdir -p uploads
chmod 755 uploads

Crear carpeta de cache (dentro de app)
mkdir -p app/cache
chmod 755 app/cache

Crear carpeta de logs (raíz del proyecto)
mkdir -p logs
chmod 755 logs

**En Windows (Git Bash/CMD):**

mkdir uploads
mkdir app\cache
mkdir logs

### **Paso 7: Iniciar el servidor**

Opción 1: PHP Built-in Server
php -S localhost:8000 -t public

Opción 2: Apache/Nginx
Configurar el DocumentRoot a /ruta/proyecto/public

Acceder a: [**http://localhost:8000**](http://localhost:8000)

---

## 👤 Credenciales de Prueba

### **Administrador**

- **Usuario**: `Admin`
- **Contraseña**: `Admin213`
- **Email**: `admin@example.com`

### **Usuario Regular**

- **Usuario**: `Pruebas`
- **Contraseña**: `Prueba123`
- **Email**: `user1@example.com`

---

## 🏗️ Decisiones de Diseño

### **1. Arquitectura MVC con Phalcon**

Se eligió Phalcon por su rendimiento (extensión C) y su framework MVC robusto. La estructura separa claramente:

- **Models**: Representan tablas de BD (User, Group, File, etc.)
- **Services**: Lógica de negocio reutilizable (UserService, QuotaService, FileService)
- **Controllers**: Manejo de peticiones HTTP (AdminController, FileController, AuthController)

### **2. Sistema de Cuotas con Prioridades**

Implementé un sistema de cuotas jerárquico:

- **Usuario > Grupo > Global**
- Si un usuario tiene cuota personalizada, se usa esa
- Si pertenece a un grupo, hereda la cuota del grupo
- Si no, usa la cuota global (configurable desde admin)

**Ventaja**: Flexibilidad total sin complejidad excesiva.

### **3. Validación de Archivos en Backend**

Todas las validaciones críticas ocurren en PHP:

- **Extensión**: Comparación contra lista prohibida editable
- **Tamaño**: Validación contra cuota disponible
- **ZIP**: Análisis recursivo con `ZipArchive`

**Ventaja**: Seguridad real (el frontend solo mejora UX).

### **4. Vanilla JavaScript (sin frameworks)**

Se usó ES6+ moderno sin React/Vue porque:

- Menor complejidad
- Menor tamaño de bundle
- Cumple requisitos del proyecto
- `fetch` + DOM manipulation es suficiente

### **5. PostgreSQL como BD**

Elegido por:

- Robustez en transacciones
- Soporte ACID completo
- Índices eficientes
- Triggers y vistas (usadas en el proyecto)

### **6. Bootstrap 5 para UI**

Framework CSS ligero que permite:

- Diseño responsive sin esfuerzo
- Componentes listos (modales, alertas, cards)
- Estética profesional con mínimo CSS custom

---

## 📁 Estructura del Proyecto

storage-controller/
├── app/
│ ├── cache/ # Caché de Phalcon/Volt
│ ├── controllers/ # Controladores (Admin, File, Auth)
│ │ ├── AdminController.php
│ │ ├── FileController.php
│ │ └── AuthController.php
│ ├── models/ # Modelos de BD
│ │ ├── User.php
│ │ ├── Group.php
│ │ ├── File.php
│ │ ├── ForbiddenExtension.php
│ │ └── ...
│ ├── services/ # Lógica de negocio
│ │ ├── UserService.php
│ │ ├── GroupService.php
│ │ ├── FileService.php
│ │ ├── QuotaService.php
│ │ └── UserGroupService.php
│ └── helpers/ # Helpers
│ └── Logger.php
├── config/
│ ├── config.php # Configuración principal
│ ├── routes.php # Rutas de la aplicación
│ └── services.php # Servicios de Phalcon
├── database/
│ └── setup.sql # Setup completo de BD
├── public/
│ ├── index.php # Entry point
│ ├── css/
│ └── js/
├── uploads/ # Archivos subidos (gitignored)
├── views/ # Vistas (Volt templates)
│ ├── admin/
│ │ └── index.volt # Panel admin
│ ├── user/
│ │ └── index.volt # Panel usuario
│ └── auth/
│ └── login.volt # Login
├── .env.example # Ejemplo de configuración
├── .gitignore
├── composer.json
└── README.md

---

## 🔐 Seguridad Implementada

| Medida de Seguridad           | Descripción                                                      |
| ----------------------------- | ---------------------------------------------------------------- |
| **Validación de extensiones** | Lista editable de extensiones prohibidas (.exe, .php, .js, etc.) |
| **Análisis de ZIP**           | Inspección recursiva del contenido antes de aprobar              |
| **Límite de tamaño**          | Máximo 5 MB por archivo (configurable)                           |
| **Control de cuota**          | Validación antes de guardar (usuario/grupo/global)               |
| **Autenticación**             | Sesiones con Phalcon Session                                     |
| **Contraseñas hasheadas**     | bcrypt con `password_hash()`                                     |
| **Escape de HTML**            | Frontend escapa todos los datos del usuario                      |
| **SQL Injection**             | Phalcon ORM con prepared statements                              |
| **CSRF Protection**           | Tokens de sesión (si se habilita)                                |

---

## 🧪 Casos de Uso

### **Caso 1: Usuario sube archivo válido**

1. Usuario accede a `/` (redirige a `/auth/login` si no autenticado)
2. Selecciona archivo `documento.pdf`
3. Frontend valida tamaño disponible
4. Backend valida: extensión (✅), cuota (✅), ZIP (N/A)
5. Archivo se guarda en `uploads/` con nombre único
6. Se actualiza cuota del usuario en tiempo real

### **Caso 2: Usuario intenta subir .exe**

1. Usuario selecciona `malware.exe`
2. Backend valida extensión → ❌ Rechazado
3. Frontend muestra: `❌ Tipo de archivo '.exe' no permitido`

### **Caso 3: Usuario intenta subir .zip con .js dentro**

1. Usuario sube `proyecto.zip` (contiene `script.js`)
2. Backend abre ZIP con `ZipArchive`
3. Detecta `script.js` en lista prohibida
4. ❌ Rechaza ZIP completo
5. Frontend muestra: `❌ Archivos prohibidos en ZIP: script.js`

### **Caso 4: Admin crea grupo con cuota**

1. Admin accede a `/admin`
2. Crea grupo "Marketing" con 100 MB
3. Sistema valida: suma de grupos no debe exceder global (300 MB)
4. Si OK: grupo creado
5. Admin asigna usuarios al grupo

### **Caso 5: Usuario excede cuota**

1. Usuario con cuota 100 MB (80 MB usados)
2. Intenta subir archivo de 30 MB
3. Backend calcula: 80 + 30 = 110 > 100 → ❌ Rechazado
4. Frontend muestra: `❌ Cuota insuficiente. Disponible: 20MB`

---

## 📊 Base de Datos

### **Diagrama ER (Simplificado)**

users ──┬── user_groups ──── groups ──── group_quotas
│
└── files

quota_settings (tabla global)
forbidden_extensions (tabla global)

### **Tablas Principales**

| Tabla                  | Descripción                              |
| ---------------------- | ---------------------------------------- |
| `users`                | Usuarios del sistema                     |
| `groups`               | Grupos de usuarios                       |
| `user_groups`          | Relación N:N usuarios-grupos             |
| `group_quotas`         | Cuota asignada por grupo                 |
| `files`                | Metadatos de archivos subidos            |
| `forbidden_extensions` | Extensiones prohibidas                   |
| `quota_settings`       | Configuración global (cuota por defecto) |

---

## 🐛 Debugging

### **Ver logs de PHP**

tail -f logs/error.log

### **Ver logs de PostgreSQL**

Linux
tail -f /var/log/postgresql/postgresql-\*.log

macOS (Homebrew)
tail -f /usr/local/var/log/postgres.log

### **Test de upload con cURL**

curl -X POST http://localhost:8000/file/upload
-F "file=@test.txt"
-b "PHPSESSID=tu_session_id"

### **Reiniciar base de datos**

psql -U postgres -c "DROP DATABASE storage_controller;"
psql -U postgres -c "CREATE DATABASE storage_controller;"
psql -U postgres -d storage_controller -f database/setup.sql

---

## 🚀 Despliegue en Producción

### **1. Configurar servidor web**

**Apache (`/etc/apache2/sites-available/storage-controller.conf`):**

<VirtualHost \*:80>
ServerName storage.example.com
DocumentRoot /var/www/storage-controller/public

<Directory /var/www/storage-controller/public>
Options -Indexes +FollowSymLinks
AllowOverride All
Require all granted
</Directory>

ErrorLog ${APACHE_LOG_DIR}/storage-error.log
CustomLog ${APACHE_LOG_DIR}/storage-access.log combined
</VirtualHost> ```
Nginx (/etc/nginx/sites-available/storage-controller):

server {
listen 80;
server_name storage.example.com;
root /var/www/storage-controller/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }

} 2. Configurar permisos

sudo chown -R www-data:www-data /var/www/storage-controller
sudo chmod -R 755 /var/www/storage-controller
sudo chmod -R 775 uploads 3. Cambiar .env a producción

APP_ENV=production
APP_DEBUG=false
📄 Licencia
MIT License - Ver archivo LICENSE para detalles.

👨‍💻 Autor
[Tu Nombre]
Email: tu@email.com
GitHub: @tuusuario

❓ Soporte
Para reportar bugs o sugerencias, abre un issue en:
https://github.com/tuusuario/storage-controller/issues

🙏 Agradecimientos
Desarrollado como parte de una prueba técnica para demostrar:

Arquitectura limpia con Phalcon

Seguridad en gestión de archivos

Vanilla JavaScript moderno

Diseño de base de datos eficiente

---

## **🔧 Archivos Adicionales**

### **.env**

```env
# Base de datos
DB_HOST=localhost
DB_PORT=5432
DB_NAME=storage_controller
DB_USER=postgres
DB_PASSWORD=su_contraseña

# Aplicación
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Cuota global (en MB)
GLOBAL_QUOTA_MB=300

# Logs
LOG_PATH=logs/error.log
LOG_LEVEL=debug
.gitignore

# Dependencias
vendor/
node_modules/

# Configuración
.env

# Archivos subidos
uploads/*
!uploads/.gitkeep

# Logs
logs/*
!logs/.gitkeep

# IDE
.vscode/
.idea/
*.swp
*.swo
*~

# Sistema
.DS_Store
Thumbs.db

# Caché
cache/*
!cache/.gitkeep

# Temp
tmp/*
!tmp/.gitkeep

```
