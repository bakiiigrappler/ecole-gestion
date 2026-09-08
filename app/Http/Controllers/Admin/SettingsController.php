<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    /**
     * Page principale des paramètres
     */
    public function index()
    {
        // Vérifier les permissions admin
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        // Statistiques générales
        $stats = [
            'users' => User::count(),
            'active_users' => User::active()->count(),
            'students' => Student::count(),
            'teachers' => Teacher::count(),
            'classes' => SchoolClass::count(),
            'subjects' => Subject::count(),
        ];

        // Informations système
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_size' => $this->getDatabaseSize(),
            'storage_used' => $this->getStorageSize(),
            'cache_size' => $this->getCacheSize(),
        ];

        // Activité récente
        $recentActivity = [
            'recent_users' => User::latest()->limit(5)->get(),
            'recent_students' => Student::latest()->limit(5)->get(),
        ];

        return view('admin.settings.index', compact('stats', 'systemInfo', 'recentActivity'));
    }

    /**
     * Page de gestion des utilisateurs
     */
    public function users()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        $users = User::orderBy('created_at', 'desc')->paginate(15);
        
        return view('admin.settings.users', compact('users'));
    }

    /**
     * Page de maintenance (superadmin uniquement)
     */
    public function maintenance()
    {
        // L'administrateur d'un etablissement reprend l'administration que
        // le superadmin assurait du temps de l'ecole unique.
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        $maintenanceInfo = [
            'last_backup' => $this->getLastBackupDate(),
            'logs_size' => $this->getLogsSize(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'cache_status' => Cache::has('app_health_check'),
        ];

        return view('admin.settings.maintenance', compact('maintenanceInfo'));
    }

    /**
     * Vider le cache
     */
    public function clearCache()
    {
        // L'administrateur d'un etablissement reprend l'administration que
        // le superadmin assurait du temps de l'ecole unique.
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache vidé avec succès !'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du vidage du cache : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimiser l'application
     */
    public function optimize()
    {
        // L'administrateur d'un etablissement reprend l'administration que
        // le superadmin assurait du temps de l'ecole unique.
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        try {
            Artisan::call('optimize');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            return response()->json([
                'success' => true,
                'message' => 'Application optimisée avec succès !'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'optimisation : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sauvegarder la base de données
     */
    public function backup()
    {
        // L'administrateur d'un etablissement reprend l'administration que
        // le superadmin assurait du temps de l'ecole unique.
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        try {
            $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
            
            // Commande mysqldump (à adapter selon votre configuration)
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
                env('DB_HOST'),
                env('DB_DATABASE'),
                storage_path('app/backups/' . $filename)
            );

            // Créer le dossier de sauvegarde s'il n'existe pas
            if (!Storage::exists('backups')) {
                Storage::makeDirectory('backups');
            }

            exec($command);

            return response()->json([
                'success' => true,
                'message' => 'Sauvegarde créée : ' . $filename
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir la taille de la base de données
     */
    private function getDatabaseSize()
    {
        try {
            $result = DB::select("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size'
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [env('DB_DATABASE')]);

            return $result[0]->size ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtenir la taille du stockage
     */
    private function getStorageSize()
    {
        try {
            $bytes = 0;
            $path = storage_path('app');
            
            if (is_dir($path)) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
                    $bytes += $file->getSize();
                }
            }

            return round($bytes / 1024 / 1024, 2); // en MB
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtenir la taille du cache
     */
    private function getCacheSize()
    {
        try {
            $bytes = 0;
            $path = storage_path('framework/cache');
            
            if (is_dir($path)) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
                    $bytes += $file->getSize();
                }
            }

            return round($bytes / 1024 / 1024, 2); // en MB
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtenir la date de la dernière sauvegarde
     */
    private function getLastBackupDate()
    {
        try {
            $backupPath = storage_path('app/backups');
            
            if (!is_dir($backupPath)) {
                return null;
            }

            $files = glob($backupPath . '/backup-*.sql');
            
            if (empty($files)) {
                return null;
            }

            $latestFile = max($files);
            return date('d/m/Y H:i:s', filemtime($latestFile));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtenir la taille des logs
     */
    private function getLogsSize()
    {
        try {
            $bytes = 0;
            $path = storage_path('logs');
            
            if (is_dir($path)) {
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
                    $bytes += $file->getSize();
                }
            }

            // L'unite fait partie de la valeur : sans elle, la page affichait
            // « 0.35 » sans qu'on sache de quoi il s'agissait.
            return round($bytes / 1024 / 1024, 2) . ' MB';
        } catch (\Exception $e) {
            return '0 MB';
        }
    }

    /**
     * Page d'informations système
     */
    public function systemInfo()
    {
        // L'administrateur d'un etablissement reprend l'administration que
        // le superadmin assurait du temps de l'ecole unique.
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        $systemInfo = [
            // Informations PHP
            'php' => [
                'version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'extensions' => $this->getImportantExtensions(),
            ],

            // Informations serveur
            'server' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible',
                'os' => php_uname('s') . ' ' . php_uname('r'),
                'hostname' => gethostname(),
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Non disponible',
            ],

            // Informations Laravel
            'laravel' => [
                'version' => app()->version(),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'cache_driver' => config('cache.default'),
                'session_driver' => config('session.driver'),
                'queue_driver' => config('queue.default'),
            ],

            // Informations base de données
            'database' => [
                'connection' => config('database.default'),
                'host' => config('database.connections.mysql.host'),
                'database' => config('database.connections.mysql.database'),
                'version' => $this->getDatabaseVersion(),
                'size' => $this->getDatabaseSize() . ' MB',
                'tables_count' => $this->getTablesCount(),
            ],

            // Performance et stockage
            'performance' => [
                'storage_used' => $this->getStorageSize() . ' MB',
                'cache_size' => $this->getCacheSize() . ' MB',
                'logs_size' => $this->getLogsSize() . ' MB',
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            ]
        ];

        return view('admin.settings.system-info', compact('systemInfo'));
    }

    /**
     * Page de sécurité et journaux
     */
    public function security()
    {
        // L'administrateur d'un etablissement reprend l'administration que
        // le superadmin assurait du temps de l'ecole unique.
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        $securityInfo = [
            // Paramètres de sécurité
            'security_settings' => [
                'app_debug' => config('app.debug'),
                'https_enabled' => $this->isHttpsEnabled(),
                'csrf_protection' => class_exists('Illuminate\Foundation\Http\Middleware\VerifyCsrfToken'),
                'session_secure' => config('session.secure'),
                'session_http_only' => config('session.http_only'),
                'session_same_site' => config('session.same_site'),
            ],

            // Statistiques de sécurité
            'security_stats' => [
                'active_sessions' => $this->getActiveSessionsCount(),
                'failed_logins_today' => $this->getFailedLoginsToday(),
                'admin_users_count' => User::whereIn('role', ['admin', 'superadmin'])->count(),
                'inactive_users_count' => User::where('is_active', false)->count(),
                'last_login_activity' => $this->getLastLoginActivity(),
            ],

            // Logs récents
            'recent_logs' => $this->getRecentLogs(),
            
            // Journaux par type
            'logs_summary' => $this->getLogsSummary(),
        ];

        return view('admin.settings.security', compact('securityInfo'));
    }

    /**
     * Télécharger les logs
     */
    public function downloadLogs(Request $request)
    {
        // L'administrateur d'un etablissement reprend l'administration que
        // le superadmin assurait du temps de l'ecole unique.
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        $logType = $request->get('type', 'laravel');
        $logFile = storage_path("logs/{$logType}.log");

        if (!File::exists($logFile)) {
            return back()->with('error', 'Fichier de log non trouvé.');
        }

        return response()->download($logFile);
    }

    /**
     * Vider les logs
     */
    public function clearLogs(Request $request)
    {
        // L'administrateur d'un etablissement reprend l'administration que
        // le superadmin assurait du temps de l'ecole unique.
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }

        try {
            $logType = $request->get('type', 'laravel');
            $logFile = storage_path("logs/{$logType}.log");

            if (File::exists($logFile)) {
                File::put($logFile, '');
            }

            return response()->json([
                'success' => true,
                'message' => 'Logs vidés avec succès !'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du vidage des logs : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les extensions PHP importantes
     */
    private function getImportantExtensions()
    {
        $extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
        $result = [];

        foreach ($extensions as $ext) {
            $result[$ext] = extension_loaded($ext);
        }

        return $result;
    }

    /**
     * Obtenir la version de la base de données
     */
    private function getDatabaseVersion()
    {
        try {
            $result = DB::select('SELECT VERSION() as version');
            return $result[0]->version ?? 'Non disponible';
        } catch (\Exception $e) {
            return 'Non disponible';
        }
    }

    /**
     * Obtenir le nombre de tables
     */
    private function getTablesCount()
    {
        try {
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [env('DB_DATABASE')]);

            return $result[0]->count ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Vérifier si HTTPS est activé
     */
    private function isHttpsEnabled()
    {
        return request()->secure() || 
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Obtenir le nombre de sessions actives
     */
    private function getActiveSessionsCount()
    {
        try {
            return DB::table('sessions')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtenir les tentatives de connexion échouées aujourd'hui
     */
    private function getFailedLoginsToday()
    {
        // Cette méthode nécessiterait un système de logging des tentatives de connexion
        // Pour l'instant, nous retournons 0
        return 0;
    }

    /**
     * Obtenir la dernière activité de connexion
     */
    private function getLastLoginActivity()
    {
        try {
            $user = User::whereNotNull('last_login_at')
                       ->orderBy('last_login_at', 'desc')
                       ->first();

            return $user ? [
                'user' => $user->name,
                'time' => $user->last_login_at->diffForHumans(),
                'email' => $user->email
            ] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtenir les logs récents
     */
    private function getRecentLogs()
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            
            if (!File::exists($logFile)) {
                return [];
            }

            $logs = File::get($logFile);
            $lines = array_filter(explode("\n", $logs));
            $recentLogs = array_slice($lines, -20); // 20 dernières lignes

            return array_reverse($recentLogs);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtenir un résumé des logs par type
     */
    private function getLogsSummary()
    {
        try {
            $logPath = storage_path('logs');
            $summary = [];

            if (is_dir($logPath)) {
                $files = File::files($logPath);
                
                foreach ($files as $file) {
                    if ($file->getExtension() === 'log') {
                        $name = $file->getFilenameWithoutExtension();
                        $summary[] = [
                            'name' => $name,
                            'size' => round($file->getSize() / 1024, 2) . ' KB',
                            'modified' => date('d/m/Y H:i:s', $file->getMTime()),
                            'lines' => count(file($file->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
                        ];
                    }
                }
            }

            return $summary;
        } catch (\Exception $e) {
            return [];
        }
    }
}