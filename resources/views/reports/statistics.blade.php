@extends('layouts.app')

@section('titre', 'Rapport statistique')
@section('sous-titre', 'Année scolaire '.($annee->name ?? '—').' — chiffres calculés sur les données de l’établissement')

@section('actions-entete')
    <button type="button"
            class="bouton-secondaire"
            data-export-pdf="rapport-statistiques"
            data-orientation="paysage"
            data-page-unique
            data-nom-fichier="Rapport_statistique_{{ \Illuminate\Support\Str::slug($annee->name ?? 'annee') }}.pdf">
        Télécharger le rapport
    </button>
    <a href="{{ route('statistics.export.csv') }}" class="bouton-primaire">Export CSV</a>
@endsection

@section('contenu')

@php
    $etablissement = \App\Models\SchoolSettings::first();

    $montant = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';
    $nombre = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $pourcent = fn ($part, $total) => $total > 0 ? round($part / $total * 100).' %' : '—';

    $libellesCycle = ['preprimaire' => 'Préprimaire', 'primaire' => 'Primaire', 'college' => 'Collège', 'lycee' => 'Lycée'];
    $ordreCycles = ['preprimaire', 'primaire', 'college', 'lycee'];

    $libellesMethode = [
        'moov_money' => 'Moov Money', 'airtel_money' => 'Airtel Money', 'card' => 'Carte bancaire',
        'bank_transfer' => 'Virement bancaire', 'cash' => 'Espèces', 'check' => 'Chèque',
    ];

    $libellesMaitrise = [
        'maximale' => 'Maîtrise maximale', 'minimale' => 'Maîtrise minimale',
        'partielle' => 'Maîtrise partielle', 'non_maitrise' => 'Non maîtrisé',
    ];
    $libellesCode = ['MAX' => 'Maîtrise maximale', 'MIN' => 'Maîtrise minimale', 'PART' => 'Maîtrise partielle', 'NM' => 'Non maîtrisé'];
    $libellesPresence = ['present' => 'Présents', 'absent' => 'Absents', 'late' => 'Retards', 'excused' => 'Absences justifiées'];

    $mentions = $scolarite['mentions'];
    $repartitionMentions = [
        'Excellent (≥ 16)' => (int) ($mentions->excellent ?? 0),
        'Bien (14 – 16)' => (int) ($mentions->bien ?? 0),
        'Assez bien (12 – 14)' => (int) ($mentions->assez_bien ?? 0),
        'Passable (10 – 12)' => (int) ($mentions->passable ?? 0),
        'Insuffisant (< 10)' => (int) ($mentions->insuffisant ?? 0),
    ];

    $totalGenre = $effectifs['par_genre']['male'] + $effectifs['par_genre']['female'];

    // Le document s'imprime : gris, noir et blanc.
    $entete = 'border border-gris-400 bg-gris-200 px-2 py-1.5 text-left font-semibold uppercase tracking-wide text-gris-700';
    $cellule = 'border border-gris-400 px-2 py-1';
    $titre = 'mt-4 mb-1.5 text-[11px] font-bold uppercase tracking-wide text-gris-700';
