@props([
    'payment',
    'bouton' => 'bouton-secondaire text-corail-600',
])

{{--
    Refuser un versement déclaré, en disant pourquoi.

    Le motif est obligatoire : c'est la seule phrase que le parent lira pour
    comprendre ce qui s'est passé et ce qu'il doit faire. Les cas qui reviennent
    au guichet sont proposés d'avance, et un motif libre reste possible — la
    liste ne prévoit jamais tout.

    Le panneau est téléporté dans <body> pour ne pas être rogné par les
    conteneurs à défilement.
--}}

@php
    $identifiant = 'rejet-'.$payment->id;
    $catalogue = \App\Support\MotifsDeRejet::CATALOGUE;
@endphp

<div x-data="{
        ouvert: false,
        motif: '{{ old('motif', array_key_first($catalogue)) }}',
        get precisionExigee() { return this.motif === 'autre'; }
     }"
     class="contents">

    <button type="button" @click="ouvert = true" class="cursor-pointer {{ $bouton }}"
            aria-haspopup="dialog" :aria-expanded="ouvert">{{ $slot->isEmpty() ? 'Refuser' : $slot }}</button>

    <template x-teleport="body">
        <div x-show="ouvert" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="{{ $identifiant }}-titre"
             @keydown.escape.window="ouvert = false">

            <div x-show="ouvert" x-transition.opacity.duration.200ms
                 class="absolute inset-0 bg-gris-900/50 motion-reduce:transition-none"
                 @click="ouvert = false"></div>

            <div x-show="ouvert"
                 x-transition:enter="transition duration-200 ease-out motion-reduce:transition-none"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl">

                <form method="POST" action="{{ route('payments.cancel', $payment) }}">
                    @csrf

                    <div class="border-b border-gris-100 px-6 py-4">
                        <h2 id="{{ $identifiant }}-titre" class="text-base font-semibold text-gris-900">
                            Refuser ce versement
                        </h2>
                        <p class="mt-1 text-sm text-gris-600">
                            {{ number_format((float) $payment->amount, 0, ',', ' ') }} FCFA déclarés
                            @if ($payment->student)
                                pour {{ $payment->student->first_name }} {{ $payment->student->last_name }}
                            @endif
                            @if ($payment->gateway_transaction_id)
                                · transaction <span class="font-mono">{{ $payment->gateway_transaction_id }}</span>
                            @endif
                        </p>
                    </div>

                    <div class="max-h-[60vh] space-y-2 overflow-y-auto px-6 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                            Motif <span class="text-corail-600">*</span>
                        </p>

                        @foreach ($catalogue as $cle => $motif)
                            <label class="block cursor-pointer">
                                <input type="radio" name="motif" value="{{ $cle }}" class="peer sr-only"
                                       x-model="motif">
                                <span class="block rounded-xl border border-gris-200 px-4 py-2.5 transition
                                             peer-checked:border-corail-400 peer-checked:bg-corail-50">
                                    <span class="block text-sm font-medium text-gris-800">{{ $motif['libelle'] }}</span>
                                    <span class="mt-0.5 block text-[11px] leading-snug text-gris-500">
                                        Le parent lira : « {{ $motif['consigne'] }} »
                                    </span>
                                </span>
                            </label>
                        @endforeach

                        <div class="pt-1">
                            <label for="{{ $identifiant }}-precision"
                                   class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gris-500">
                                Précision
                                <span x-show="precisionExigee" class="text-corail-600">*</span>
                                <span x-show="! precisionExigee" class="font-normal normal-case tracking-normal text-gris-400">
                                    — facultative
                                </span>
                            </label>
                            <textarea name="precision" id="{{ $identifiant }}-precision" rows="3"
                                      class="champ w-full text-sm"
                                      :required="precisionExigee"
                                      placeholder="Ce que le parent doit savoir ou faire.">{{ old('precision') }}</textarea>
                            <p class="mt-1 text-[11px] text-gris-400">
                                S’ajoute au motif, sur l’écran du parent.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gris-100 px-6 py-4">
                        <button type="button" @click="ouvert = false" class="bouton-secondaire cursor-pointer">
                            Annuler
                        </button>
                        <button type="submit" class="bouton-danger cursor-pointer">
                            Refuser le versement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
