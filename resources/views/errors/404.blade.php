<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page introuvable &middot; {{ $schoolSettings->school_name ?? 'Egesco' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center bg-gris-50 px-4 py-12 font-sans text-gris-800 antialiased">

<main class="w-full max-w-lg text-center">
    <x-mascotte pose="vide" taille="h-40" :decor="true" legende="Erreur 404"/>

    <h1 class="mt-6 text-2xl font-semibold text-ardoise-900">Cette page n’existe pas</h1>
    <p class="mt-2 text-sm leading-relaxed text-gris-500">
        Le lien est peut-être périmé, ou la fiche que vous cherchiez a été supprimée.
    </p>

    <div class="mt-7 flex flex-wrap justify-center gap-2">
        <a href="{{ url()->previous() }}" class="bouton-secondaire">Revenir en arrière</a>
        <a href="{{ url('/') }}" class="bouton-primaire">Retour à l’accueil</a>
    </div>
</main>

</body>
</html>
