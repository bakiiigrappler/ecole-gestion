@extends('layouts.app')

@section('titre', 'Nouveau frais')
@section('sous-titre', 'Ajouter une ligne à la grille tarifaire')

@section('actions-entete')
    <a href="{{ route('fees.index') }}" class="bouton-secondaire">Retour à la liste</a>
@endsection

@section('contenu')

<form method="POST" action="{{ route('fees.store') }}" class="grid gap-6 lg:grid-cols-3">
    @csrf
    @include('fees._formulaire', ['fee' => null])
</form>

@endsection
