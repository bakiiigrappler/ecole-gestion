<?php

use App\Models\ParentModel;
use App\Models\Teacher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le numéro de téléphone devient un identifiant de connexion.
 *
 * Un parent d'élève n'a pas toujours d'adresse électronique, et un enseignant
 * retient plus sûrement son numéro que le courriel ouvert pour l'occasion. Le
 * numéro rejoint donc le courriel et le matricule parmi ce qui permet
 * d'entrer.
 *
 * Il est porté par `users` plutôt que cherché dans `parents` ou `teachers` :
 * la connexion doit rester une seule requête, et l'unicité doit pouvoir être
 * tenue par la base. Les numéros sont normalisés — seuls les chiffres, sans
 * indicatif ni séparateurs — sinon « 06 12 34 56 78 » et « +241 06123456 78 »
 * désigneraient deux personnes différentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'telephone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('telephone', 30)->nullable()->unique()->after('matricule');
            });
        }

        // Report des numéros déjà connus, pour les comptes existants.
        foreach ([ParentModel::class, Teacher::class] as $modele) {
            $porteurs = $modele::query()
                ->withoutGlobalScopes()
                ->whereNotNull('user_id')
                ->whereNotNull('phone')
                ->get(['user_id', 'phone']);

            foreach ($porteurs as $porteur) {
                $numero = self::normaliser($porteur->phone);

                if ($numero === null) {
                    continue;
                }

                // Un numéro partagé — deux parents, un seul téléphone — ne peut
                // être porté que par un compte : le premier arrivé le garde.
                $dejaPris = DB::table('users')
                    ->where('telephone', $numero)
                    ->where('id', '!=', $porteur->user_id)
                    ->exists();

                if ($dejaPris) {
                    continue;
                }

                DB::table('users')->where('id', $porteur->user_id)->update(['telephone' => $numero]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'telephone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('telephone');
            });
        }
    }

    /**
     * Un numéro réduit à ses chiffres, indicatif gabonais retiré.
     */
    private static function normaliser(?string $numero): ?string
    {
        $chiffres = preg_replace('/\D+/', '', (string) $numero);

        if ($chiffres === '') {
            return null;
        }

        // « +241 06 12 34 56 » et « 06 12 34 56 » sont le même abonné.
        if (str_starts_with($chiffres, '241') && strlen($chiffres) > 9) {
            $chiffres = substr($chiffres, 3);
        }

        return strlen($chiffres) >= 6 ? $chiffres : null;
    }
};
