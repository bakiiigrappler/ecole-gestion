/*
 * Export d'un bloc de page en PDF (bulletin, emploi du temps, etc.).
 *
 * Le PDF était rendu par dompdf à partir d'un gabarit séparé : deux documents à
 * maintenir, et une mise en page qui divergeait de celle affichée à l'écran.
 * Ici la page elle-même est photographiée puis posée sur une feuille A4, ce qui
 * garantit que le PDF est exactement ce que l'utilisateur voit.
 *
 * Accroche : un bouton `data-export-pdf="<id du bloc>"`, avec cinq options
 * facultatives — `data-nom-fichier`, `data-format="a5"` (un reçu tient sur une
 * demi-feuille), `data-marge` (en millimètres), `data-orientation="paysage"` (une grille
 * hebdomadaire ne tient pas en portrait) et `data-page-unique` (le document est
 * réduit pour tenir sur une seule feuille au lieu d'être déroulé).
 *
 * Un document en plusieurs feuillets — une page de garde puis la grille, par
 * exemple — marque chacun de ses blocs d'un `data-page-pdf` : ils deviennent
 * alors une feuille chacun, dans l'ordre du balisage.
 */

/*
 * Marge de la feuille, en millimètres.
 *
 * Nulle : ces documents portent déjà leur propre marge intérieure — c'est elle
 * qui fait la page, et c'est elle qu'on voit à l'aperçu. Toute bordure ajoutée
 * ici forme un second cadre et rétrécit le document d'autant : à l'écran il
 * remplissait la largeur, sur le PDF il flottait au milieu. Le PDF doit être
 * l'aperçu, à l'identique.
 *
 * Rien n'est perdu au tirage : le contenu ne touche jamais le bord, la marge
 * intérieure du document le tient à distance. `data-marge` permet d'en demander
 * une au cas par cas.
 */
const MARGE_DEFAUT_MM = 0;

/* Formats de papier admis, en millimètres, orientation portrait. */
const FORMATS = {
    a4: { largeur: 210, hauteur: 297 },
    a5: { largeur: 148, hauteur: 210 },
};

/*
 * Dimensions utiles de la feuille, selon le format et l'orientation demandés.
 *
 * Un reçu ou une autorisation d'entrée tient sur une demi-feuille : les tirer
 * en A4 gaspille le papier et donne un document mou. `data-format` choisit le
 * grain, `data-orientation` le sens.
 */
function feuille(format, orientation) {
    const papier = FORMATS[format] ?? FORMATS.a4;

    return orientation === 'paysage'
        ? { largeur: papier.hauteur, hauteur: papier.largeur }
        : { largeur: papier.largeur, hauteur: papier.hauteur };
}

/*
 * Charge les outils à la demande : ils ne pèsent que sur les pages qui exportent.
 *
 * `html2canvas-pro` plutôt que `html2canvas` : Tailwind 4 exprime ses couleurs
 * en `oklch()`, que la version d'origine refuse d'analyser — « Attempting to
 * parse an unsupported color function "oklch" » — ce qui faisait échouer toute
 * capture. Le fork maintenu comprend oklch, lab et color-mix.
 */
async function outils() {
    const [{ default: html2canvas }, { jsPDF }] = await Promise.all([
        import('html2canvas-pro'),
        import('jspdf'),
    ]);

    return { html2canvas, jsPDF };
}

/* Photographie un element, deplie meme s'il est dans un onglet masque. */
async function photographier(html2canvas, element) {
    return html2canvas(element, {
        scale: 2,                 // net à l'impression
        useCORS: true,
        backgroundColor: '#ffffff',
        logging: false,
        onclone: (copie, clone) => {
            clone.style.display = 'block';
            clone.style.width = `${element.offsetWidth}px`;
        },
    });
}

