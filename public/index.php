<?php

use Phalcon\Loader;
use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Dispatcher;

// Iniciar sesión PHP nativa
session_start();

// Configuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar config
$config = include dirname(__DIR__) . '/config/config.php';

// Registrar loader
$loader = new Loader();
$loader->registerNamespaces([
    'App\Controllers' => $config->application->controllersDir,
    'App\Models'      => $config->application->modelsDir,
    'App\Services'    => $config->application->servicesDir,
    'App\Helpers'     => $config->application->appDir . '/Helpers',
]);
$loader->register();

// ⭐ CREAR CONTENEDOR DE INYECCIÓN
$di = new FactoryDefault();

// ⭐ REGISTRAR CONFIG EN CONTENEDOR
$di->setShared('config', $config);

// ⭐ REGISTRAR DATABASE
$di->setShared('db', function () use ($config) {
    $dbConfig = $config->database->toArray();
    $className = 'Phalcon\Db\Adapter\Pdo\\' . $dbConfig['adapter'];

    $connection = new $className([
        'host'     => $dbConfig['host'],
        'port'     => $dbConfig['port'],
        'username' => $dbConfig['username'],
        'password' => $dbConfig['password'],
        'dbname'   => $dbConfig['dbname'],
    ]);

    return $connection;
});

// ⭐ REGISTRAR MODELO MANAGER
$di->setShared('modelsManager', function () {
    return new \Phalcon\Mvc\Model\Manager();
});

// ⭐ REGISTRAR MODELOS METADATA
$di->setShared('modelsMetadata', function () {
    return new \Phalcon\Mvc\Model\MetaData\Memory();
});

// Registrar vista con Volt
$di->setShared('view', function () use ($config) {
    $view = new View();
    $view->setViewsDir($config->application->viewsDir);

    $view->registerEngines([
        '.volt' => function ($view, $di) use ($config) {
            $volt = new Volt($view, $di);
            $volt->setOptions([
                'compiledPath'      => $config->application->cacheDir,
                'compiledSeparator' => '_',
                'compileAlways'     => true,
            ]);
            return $volt;
        }
    ]);

    return $view;
});

// ⭐ REGISTRAR SESIÓN (Phalcon 3.4.5)
$di->setShared('session', function () {
    $session = new \Phalcon\Session\Adapter\Files();
    $session->start();
    return $session;
});

// Registrar dispatcher
$di->setShared('dispatcher', function () {
    $dispatcher = new Dispatcher();
    $dispatcher->setDefaultNamespace('App\Controllers');
    return $dispatcher;
});

