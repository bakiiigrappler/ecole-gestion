@extends('layouts.app')

@section('titre', 'Modifier '.$subject->name)
@section('sous-titre', 'Code '.$subject->code)

@section('actions-entete')
    <a href="{{ route('subjects.show', $subject->id) }}" class="bouton-secondaire">Voir la fiche</a>
@endsection

@section('contenu')

@include('subjects._formulaire', ['action' => route('subjects.update', $subject->id)])

@endsection
