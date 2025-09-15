<?php

echo "=== TEST DE CORRECTION DU TEMPS ===\n\n";

// Simuler la fonction JavaScript convertTimeFormat
function convertTimeFormat($timeStr) {
    // Vérification de sécurité
    if (!$timeStr || !is_string($timeStr)) {
        echo "⚠️ convertTimeFormat: timeStr invalide: " . var_export($timeStr, true) . "\n";
        return '00:00:00'; // Valeur par défaut
    }
    
    // Format attendu: "7h30" -> "07:30:00"
    if (preg_match('/(\d+)h(\d+)/', $timeStr, $matches)) {
        $hours = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        return "{$hours}:{$minutes}:00";
    }
    
    echo "⚠️ convertTimeFormat: format non reconnu: {$timeStr}\n";
    return '00:00:00'; // Valeur par défaut
}

// Tests avec différents formats
$testCases = [
    '7h30',
    '8h30',
    '14h00',
    '16h45',
    null,
    '',
    'invalid',
    '7h30-8h30'
];

foreach ($testCases as $test) {
    $result = convertTimeFormat($test);
    echo "🕐 '{$test}' -> '{$result}'\n";
}

echo "\n✅ Test terminé !\n";
