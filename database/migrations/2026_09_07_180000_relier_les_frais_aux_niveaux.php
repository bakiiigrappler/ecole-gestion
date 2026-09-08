<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les frais de scolarité et d'inscription visent un niveau, mais ce niveau
 * n'était écrit nulle part ailleurs que dans le libellé : « Frais de scolarité
 * 6ème ». Impossible de retrouver le tarif d'une classe autrement qu'en
 * découpant une chaîne de caractères — et le libellé « Frais d'inscription
 * Terminal » ne correspond déjà plus au niveau, renommé « Terminale ».
 *
 * On ajoute la clé étrangère et on la remplit en lisant les libellés une
 * dernière fois. Les frais sans niveau reconnu (cantine, transport, uniforme)
 * restent rattachés à aucun niveau : ils s'appliquent partout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->foreignId('level_id')->nullable()->after('class_id')
                ->constrained('levels')->nullOnDelete();
        });

        // Les niveaux au libellé le plus long d'abord : « CM1 » ne doit pas
        // capter un frais destiné à « CM10 » s'il en existait un jour.
        $niveaux = DB::table('levels')->get(['id', 'name'])
            ->sortByDesc(fn ($n) => mb_strlen($n->name));

        foreach (DB::table('fees')->get(['id', 'name']) as $frais) {
            $reference = $this->normaliser($frais->name);

            $correspondance = $niveaux->first(
                fn ($n) => str_contains($reference, $this->normaliser($n->name))
            );

            if ($correspondance) {
                DB::table('fees')->where('id', $frais->id)->update(['level_id' => $correspondance->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropColumn('level_id');
        });
    }

    /**
     * Rapprochement tolérant : accents, casse et « e » final ne doivent pas
     * empêcher de reconnaître « Terminale » dans « Terminal ».
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
