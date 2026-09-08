@props([
    'pose' => 'repos',   // repos | chargement | vide | erreur | succes
    'taille' => 'h-24',
    'legende' => null,
    'perchoir' => null,  // null = automatique, true/false pour forcer
    'decor' => false,    // les petites icônes scolaires qui flottent autour
])

@php
    /*
     * Le hibou lettré : mascotte d'Egesco.
     *
     * Un plumage orange, une toque bleu nuit au gland doré, de grands yeux
     * ronds et un plastron en écailles de plumes : la silhouette est ronde et
     * franche, lisible aussi bien en h-16 dans un voile de chargement qu'en
     * h-40 sur une page d'erreur.
     *
     * Le hibou se perche sur un crayon — c'est lui qui pose le dessin et le
     * rattache à l'école. Il disparaît quand la mascotte vole (pose
     * « chargement ») : un oiseau qui bat des ailes ne reste pas sur son
     * perchoir.
     *
     * Les animations sont portées par la classe `hibou--<pose>` et définies
     * dans app.css ; elles s'arrêtent si le système demande moins de mouvement.
     */
    $poses = ['repos', 'chargement', 'vide', 'erreur', 'succes'];
    $pose = in_array($pose, $poses, true) ? $pose : 'repos';

    $surLeCrayon = $perchoir ?? ($pose !== 'chargement');

    // Les dégradés portent un identifiant propre : deux mascottes sur la même
    // page se voleraient leurs couleurs si elles partageaient les mêmes ids.
    $id = 'hibou-'.substr(md5(uniqid('', true)), 0, 8);

    // Sourcils de plumes : c'est eux qui portent l'expression.
    $sourcils = match ($pose) {
        'vide'   => ['M43 61 Q57 49 71 55', 'M107 61 Q93 49 79 55'],   // tombants, air désolé
        'erreur' => ['M43 50 Q57 52 71 61', 'M107 50 Q93 52 79 61'],   // froncés vers le nez
        'succes' => ['M44 56 Q57 43 71 52', 'M106 56 Q93 43 79 52'],   // relevés, arqués
        default  => ['M44 57 Q57 49 71 55', 'M106 57 Q93 49 79 55'],   // neutres
    };

    // Un hibou qui ne regarde pas droit devant paraît vivant.
    $regard = match ($pose) {
        'vide'   => ['dx' => 0, 'dy' => 3],
        'erreur' => ['dx' => -2, 'dy' => 2],
        'succes' => ['dx' => 0, 'dy' => -2],
        default  => ['dx' => 0, 'dy' => 0],
    };

    // Le bec s'ouvre un peu quand la mascotte se réjouit.
    $bec = $pose === 'succes'
        ? 'M67 88 q8 -7 16 0 q-2 15 -8 16 q-6 -1 -8 -16 Z'
        : 'M68 88 q7 -6 14 0 q-2 11 -7 12 q-5 -1 -7 -12 Z';
@endphp

