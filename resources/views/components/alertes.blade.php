{{--
    Messages flash de l'application. Les contrôleurs utilisent 'success',
    'error' et 'info' ; 'warning' est accepté pour les alertes non bloquantes.
--}}

@if (session('success'))
    <div class="mb-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="mb-4 flex items-start gap-3 rounded-xl border border-corail-200 bg-corail-50 px-4 py-3 text-sm text-corail-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if (session('warning'))
    <div class="mb-4 flex items-start gap-3 rounded-xl border border-soleil-200 bg-soleil-50 px-4 py-3 text-sm text-soleil-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
        <span>{{ session('warning') }}</span>
    </div>
@endif

@if (session('info'))
    <div class="mb-4 rounded-xl border border-ogar-200 bg-ogar-50 px-4 py-3 text-sm text-ogar-800">
        {{ session('info') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-xl border border-corail-200 bg-corail-50 px-4 py-3 text-sm text-corail-800">
        <div class="font-semibold">Le formulaire contient {{ $errors->count() }} erreur(s) :</div>
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $erreur)
                <li>{{ $erreur }}</li>
            @endforeach
        </ul>
    </div>
@endif