@endphp

    {{-- ------------------------------------------------------------------
         Le rapport. Ce bloc est celui que html2canvas photographie :
         le PDF est exactement ce qui s'affiche ici.
         ------------------------------------------------------------------ --}}
    <div id="rapport-statistiques" class="mx-auto max-w-6xl bg-white p-6 text-gris-900 ring-1 ring-gris-200">

        {{-- En-tête : logo de l'établissement à gauche, sceau de la République à droite --}}
        <div class="flex items-start justify-between gap-4 border-b-2 border-gris-800 pb-3">
            <div class="flex items-start gap-3">
                @if ($etablissement->logo_url ?? null)
                    <img src="{{ $etablissement->logo_url }}" alt="Logo de l’établissement"
                         class="h-12 w-12 shrink-0 object-contain">
                @else
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Logo<br>établissement
                    </span>
                @endif
                <div class="leading-tight">
                    <p class="text-[10px] text-gris-600">Ministère de l’Éducation Nationale</p>
                    <p class="text-sm font-bold uppercase">{{ $etablissement->school_name ?? 'Établissement scolaire' }}</p>
                    <p class="text-[9px] text-gris-500">
                        @if ($etablissement->school_bp ?? null) {{ $etablissement->school_bp }} @endif
                        @if ($etablissement->school_phone ?? null) Tél/fax : {{ $etablissement->school_phone }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="text-right leading-tight">
                    <p class="text-[10px] uppercase tracking-wide text-gris-500">Année scolaire</p>
                    <p class="text-sm font-semibold">{{ $annee->name ?? '—' }}</p>
                </div>

                @if ($etablissement->seal_url ?? null)
                    <img src="{{ $etablissement->seal_url }}" alt="Sceau de la République"
                         class="h-12 w-16 shrink-0 object-contain">
                @else
                    <span class="flex h-12 w-16 shrink-0 items-center justify-center rounded border border-dashed border-gris-400 text-center text-[7px] leading-tight text-gris-500">
                        Sceau de la<br>République
                    </span>
                @endif
            </div>
        </div>

        <div class="mt-4 text-center">
            <h1 class="text-lg font-bold uppercase tracking-wide">Rapport statistique annuel</h1>
            <p class="mt-0.5 text-sm text-gris-600">
                Situation arrêtée au {{ now()->translatedFormat('j F Y') }}
            </p>
        </div>

        {{-- I. Chiffres clés ------------------------------------------------ --}}
        <p class="{{ $titre }}">I. Chiffres clés</p>

        <table class="w-full border-collapse text-[10px]">
            <tbody>
                <tr>
                    <td class="{{ $entete }} w-1/4">Élèves inscrits</td>
                    <td class="{{ $cellule }} w-1/4 font-semibold">{{ $nombre($effectifs['eleves']) }}</td>
                    <td class="{{ $entete }} w-1/4">Classes ouvertes</td>
                    <td class="{{ $cellule }} w-1/4 font-semibold">{{ $effectifs['classes'] }}</td>
                </tr>
                <tr>
                    <td class="{{ $entete }}">Frais encaissés</td>
                    <td class="{{ $cellule }} font-semibold">{{ $montant($finances['encaisse']) }}</td>
                    <td class="{{ $entete }}">Taux de recouvrement</td>
                    <td class="{{ $cellule }} font-semibold">{{ $finances['taux'] }} %</td>
                </tr>
                <tr>
                    <td class="{{ $entete }}">Moyenne générale (secondaire)</td>
                    <td class="{{ $cellule }} font-semibold">
                        {{ $scolarite['moyenne'] !== null ? number_format($scolarite['moyenne'], 2, ',', ' ').' / 20' : '—' }}
                    </td>
                    <td class="{{ $entete }}">Taux de présence</td>
                    <td class="{{ $cellule }} font-semibold">
                        {{ $assiduite['taux'] !== null ? $assiduite['taux'].' %' : '—' }}
                    </td>
                </tr>
                <tr>
                    <td class="{{ $entete }}">Enseignants en activité</td>
                    <td class="{{ $cellule }}">{{ $effectifs['enseignants'] }}</td>
                    <td class="{{ $entete }}">Responsables légaux</td>
                    <td class="{{ $cellule }}">{{ $nombre($effectifs['parents']) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- II. Effectifs ---------------------------------------------------- --}}
        <p class="{{ $titre }}">II. Effectifs</p>

        <div class="flex items-start gap-4">
            <table class="w-2/3 border-collapse text-[10px]">
                <thead>
                    <tr>
                        <th class="{{ $entete }}">Cycle</th>
                        <th class="{{ $entete }} w-20 text-center">Classes</th>
                        <th class="{{ $entete }} w-20 text-center">Élèves</th>
                        <th class="{{ $entete }} w-24 text-center">Part</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ordreCycles as $cycle)
                        @php($chiffres = $effectifs['par_cycle'][$cycle] ?? null)
                        @continue(! $chiffres)
                        <tr>
                            <td class="{{ $cellule }}">{{ $libellesCycle[$cycle] }}</td>
                            <td class="{{ $cellule }} text-center">{{ $chiffres['classes'] }}</td>
                            <td class="{{ $cellule }} text-center font-semibold">{{ $chiffres['eleves'] }}</td>
                            <td class="{{ $cellule }} text-center">{{ $pourcent($chiffres['eleves'], $effectifs['eleves']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-gris-100">
                        <td class="{{ $cellule }} font-bold uppercase">Total</td>
                        <td class="{{ $cellule }} text-center font-bold">{{ $effectifs['classes'] }}</td>
                        <td class="{{ $cellule }} text-center font-bold">{{ $nombre($effectifs['eleves']) }}</td>
                        <td class="{{ $cellule }} text-center font-bold">100 %</td>
                    </tr>
                </tbody>
            </table>

            <table class="w-1/3 self-start border-collapse text-[10px]">
                <thead>
                    <tr>
                        <th class="{{ $entete }}" colspan="3">Répartition par sexe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="{{ $cellule }}">Garçons</td>
                        <td class="{{ $cellule }} text-center font-semibold">{{ $effectifs['par_genre']['male'] }}</td>
                        <td class="{{ $cellule }} w-16 text-center">{{ $pourcent($effectifs['par_genre']['male'], $totalGenre) }}</td>
                    </tr>
                    <tr>
                        <td class="{{ $cellule }}">Filles</td>
                        <td class="{{ $cellule }} text-center font-semibold">{{ $effectifs['par_genre']['female'] }}</td>
                        <td class="{{ $cellule }} text-center">{{ $pourcent($effectifs['par_genre']['female'], $totalGenre) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- III. Situation financière ---------------------------------------- --}}
        <p class="{{ $titre }}">III. Situation financière</p>

        <div class="flex items-start gap-4">
            <table class="w-1/2 self-start border-collapse text-[10px]">
                <tbody>
                    <tr>
                        <td class="{{ $entete }} w-1/2">Frais attendus</td>
                        <td class="{{ $cellule }} text-right font-semibold">{{ $montant($finances['attendu']) }}</td>
                    </tr>
                    <tr>
                        <td class="{{ $entete }}">Frais encaissés</td>
                        <td class="{{ $cellule }} text-right font-semibold">{{ $montant($finances['encaisse']) }}</td>
                    </tr>
                    <tr>
                        <td class="{{ $entete }}">Reste à recouvrer</td>
                        <td class="{{ $cellule }} text-right font-semibold">{{ $montant($finances['reste']) }}</td>
                    </tr>
                    <tr>
                        <td class="{{ $entete }}">Transactions encaissées</td>
                        <td class="{{ $cellule }} text-right">{{ $nombre($finances['transactions']) }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="w-1/2 self-start border-collapse text-[10px]">
                <thead>
                    <tr>
                        <th class="{{ $entete }}">Moyen de paiement</th>
                        <th class="{{ $entete }} w-28 text-right">Montant</th>
                        <th class="{{ $entete }} w-16 text-center">Part</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($finances['par_methode'] as $ligne)
                        <tr>
                            <td class="{{ $cellule }}">{{ $libellesMethode[$ligne->payment_method] ?? $ligne->payment_method }}</td>
                            <td class="{{ $cellule }} text-right">{{ $montant($ligne->montant) }}</td>
                            <td class="{{ $cellule }} text-center">{{ $pourcent($ligne->montant, $finances['encaisse']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="{{ $cellule }} text-gris-400" colspan="3">Aucune transaction encaissée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- IV. Résultats scolaires ------------------------------------------ --}}
        <p class="{{ $titre }}">IV. Résultats scolaires — secondaire</p>

        <div class="flex items-start gap-4">
            <table class="w-1/2 self-start border-collapse text-[10px]">
                <thead>
                    <tr>
                        <th class="{{ $entete }}">Mention</th>
                        <th class="{{ $entete }} w-20 text-center">Notes</th>
                        <th class="{{ $entete }} w-16 text-center">Part</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($repartitionMentions as $libelle => $valeur)
                        <tr>
                            <td class="{{ $cellule }}">{{ $libelle }}</td>
                            <td class="{{ $cellule }} text-center">{{ $nombre($valeur) }}</td>
                            <td class="{{ $cellule }} text-center">{{ $pourcent($valeur, $scolarite['notes']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-gris-100">
                        <td class="{{ $cellule }} font-bold uppercase">Moyenne générale</td>
                        <td class="{{ $cellule }} text-center font-bold" colspan="2">
                            {{ $scolarite['moyenne'] !== null ? number_format($scolarite['moyenne'], 2, ',', ' ').' / 20' : '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <table class="w-1/2 self-start border-collapse text-[10px]">
                <thead>
                    <tr>
                        <th class="{{ $entete }}">Classe</th>
                        <th class="{{ $entete }} w-20 text-center">Élèves</th>
                        <th class="{{ $entete }} w-24 text-center">Moyenne</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($scolarite['par_classe']->take(6) as $ligne)
                        <tr>
                            <td class="{{ $cellule }}">{{ $ligne['classe'] }}</td>
                            <td class="{{ $cellule }} text-center">{{ $ligne['eleves'] }}</td>
                            <td class="{{ $cellule }} text-center font-semibold">{{ number_format($ligne['moyenne'], 2, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td class="{{ $cellule }} text-gris-400" colspan="3">Aucune note saisie.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- V. Assiduité et compétences -------------------------------------- --}}
        <p class="{{ $titre }}">V. Assiduité et évaluation par compétences</p>

        <div class="flex items-start gap-4">
            <table class="w-1/3 self-start border-collapse text-[10px]">
                <thead>
                    <tr>
                        <th class="{{ $entete }}" colspan="3">Assiduité — {{ $assiduite['jours'] }} jour(s) d’appel</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($libellesPresence as $cle => $libelle)
                        @php($valeur = (int) ($assiduite['par_statut'][$cle] ?? 0))
                        <tr>
                            <td class="{{ $cellule }}">{{ $libelle }}</td>
                            <td class="{{ $cellule }} w-20 text-center">{{ $nombre($valeur) }}</td>
                            <td class="{{ $cellule }} w-16 text-center">{{ $pourcent($valeur, $assiduite['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="w-1/3 self-start border-collapse text-[10px]">
                <thead>
                    <tr>
                        <th class="{{ $entete }}" colspan="3">Compétences — primaire</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($libellesMaitrise as $cle => $libelle)
                        @php($valeur = (int) ($competences['primaire'][$cle] ?? 0))
                        <tr>
                            <td class="{{ $cellule }}">{{ $libelle }}</td>
                            <td class="{{ $cellule }} w-20 text-center">{{ $nombre($valeur) }}</td>
                            <td class="{{ $cellule }} w-16 text-center">{{ $pourcent($valeur, $competences['primaire_total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="w-1/3 self-start border-collapse text-[10px]">
                <thead>
                    <tr>
                        <th class="{{ $entete }}" colspan="3">Compétences — préprimaire</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($libellesCode as $cle => $libelle)
                        @php($valeur = (int) ($competences['preprimaire'][$cle] ?? 0))
                        <tr>
                            <td class="{{ $cellule }}">{{ $libelle }}</td>
                            <td class="{{ $cellule }} w-20 text-center">{{ $nombre($valeur) }}</td>
                            <td class="{{ $cellule }} w-16 text-center">{{ $pourcent($valeur, $competences['preprimaire_total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Visa --}}
        <div class="mt-5 flex items-start justify-between text-[9px] text-gris-500">
            <span>Édité le {{ now()->format('d/m/Y à H:i') }} — document généré automatiquement.</span>
            <span class="inline-block w-40 text-center">
                <span class="block">{{ $etablissement->principal_title ?? 'Le Chef d’établissement' }}</span>
                <span class="mt-8 block border-t border-gris-400"></span>
            </span>
        </div>
    </div>

@endsection
