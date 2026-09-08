@extends('layouts.app')

@section('titre', 'Nouvelle matière')
@section('sous-titre', 'Ajouter une matière au programme d’enseignement')

@section('actions-entete')
    <a href="{{ route('subjects.index') }}" class="bouton-secondaire">Retour à la liste</a>
@endsection

@section('contenu')

@include('subjects._formulaire', ['action' => route('subjects.store')])

@endsection
