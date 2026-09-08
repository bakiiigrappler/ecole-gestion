<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\QueryException;
use PDOException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DatabaseConnectionExceptionHandler extends Exception
{
    /**
     * Gérer les erreurs de connexion à la base de données
     */
    public static function handle(Exception $exception, Request $request)
    {
        // Vérifier si c'est une erreur de connexion à la base de données
        if (self::isDatabaseConnectionError($exception)) {
            return self::handleDatabaseConnectionError($request, $exception);
        }
        
        return null;
    }
    
    /**
     * Vérifier si l'erreur est liée à la connexion à la base de données
     */
    private static function isDatabaseConnectionError(Exception $exception): bool
    {
        if ($exception instanceof QueryException) {
            return self::isQueryConnectionError($exception);
        }
        
        if ($exception instanceof PDOException) {
            return self::isPDOConnectionError($exception);
        }
        
        return false;
    }
    
    /**
     * Vérifier si l'erreur QueryException est liée à la connexion
     */
    private static function isQueryConnectionError(QueryException $e): bool
    {
        $errorMessage = strtolower($e->getMessage());
        
        $connectionErrors = [
            'sqlstate[hy000] [2002]',
            'sqlstate[hy000] [2003]',
            'sqlstate[08001]',
            'sqlstate[08004]',
            'connection refused',
            'host not found',
            'getaddrinfo failed',
            'php_network_getaddresses',
            'mysql server has gone away',
            'lost connection to mysql server',
            'can\'t connect to mysql server',
            'access denied for user',
            'unknown database',
            'too many connections'
        ];
        
        foreach ($connectionErrors as $error) {
            if (strpos($errorMessage, $error) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifier si l'erreur PDO est liée à la connexion
     */
    private static function isPDOConnectionError(PDOException $e): bool
    {
        $errorCode = $e->getCode();
        
        $connectionErrorCodes = [
            2002, // Host not found
            2003, // Can't connect to MySQL server
            2006, // MySQL server has gone away
            2013, // Lost connection to MySQL server during query
            1045, // Access denied for user
            1049, // Unknown database
            1205, // Lock wait timeout exceeded
        ];
        
        return in_array($errorCode, $connectionErrorCodes);
    }
    
    /**
     * Gérer l'erreur de connexion à la base de données
     */
    private static function handleDatabaseConnectionError(Request $request, Exception $exception)
    {
        // Sauvegarder l'URL actuelle pour le rechargement
        session(['database_error_previous_url' => $request->fullUrl()]);
        
        // Si c'est une requête AJAX, retourner une réponse JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => 'database_connection_error',
                'message' => 'Problème de connexion à la base de données',
                'details' => 'Veuillez vérifier votre connexion internet et réessayer.',
                'retry_url' => $request->fullUrl(),
                'redirect_url' => route('database.connection.error')
            ], 503);
        }
        
        // Pour les requêtes normales, rediriger vers la page d'erreur
        return redirect()->route('database.connection.error');
    }
}