// Registrar router
$di->setShared('router', function () {
    $router = new Router(false);
    $router->setDefaultNamespace('App\Controllers');
    $router->setDefaultController('auth');
    $router->setDefaultAction('login');
    $router->removeExtraSlashes(true);

    // ===== RUTAS DE AUTENTICACIÓN =====
    $router->addGet('/auth/login', ['controller' => 'auth', 'action' => 'login']);
    $router->addPost('/auth/processLogin', ['controller' => 'auth', 'action' => 'processLogin']);
    $router->addGet('/auth/logout', ['controller' => 'auth', 'action' => 'logout']);

    // ===== RUTAS DE USUARIO (ARCHIVOS) =====
    $router->addGet('/', ['controller' => 'file', 'action' => 'index']);
    $router->addGet('/file', ['controller' => 'file', 'action' => 'index']);
    $router->addGet('/file/quota', ['controller' => 'file', 'action' => 'quota']);
    $router->addGet('/file/list', ['controller' => 'file', 'action' => 'list']);
    $router->addPost('/file/upload', ['controller' => 'file', 'action' => 'upload']);
    $router->addPost('/file/delete', ['controller' => 'file', 'action' => 'delete']);
    $router->addGet('/file/download/{fileId:\d+}', ['controller' => 'file', 'action' => 'download']);

    // ===== RUTAS DE ADMIN - PANEL PRINCIPAL =====
    $router->addGet('/admin', ['controller' => 'admin', 'action' => 'index']);

    // ===== RUTAS DE ADMIN - USUARIOS =====
    $router->addGet('/admin/users', ['controller' => 'admin', 'action' => 'users']);
    $router->addGet('/admin/users/{userId:\d+}', ['controller' => 'admin', 'action' => 'getUserById']);
    $router->addPost('/admin/users/create', ['controller' => 'admin', 'action' => 'createUser']);
    $router->addPost('/admin/users/update', ['controller' => 'admin', 'action' => 'updateUser']);
    $router->addPost('/admin/users/delete', ['controller' => 'admin', 'action' => 'deleteUser']);

    // ===== RUTAS DE ADMIN - GRUPOS =====
    $router->addGet('/admin/groups', ['controller' => 'admin', 'action' => 'groups']);
    $router->addGet('/admin/groups/{groupId:\d+}', ['controller' => 'admin', 'action' => 'getGroupById']);
    $router->addPost('/admin/groups/create', ['controller' => 'admin', 'action' => 'createGroup']);
    $router->addPost('/admin/groups/update', ['controller' => 'admin', 'action' => 'updateGroup']);
    $router->addPost('/admin/groups/delete', ['controller' => 'admin', 'action' => 'deleteGroup']);

    // ===== RUTAS DE ADMIN - EXTENSIONES =====
    $router->addGet('/admin/extensions', ['controller' => 'admin', 'action' => 'extensions']);
    $router->addGet('/admin/extensions/{extensionId:\d+}', ['controller' => 'admin', 'action' => 'getExtensionById']);
    $router->addPost('/admin/extensions/add', ['controller' => 'admin', 'action' => 'addExtension']);
    $router->addPost('/admin/extensions/update', ['controller' => 'admin', 'action' => 'updateExtension']);
    $router->addPost('/admin/extensions/delete', ['controller' => 'admin', 'action' => 'deleteExtension']);

    // ===== RUTAS DE ADMIN - ASIGNACIONES USUARIO-GRUPO =====
    $router->addGet('/admin/assignments', ['controller' => 'admin', 'action' => 'assignments']);
    $router->addPost('/admin/assign-user-to-group', ['controller' => 'admin', 'action' => 'assignUserToGroup']);
    $router->addPost('/admin/remove-user-from-group', ['controller' => 'admin', 'action' => 'removeUserFromGroup']);

    // ===== RUTAS DE ADMIN - CONFIGURACIÓN =====
    $router->addGet('/admin/settings', ['controller' => 'admin', 'action' => 'settings']);
    $router->addPost('/admin/settings/update', ['controller' => 'admin', 'action' => 'settingsUpdate']);

    // ===== RUTAS DE ADMIN - CUOTA =====
    $router->addGet('/admin/users/{userId:\d+}/quota', ['controller' => 'admin', 'action' => 'userQuotaInfo']);
    $router->addPost('/admin/users/quota/set', ['controller' => 'admin', 'action' => 'setUserQuota']);
    $router->addPost('/admin/users/quota/remove', ['controller' => 'admin', 'action' => 'removeUserQuota']);

    return $router;
});

// Crear aplicación
$application = new Application($di);

try {
    // Procesar URI
    $uri = $_SERVER['REQUEST_URI'];

    // Remover query string
    if (($pos = strpos($uri, '?')) !== false) {
        $uri = substr($uri, 0, $pos);
    }

    // Por defecto /
    if (empty($uri) || $uri === '/') {
        $uri = '/';
    }

    $response = $application->handle($uri);
    $response->send();
} catch (\Exception $e) {
    http_response_code(500);

    // Si es AJAX/API request, devolver JSON
    $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false ||
        strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false ||
        (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST');

    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_PRETTY_PRINT);
    } else {
        echo '<div style="background: #fee; border: 1px solid #f00; padding: 20px; margin: 20px; border-radius: 5px; font-family: monospace; color: #333;">';
        echo '<h2 style="color: #d32f2f; margin: 0 0 10px 0;">❌ Error en la Aplicación</h2>';
        echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<details style="margin-top: 15px;">';
        echo '<summary style="cursor: pointer; color: #0066cc; font-weight: bold;">👇 Ver Trace Completo</summary>';
        echo '<pre style="background: #f5f5f5; padding: 10px; overflow-x: auto; margin-top: 10px; font-size: 11px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</details>';
        echo '</div>';
    }
}
