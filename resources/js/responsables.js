/*
 * Rattachement des responsables légaux depuis la fiche d'un élève.
 *
 * Chaque ligne désigne soit un parent déjà enregistré — retrouvé par recherche
 * plutôt que dans une liste de plusieurs centaines d'entrées — soit une fiche
 * créée au vol avec le strict nécessaire.
 *
 * Le lien de parenté et les autorisations sont portés par le rattachement :
 * ils décrivent la relation à CET élève, pas la personne.
 */

const LIBELLES_LIEN = {
    father: 'Père',
    mother: 'Mère',
    guardian: 'Tuteur ou tutrice',
    other: 'Autre',
};

export function responsablesDeLEleve(parents = []) {
    return {
        parents,
        lignes: [],

        init() {
            // Une première ligne ouverte : rattacher un responsable est le cas
            // courant, en ouvrir une évite un clic à chaque création.
            if (! this.lignes.length) this.ajouter();
        },

        ajouter() {
            this.lignes.push({
                mode: 'existant',
                parent_id: '',
                first_name: '',
                last_name: '',
                gender: '',
                phone: '',
                relationship_type: 'father',
                // Le premier responsable saisi est le contact principal par défaut.
                is_primary_contact: this.lignes.length === 0,
                lives_with_student: true,
                can_pickup: true,
                recherche: '',
                ouvert: false,
                surligne: 0,
            });
        },

        retirer(rang) {
            this.lignes.splice(rang, 1);
        },

        // --- Sélecteur de responsable existant ------------------------------

        parentDe(rang) {
            return this.parents.find((p) => String(p.id) === String(this.lignes[rang].parent_id)) || null;
        },

        resultats(rang) {
            const motif = (this.lignes[rang].recherche || '').trim().toLowerCase();
            const pris = this.lignes
                .filter((_, i) => i !== rang)
                .map((ligne) => String(ligne.parent_id));

            const libres = this.parents.filter((p) => ! pris.includes(String(p.id)));

            if (! motif) return libres.slice(0, 40);

            return libres
                .filter((p) => [p.nom, p.telephone, p.email]
                    .some((v) => (v || '').toLowerCase().includes(motif)))
                .slice(0, 40);
        },

        choisir(rang, parent) {
            const ligne = this.lignes[rang];
            ligne.parent_id = String(parent.id);
            ligne.recherche = '';
            ligne.ouvert = false;
            ligne.gender = parent.sexe;
            this.revaliderLien(rang);
        },

        effacer(rang) {
            const ligne = this.lignes[rang];
            ligne.parent_id = '';
            ligne.recherche = '';
            ligne.ouvert = true;
        },

        naviguer(rang, pas) {
            const ligne = this.lignes[rang];
            if (! ligne.ouvert) { ligne.ouvert = true; return; }

            const total = this.resultats(rang).length;
            if (! total) return;
            ligne.surligne = (ligne.surligne + pas + total) % total;
        },

        valider(rang) {
            const parent = this.resultats(rang)[this.lignes[rang].surligne];
            if (parent) this.choisir(rang, parent);
        },

        // --- Cohérence du lien ----------------------------------------------

        /* Un homme ne peut pas être « mère », ni une femme « père ». */
        liensPossibles(rang) {
            const sexe = this.lignes[rang].gender;
            const exclu = sexe === 'male' ? 'mother' : (sexe === 'female' ? 'father' : null);

            return Object.entries(LIBELLES_LIEN)
                .filter(([valeur]) => valeur !== exclu)
                .map(([valeur, libelle]) => ({ valeur, libelle }));
        },

        revaliderLien(rang) {
            const ligne = this.lignes[rang];
            const permis = this.liensPossibles(rang).map((l) => l.valeur);

            if (! permis.includes(ligne.relationship_type)) {
                ligne.relationship_type = ligne.gender === 'male' ? 'father' : 'mother';
            }
        },

        /* Un élève n'a qu'un contact principal. */
        basculerPrincipal(rang) {
            if (! this.lignes[rang].is_primary_contact) return;

            this.lignes.forEach((ligne, i) => {
                if (i !== rang) ligne.is_primary_contact = false;
            });
        },
    };
}
