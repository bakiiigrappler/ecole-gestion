@props(['rejets'])

{{--
    Ce que l'école a refusé, annoncé au parent.

    Un versement refusé restait invisible : le parent voyait « annulé » dans son
    historique, sans savoir s'il avait mal recopié un chiffre, si son argent
    était perdu, ni ce qu'il devait faire. L'alerte porte le motif et la conduite
    à tenir, et reste affichée tant qu'il ne l'a pas lue — un message qui
    disparaît au premier changement de page n'a averti personne.
--}}

@if ($rejets->isNotEmpty())
    <div class="mb-6 space-y-3">
        @foreach ($rejets as $rejete)
            @php($rejet = $rejete->metadata['rejet'] ?? [])

            <div class="carte overflow-hidden border-corail-300">
                <div class="flex items-start gap-3 border-b border-corail-200 bg-corail-50 px-5 py-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-corail-600" fill="none" stroke="currentColor"
                         stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-corail-900">
                            Versement refusé par l’établissement
                        </h2>
                        <p class="mt-0.5 text-[11px] text-gris-600">
                            {{ number_format((float) $rejete->amount, 0, ',', ' ') }} FCFA déclarés
                            @if ($rejete->student)
                                pour {{ $rejete->student->first_name }} {{ $rejete->student->last_name }}
                            @endif
                            le {{ optional($rejete->created_at)->format('d/m/Y') }}
                            @if ($rejete->gateway_transaction_id)
                                · transaction <span class="font-mono">{{ $rejete->gateway_transaction_id }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="p-5">
                    <p class="text-sm font-semibold text-gris-900">
                        {{ $rejet['libelle'] ?? 'Motif non précisé' }}
                    </p>

                    @if (! empty($rejet['precision']))
                        <p class="mt-1 text-sm leading-relaxed text-gris-700">{{ $rejet['precision'] }}</p>
                    @endif

                    <p class="mt-2 text-sm leading-relaxed text-gris-600">
                        {{ \App\Support\MotifsDeRejet::consigne($rejet['motif'] ?? null) }}
                    </p>

                    <p class="mt-3 rounded-lg bg-gris-50 px-3 py-2 text-[11px] leading-relaxed text-gris-600">
                        Ce versement n’a été porté à aucun dossier : le solde de la scolarité est resté
                        celui d’avant votre déclaration. Si votre compte a bien été débité, présentez le
                        SMS de l’opérateur au secrétariat.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('parent-portal.paiement') }}" class="bouton-primaire">
                            Déclarer de nouveau
                        </a>
                        <a href="{{ route('payments.receipt', $rejete) }}" class="bouton-secondaire">
                            Voir le détail
                        </a>

                        <form method="POST" action="{{ route('parent-portal.rejet-lu', $rejete) }}">
                            @csrf
                            <button type="submit" class="bouton-secondaire">J’ai compris</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
