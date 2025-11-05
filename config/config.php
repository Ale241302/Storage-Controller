<?php
// Configuración centralizada de Storage Controller

// Cargar variables de entorno
if (file_exists(dirname(__DIR__) . '/.env')) {
    $envFile = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envFile as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

return new \Phalcon\Config([
    // Configuración de base de datos PostgreSQL
    'database' => [
        'adapter'  => 'Postgresql',
        'host'     => getenv('DB_HOST') ?: 'localhost',
        'port'     => (int)(getenv('DB_PORT') ?: 5432),
        'username' => getenv('DB_USER') ?: 'postgres',
        'password' => getenv('DB_PASS') ?: '241302',
        'dbname'   => getenv('DB_NAME') ?: 'storage_controller',
        'charset'  => 'utf8',
        'options'  => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false
        ]
    ],

    // Directorios de la aplicación
    'application' => [
        'baseDir'        => dirname(__DIR__),
        'appDir'         => dirname(__DIR__) . '/app',
        'modelsDir'      => dirname(__DIR__) . '/app/models/',
        'controllersDir' => dirname(__DIR__) . '/app/controllers/',
        'viewsDir'       => dirname(__DIR__) . '/app/views/',
        'pluginsDir'     => dirname(__DIR__) . '/app/plugins/',
        'libraryDir'     => dirname(__DIR__) . '/app/library/',
        'cacheDir'       => dirname(__DIR__) . '/app/cache/',
        'servicesDir'    => dirname(__DIR__) . '/app/services/',
    ],

    // Configuración de seguridad y almacenamiento
    'security' => [
        'uploadDir'      => dirname(__DIR__) . '/storage/uploads',
        'maxUploadSize'  => 50 * 1024 * 1024,  // 50 MB máximo
        'allowedMimes'   => [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ]
    ],

    // Configuración de aplicación
    'app' => [
        'name'    => getenv('APP_NAME') ?: 'Storage Controller',
        'debug'   => (bool)(getenv('APP_DEBUG') ?: true),
        'env'     => getenv('APP_ENV') ?: 'development',
        'url'     => getenv('APP_URL') ?: 'http://localhost:8000',
        'timezone' => 'America/Bogota'
    ],

    // Configuración de sesión
    'session' => [
        'timeout' => 3600,  // 1 hora
        'cookieName' => 'STORAGE_CTRL',
        'path' => '/storage-controller/'
    ]
]);
