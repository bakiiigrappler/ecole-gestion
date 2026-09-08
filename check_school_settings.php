<?php
/**
 * Script de vérification des paramètres de l'école
 * 
 * Ce script vérifie si les paramètres de l'école sont correctement configurés
 * pour la génération des bulletins.
 * 
 * Usage: php check_school_settings.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SchoolSettings;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║   VÉRIFICATION DES PARAMÈTRES DE L'ÉTABLISSEMENT             ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$settings = SchoolSettings::where('is_active', true)->first();

if (!$settings) {
    echo "❌ ERREUR : Aucun paramètre d'école configuré !\n";
    echo "\n";
    echo "👉 Action requise :\n";
    echo "   Accédez à : http://127.0.0.1:8000/admin/school-settings\n";
    echo "   pour configurer les paramètres de votre établissement.\n";
    echo "\n";
    exit(1);
}

echo "✅ Paramètres de l'école trouvés !\n";
echo "\n";

// Vérifier les informations essentielles
echo "📋 INFORMATIONS DE L'ÉTABLISSEMENT\n";
echo "──────────────────────────────────────────────────────────────\n";

$checks = [
    'primary_school_name' => 'Nom de l\'école (Préprimaire/Primaire)',
    'school_phone' => 'Téléphone',
    'city' => 'Ville',
    'school_motto' => 'Devise de l\'école',
    'academic_year' => 'Année scolaire',
];

$allOk = true;

foreach ($checks as $field => $label) {
    $value = $settings->{$field};
    $status = !empty($value) ? '✅' : '❌';
    $display = !empty($value) ? $value : '(non renseigné)';
    
    echo sprintf("%-45s : %s %s\n", $label, $status, $display);
    
    if (empty($value)) {
        $allOk = false;
    }
}

echo "\n";
echo "🖼️  LOGO DE L'ÉCOLE\n";
echo "──────────────────────────────────────────────────────────────\n";

if ($settings->school_logo) {
    echo "✅ Logo configuré : " . $settings->school_logo . "\n";
    
    // Vérifier si le fichier existe
    $logoPath = storage_path('app/public/' . $settings->school_logo);
    if (file_exists($logoPath)) {
        echo "✅ Fichier trouvé sur le serveur\n";
        $fileSize = filesize($logoPath);
        echo "   Taille : " . round($fileSize / 1024, 2) . " Ko\n";
    } else {
        echo "❌ Fichier introuvable : " . $logoPath . "\n";
        $allOk = false;
    }
    
    // URL d'accès
    $logoUrl = $settings->logo_url;
    echo "   URL : " . ($logoUrl ?? 'N/A') . "\n";
} else {
    echo "⚠️  Aucun logo configuré\n";
    echo "   Les bulletins afficheront les initiales de l'école\n";
}

echo "\n";
echo "══════════════════════════════════════════════════════════════\n";

if ($allOk) {
    echo "✅ TOUT EST OK ! L'établissement est correctement configuré.\n";
} else {
    echo "⚠️  ATTENTION : Certaines informations sont manquantes.\n";
    echo "\n";
    echo "👉 Pour compléter la configuration :\n";
    echo "   1. Accédez à : http://127.0.0.1:8000/admin/school-settings\n";
    echo "   2. Remplissez les champs manquants\n";
    echo "   3. Téléchargez le logo de l'école\n";
    echo "   4. Enregistrez les modifications\n";
}

echo "\n";
echo "📄 Pour tester la génération de bulletin :\n";
echo "   1. Allez sur : http://127.0.0.1:8000/pre-primary-evaluations\n";
echo "   2. Sélectionnez une classe\n";
echo "   3. Cliquez sur 'Voir Bulletin' pour un élève\n";
echo "   4. Téléchargez le PDF\n";
echo "\n";

exit($allOk ? 0 : 1);

