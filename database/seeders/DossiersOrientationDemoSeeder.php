<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\OrientationDossier;
use App\Models\OrientationEtablissement;
use App\Models\Student;
use App\Models\User;
use App\Support\Orientation\Conseil;
use App\Support\Orientation\ProfilEleve;
use App\Support\Orientation\VoiesApresTerminale;
use App\Support\Orientation\VoiesApresTroisieme;
use Illuminate\Database\Seeder;

/**
 * Des dossiers d'orientation dans les quatre états.
 *
 * Un écran de service d'orientation vide ne se juge pas : on ne voit ni ce
 * qu'un dossier contient, ni ce que donne un désaccord motivé, ni comment se
 * présente la répartition d'une promotion. Ce peuplement pose donc des cas —
 * en cours, en attente, accord, désaccord — sur de vrais élèves de 3ème et de
 * terminale, avec leurs vraies notes.
 *
 * Les vœux sont pris dans le répertoire, la voie est celle que le profil
 * conseille, et le désaccord porte un motif du catalogue : ce qu'un conseiller
 * verrait un lundi de juin.
 */
class DossiersOrientationDemoSeeder extends Seeder
{
    /** Combien de dossiers par état, au plus. */
    private const PAR_ETAT = [
        'soumis' => 6,
        'accorde' => 4,
        'refuse' => 2,
        'brouillon' => 3,
    ];

    public function run(): void
    {
        $annee = AcademicYear::where('is_current', true)->first();

        if (OrientationDossier::when($annee, fn ($q) => $q->where('academic_year_id', $annee->id))->exists()) {
            $this->command?->info('Des dossiers d’orientation existent déjà : rien à poser.');

            return;
        }

        $eleves = $this->elevesConcernes($annee);

        if ($eleves->isEmpty()) {
            $this->command?->warn('Aucun élève de 3ème ou de terminale : dossiers d’orientation ignorés.');

            return;
        }

        $conseiller = User::whereIn('role', ['admin', 'directeur', 'proviseur', 'censeur'])->first();
        $poses = [];

        foreach (self::PAR_ETAT as $statut => $nombre) {
            foreach ($eleves->splice(0, $nombre) as $eleve) {
                $this->poser($eleve, $annee, $statut, $conseiller);
                $poses[$statut] = ($poses[$statut] ?? 0) + 1;
            }
        }

        $this->command?->info('Dossiers d’orientation posés : '.collect($poses)
            ->map(fn ($n, $statut) => $n.' '.OrientationDossier::STATUTS[$statut])
            ->join(', ').'.');
    }

    /**
     * Les élèves de 3ème et de terminale qui ont des notes : sans elles, le
     * dossier n'aurait aucun profil à montrer.
     */
    private function elevesConcernes(?AcademicYear $annee)
    {
        return Student::query()
            ->whereHas('grades', fn ($q) => $q->when($annee, fn ($r) => $r->where('academic_year_id', $annee->id)))
            ->whereHas('enrollments', fn ($q) => $q
                ->where('status', 'active')
                ->whereHas('schoolClass.level', fn ($n) => $n
                    ->where('name', 'like', '%3%')
                    ->orWhere('name', 'like', '%erminale%')))
            ->with(['enrollments.schoolClass.level', 'enrollments.schoolClass.serie'])
            ->inRandomOrder()
            ->limit(array_sum(self::PAR_ETAT))
            ->get();
    }

