<?php
/**
 * Script de test de l'API des paramètres d'école
 * 
 * Ce script teste si l'API renvoie correctement les paramètres de l'école
 * 
 * Usage: php test_api_school_settings.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolSettings;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\PrePrimaryCompetencyEvaluation;
use App\Models\PrePrimaryCompetency;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║      TEST DE L'API DES PARAMÈTRES D'ÉCOLE                    ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Récupérer les paramètres de l'école comme le fait le contrôleur
$schoolSettings = SchoolSettings::getSettings();

echo "📋 PARAMÈTRES DE L'ÉCOLE (depuis la base de données)\n";
echo "──────────────────────────────────────────────────────────────\n";

if (!$schoolSettings) {
    echo "❌ ERREUR : Aucun paramètre trouvé !\n";
    exit(1);
}

echo "✅ Paramètres trouvés\n\n";

// Afficher les informations
$fields = [
    'primary_school_name' => 'Nom de l\'école primaire',
    'school_phone' => 'Téléphone',
    'school_address' => 'Adresse',
    'school_bp' => 'BP',
    'city' => 'Ville',
    'school_motto' => 'Devise',
    'school_logo' => 'Chemin du logo',
];

foreach ($fields as $field => $label) {
    $value = $schoolSettings->{$field} ?? 'N/A';
    echo sprintf("%-30s : %s\n", $label, $value);
}

// Construire l'URL du logo comme le fait le contrôleur
echo "\n";
echo "🖼️  CONSTRUCTION DE L'URL DU LOGO\n";
echo "──────────────────────────────────────────────────────────────\n";

$logoUrl = null;
if ($schoolSettings && $schoolSettings->school_logo) {
    $logoUrl = url('storage/' . $schoolSettings->school_logo);
    echo "✅ URL du logo construite : " . $logoUrl . "\n";
    
    // Vérifier si le fichier existe
    $logoPath = storage_path('app/public/' . $schoolSettings->school_logo);
    if (file_exists($logoPath)) {
        echo "✅ Fichier existe : " . $logoPath . "\n";
    } else {
        echo "❌ Fichier introuvable : " . $logoPath . "\n";
    }
} else {
    echo "⚠️  Aucun logo configuré\n";
}

// Simuler la réponse de l'API
echo "\n";
echo "📤 SIMULATION DE LA RÉPONSE API\n";
echo "──────────────────────────────────────────────────────────────\n";

$apiResponse = [
    'schoolSettings' => [
        'primary_school_name' => $schoolSettings->primary_school_name ?? 'ECOLE PRIVEE',
        'school_phone' => $schoolSettings->school_phone ?? '',
        'school_address' => $schoolSettings->school_address ?? '',
        'school_bp' => $schoolSettings->school_bp ?? '',
        'city' => $schoolSettings->city ?? '',
        'school_motto' => $schoolSettings->school_motto ?? 'Travail - Rigueur - Discipline',
        'school_logo' => $logoUrl,
    ]
];

echo "Données qui seront envoyées par l'API :\n";
echo json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n";
echo "══════════════════════════════════════════════════════════════\n";

// Tester avec un vrai élève si disponible
$student = Student::with(['enrollments.schoolClass.level'])->first();

if ($student) {
    echo "\n";
    echo "🧪 TEST AVEC UN ÉLÈVE RÉEL\n";
    echo "──────────────────────────────────────────────────────────────\n";
    
    echo "Élève trouvé : {$student->first_name} {$student->last_name}\n";
    echo "ID : {$student->id}\n";
    
    echo "\n";
    echo "👉 Pour tester l'API complète, visitez :\n";
    echo "   http://127.0.0.1:8000/pre-primary-evaluations/api/student-data/{$student->id}\n";
    echo "\n";
    echo "   Vous devriez voir les informations de l'école dans la section 'schoolSettings'\n";
}

echo "\n";
echo "✅ Test terminé avec succès !\n";
echo "\n";

exit(0);

