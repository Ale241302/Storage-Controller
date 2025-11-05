<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Services\QuotaService;

class QuotaController extends Controller
{
    /**
     * Validar que sea admin
     */
    public function beforeExecuteRoute(\Phalcon\Mvc\Dispatcher $dispatcher)
    {
        if (!$this->session->has('user_id') || $this->session->get('role') !== 'admin') {
            $this->response->setStatusCode(403);
            $this->response->setJsonContent(['success' => false, 'error' => 'Acceso denegado']);
            $this->response->send();
            return false;
        }

        return true;
    }

    /**
     * Obtener cuota global
     */
    public function getGlobalQuotaAction()
    {
        $this->view->disable();
        try {
            $quotaMb = QuotaService::getGlobalDefaultQuota();
            return $this->response->setJsonContent([
                'success' => true,
                'data' => ['quota_mb' => $quotaMb, 'type' => 'global']
            ]);
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar cuota global
     */
    public function updateGlobalQuotaAction()
    {
        $this->view->disable();
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'success' => false,
                'error' => 'Método no permitido'
            ]);
        }

        try {
            $data = $this->request->getJsonRawBody(true);
            $quotaMb = $data['quota_mb'] ?? $this->request->getPost('quota_mb', 'int');

            if (!$quotaMb || $quotaMb <= 0) {
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => 'Cuota debe ser mayor a 0'
                ]);
            }

            $result = QuotaService::setGlobalQuota($quotaMb);
            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener cuota de usuario
     */
    public function getUserQuotaAction($userId)
    {
        $this->view->disable();
        try {
            $quotaInfo = QuotaService::getQuotaInfo($userId);
            return $this->response->setJsonContent([
                'success' => true,
                'data' => $quotaInfo
            ]);
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Establecer cuota de usuario
     */
    public function setUserQuotaAction()
    {
        $this->view->disable();
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'success' => false,
                'error' => 'Método no permitido'
            ]);
        }

        try {
            $data = $this->request->getJsonRawBody(true);
            $userId = $data['user_id'] ?? $this->request->getPost('user_id', 'int');
            $quotaMb = $data['quota_mb'] ?? $this->request->getPost('quota_mb', 'int');

            if (!$userId || !$quotaMb || $quotaMb <= 0) {
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => 'Usuario y cuota válidos son requeridos'
                ]);
            }

            $result = QuotaService::setUserQuota($userId, $quotaMb);
            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remover cuota de usuario
     */
    public function removeUserQuotaAction()
    {
        $this->view->disable();
        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'success' => false,
                'error' => 'Método no permitido'
            ]);
        }

        try {
            $data = $this->request->getJsonRawBody(true);
            $userId = $data['user_id'] ?? $this->request->getPost('user_id', 'int');

            if (!$userId) {
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => 'Usuario requerido'
                ]);
            }

            $result = QuotaService::removeUserQuota($userId);
            return $this->response->setJsonContent($result);
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
