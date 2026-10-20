<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1C75BC">

    <title>@yield('titre', 'Résultats') &middot; {{ config('app.name', 'Egesco') }}</title>

    {{--
        Gabarit des pages ouvertes à tous — la consultation des résultats, et
        rien d'autre pour l'instant.

        Il est volontairement nu : ni menu, ni barre latérale, ni Bootstrap, ni
        requête d'établissement. Le jour de la proclamation, tout le pays passe
        par ici en même temps ; chaque octet servi et chaque requête épargnée
        se multiplient par vingt mille.
    --}}
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-gris-50 font-sans text-gris-800 antialiased">

<div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-ogar-100/70 blur-3xl"></div>
    <div class="absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-soleil-100/60 blur-3xl"></div>
</div>

<main class="relative min-h-screen">
    @yield('contenu')
</main>

<footer class="relative border-t border-gris-200 bg-white/70 px-6 py-4 text-center text-[11px] text-gris-500">
    {{ config('app.name', 'Egesco') }} — consultation des résultats d’examen
</footer>

</body>
</html>
