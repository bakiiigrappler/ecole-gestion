@props(['sombre' => false])

{{--
    Le menu du compte connecté.

    Extrait de l'en-tête : les deux dispositions — barre latérale et barre
    d'en-tête — le portent, et il n'a pas à être écrit deux fois. Sur fond
    sombre, le nom et le chevron s'éclaircissent.
--}}

@auth
    <div x-data="{ ouvert: false }" class="relative">
        <button @click="ouvert = !ouvert"
                class="flex cursor-pointer items-center gap-2 rounded-lg p-1.5 transition-colors
                       {{ $sombre ? 'hover:bg-white/10' : 'hover:bg-gris-100' }}">
            <x-avatar :nom="auth()->user()->name" taille="h-8 w-8"/>
            <span class="hidden text-sm font-medium sm:block {{ $sombre ? 'text-white' : 'text-gris-700' }}">
                {{ auth()->user()->name }}
            </span>
            <svg class="h-4 w-4 {{ $sombre ? 'text-gris-400' : 'text-gris-400' }}"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="m19 9-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="ouvert" x-cloak @click.outside="ouvert = false"
             class="absolute right-0 mt-2 w-64 overflow-hidden rounded-xl border border-gris-200 bg-white pb-2 shadow-lg">
            {{-- Emblème de l'application : le menu du compte ne dépend pas de
                 l'établissement où l'on se trouve. --}}
            <div class="flex items-center justify-center border-b border-gris-100 bg-gris-50 py-3">
                <x-logo taille="sm" :application="true"/>
            </div>

            <div class="border-b border-gris-100 px-4 py-2">
                <div class="text-sm font-semibold text-gris-900">{{ auth()->user()->name }}</div>
                <div class="text-xs text-gris-500">{{ auth()->user()->email }}</div>
                <div class="mt-1 inline-flex rounded-full bg-gris-100 px-2 py-0.5 text-[11px] font-semibold text-gris-600">
                    @switch(auth()->user()->role)
                        @case('superadmin') Super administrateur @break
                        @case('admin')      Administrateur @break
                        @case('secretary')  Secrétariat @break
                        @case('teacher')    Enseignant @break
                        @case('parent')     Parent @break
                        @case('student')    Élève @break
                        @default {{ ucfirst(auth()->user()->role ?? 'Utilisateur') }}
                    @endswitch
                </div>
            </div>

            <x-confirmation :action="route('logout')"
                            titre="Se déconnecter ?"
                            message="Votre session sera fermée et vous reviendrez à la page de connexion."
                            confirmer="Se déconnecter"
                            bouton="block w-full px-4 py-2 text-left text-sm text-corail-600 hover:bg-corail-50">
                Se déconnecter
            </x-confirmation>
        </div>
    </div>
@endauth
