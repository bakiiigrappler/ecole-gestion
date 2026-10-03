{{--
    Le choix d'après la 3ème : une voie, puis une filière.

    Les voies conseillées viennent en tête, chacune avec la raison qui la rend
    conseillée ou non — une voie écartée sans explication se contourne par la
    rumeur, une voie expliquée se discute avec un professeur.
--}}

<div class="carte overflow-hidden">
    <div class="carte-entete">
        <div>
            <h2 class="text-sm font-semibold text-gris-900">La voie</h2>
            <p class="mt-0.5 text-xs text-gris-400">
                Quatre routes sortent de la 3ème. Elles ne mènent pas aux mêmes métiers.
            </p>
        </div>

        @unless ($fige)
            <a href="{{ route('orientation.mon-dossier', array_filter([
                    'eleve' => $estParent ? $eleve->id : null,
                    'pratique' => $pratique ? null : 1,
                ])) }}"
               class="text-xs font-semibold text-ogar-700 hover:underline">
                {{ $pratique ? 'Je préfère la théorie' : 'Je préfère la pratique' }}
            </a>
        @endunless
    </div>

    <div class="grid gap-2 p-5 sm:grid-cols-2">
        @foreach ($voies as $voie)
            <label class="cursor-pointer">
                <input type="radio" name="voie" value="{{ $voie['voie'] }}" class="peer sr-only"
                       @checked(old('voie', $dossier?->voie) === $voie['voie'])
                       @disabled($fige)
                       onchange="this.form.submit()">
                <span class="block h-full rounded-xl border px-4 py-3 transition
                             peer-checked:border-ogar-500 peer-checked:bg-ogar-50
                             {{ $voie['recommandee'] ? 'border-emerald-300' : 'border-gris-200' }}">
                    <span class="flex items-start justify-between gap-2">
                        <span class="block text-sm font-semibold text-gris-800">{{ $voie['libelle'] }}</span>
                        @if ($voie['recommandee'])
                            <x-puce couleur="emerald">Conseillée</x-puce>
                        @endif
                    </span>
                    <span class="mt-0.5 block text-[11px] text-gris-500">{{ $voie['detail'] }}</span>
                    <span class="mt-1.5 block text-[11px] leading-snug text-gris-600">{{ $voie['raison'] }}</span>
                </span>
            </label>
        @endforeach
    </div>

    <p class="border-t border-gris-100 px-5 py-2 text-[11px] text-gris-400">
        Choisir une voie recharge la page : les filières et les établissements qui s’affichent en dépendent.
    </p>
</div>

{{-- La filière, dès qu'une voie est choisie --}}
@if ($filieres)
    <div class="carte overflow-hidden">
        <div class="carte-entete">
            <div>
                <h2 class="text-sm font-semibold text-gris-900">La filière</h2>
                <p class="mt-0.5 text-xs text-gris-400">
                    Chacune dit la moyenne qu’elle demande — et ce qu’elle ouvre comme métiers.
                </p>
            </div>
        </div>

        <div class="divide-y divide-gris-100">
            @foreach ($filieres as $filiere)
                <label class="flex cursor-pointer items-start gap-3 px-5 py-3 transition hover:bg-gris-50">
                    <input type="radio" name="filiere" value="{{ $filiere['nom'] }}" class="mt-1 shrink-0"
                           @checked(old('filiere', $dossier?->filiere) === $filiere['nom'])
                           @disabled($fige)>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-gris-900">{{ $filiere['nom'] }}</span>

                            @if ($filiere['etat'] === 'conseillee')
                                <x-puce couleur="emerald">Conseillée pour votre profil</x-puce>
                            @elseif ($filiere['etat'] === 'concours')
                                <x-puce couleur="amber">Sur concours</x-puce>
                            @else
                                <x-puce couleur="slate">Ouverte</x-puce>
                            @endif
                        </div>

                        <p class="mt-0.5 text-[11px] leading-snug text-gris-600">{{ $filiere['mention'] }}</p>

                        @if ($filiere['debouches'])
                            <p class="mt-0.5 text-[11px] leading-snug text-gris-500">
                                Débouchés : {{ $filiere['debouches'] }}
                            </p>
                        @endif
                    </div>
                </label>
            @endforeach
        </div>
    </div>
@endif
