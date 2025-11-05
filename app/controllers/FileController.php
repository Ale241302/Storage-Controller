<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use App\Services\FileService;
use App\Services\UserService;
use App\Services\QuotaService;
use App\Helpers\Logger;


class FileController extends Controller
{
    /**
     * Se ejecuta ANTES de cada acción
     */
    public function beforeExecuteRoute(\Phalcon\Mvc\Dispatcher $dispatcher)
    {
        // Si no está autenticado, redirigir a login
        if (!$this->session->has('user_id')) {
            Logger::warn('Acceso no autenticado a FileController');
            return $this->response->redirect('/auth/login');
        }

        return true;
    }

    /**
     * Panel de usuario - PÁGINA PRINCIPAL
     * GET /
     * ⭐ Renderiza la vista con variables
     */
    public function indexAction()
    {
        $userId = $this->session->get('user_id');
        $username = $this->session->get('username');
        $role = $this->session->get('role');  // ⭐ OBTENER ROLE

        Logger::info("Usuario $username ($userId) accedió al panel - Role: $role");

        // ⭐ Pasar datos a la vista
        $this->view->setVar('userId', $userId);
        $this->view->setVar('username', $username);
        $this->view->setVar('role', $role);  // ⭐ PASAR ROLE A LA VISTA
    }
    /**
     * Obtener información de cuota y archivos del usuario (API)
     * GET /file/quota
     * 
     * ✅ Refactorizado: TODO por servicios, sin tocar modelos
     */
    public function quotaAction()
    {
        $this->view->disable();

        // 1️⃣ VALIDAR AUTENTICACIÓN
        if (!$this->session->has('user_id')) {
            Logger::warn('Intento de acceso a quota sin autenticación');
            $this->response->setStatusCode(403);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => 'No autenticado'
            ]);
        }

        try {
            $userId = $this->session->get('user_id');
            Logger::info("Obteniendo cuota para usuario ID: $userId");

            // 2️⃣ OBTENER DATOS VÍA SERVICIOS (NO MODELOS)

            // ⭐ Cuota efectiva del usuario
            $quotaInfo = QuotaService::getQuotaInfo($userId);

            // ⭐ Lista de archivos del usuario
            $filesData = FileService::getUserFiles($userId);

            Logger::info("Cuota devuelta: " . json_encode($quotaInfo));

            // 3️⃣ DEVOLVER RESPUESTA
            return $this->response->setJsonContent([
                'success' => true,
                'data' => [
                    'quota' => $quotaInfo,
                    'files' => $filesData
                ]
            ]);
        } catch (\Exception $e) {
            Logger::error('quotaAction error: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener archivos del usuario (API)
     * GET /file/list
     */
    public function listAction()
    {
        $this->view->disable();

        try {
            $userId = $this->session->get('user_id');
            Logger::info("Listando archivos del usuario ID: $userId");

            $files = FileService::getUserFiles($userId);

            return $this->response->setJsonContent([
                'success' => true,
                'data' => [
                    'files' => $files
                ]
            ]);
        } catch (\Exception $e) {
            Logger::error('listAction error: ' . $e->getMessage());
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Subir archivo (API)
     * POST /file/upload
     */
    public function uploadAction()
    {
        $this->view->disable();

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'success' => false,
                'error' => 'Método no permitido'
            ]);
        }

        try {
            $userId = $this->session->get('user_id');

            if (!$this->request->hasFiles()) {
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => 'No se envió ningún archivo'
                ]);
            }

            $uploadedFiles = $this->request->getUploadedFiles();
            if (count($uploadedFiles) === 0) {
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => 'No se envió ningún archivo'
                ]);
            }

            $uploadedFile = $uploadedFiles[0];
            $fileName = $uploadedFile->getName();
            $tempPath = $uploadedFile->getTempName();
            $fileSize = $uploadedFile->getSize();

            Logger::info("Iniciando subida de archivo: $fileName (Usuario ID: $userId)");

            // 1. Validar cuota
            $quotaCheck = QuotaService::canUploadFile($userId, $fileSize);
            if (!$quotaCheck['allowed']) {
                @unlink($tempPath);
                Logger::warn("Subida rechazada - Cuota excedida: $fileName");
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => $quotaCheck['message'],
                    'data' => [
                        'quota' => QuotaService::getQuotaInfo($userId)
                    ]
                ]);
            }

            // 2. Validar archivo
            $fileValidation = FileService::validateFile($tempPath, $fileName);
            if (!$fileValidation['valid']) {
                @unlink($tempPath);
                Logger::warn("Subida rechazada - Archivo no válido: $fileName");
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => $fileValidation['error']
                ]);
            }

            // 3. Guardar archivo
            $saveResult = FileService::saveUploadedFile($userId, $fileName, $tempPath, $fileSize);

            if ($saveResult['success']) {
                Logger::info("Archivo subido exitosamente: $fileName (File ID: {$saveResult['file_id']})");
                return $this->response->setJsonContent([
                    'success' => true,
                    'message' => $saveResult['message'],
                    'data' => [
                        'file_id' => $saveResult['file_id'],
                        'quota' => QuotaService::getQuotaInfo($userId)
                    ]
                ]);
            } else {
                Logger::error("Error guardando archivo: {$saveResult['error']}");
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => $saveResult['error']
                ]);
            }
        } catch (\Exception $e) {
            Logger::error('uploadAction error: ' . $e->getMessage());
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar archivo (API)
     * POST /file/delete
     */
    public function deleteAction()
    {
        $this->view->disable();

        if (!$this->request->isPost()) {
            return $this->response->setJsonContent([
                'success' => false,
                'error' => 'Método no permitido'
            ]);
        }

        try {
            $userId = $this->session->get('user_id');
            $fileId = $this->request->getPost('file_id', 'int');

            if (!$fileId) {
                return $this->response->setJsonContent([
                    'success' => false,
                    'error' => 'ID de archivo no proporcionado'
                ]);
            }

            Logger::info("Eliminando archivo ID: $fileId (Usuario ID: $userId)");

            $deleteResult = FileService::deleteFile($fileId, $userId);

            if ($deleteResult['success']) {
                Logger::info("Archivo eliminado exitosamente: $fileId");
                return $this->response->setJsonContent([
                    'success' => true,
                    'message' => $deleteResult['message'],
                    'data' => [
                        'quota' => QuotaService::getQuotaInfo($userId)
                    ]
                ]);
            } else {
                Logger::warn("Error eliminando archivo: {$deleteResult['error']}");
                return $this->response->setJsonContent($deleteResult);
            }
        } catch (\Exception $e) {
            Logger::error('deleteAction error: ' . $e->getMessage());
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Descargar archivo
     * GET /file/download/{fileId}
     */
    public function downloadAction($fileId)
    {
        $this->view->disable();

        try {
            $userId = $this->session->get('user_id');
            Logger::info("Descargando archivo ID: $fileId (Usuario ID: $userId)");

            $downloadResult = FileService::downloadFile($fileId, $userId);

            if (!$downloadResult['success']) {
                $this->response->setStatusCode(404);
                return $this->response->setJsonContent($downloadResult);
            }

            $this->response->setHeader('Content-Description', 'File Transfer');
            $this->response->setHeader('Content-Type', $downloadResult['mime_type']);
            $this->response->setHeader(
                'Content-Disposition',
                'attachment; filename="' . $downloadResult['file_name'] . '"'
            );
            $this->response->setHeader('Content-Length', filesize($downloadResult['file_path']));

            readfile($downloadResult['file_path']);
            exit;
        } catch (\Exception $e) {
            Logger::error('downloadAction error: ' . $e->getMessage());
            $this->response->setStatusCode(500);
            return $this->response->setJsonContent([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
