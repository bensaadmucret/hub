import { startStimulusApp } from '@symfony/stimulus-bridge';

// Configuration de Stimulus avec les logs désactivés
const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
), {
    // Désactive les logs de débogage
    debug: false,
    // Désactive les logs de développement
    development: false,
    // Désactive les logs de connexion des contrôleurs
    log: false,
    // Désactive les logs des événements
    logEvents: false
});

export { app };
