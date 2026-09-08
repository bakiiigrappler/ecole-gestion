@extends('layouts.app')

@section('titre', 'Nouveau parent')
@section('sous-titre', 'Rattachez-le à au moins un élève')

@section('actions-entete')
    <a href="{{ route('parents.index') }}" class="bouton-secondaire">Retour à la liste</a>
@endsection

@section('contenu')
    @include('parents._formulaire', ['parent' => new \App\Models\ParentModel])
@endsection
