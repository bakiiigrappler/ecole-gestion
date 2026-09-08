@extends('layouts.app')

@section('titre', 'Modifier '.$series->name)
@section('sous-titre', $series->code.' — '.$series->level)

@section('actions-entete')
    <a href="{{ route('series.show', $series->id) }}" class="bouton-secondaire">Voir la fiche</a>
@endsection

@section('contenu')

@include('series._formulaire', ['action' => route('series.update', $series->id)])

@endsection
