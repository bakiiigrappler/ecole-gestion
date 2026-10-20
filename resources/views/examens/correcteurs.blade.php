@extends('layouts.app')

@section('titre', 'Correcteurs — '.$examen->intitule_complet)
@section('sous-titre', $correcteurs->count().' convocation(s)')

@section('actions-entete')
    <a href="{{ route('examens.show', $examen) }}" class="bouton-secondaire">La session</a>
@endsection

@section('contenu')

<div class="grid gap-6 lg:grid-cols-3">

    <div class="space-y-4 lg:col-span-2">
        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Les convoqués</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        Un correcteur ne voit que les copies de son épreuve, sous anonymat.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Enseignant</th>
                            <th>Établissement</th>
                            <th>Épreuve</th>
                            <th>Centre</th>
                            <th>Rôle</th>
                            <th class="text-center">État</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($correcteurs as $correcteur)
                            <tr>
                                <td class="font-medium text-gris-800">
                                    {{ $correcteur->nom_affiche }}
                                    @unless ($correcteur->user_id)
                                        <span class="block text-[11px] text-corail-600">
                                            Sans compte : il ne pourra pas saisir lui-même
                                        </span>
                                    @endunless
                                </td>

                                <td class="text-[11px] text-gris-600">{{ $correcteur->school->name ?? '—' }}</td>
                                <td class="text-gris-700">{{ $correcteur->epreuve->intitule ?? 'Toutes' }}</td>
                                <td class="text-[11px] text-gris-600">{{ $correcteur->centre->nom ?? '—' }}</td>
                                <td class="text-[11px] text-gris-600">{{ $correcteur->libelle_role }}</td>

                                <td class="text-center">
                                    <x-puce :couleur="match ($correcteur->statut) {
                                        'termine' => 'emerald', 'accepte' => 'sky', 'excuse' => 'rose', default => 'slate',
                                    }">{{ $correcteur->libelle_statut }}</x-puce>
                                </td>

                                <td class="text-right">
                                    <x-confirmation :action="route('examens.correcteurs.retirer', [$examen, $correcteur])"
                                                    methode="DELETE"
                                                    titre="Retirer cette convocation ?"
                                                    :message="$correcteur->nom_affiche.' ne sera plus convoqué pour cette épreuve.'"
                                                    confirmer="Retirer"
                                                    bouton="bouton-mini text-corail-600">
                                        Retirer
                                    </x-confirmation>
                                </td>
                            </tr>
                        @empty
                            <x-vide :colonnes="7" message="Aucun correcteur convoqué."/>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <form method="POST" action="{{ route('examens.convoquer', $examen) }}" class="carte overflow-hidden">
            @csrf

            <div class="carte-entete">
                <div>
                    <h2 class="text-sm font-semibold text-gris-900">Convoquer</h2>
                    <p class="mt-0.5 text-xs text-gris-400">
                        L’enseignant reçoit son paquet de copies sur son espace.
                    </p>
                </div>
            </div>

            <div class="space-y-3 p-5">
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Enseignant <span class="text-corail-600">*</span>
                    </label>
                    <select name="teacher_id" required class="champ w-full text-sm">
                        @foreach ($enseignants as $enseignant)
                            <option value="{{ $enseignant->id }}">
                                {{ $enseignant->last_name }} {{ $enseignant->first_name }}
                                @if ($enseignant->school) — {{ $enseignant->school->name }} @endif
                                @unless ($enseignant->user_id) (sans compte) @endunless
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Épreuve
                    </label>
                    <select name="epreuve_id" class="champ w-full text-sm">
                        <option value="">Toutes (jury)</option>
                        @foreach ($examen->epreuves as $epreuve)
                            <option value="{{ $epreuve->id }}">{{ $epreuve->intitule }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Centre de correction
                    </label>
                    <select name="centre_id" class="champ w-full text-sm">
                        <option value="">Tous les centres</option>
                        @foreach ($examen->centres as $centre)
                            <option value="{{ $centre->id }}">{{ $centre->nom }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-gris-400">
                        Sans centre, il corrige toutes les copies de l’épreuve.
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                        Rôle
                    </label>
                    <select name="role" class="champ w-full text-sm">
                        @foreach (\App\Models\ExamenCorrecteur::ROLES as $cle => $libelle)
                            <option value="{{ $cle }}">{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="border-t border-gris-100 p-5">
                <button type="submit" class="bouton-primaire w-full justify-center">Convoquer</button>
            </div>
        </form>
    </div>
</div>

@endsection
