<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseErrorController extends Controller
{
    /**
     * Afficher la page d'erreur de connexion à la base de données
     */
    public function showConnectionError(Request $request)
    {
        $previousUrl = session('database_error_previous_url', '/');
        
        return view('errors.database-connection', compact('previousUrl'));
    }
    
    /**
     * Vérifier la connexion à la base de données
     */
    public function checkConnection(Request $request)
    {
        try {
            // Tester la connexion à la base de données
            DB::connection()->getPdo();
            
            // Tester une requête simple
            DB::table('school_settings')->where('is_active', 1)->limit(1)->get();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Connexion à la base de données réussie',
                'timestamp' => now()->toISOString()
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Problème de connexion à la base de données',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }
    
    /**
     * Retry connection and redirect to previous page
     */
    public function retryConnection(Request $request)
    {
        try {
            // Tester la connexion
            DB::connection()->getPdo();
            DB::table('school_settings')->where('is_active', 1)->limit(1)->get();
            
            // Si la connexion est rétablie, rediriger vers la page précédente
            $previousUrl = session('database_error_previous_url', '/');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Connexion rétablie',
                'redirect_url' => $previousUrl
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'La connexion n\'est pas encore rétablie',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
