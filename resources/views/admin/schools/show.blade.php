@extends('layouts.app')

@section('titre', $school->name)
@section('sous-titre', 'Code '.$school->code.' · '.($school->city ?? '—').' — '.implode(', ', $school->libellesDesCycles() ?: ['aucun cycle']))

@section('actions-entete')
    <a href="{{ route('admin.schools.edit', $school) }}" class="bouton-secondaire">Modifier</a>
    <a href="{{ route('admin.schools.index') }}" class="bouton-primaire">Tous les établissements</a>
@endsection

@section('contenu')

@php
    $nombre = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $courante = \App\Support\EcoleCourante::id();

    $libellesRole = [
        'admin' => 'Administrateur', 'secretary' => 'Secrétariat',
        'teacher' => 'Enseignant', 'parent' => 'Parent', 'student' => 'Élève',
    ];

    // Classes écrites en entier : Tailwind ne compile pas une teinte interpolée.
    $puceCycle = [
        'preprimaire' => 'amber', 'primaire' => 'emerald',
        'college' => 'sky', 'lycee' => 'violet',
    ];

    $administrateur = $comptes->firstWhere('role', 'admin');
@endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-statistique libelle="Élèves" :valeur="$nombre($chiffres['eleves'])"
                       :detail="$chiffres['classes'].' classe(s)'" couleur="ogar"/>
        <x-statistique libelle="Enseignants" :valeur="$chiffres['enseignants']"
                       detail="En activité" couleur="emerald"/>
        <x-statistique libelle="Comptes" :valeur="$chiffres['comptes']"
                       detail="Rattachés à cet établissement" couleur="violet"/>
        <x-statistique libelle="État" :valeur="$school->is_active ? 'Actif' : 'Désactivé'"
                       :detail="$school->is_active ? 'Accès ouvert' : 'Accès suspendu'"
                       :couleur="$school->is_active ? 'emerald' : 'rose'"/>
    </div>

    @unless ($administrateur)
        <div class="mt-4 flex items-start gap-3 rounded-xl border border-corail-200 bg-corail-50 px-4 py-3 text-sm text-corail-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span>
                <strong>Cet établissement n’a pas d’administrateur.</strong>
                Personne ne peut y entrer tant qu’un compte n’a pas été créé.
            </span>
        </div>
    @endunless

    <div class="mt-6 grid items-start gap-4 lg:grid-cols-3">

        <div class="carte p-5">
            <h2 class="mb-1 text-sm font-semibold text-gris-900">Identité</h2>
            <p class="mb-3 text-[11px] text-gris-400">
                Les coordonnées sont tenues par l’administrateur de l’établissement.
            </p>

            @php($parametres = $school->settings)

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Code</dt>
                    <dd class="font-mono font-medium text-gris-800">{{ $school->code }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Ville</dt>
                    <dd class="font-medium text-gris-800">{{ $school->city ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Téléphone</dt>
                    <dd class="font-medium text-gris-800">{{ $parametres->school_phone ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Courriel</dt>
                    <dd class="truncate font-medium text-gris-800">{{ $parametres->school_email ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Boîte postale</dt>
                    <dd class="font-medium text-gris-800">{{ $parametres->school_bp ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gris-500">Direction</dt>
                    <dd class="truncate font-medium text-gris-800">{{ $parametres->principal_name ?? '—' }}</dd>
                </div>
            </dl>

            @if ($school->notes)
                <p class="mt-4 rounded-lg bg-gris-50 p-3 text-xs leading-relaxed text-gris-600">{{ $school->notes }}</p>
            @endif
        </div>

        <div class="carte p-5">
            <h2 class="mb-3 text-sm font-semibold text-gris-900">Cycles ouverts</h2>

            <div class="flex flex-wrap gap-2">
                @forelse ($school->cyclesOuverts() as $cycle)
                    <x-puce :couleur="$puceCycle[$cycle] ?? 'slate'">{{ \App\Models\School::CYCLES[$cycle] }}</x-puce>
                @empty
                    <span class="text-sm text-corail-600">Aucun cycle ouvert.</span>
                @endforelse
            </div>

            <div class="mt-5 space-y-2 border-t border-gris-100 pt-4">
                @if ((int) $courante !== $school->id)
                    <form method="POST" action="{{ route('admin.schools.basculer') }}">
                        @csrf
                        <input type="hidden" name="school_id" value="{{ $school->id }}">
                        <button type="submit" class="bouton-primaire w-full justify-center">Travailler dans cet établissement</button>
                    </form>
                @else
                    <p class="rounded-lg bg-ogar-50 p-3 text-center text-xs text-ogar-800">
                        Vous travaillez dans cet établissement.
                    </p>
                    <form method="POST" action="{{ route('admin.schools.basculer') }}">
                        @csrf
                        <button type="submit" class="bouton-secondaire w-full justify-center">Revenir à la vue d’ensemble</button>
                    </form>
                @endif

                <x-confirmation :action="route('admin.schools.toggle-status', $school)"
                                methode="POST"
                                :titre="$school->is_active ? 'Désactiver cet établissement ?' : 'Réactiver cet établissement ?'"
                                :message="$school->is_active
                                    ? 'Les comptes de « '.$school->name.' » perdront l’accès. Aucune donnée n’est effacée.'
                                    : 'Les comptes de « '.$school->name.' » retrouveront l’accès.'"
                                :confirmer="$school->is_active ? 'Désactiver' : 'Réactiver'"
                                :ton="$school->is_active ? 'danger' : 'primaire'"
                                bouton="bouton-secondaire w-full justify-center">
                    {{ $school->is_active ? 'Désactiver l’établissement' : 'Réactiver l’établissement' }}
                </x-confirmation>
            </div>
        </div>

        <div class="carte overflow-hidden">
            <div class="carte-entete">
                <h2 class="text-sm font-semibold text-gris-900">Comptes rattachés</h2>
                <span class="text-xs text-gris-400">{{ $comptes->count() }}</span>
            </div>

            <div class="max-h-96 divide-y divide-gris-100 overflow-y-auto">
                @forelse ($comptes as $compte)
                    <div class="flex items-center gap-3 px-5 py-2.5">
                        <x-avatar :nom="$compte->name" class="h-8 w-8 shrink-0 text-[10px]"/>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gris-800">{{ $compte->name }}</p>
                            <p class="truncate text-[11px] text-gris-400">{{ $compte->email ?? $compte->matricule }}</p>
                        </div>
                        <x-puce :couleur="$compte->role === 'admin' ? 'violet' : 'slate'">
                            {{ $libellesRole[$compte->role] ?? $compte->role }}
                        </x-puce>
                    </div>
                @empty
                    <p class="p-5 text-sm text-gris-400">Aucun compte rattaché.</p>
                @endforelse
            </div>
        </div>
    </div>

@endsection
