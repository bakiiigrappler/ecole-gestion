@extends('layouts.app')

@section('titre', 'Compte à rattacher')
@section('sous-titre', 'Votre accès existe, il attend d’être relié à votre dossier')

@section('contenu')

<div class="carte mx-auto max-w-2xl p-8 text-center">
    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-soleil-100 text-soleil-700">
        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
        </svg>
    </span>

    <h2 class="mt-4 text-lg font-semibold text-gris-900">
        Votre compte n’est pas encore relié à votre dossier
    </h2>

    <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-gris-600">
        {{ $utilisateur->name }}, votre accès fonctionne — c’est le rattachement à votre fiche de
        parent qui manque. Sans elle, l’application ne sait pas quels enfants sont les vôtres, et
        n’a donc ni notes, ni absences, ni scolarité à vous montrer.
    </p>

    <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-gris-600">
        Le secrétariat fait ce lien depuis le module <span class="font-medium text-gris-800">Parents</span>,
        en quelques secondes. Signalez-le-lui en indiquant l’adresse
        <span class="font-medium text-gris-800">{{ $utilisateur->email ?: $utilisateur->matricule }}</span>.
    </p>

    @if ($reglages->school_phone ?? null)
        <p class="mt-4 text-sm text-gris-700">
            {{ $reglages->school_name ?? 'Secrétariat' }} :
            <span class="font-semibold tabular-nums">{{ $reglages->school_phone }}</span>
            @if ($reglages->school_email ?? null)
                · <a href="mailto:{{ $reglages->school_email }}" class="text-ogar-700 hover:underline">
                    {{ $reglages->school_email }}
                </a>
            @endif
        </p>
    @endif

    <form method="GET" action="{{ route('parent-portal.dashboard') }}" class="mt-6">
        <button type="submit" class="bouton-secondaire">Réessayer</button>
    </form>
</div>

@endsection