<figure {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2']) }}>
    <svg class="hibou--{{ $pose }} {{ $taille }} w-auto" viewBox="0 0 150 172" fill="none"
         role="img" aria-label="{{ $legende ?? 'Mascotte d’Egesco' }}">

        <defs>
            {{-- Plumage : plus clair en haut à gauche, plus dense vers le bas --}}
            <linearGradient id="{{ $id }}-corps" x1="0.2" y1="0" x2="0.85" y2="1">
                <stop offset="0" stop-color="#ffb457"/>
                <stop offset="0.5" stop-color="#f7941e"/>
                <stop offset="1" stop-color="#dd6f0c"/>
            </linearGradient>

            {{-- Le crème du disque facial et du plastron --}}
            <linearGradient id="{{ $id }}-creme" x1="0.3" y1="0" x2="0.7" y2="1">
                <stop offset="0" stop-color="#fff5e9"/>
                <stop offset="1" stop-color="#ffddbb"/>
            </linearGradient>

            <linearGradient id="{{ $id }}-toque" x1="0" y1="0" x2="0.9" y2="1">
                <stop offset="0" stop-color="#2b5486"/>
                <stop offset="1" stop-color="#12283f"/>
            </linearGradient>

            <linearGradient id="{{ $id }}-bec" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#ffd166"/>
                <stop offset="1" stop-color="#ef9d12"/>
            </linearGradient>

            {{-- L'iris : clair au sommet, profond en bas, comme une bille --}}
            <radialGradient id="{{ $id }}-iris" cx="0.4" cy="0.3" r="0.85">
                <stop offset="0" stop-color="#8fdcfb"/>
                <stop offset="0.6" stop-color="#39a5e0"/>
                <stop offset="1" stop-color="#1462a5"/>
            </radialGradient>

            <linearGradient id="{{ $id }}-crayon" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#5cb0e8"/>
                <stop offset="0.45" stop-color="#2b86cd"/>
                <stop offset="1" stop-color="#175f9c"/>
            </linearGradient>

            {{-- Le plastron est découpé dans le corps : les écailles de plumes
                 débordent volontairement et se font rogner ici. --}}
            <clipPath id="{{ $id }}-plastron">
                <path d="M75 92c19 0 32 15 32 29 0 15-14 25-32 25s-32-10-32-25c0-14 13-29 32-29Z"/>
            </clipPath>
        </defs>

        {{-- ----------------------------------------------------------------
             Le perchoir : un crayon, qui pose la mascotte au sol.
             ---------------------------------------------------------------- --}}
        @if ($surLeCrayon)
            <ellipse cx="75" cy="167" rx="46" ry="4" fill="#122b46" opacity="0.10"/>

            <g>
                {{-- Gomme et virole --}}
                <path d="M12 150h16v18H12a6 6 0 0 1-6-6v-6a6 6 0 0 1 6-6Z" fill="#1e3a5f"/>
                <rect x="26" y="150" width="8" height="18" fill="#f7941e"/>

                {{-- Corps du crayon --}}
                <rect x="34" y="150" width="82" height="18" fill="url(#{{ $id }}-crayon)"/>
                <rect x="34" y="152.5" width="82" height="3" fill="#ffffff" opacity="0.30"/>

                {{-- Bois taillé, puis la mine --}}
                <path d="M116 150 138 159 116 168Z" fill="#f2c48d"/>
                <path d="M131 156.2 138 159l-7 2.8Z" fill="#1e3a5f"/>
            </g>
        @else
            <ellipse cx="75" cy="152" rx="30" ry="5" fill="#122b46" opacity="0.10"/>
        @endif

        <g class="hibou-corps">

            {{-- Aigrettes : deux flammes de plumes, dessinées avant le corps
                 pour s'y fondre, et que la planche de la toque recoupe. --}}
            <path d="M54 42c-13-4-30-3-42 8 11-3 18-1 22 3-9 3-16 10-19 19 11-11 24-15 38-13Z"
                  fill="#f08c14"/>
            <path d="M96 42c13-4 30-3 42 8-11-3-18-1-22 3 9 3 16 10 19 19-11-11-24-15-38-13Z"
                  fill="#f08c14"/>

            {{-- Corps : une seule masse ronde, tête et ventre confondus --}}
            <path d="M75 38c34 0 53 23 53 53 0 33-23 55-53 55s-53-22-53-55c0-30 19-53 53-53Z"
                  fill="url(#{{ $id }}-corps)" stroke="#d1660b" stroke-width="2"/>

            {{-- Ailes : posées sur les flancs, plus foncées que le plumage.
                 Ce sont elles qui battent en pose « chargement ». --}}
            <path class="hibou-aile-g"
                  d="M35 88c-8 6-11 21-8 34 2 10 8 17 15 18-8-16-9-36-7-52Z"
                  fill="#e2740c" stroke="#c85c07" stroke-width="1.6"/>
            <path class="hibou-aile-d"
                  d="M115 88c8 6 11 21 8 34-2 10-8 17-15 18 8-16 9-36 7-52Z"
                  fill="#e2740c" stroke="#c85c07" stroke-width="1.6"/>

            {{-- Plastron crème, puis ses écailles de plumes --}}
            <path d="M75 92c19 0 32 15 32 29 0 15-14 25-32 25s-32-10-32-25c0-14 13-29 32-29Z"
                  fill="url(#{{ $id }}-creme)" stroke="#f0bd8e" stroke-width="1.6"/>

            <g clip-path="url(#{{ $id }}-plastron)">
                <path d="M28 122h96v14q-8 10-16 0-8 10-16 0-8 10-16 0-8 10-16 0-8 10-16 0-8 10-16 0Z"
                      fill="#ffd5ab"/>
                <path d="M28 106h96v14q-8 10-16 0-8 10-16 0-8 10-16 0-8 10-16 0-8 10-16 0-8 10-16 0Z"
                      fill="#ffe6cd"/>
                <path d="M28 88h96v14q-8 10-16 0-8 10-16 0-8 10-16 0-8 10-16 0-8 10-16 0-8 10-16 0Z"
                      fill="#fff3e4"/>
            </g>

            {{-- Pattes : dessinées après le plastron, elles se posent sur le
                 crayon au lieu de disparaître sous le ventre. --}}
            <g fill="#ffb13d" stroke="#d1660b" stroke-width="1.6" stroke-linejoin="round">
                <path d="M61 138h8v10h-8z"/>
                <path d="M81 138h8v10h-8z"/>
                <path d="M53 147h24a3.5 3.5 0 0 1 0 7H53a3.5 3.5 0 0 1 0-7Z"/>
                <path d="M73 147h24a3.5 3.5 0 0 1 0 7H73a3.5 3.5 0 0 1 0-7Z"/>
            </g>

            {{-- Disque facial : deux lobes joints, c'est ce qui fait « hibou » --}}
            <path d="M75 60q-9-12-22-9t-17 20q-4 17 8 26t26 1q13 8 26-1t8-26q-4-17-17-20t-22 9Z"
                  fill="url(#{{ $id }}-creme)" stroke="#f3c294" stroke-width="1.6"/>

            {{-- Yeux --}}
            <circle cx="57" cy="74" r="17" fill="#ffffff" stroke="#f3c294" stroke-width="1.4"/>
            <circle cx="93" cy="74" r="17" fill="#ffffff" stroke="#f3c294" stroke-width="1.4"/>

            <circle cx="{{ 57 + $regard['dx'] }}" cy="{{ 74 + $regard['dy'] }}" r="12" fill="url(#{{ $id }}-iris)"/>
            <circle cx="{{ 93 + $regard['dx'] }}" cy="{{ 74 + $regard['dy'] }}" r="12" fill="url(#{{ $id }}-iris)"/>

            <circle cx="{{ 57 + $regard['dx'] }}" cy="{{ 74 + $regard['dy'] }}" r="6" fill="#0e2036"/>
            <circle cx="{{ 93 + $regard['dx'] }}" cy="{{ 74 + $regard['dy'] }}" r="6" fill="#0e2036"/>

            {{-- Les reflets : c'est eux qui donnent le regard vivant --}}
            <circle cx="{{ 51.5 + $regard['dx'] }}" cy="{{ 68 + $regard['dy'] }}" r="4.6" fill="#ffffff"/>
            <circle cx="{{ 87.5 + $regard['dx'] }}" cy="{{ 68 + $regard['dy'] }}" r="4.6" fill="#ffffff"/>
            <circle cx="{{ 61 + $regard['dx'] }}" cy="{{ 80 + $regard['dy'] }}" r="2.2" fill="#ffffff" opacity="0.8"/>
            <circle cx="{{ 97 + $regard['dx'] }}" cy="{{ 80 + $regard['dy'] }}" r="2.2" fill="#ffffff" opacity="0.8"/>

            {{-- Paupières : elles retombent pour le clignement.
                 Repliées en dur dans l'attribut : sans la feuille de style, un
                 rectangle plein tenait lieu d'yeux. L'animation CSS l'emporte
                 sur le style en ligne, le clignement fonctionne quand même. --}}
            <path class="hibou-paupiere" d="M40 57h34v34H40z" fill="#ffeedc"
                  style="transform-box: fill-box; transform-origin: center top; transform: scaleY(0);"/>
            <path class="hibou-paupiere" d="M76 57h34v34H76z" fill="#ffeedc"
                  style="transform-box: fill-box; transform-origin: center top; transform: scaleY(0);"/>

            {{-- Sourcils : l'expression de la pose --}}
            <path d="{{ $sourcils[0] }}" stroke="#d1660b" stroke-width="3.4" stroke-linecap="round" fill="none"/>
            <path d="{{ $sourcils[1] }}" stroke="#d1660b" stroke-width="3.4" stroke-linecap="round" fill="none"/>

            {{-- Bec --}}
            <path d="{{ $bec }}" fill="url(#{{ $id }}-bec)" stroke="#d98c0a" stroke-width="1.2"/>

            {{-- ------------------------------------------------------------
                 La toque : bandeau d'abord, planche par-dessus.
                 ------------------------------------------------------------ --}}
            <path d="M56 38h38v14q0 7-19 7t-19-7V38Z" fill="#1b3a5e"/>
            <path d="M75 6 133 28 75 50 17 28 75 6Z" fill="url(#{{ $id }}-toque)"/>
            <path d="M75 50 17 28v3.5l58 22 58-22V28L75 50Z" fill="#0d1f33" opacity="0.6"/>
            <path d="M75 12 118 28 75 44 32 28 75 12Z" fill="#ffffff" opacity="0.07"/>

            {{-- Le gland, accroché au coin droit --}}
            <g class="hibou-gland">
                <path d="M136 30v13" stroke="#f7941e" stroke-width="2.8" stroke-linecap="round"/>
                <circle cx="136" cy="46" r="4.5" fill="#f7941e"/>
                <path d="M136 50v5" stroke="#ffc46b" stroke-width="2.4" stroke-linecap="round"/>
            </g>
        </g>

        {{-- ----------------------------------------------------------------
             Les petites icônes scolaires : elles n'apparaissent que si on les
             demande, sur les grands formats où le vide autour se voit.
             ---------------------------------------------------------------- --}}
        @if ($decor)
            {{-- Les marges laterales sont les seules zones libres : les icones
                 s'y logent sans mordre sur la toque ni sur les aigrettes. --}}
            <g>
                <rect x="2" y="80" width="21" height="21" rx="6.5" fill="#2b86cd"/>
                <text x="12.5" y="95.5" text-anchor="middle" font-family="system-ui, sans-serif"
                      font-size="14" font-weight="700" fill="#ffffff">2</text>

                <path d="M12 112l2.7 5.6 5.6 2.7-5.6 2.7-2.7 5.6-2.7-5.6-5.6-2.7 5.6-2.7 2.7-5.6Z"
                      fill="#f7c948"/>

                <text x="138" y="90" text-anchor="middle" font-family="system-ui, sans-serif"
                      font-size="19" font-weight="700" fill="#2b86cd">A</text>

                <rect x="127" y="102" width="21" height="21" rx="6.5" fill="#1e3a5f"/>
                <text x="137.5" y="117.5" text-anchor="middle" font-family="system-ui, sans-serif"
                      font-size="14" font-weight="700" fill="#ffffff">8</text>
            </g>
        @endif

        @if ($pose === 'succes')
            <path class="hibou-etoile" d="M18 30l2.6 5.4 5.4 2.6-5.4 2.6-2.6 5.4-2.6-5.4-5.4-2.6 5.4-2.6 2.6-5.4Z" fill="#f7941e"/>
            <path class="hibou-etoile hibou-etoile--2" d="M136 84l2.1 4.4 4.4 2.1-4.4 2.1-2.1 4.4-2.1-4.4-4.4-2.1 4.4-2.1 2.1-4.4Z" fill="#f7c948"/>
            <path class="hibou-etoile hibou-etoile--3" d="M14 108l1.8 3.7 3.7 1.8-3.7 1.8-1.8 3.7-1.8-3.7-3.7-1.8 3.7-1.8 1.8-3.7Z" fill="#2b86cd"/>
        @endif
    </svg>

    @if ($legende)
        <figcaption class="text-center text-sm text-gris-500">{{ $legende }}</figcaption>
    @endif
</figure>
