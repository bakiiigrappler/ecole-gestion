<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `series.level` rattachait une série à son niveau par une chaîne de caractères
 * devant correspondre exactement à `levels.name`. Une seule lettre suffisait à
 * rompre le lien : le référentiel écrivait « Terminale » et la table des niveaux
 * « Terminal », si bien que les quatorze séries de Terminale — la majorité du
 * référentiel — n'étaient rattachables à aucun niveau et n'apparaissaient dans
 * aucun formulaire.
 *
 * On remplace le libellé par une clé étrangère, et on corrige la faute au
 * passage : le niveau du lycée s'écrit « Terminale ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->foreignId('level_id')->nullable()->after('name')
                ->constrained('levels')->nullOnDelete();
        });

        $niveaux = DB::table('levels')->get(['id', 'name']);

        foreach (DB::table('series')->get(['id', 'level']) as $serie) {
            $correspondance = $niveaux->first(
                fn ($n) => $this->normaliser($n->name) === $this->normaliser($serie->level)
            );

            if ($correspondance) {
                DB::table('series')->where('id', $serie->id)->update(['level_id' => $correspondance->id]);
            }
        }

        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn('level');
        });

        // Correction de l'orthographe, dans le niveau puis dans les classes qui
        // en tirent leur nom (« Terminal 1 » devient « Terminale 1 »).
        $terminale = DB::table('levels')->where('name', 'Terminal')->first();

        if ($terminale) {
            DB::table('levels')->where('id', $terminale->id)->update(['name' => 'Terminale']);

            foreach (DB::table('classes')->where('level_id', $terminale->id)->get(['id', 'name']) as $classe) {
                if (str_starts_with($classe->name, 'Terminal ')) {
                    DB::table('classes')->where('id', $classe->id)->update([
                        'name' => 'Terminale '.substr($classe->name, strlen('Terminal ')),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $terminale = DB::table('levels')->where('name', 'Terminale')->first();

        if ($terminale) {
            DB::table('levels')->where('id', $terminale->id)->update(['name' => 'Terminal']);

            foreach (DB::table('classes')->where('level_id', $terminale->id)->get(['id', 'name']) as $classe) {
                if (str_starts_with($classe->name, 'Terminale ')) {
                    DB::table('classes')->where('id', $classe->id)->update([
                        'name' => 'Terminal '.substr($classe->name, strlen('Terminale ')),
                    ]);
                }
            }
        }

        Schema::table('series', function (Blueprint $table) {
            $table->string('level')->nullable()->after('name');
        });

        foreach (DB::table('series')->whereNotNull('level_id')->get(['id', 'level_id']) as $serie) {
            DB::table('series')->where('id', $serie->id)->update([
                'level' => DB::table('levels')->where('id', $serie->level_id)->value('name'),
            ]);
        }

        Schema::table('series', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropColumn('level_id');
        });
    }

    /**
     * Rapprochement tolérant : accents, casse, espaces et « e » final ne doivent
     * pas empêcher de reconnaître « Terminale » dans « Terminal ».
     */
    private function normaliser(?string $texte): string
    {
        $texte = mb_strtolower(trim((string) $texte));

        $texte = strtr($texte, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);

        $texte = preg_replace('/[^a-z0-9]/', '', $texte);

        return rtrim($texte, 'e') ?: $texte;
    }
};
