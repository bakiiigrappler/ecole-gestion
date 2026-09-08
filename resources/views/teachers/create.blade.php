@extends('layouts.app')

@section('titre', 'Nouvel enseignant')
@section('sous-titre', 'Le matricule est attribué automatiquement')

@section('contenu')
    @include('teachers._formulaire', ['teacher' => new \App\Models\Teacher])
@endsection
