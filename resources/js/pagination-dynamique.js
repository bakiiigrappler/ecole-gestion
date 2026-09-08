/*
 * Pagination et filtrage sans rechargement de page.
 *
 * Une liste s'y raccorde en posant `data-liste-dynamique="<identifiant>"` sur
 * le conteneur à remplacer. Les liens de pagination qu'il contient et le
 * formulaire de filtres portant le même identifiant sont interceptés : la page
 * suivante est récupérée en arrière-plan, et seul ce conteneur est remplacé.
 *
 * Le reste — barre latérale, en-tête, position de défilement — ne bouge pas.
 * L'URL est tenue à jour pour que le rechargement et le bouton Précédent du
 * navigateur retombent sur la même liste.
 */

const SELECTEUR_LISTE = '[data-liste-dynamique]';

export function initPaginationDynamique() {
    // Délégation : le contenu étant remplacé, on n'attache rien aux éléments.
    document.addEventListener('click', surClic);
    document.addEventListener('submit', surSoumission);
    window.addEventListener('popstate', surRetourNavigateur);
}

function surClic(evenement) {
    const lien = evenement.target.closest(`${SELECTEUR_LISTE} [data-pagination] a[href]`);

    if (!lien || lien.target === '_blank' || evenement.metaKey || evenement.ctrlKey) {
        return;
    }

    const conteneur = lien.closest(SELECTEUR_LISTE);
    evenement.preventDefault();
    charger(lien.href, conteneur);
}

function surSoumission(evenement) {
    const formulaire = evenement.target.closest('form[data-filtre-dynamique]');

    if (!formulaire) return;

    const conteneur = document.querySelector(
        `[data-liste-dynamique="${formulaire.dataset.filtreDynamique}"]`,
    );

    if (!conteneur) return;

    evenement.preventDefault();

    // Les champs laissés vides n'ont pas à encombrer l'URL.
    const parametres = new URLSearchParams();
    for (const [nom, valeur] of new FormData(formulaire).entries()) {
        if (String(valeur).trim() !== '') parametres.append(nom, valeur);
    }

    const chaine = parametres.toString();
    charger(formulaire.action + (chaine ? `?${chaine}` : ''), conteneur);
}

function surRetourNavigateur() {
    const conteneur = document.querySelector(SELECTEUR_LISTE);
    if (conteneur) charger(window.location.href, conteneur, false);
}

async function charger(url, conteneur, empilerHistorique = true) {
    if (!conteneur) return;

    const voile = conteneur.querySelector('[data-voile-chargement]');
    voile?.removeAttribute('hidden');

    try {
        const reponse = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });

        if (!reponse.ok) throw new Error(`Réponse ${reponse.status}`);

        const document_ = new DOMParser().parseFromString(await reponse.text(), 'text/html');
        const identifiant = conteneur.dataset.listeDynamique;
        const remplacant = document_.querySelector(`[data-liste-dynamique="${identifiant}"]`);

        if (!remplacant) throw new Error('Conteneur absent de la réponse');

        conteneur.innerHTML = remplacant.innerHTML;

        // Le formulaire de filtres vit hors du conteneur : on le resynchronise
        // pour que les champs reflètent l'URL réellement chargée.
        const formulaire = document.querySelector(`form[data-filtre-dynamique="${identifiant}"]`);
        const formulaireRecu = document_.querySelector(`form[data-filtre-dynamique="${identifiant}"]`);
        if (formulaire && formulaireRecu) formulaire.innerHTML = formulaireRecu.innerHTML;

        if (empilerHistorique) window.history.pushState({}, '', url);

        conteneur.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (erreur) {
        // En cas d'échec, on laisse le navigateur faire la navigation complète
        // plutôt que de laisser l'utilisateur devant une liste figée.
        console.error('Chargement de la liste impossible :', erreur);
        window.location.assign(url);
    } finally {
        voile?.setAttribute('hidden', '');
    }
}