    private function poser(Student $eleve, ?AcademicYear $annee, string $statut, ?User $conseiller): void
    {
        $classe = $eleve->enrollments->firstWhere('status', 'active')?->schoolClass;
        $niveauClasse = mb_strtolower((string) ($classe->level->name ?? ''));
        $niveau = str_contains($niveauClasse, 'erminale') ? 'terminale' : 'troisieme';

        $profil = ProfilEleve::etablir($eleve, $annee);

        [$voie, $filiere] = $niveau === 'troisieme'
            ? $this->choixDeTroisieme($profil)
            : $this->choixDeTerminale($classe?->serie?->code);

        $voeux = $this->voeux($niveau, $voie, $filiere);

        $dossier = new OrientationDossier([
            'school_id' => $eleve->school_id,
            'student_id' => $eleve->id,
            'academic_year_id' => $annee?->id,
            'niveau' => $niveau,
            'serie_actuelle' => $classe?->serie?->code,
            'profil' => $profil['profil'],
            'moyennes' => [
                'generale' => $profil['generale'],
                'sciences' => $profil['sciences'],
                'lettres' => $profil['lettres'],
                'gestion' => $profil['gestion'],
                'notes' => $profil['notes'],
                'par_matiere' => $profil['par_matiere'],
                'libelle' => $profil['libelle'],
                'lecture' => $profil['lecture'],
            ],
            'voie' => $voie,
            'filiere' => $filiere,
            'mode_admission' => $niveau === 'troisieme'
                ? Conseil::modeAdmission($profil['generale'], VoiesApresTroisieme::filiere($voie, $filiere))
                : 'dossier',
            'voeux' => $voeux,
            'statut' => $statut,
            'commentaire_eleve' => $this->mot($niveau, $filiere),
        ]);

        if ($statut !== 'brouillon') {
            $dossier->soumis_le = now()->subDays(random_int(2, 20));
        }

        if (in_array($statut, ['accorde', 'refuse'], true)) {
            $dossier->decide_par = $conseiller?->id;
            $dossier->decide_le = $dossier->soumis_le->copy()->addDays(random_int(1, 5));

            if ($statut === 'refuse') {
                // Le motif suit ce que disent les notes : un désaccord
                // inventé au hasard ne ressemblerait à rien.
                $dossier->motif_code = ($profil['generale'] ?? 20) < 10
                    ? 'redoublement'
                    : 'moyenne_insuffisante';

                $dossier->motif_precision = 'Le conseil de classe a examiné le dossier le '
                    .$dossier->decide_le->locale('fr')->isoFormat('D MMMM').'.';
            }
        }

        $dossier->save();
    }

    /** @return array{0: string, 1: ?string} */
    private function choixDeTroisieme(array $profil): array
    {
        $voies = Conseil::voies($profil, (bool) random_int(0, 1));
        $voie = $voies[0]['voie'] ?? 'generale';

        $filieres = Conseil::filieres($voie, $profil);
        $conseillee = collect($filieres)->firstWhere('etat', 'conseillee') ?? ($filieres[0] ?? null);

        return [$voie, $conseillee['nom'] ?? null];
    }

    /** @return array{0: string, 1: ?string} */
    private function choixDeTerminale(?string $code): array
    {
        $serie = VoiesApresTerminale::depuisLeCode($code);
        $filieres = VoiesApresTerminale::filieres($serie);

        // Au hasard parmi celles de la serie : prendre toujours la premiere
        // donnait une promotion entiere en genie civil.
        return ['superieur', $filieres ? $filieres[array_rand($filieres)] : null];
    }

    /**
     * Deux ou trois vœux, pris dans le répertoire selon la voie.
     */
    private function voeux(string $niveau, ?string $voie, ?string $filiere): array
    {
        $types = $niveau === 'terminale'
            ? ['universite', 'ecole_superieure']
            : VoiesApresTroisieme::typesEtablissement($voie);

        $etablissements = OrientationEtablissement::whereNull('school_id')
            ->whereIn('type', $types)
            ->inRandomOrder()
            ->limit(random_int(2, 3))
            ->get();

        return $etablissements->values()->map(fn ($e, $rang) => [
            'rang' => $rang + 1,
            'etablissement_id' => $e->id,
            'nom' => $e->nom,
            'filiere' => $filiere,
        ])->all();
    }

    private function mot(string $niveau, ?string $filiere): ?string
    {
        if (random_int(0, 2) === 0) {
            return null;
        }

        return $niveau === 'troisieme'
            ? 'Je souhaite rester dans un établissement proche de mon quartier.'
            : 'Mon projet est de poursuivre en '.mb_strtolower($filiere ?: 'filière scientifique').'.';
    }
}
