<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{
    public function beforeExecuteRoute(\Phalcon\Mvc\Dispatcher $dispatcher)
    {
        // Rutas que NO requieren autenticación
        $publicRoutes = [
            'auth' => ['login', 'processLogin', 'logout']
        ];

        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        // Si es ruta pública, permitir
        if (isset($publicRoutes[$controller]) && in_array($action, $publicRoutes[$controller])) {
            return true;
        }

        // Si no está autenticado, bloquear
        if (!$this->session->has('user_id')) {
            if (strpos($this->request->getHeader('Accept'), 'application/json') !== false) {
                $this->response->setStatusCode(401);
                $this->response->setJsonContent([
                    'success' => false,
                    'error' => 'No autenticado'
                ]);
                $this->response->send();
            } else {
                $this->response->redirect('/auth/login');
            }
            return false;
        }

        return true;
    }
}
