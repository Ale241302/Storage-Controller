<?php

namespace App\Controllers;

use App\Models\User;
use App\Helpers\Logger;

class AuthController extends ControllerBase
{
    /**
     * No aplica protección a auth
     */
    public function beforeExecuteRoute(\Phalcon\Mvc\Dispatcher $dispatcher)
    {
        // No llamar a parent::beforeExecuteRoute para evitar redireccionamiento
        return true;
    }

    /**
     * Mostrar formulario de login
     * GET /auth/login
     */
    public function loginAction()
    {
        // Si ya está autenticado, redirigir
        if ($this->session->has('user_id')) {
            $role = $this->session->get('role');
            if ($role === 'admin') {
                return $this->response->redirect('/admin');
            }
            return $this->response->redirect('/');
        }

        // Renderizar vista de login
        $this->view->render('auth', 'login');
    }

    /**
     * Procesar login
     * POST /auth/processLogin
     */
    public function processLoginAction()
    {
        $this->view->disable();

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'success' => false,
                'error' => 'Método no permitido'
            ]);
        }

        try {
            // Obtener datos del request
            $data = $this->request->getJsonRawBody(true);

            if (!$data) {
                $username = $this->request->getPost('username');
                $password = $this->request->getPost('password');
            } else {
                $username = $data['username'] ?? null;
                $password = $data['password'] ?? null;
            }

            if (empty($username) || empty($password)) {
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => 'Usuario y contraseña requeridos'
                ]);
            }

            Logger::info("Intento de login: $username");

            // Buscar usuario en BD
            $user = User::findFirst([
                'username = :user:',
                'bind' => ['user' => $username]
            ]);

            if (!$user) {
                Logger::warn("Usuario no encontrado: $username");
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => 'Usuario o contraseña incorrectos'
                ]);
            }

            // Verificar contraseña
            if (!password_verify($password, $user->password)) {
                Logger::warn("Contraseña incorrecta para: $username");
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => 'Usuario o contraseña incorrectos'
                ]);
            }

            // ⭐ Crear sesión
            $this->session->set('user_id', $user->id);
            $this->session->set('username', $user->username);
            $this->session->set('email', $user->email);
            $this->session->set('role', $user->role);

            Logger::info("Login exitoso: $username (ID: {$user->id})");

            // ⭐ Devolver redirect en JSON
            return $this->response->setJsonContent([
                'success' => true,
                'message' => 'Sesión iniciada correctamente',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role
                ],
                'redirect' => $user->role === 'admin' ? '/admin' : '/'  // ⭐ Frontend maneja el redirect
            ]);
        } catch (\Exception $e) {
            Logger::error('processLoginAction error: ' . $e->getMessage());
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => 'Error en el servidor: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cerrar sesión
     * GET /auth/logout
     */
    public function logoutAction()
    {
        $this->view->disable();

        Logger::info('Usuario cerró sesión');

        // Destruir sesión
        $this->session->destroy();

        // Verificar si es AJAX
        if (strpos($this->request->getHeader('Accept'), 'application/json') !== false) {
            return $this->response->setJsonContent([
                'success' => true,
                'message' => 'Sesión cerrada correctamente'
            ]);
        } else {
            return $this->response->redirect('/auth/login');
        }
    }
}
