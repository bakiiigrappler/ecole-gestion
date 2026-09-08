@extends('layouts.app')

@section('titre', 'Modifier — '.$fee->name)
@section('sous-titre', number_format($fee->amount, 0, ',', ' ').' FCFA · '.($frequencies[$fee->frequency] ?? $fee->frequency))

@section('actions-entete')
    <a href="{{ route('fees.show', $fee) }}" class="bouton-secondaire">Voir le frais</a>
    <a href="{{ route('fees.index') }}" class="bouton-secondaire">Retour à la liste</a>
@endsection

@section('contenu')

<form method="POST" action="{{ route('fees.update', $fee) }}" class="grid gap-6 lg:grid-cols-3">
    @csrf
    @method('PUT')
    @include('fees._formulaire')
</form>

@endsection
