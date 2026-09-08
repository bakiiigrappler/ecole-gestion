<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use PDOException;
use Symfony\Component\HttpFoundation\Response;

class HandleDatabaseConnectionErrors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (QueryException $e) {
            // Vérifier si c'est une erreur de connexion à la base de données
            if ($this->isDatabaseConnectionError($e)) {
                return $this->handleDatabaseConnectionError($request, $e);
            }
            
            // Si ce n'est pas une erreur de connexion, laisser Laravel gérer
            throw $e;
        } catch (PDOException $e) {
            // Vérifier si c'est une erreur de connexion PDO
            if ($this->isPDOConnectionError($e)) {
                return $this->handleDatabaseConnectionError($request, $e);
            }
            
            // Si ce n'est pas une erreur de connexion, laisser Laravel gérer
            throw $e;
        }
    }
    
    /**
     * Vérifier si l'erreur est liée à la connexion à la base de données
     */
    private function isDatabaseConnectionError(QueryException $e): bool
    {
        $errorMessage = strtolower($e->getMessage());
        
        // Codes d'erreur et messages liés aux problèmes de connexion
        $connectionErrors = [
            'sqlstate[hy000] [2002]', // Host not found
            'sqlstate[hy000] [2003]', // Can't connect to MySQL server
            'sqlstate[08001]',        // Client unable to establish connection
            'sqlstate[08004]',        // Server rejected the connection
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
    private function isPDOConnectionError(PDOException $e): bool
    {
        $errorCode = $e->getCode();
        $errorMessage = strtolower($e->getMessage());
        
        // Codes d'erreur PDO liés à la connexion
        $connectionErrorCodes = [
            2002, // Host not found
            2003, // Can't connect to MySQL server
            2006, // MySQL server has gone away
            2013, // Lost connection to MySQL server during query
            1045, // Access denied for user
            1049, // Unknown database
            1205, // Lock wait timeout exceeded
        ];
        
        if (in_array($errorCode, $connectionErrorCodes)) {
            return true;
        }
        
        // Vérifier aussi le message d'erreur
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
     * Gérer l'erreur de connexion à la base de données
     */
    private function handleDatabaseConnectionError(Request $request, $exception): Response
    {
        // Sauvegarder l'URL actuelle pour le rechargement
        session(['database_error_previous_url' => $request->fullUrl()]);
        
        // Si c'est une requête AJAX, retourner une réponse JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => 'database_connection_error',
                'message' => 'Problème de connexion à la base de données',
                'details' => 'Veuillez vérifier votre connexion internet et réessayer.',
                'retry_url' => $request->fullUrl()
            ], 503);
        }
        
        // Pour les requêtes normales, rediriger vers la page d'erreur
        return redirect()->route('database.connection.error');
    }
}
