@extends('layouts.app')

@section('titre', 'Modifier '.$teacher->full_name)
@section('sous-titre', 'Matricule '.$teacher->employee_id)

@section('actions-entete')
    <a href="{{ route('teachers.show', $teacher) }}" class="bouton-secondaire">Voir la fiche</a>
@endsection

@section('contenu')
    @include('teachers._formulaire')
@endsection
