@extends('layouts.app')

@section('titre', 'Modifier '.$parent->first_name.' '.$parent->last_name)
@section('sous-titre', $parent->students->count().' enfant(s) rattaché(s)')

@section('actions-entete')
    <a href="{{ route('parents.show', $parent->id) }}" class="bouton-secondaire">Voir la fiche</a>
@endsection

@section('contenu')
    @include('parents._formulaire')
@endsection
