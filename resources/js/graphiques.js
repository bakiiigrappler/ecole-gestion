import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement, BarController, BarElement, CategoryScale, DoughnutController,
    Filler, Legend, LineController, LineElement, LinearScale, PointElement, Tooltip,
);

Chart.defaults.font.family = "'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6d6e71';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.boxWidth = 8;

/**
 * Chaque graphique est declare cote Blade via un canvas :
 *   <canvas data-graphique="doughnut" data-donnees="{...}"></canvas>
 *
 * `data-donnees` porte { labels, datasets, legende, position }. Les couleurs
 * viennent de la charte : inutile de les repeter dans chaque vue.
 */
export function initGraphiques() {
    document.querySelectorAll('canvas[data-graphique]').forEach((canvas) => {
        let config;

        try {
            config = JSON.parse(canvas.dataset.donnees || '{}');
        } catch (erreur) {
            console.error('Données de graphique invalides', erreur);
            return;
        }

        const type = canvas.dataset.graphique;
        const horizontal = canvas.dataset.horizontal === 'true';

        new Chart(canvas, {
            type,
            data: {
                labels: config.labels || [],
                datasets: (config.datasets || []).map((jeu, index) => ({
                    borderWidth: type === 'line' ? 2 : 0,
                    borderRadius: type === 'bar' ? 6 : 0,
                    tension: 0.35,
                    fill: type === 'line',
                    pointRadius: type === 'line' ? 3 : undefined,
                    backgroundColor: jeu.couleurs || palette(index),
                    borderColor: jeu.bordure || palette(index)[0],
                    ...jeu,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: horizontal ? 'y' : 'x',
                cutout: type === 'doughnut' ? '68%' : undefined,
                plugins: {
                    legend: { display: config.legende !== false, position: config.position || 'bottom' },
                    tooltip: { padding: 10, cornerRadius: 8 },
                },
                scales: ['doughnut', 'pie'].includes(type)
                    ? {}
                    : {
                        x: { grid: { display: horizontal }, border: { display: false } },
                        y: { grid: { color: '#ececed' }, border: { display: false }, beginAtZero: true },
                    },
            },
        });
    });
}

/**
 * Trois jeux issus de la charte : bleu institutionnel, orange, puis les teintes
 * de service. Un jeu par dataset, pour que deux series restent distinguables.
 */
function palette(index) {
    const jeux = [
        ['#1c75bc', '#43a7e0', '#f7941e', '#ef4023', '#0d9488', '#1b4369'],
        ['#f7941e', '#fda238', '#ffc071', '#ffdba8', '#ffefd4'],
        ['#185e99', '#1c8bd0', '#7fc4ec', '#b4ddf5', '#ddeefa'],
    ];

    return jeux[index % jeux.length];
}
