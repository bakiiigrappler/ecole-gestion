import './bootstrap';

import Alpine from 'alpinejs';
import { initGraphiques } from './graphiques';
import { initPaginationDynamique } from './pagination-dynamique';
import { initExportPdf } from './bulletin-pdf';
import { responsablesDeLEleve } from './responsables';

window.Alpine = Alpine;

// Composants Alpine partagés, appelés depuis les vues via x-data.
Alpine.data('responsablesDeLEleve', responsablesDeLEleve);

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initGraphiques();
    initPaginationDynamique();
    initExportPdf();
});
