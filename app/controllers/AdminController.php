<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Services\UserService;
use App\Services\GroupService;
use App\Services\ExtensionService;
use App\Services\UserGroupService;
use App\Helpers\Logger;
use App\Services\QuotaService;

class AdminController extends Controller
{
    /**
     * Panel principal - Renderiza la vista
     */
    public function indexAction()
    {
        // Validar sesión
        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::info('Intento de acceso no autorizado a /admin');
            return $this->response->redirect('/auth/login');
        }

        Logger::info('Admin ' . $this->session->get('username') . ' accedió al panel');

        $this->view->setVar('username', $this->session->get('username'));
        $this->view->setVar('userId', $this->session->get('user_id'));
        $this->view->pick('admin/index');
    }

    // ===== USUARIOS =====

    public function usersAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a usersAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        try {
            Logger::info('Cargando usuarios...');
            $users = UserService::getAllUsers();
            Logger::info('Se cargaron ' . count($users) . ' usuarios');

            return $this->response->setJsonContent([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            Logger::error('usersAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function createUserAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a createUserAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $username = $this->request->getPost('username');
            $email = $this->request->getPost('email');
            $password = $this->request->getPost('password', null);  // ⭐ Puede ser null
            $role = $this->request->getPost('role', 'string', 'user');
            $quotaMb = $this->request->getPost('quota_mb', 'int', null);

            if (empty($username) || empty($email)) {
                return $this->response->setJsonContent(['success' => false, 'error' => 'Usuario y email son requeridos']);
            }

            Logger::info("Creando usuario: $username ($email)" . ($quotaMb ? " - Cuota: $quotaMb MB" : ""));
            $result = UserService::createUser($username, $email, $password, $role, $quotaMb);

            if ($result['success']) {
                Logger::info("Usuario creado exitosamente: $username");
                // ⭐ Si se generó contraseña aleatoria, guardarla en log
                if (!empty($result['password']) && $result['password'] !== $password) {
                    Logger::info("Contraseña generada automáticamente para $username: {$result['password']}");
                }
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('createUserAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    public function updateUserAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a updateUserAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $userId = $this->request->getPost('user_id');
            $username = $this->request->getPost('username');
            $email = $this->request->getPost('email');
            $role = $this->request->getPost('role', 'string', 'user');
            $password = $this->request->getPost('password');
            $quotaMb = $this->request->getPost('quota_mb');  // ⭐ OBTENER CUOTA

            if (empty($username) || empty($email)) {
                return $this->response->setJsonContent(['success' => false, 'error' => 'Usuario y email son requeridos']);
            }

            // ⭐ Convertir cuota a int si se proporciona
            $quotaMb = !empty($quotaMb) ? (int)$quotaMb : null;

            Logger::info("Actualizando usuario ID: $userId - Cuota: " . ($quotaMb ? "$quotaMb MB" : "Global"));
            $result = UserService::updateUser($userId, $username, $email, $role, $password, $quotaMb);  // ⭐ PASAR CUOTA

            if ($result['success']) {
                Logger::info("Usuario actualizado exitosamente: $username");
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('updateUserAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    public function deleteUserAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a deleteUserAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $userId = $this->request->getPost('user_id');

            if ($userId == $this->session->get('user_id')) {
                Logger::warn("Intento de eliminar usuario propio: $userId");
                return $this->response->setJsonContent(['success' => false, 'error' => 'No puedes eliminarte a ti mismo']);
            }

            Logger::info("Eliminando usuario ID: $userId");
            $result = UserService::deleteUser($userId);

            if ($result['success']) {
                Logger::info("Usuario eliminado exitosamente: $userId");
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('deleteUserAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getUserByIdAction($userId)
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a getUserByIdAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        try {
            Logger::info("Obteniendo usuario ID: $userId");
            $user = UserService::getUserById($userId);

            if (!$user) {
                Logger::warn("Usuario no encontrado: $userId");
                return $this->response->setJsonContent(['success' => false, 'error' => 'Usuario no encontrado'], 404);
            }

            return $this->response->setJsonContent(['success' => true, 'data' => $user]);
        } catch (\Exception $e) {
            Logger::error('getUserByIdAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    // ===== MÉTODOS AUXILIARES PARA CUOTA =====

    /**
     * Obtener información detallada de cuota de un usuario
     */
    public function userQuotaInfoAction($userId)
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        try {
            $user = UserService::getUserById($userId);
            if (!$user) {
                return $this->response->setJsonContent(['success' => false, 'error' => 'Usuario no encontrado'], 404);
            }

            $quotaMb = UserService::getUserEffectiveQuota($userId);
            $usedMb = UserService::getUserUsedSpace($userId);
            $availableMb = $quotaMb - $usedMb;
            $percentageUsed = round(($usedMb / $quotaMb) * 100, 2);

            Logger::info("Información de cuota para usuario: " . $user['username']);

            return $this->response->setJsonContent([
                'success' => true,
                'data' => [
                    'username' => $user['username'],
                    'quota_mb' => $quotaMb,
                    'used_mb' => $usedMb,
                    'available_mb' => max(0, $availableMb),
                    'percentage_used' => $percentageUsed
                ]
            ]);
        } catch (\Exception $e) {
            Logger::error('userQuotaInfoAction error: ' . $e->getMessage());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener lista de usuarios con información de cuota
     */
    public function usersWithQuotaAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        try {
            $users = UserService::getAllUsers();

            // ⭐ Enriquecer datos con información de cuota
            foreach ($users as &$user) {
                $user['effective_quota_mb'] = UserService::getUserEffectiveQuota($user['id']);
                $user['used_space_mb'] = UserService::getUserUsedSpace($user['id']);
                $user['available_space_mb'] = $user['effective_quota_mb'] - $user['used_space_mb'];
            }

            Logger::info('Cargando usuarios con información de cuota');

            return $this->response->setJsonContent([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            Logger::error('usersWithQuotaAction error: ' . $e->getMessage());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ===== GRUPOS =====

    public function groupsAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a groupsAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        try {
            Logger::info('Cargando grupos...');
            $groups = GroupService::getAllGroupsWithQuota();
            Logger::info('Se cargaron ' . count($groups) . ' grupos');

            return $this->response->setJsonContent([
                'success' => true,
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            Logger::error('groupsAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    public function createGroupAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a createGroupAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $name = $this->request->getPost('name');
            $description = $this->request->getPost('description', 'string', '');
            $quotaMb = $this->request->getPost('quota_mb', 'int', null);  // ⭐ OBTENER quota_mb

            if (empty($name)) {
                return $this->response->setJsonContent(['success' => false, 'error' => 'El nombre es requerido']);
            }

            Logger::info("Creando grupo: $name con cuota solicitada: " . ($quotaMb ? $quotaMb . 'MB' : 'automática'));
            // ⭐ PASAR $quotaMb al servicio
            $result = GroupService::createGroup($name, $description, $quotaMb);

            if ($result['success']) {
                Logger::info("Grupo creado exitosamente: $name con cuota {$result['quota_mb']}MB");
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('createGroupAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateGroupAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a updateGroupAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $groupId = $this->request->getPost('group_id');
            $name = $this->request->getPost('name');
            $description = $this->request->getPost('description', 'string', '');
            $quotaMb = $this->request->getPost('quota_mb', 'int', null);  // ⭐ OBTENER quota_mb

            if (empty($name)) {
                return $this->response->setJsonContent(['success' => false, 'error' => 'El nombre es requerido']);
            }

            Logger::info("Actualizando grupo ID: $groupId con cuota solicitada: " . ($quotaMb ? $quotaMb . 'MB' : 'automática'));
            // ⭐ PASAR $quotaMb al servicio
            $result = GroupService::updateGroup($groupId, $name, $description, $quotaMb);

            if ($result['success']) {
                Logger::info("Grupo actualizado exitosamente: $name con cuota {$result['quota_mb']}MB");
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('updateGroupAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteGroupAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a deleteGroupAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $groupId = $this->request->getPost('group_id');

            Logger::info("Eliminando grupo ID: $groupId");
            $result = GroupService::deleteGroup($groupId);

            if ($result['success']) {
                Logger::info("Grupo eliminado exitosamente: $groupId");
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('deleteGroupAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getGroupByIdAction($groupId)
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a getGroupByIdAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        try {
            Logger::info("Obteniendo grupo ID: $groupId");
            $group = GroupService::getGroupById($groupId);

            if (!$group) {
                Logger::warn("Grupo no encontrado: $groupId");
                return $this->response->setJsonContent(['success' => false, 'error' => 'Grupo no encontrado'], 404);
            }

            $quota = \App\Models\GroupQuota::findFirst([
                'conditions' => 'group_id = ?1',
                'bind' => [1 => $groupId]
            ]);

            $groupData = $group->toArray();
            $groupData['quota_mb'] = $quota ? (int)$quota->quota_mb : 0;

            return $this->response->setJsonContent(['success' => true, 'data' => $groupData]);
        } catch (\Exception $e) {
            Logger::error('getGroupByIdAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ===== EXTENSIONES =====

    public function extensionsAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a extensionsAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        try {
            Logger::info('Cargando extensiones prohibidas...');
            $extensions = ExtensionService::getAllExtensions();
            Logger::info('Se cargaron ' . count($extensions) . ' extensiones');

            return $this->response->setJsonContent([
                'success' => true,
                'data' => $extensions
            ]);
        } catch (\Exception $e) {
            Logger::error('extensionsAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function addExtensionAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a addExtensionAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $extension = $this->request->getPost('extension');
            $description = $this->request->getPost('description', 'string', '');

            if (empty($extension)) {
                return $this->response->setJsonContent(['success' => false, 'error' => 'La extensión es requerida']);
            }

            Logger::info("Añadiendo extensión prohibida: $extension");
            $result = ExtensionService::addExtension($extension, $description);

            if ($result['success']) {
                Logger::info("Extensión añadida exitosamente: $extension");
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('addExtensionAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateExtensionAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a updateExtensionAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $extensionId = $this->request->getPost('extension_id');
            $extension = $this->request->getPost('extension');
            $description = $this->request->getPost('description', 'string', '');

            if (empty($extension)) {
                return $this->response->setJsonContent(['success' => false, 'error' => 'La extensión es requerida']);
            }

            Logger::info("Actualizando extensión ID: $extensionId");
            $result = ExtensionService::updateExtension($extensionId, $extension, $description);

            if ($result['success']) {
                Logger::info("Extensión actualizada exitosamente: $extension");
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('updateExtensionAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteExtensionAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a deleteExtensionAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $extensionId = $this->request->getPost('extension_id');

            Logger::info("Eliminando extensión ID: $extensionId");
            $result = ExtensionService::deleteExtension($extensionId);

            if ($result['success']) {
                Logger::info("Extensión eliminada exitosamente: $extensionId");
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('deleteExtensionAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getExtensionByIdAction($extensionId)
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a getExtensionByIdAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        try {
            Logger::info("Obteniendo extensión ID: $extensionId");
            $extension = ExtensionService::getExtensionById($extensionId);

            if (!$extension) {
                Logger::warn("Extensión no encontrada: $extensionId");
                return $this->response->setJsonContent(['success' => false, 'error' => 'Extensión no encontrada'], 404);
            }

            return $this->response->setJsonContent(['success' => true, 'data' => $extension]);
        } catch (\Exception $e) {
            Logger::error('getExtensionByIdAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ===== ASIGNACIONES =====

    public function assignmentsAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a assignmentsAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        try {
            Logger::info('Cargando asignaciones usuario-grupo...');
            $data = [
                'users' => UserGroupService::getUsersWithGroups(),
                'groups' => UserGroupService::getAllGroupsForAssignment()
            ];
            Logger::info('Asignaciones cargadas exitosamente');

            return $this->response->setJsonContent([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Logger::error('assignmentsAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function assignUserToGroupAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a assignUserToGroupAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $userId = $this->request->getPost('user_id');
            $groupId = $this->request->getPost('group_id');

            if (empty($userId) || empty($groupId)) {
                return $this->response->setJsonContent(['success' => false, 'error' => 'Usuario y grupo son requeridos']);
            }

            Logger::info("Asignando usuario $userId a grupo $groupId");
            $result = UserGroupService::assignUserToGroup($userId, $groupId);

            if ($result['success']) {
                Logger::info("Usuario asignado exitosamente: " . $result['message']);
            } else {
                Logger::warn("Fallo asignación: " . $result['error']);
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('assignUserToGroupAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    public function removeUserFromGroupAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a removeUserFromGroupAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $userId = $this->request->getPost('user_id');
            $groupId = $this->request->getPost('group_id');

            if (empty($userId) || empty($groupId)) {
                return $this->response->setJsonContent(['success' => false, 'error' => 'Usuario y grupo son requeridos']);
            }

            Logger::info("Removiendo usuario $userId del grupo $groupId");
            $result = UserGroupService::removeUserFromGroup($userId, $groupId);

            if ($result['success']) {
                Logger::info("Usuario removido exitosamente");
            }

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('removeUserFromGroupAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    // ===== CONFIGURACIÓN =====

    public function settingsAction()
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a settingsAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        try {
            Logger::info('Cargando configuración global...');
            $globalQuota = QuotaService::getGlobalDefaultQuota();

            return $this->response->setJsonContent([
                'success' => true,
                'data' => [
                    'global_quota_mb' => $globalQuota
                ]
            ]);
        } catch (\Exception $e) {
            Logger::error('settingsAction error: ' . $e->getMessage());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function settingsUpdateAction()  // ⭐ ESTE ES EL NOMBRE CORRECTO
    {
        $this->view->disable();

        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            Logger::error('Acceso no autorizado a settingsUpdateAction');
            return $this->response->setJsonContent(['success' => false, 'error' => 'No autenticado'], 401);
        }

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent(['success' => false, 'error' => 'Método no permitido']);
        }

        try {
            $quotaMb = $this->request->getPost('global_quota_mb', 'int');

            if (!$quotaMb || $quotaMb <= 0) {
                return $this->response->setJsonContent(['success' => false, 'error' => 'La cuota debe ser mayor a 0']);
            }

            Logger::info("Actualizando cuota global a: $quotaMb MB");
            $result = QuotaService::setGlobalQuota($quotaMb);

            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            Logger::error('settingsUpdateAction error: ' . $e->getMessage());
            return $this->response->setJsonContent(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
