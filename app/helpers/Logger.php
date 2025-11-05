<?php

namespace App\Helpers;

class Logger
{
    private static $logFile;

    /**
     * Inicializar el logger
     */
    public static function init()
    {
        $logDir = dirname(dirname(__DIR__)) . '/logs';

        // Crear directorio si no existe
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        self::$logFile = $logDir . '/app.log';
    }

    /**
     * Escribir log genérico
     */
    public static function log($message, $level = 'INFO')
    {
        if (!self::$logFile) {
            self::init();
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

        // Escribir en archivo
        @file_put_contents(self::$logFile, $logMessage, FILE_APPEND);

        // También escribir en error_log de PHP
        @error_log($logMessage);
    }

    /**
     * Log de ERROR
     */
    public static function error($message)
    {
        self::log($message, 'ERROR');
    }

    /**
     * Log de INFO
     */
    public static function info($message)
    {
        self::log($message, 'INFO');
    }

    /**
     * Log de DEBUG
     */
    public static function debug($message)
    {
        self::log($message, 'DEBUG');
    }

    /**
     * Log de WARNING
     */
    public static function warn($message)
    {
        self::log($message, 'WARN');
    }
}

// Inicializar al cargar
Logger::init();
