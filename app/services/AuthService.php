<?php

namespace App\Services;

class AuthService
{

    protected $db;

    public function setDb($db)
    {
        $this->db = $db;
    }

    // Login con consulta segura
    public function login($username, $password)
    {
        try {
            // Usar PDO prepare + execute (forma correcta)
            $stmt = $this->db->prepare(
                "SELECT id, username, email, password, role FROM users WHERE username = ?"
            );

            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }

            // Verificar contraseña
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Contraseña incorrecta'];
            }

            // Crear sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            return [
                'success' => true,
                'user_id' => $user['id'],
                'role' => $user['role']
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Error BD: ' . $e->getMessage()];
        }
    }

    // Logout
    public function logout()
    {
        session_destroy();
        return ['success' => true];
    }

    // Verificar autenticación
    public function isAuthenticated()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    // Obtener usuario actual
    public function getAuthenticatedUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT id, username, email, role FROM users WHERE id = ?"
            );
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return null;
        }
    }

    // Verificar rol
    public function hasRole($requiredRole)
    {
        return $this->isAuthenticated() &&
            isset($_SESSION['role']) &&
            $_SESSION['role'] === $requiredRole;
    }
}
