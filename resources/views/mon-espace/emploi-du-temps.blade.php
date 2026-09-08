@extends('layouts.app')

@section('titre', 'Mon emploi du temps')
@section('sous-titre', (optional($inscription?->schoolClass)->name ?? 'sans classe').' — '.($annee->name ?? ''))

@section('actions-entete')
    <a href="{{ route('mon-espace') }}" class="bouton-secondaire">Mon tableau de bord</a>
@endsection

@section('contenu')

    <x-emploi-du-temps :creneaux="$creneaux"
                       titre="Emploi du temps"
                       :sous-titre="trim($eleve->first_name.' '.$eleve->last_name.' · '.(optional($inscription?->schoolClass)->name ?? ''), ' ·')"
                       :annee="$annee"
                       complement="enseignant"
                       fichier="Mon_emploi_du_temps.pdf"/>

@endsection
