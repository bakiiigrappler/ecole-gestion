@extends('layouts.app')

@section('titre', 'Nouvelle série')
@section('sous-titre', 'Ajouter une série au référentiel du lycée')

@section('actions-entete')
    <a href="{{ route('series.index') }}" class="bouton-secondaire">Retour à la liste</a>
@endsection

@section('contenu')

@include('series._formulaire', ['action' => route('series.store')])

@endsection