/* Pose une capture sur la feuille, centree et reduite pour y tenir. */
function poser(pdf, capture, page, marge) {
    const largeurUtile = page.largeur - marge * 2;
    const hauteurUtile = page.hauteur - marge * 2;

    let largeur = largeurUtile;
    let hauteur = (capture.height * largeur) / capture.width;

    if (hauteur > hauteurUtile) {
        hauteur = hauteurUtile;
        largeur = (capture.width * hauteur) / capture.height;
    }

    pdf.addImage(
        capture.toDataURL('image/jpeg', 0.95),
        'JPEG',
        marge + (largeurUtile - largeur) / 2,
        marge,
        largeur,
        hauteur,
    );
}

async function exporter(bouton) {
    const bloc = document.getElementById(bouton.dataset.exportPdf);
    if (!bloc) return;

    const libelleInitial = bouton.innerHTML;
    bouton.disabled = true;
    bouton.innerHTML = 'Génération…';

    try {
        const { html2canvas, jsPDF } = await outils();


        const orientation = bouton.dataset.orientation === 'paysage' ? 'paysage' : 'portrait';
        const format = FORMATS[bouton.dataset.format] ? bouton.dataset.format : 'a4';
        const page = feuille(format, orientation);

        const marge = Number.isFinite(parseFloat(bouton.dataset.marge))
            ? Math.max(0, parseFloat(bouton.dataset.marge))
            : MARGE_DEFAUT_MM;

        const pdf = new jsPDF({
            orientation: orientation === 'paysage' ? 'landscape' : 'portrait',
            unit: 'mm',
            format,
        });

        // Document en plusieurs feuillets : une feuille par bloc marqué.
        const feuillets = bloc.querySelectorAll('[data-page-pdf]');


        if (feuillets.length > 0) {
            for (const [rang, feuillet] of [...feuillets].entries()) {
                if (rang > 0) {
                    pdf.addPage();
                }

                poser(pdf, await photographier(html2canvas, feuillet), page, marge);
            }

            pdf.save(bouton.dataset.nomFichier || 'document.pdf');

            return;
        }

        const capture = await photographier(html2canvas, bloc);

        const largeur = page.largeur - marge * 2;
        const hauteur = (capture.height * largeur) / capture.width;
        const hauteurUtile = page.hauteur - marge * 2;
        const image = capture.toDataURL('image/jpeg', 0.95);

        if (hauteur <= hauteurUtile) {
            pdf.addImage(image, 'JPEG', marge, marge, largeur, hauteur);
        } else if ('pageUnique' in bouton.dataset) {
            /* Un document qui se lit d'un seul tenant — une grille horaire,
               une fiche — perd tout sens coupé en deux : on le réduit pour
               qu'il tienne, et on le centre sur la feuille. */
            const reduction = hauteurUtile / hauteur;
            const largeurReduite = largeur * reduction;

            pdf.addImage(
                image,
                'JPEG',
                marge + (largeur - largeurReduite) / 2,
                marge,
                largeurReduite,
                hauteurUtile,
            );
        } else {
            // Document plus haut qu'une page : on le déroule page par page.
            let reste = hauteur;
            let position = marge;

            while (reste > 0) {
                pdf.addImage(image, 'JPEG', marge, position, largeur, hauteur);
                reste -= hauteurUtile;
                position -= hauteurUtile;

                if (reste > 0) {
                    pdf.addPage();
                }
            }
        }

        pdf.save(bouton.dataset.nomFichier || 'document.pdf');
    } catch (erreur) {
        console.error('Export PDF impossible', erreur);
        alert('La génération du PDF a échoué. Vous pouvez imprimer la page via votre navigateur.');
    } finally {
        bouton.disabled = false;
        bouton.innerHTML = libelleInitial;
    }
}

export function initExportPdf() {
    document.addEventListener('click', (evenement) => {
        const bouton = evenement.target.closest('[data-export-pdf]');
        if (bouton) {
            evenement.preventDefault();
            exporter(bouton);
        }
    });
}
