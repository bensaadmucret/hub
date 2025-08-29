# Flux de traitement des webhooks Paddle

Ce document décrit l'architecture et le flux de traitement des webhooks Paddle dans l'application, incluant le mécanisme de retry pour assurer la fiabilité du traitement des événements.

## Architecture globale

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│             │     │             │     │             │     │             │
│   Paddle    │────▶│    ngrok    │────▶│ Contrôleur  │────▶│  Service    │
│   Webhook   │     │   Tunnel    │     │  Webhook    │     │  de Retry   │
│             │     │             │     │             │     │             │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
                                               │                   │
                                               ▼                   ▼
                                        ┌─────────────┐     ┌─────────────┐
                                        │             │     │             │
                                        │   Base de   │◀───▶│  Commande   │
                                        │   Données   │     │  de Retry   │
                                        │             │     │             │
                                        └─────────────┘     └─────────────┘
```

## Composants principaux

1. **PaddleWebhookController**: Point d'entrée des webhooks Paddle
2. **PaddleWebhookEvent**: Entité pour stocker les événements et leur statut
3. **PaddleEventRouter**: Routeur qui dirige les événements vers les handlers appropriés
4. **PaddleWebhookRetryService**: Service qui gère les retries en cas d'échec
5. **ProcessPaddleWebhookRetryCommand**: Commande pour retraiter les événements échoués

## Flux de traitement

### 1. Réception d'un webhook

1. Paddle envoie un webhook à notre endpoint `/webhooks/paddle` (ou `/webhook/paddle` pour compatibilité)
2. Le contrôleur vérifie la signature du webhook avec `PaddleSignatureVerifier`
3. Le contrôleur extrait l'ID et le type d'événement
4. Le contrôleur vérifie l'idempotence (si l'événement a déjà été reçu)
5. Le contrôleur persiste l'événement avec le statut `received` et le payload complet
6. Le contrôleur répond immédiatement avec un code 204 (No Content) pour accuser réception

### 2. Traitement initial

1. Le contrôleur change le statut de l'événement à `processing`
2. Le service de retry traite l'événement via `processEvent()`
3. Le service de retry appelle le `PaddleEventRouter` pour router l'événement au handler approprié
4. Si le traitement réussit, le statut devient `processed`
5. Si le traitement échoue, le service de retry marque l'événement comme `failed` et planifie un retry

### 3. Mécanisme de retry

1. En cas d'échec, le service de retry:
   - Incrémente le compteur de tentatives (`retryCount`)
   - Enregistre le message d'erreur (`errorMessage`)
   - Calcule la prochaine date de retry avec un délai exponentiel (`nextRetryAt`)
   - Change le statut à `retry_scheduled`

2. Stratégie de délai exponentiel:
   - 1ère tentative: 5 minutes
   - 2ème tentative: 15 minutes
   - 3ème tentative: 45 minutes
   - 4ème tentative: 2 heures
   - 5ème tentative: 6 heures

3. Après 5 tentatives infructueuses, l'événement reste en statut `failed` sans planification de retry

### 4. Traitement des retries programmés

1. La commande `app:paddle:process-webhook-retries` est exécutée périodiquement (via cron)
2. La commande récupère tous les événements avec statut `retry_scheduled` dont la date de retry est passée
3. Pour chaque événement, le service de retry:
   - Change le statut à `processing`
   - Tente de traiter l'événement à nouveau
   - Met à jour le statut selon le résultat (`processed` ou `failed`/`retry_scheduled`)

## Statuts des événements

- `received`: Événement reçu mais pas encore traité
- `processing`: Événement en cours de traitement
- `processed`: Événement traité avec succès
- `failed`: Événement dont le traitement a échoué définitivement (après max retries)
- `retry_scheduled`: Événement dont le traitement a échoué et qui est programmé pour retry

## Configuration et utilisation

### Configuration ngrok

Pour exposer l'endpoint webhook en développement:

1. Démarrer le serveur Symfony: `make dev`
2. Lancer ngrok: `make ngrok`
3. Configurer l'URL générée dans le dashboard Paddle: `https://xxxx-xxxx-xxxx.ngrok.io/webhooks/paddle`
4. Interface d'inspection des requêtes: `http://localhost:4040`

### Commande de retry

Pour exécuter manuellement le traitement des retries:

```bash
# Traiter uniquement les événements programmés pour retry
bin/console app:paddle:process-webhook-retries

# Traiter également les événements en échec définitif
bin/console app:paddle:process-webhook-retries --failed

# Limiter le nombre d'événements à traiter
bin/console app:paddle:process-webhook-retries --limit=10

# Modifier le nombre maximum de tentatives
bin/console app:paddle:process-webhook-retries --max-retries=3
```

### Configuration cron recommandée

Pour automatiser le traitement des retries, deux options sont disponibles :

#### 1. Utilisation du script shell

Un script shell est fourni pour faciliter l'exécution des retries :

```bash
# Exécuter manuellement
./bin/process-paddle-webhook-retries.sh prod

# Ou via le Makefile
make process-webhook-retries env=prod
```

#### 2. Configuration crontab

Pour automatiser l'exécution périodique, ajouter au crontab :

```
# Exécuter toutes les 5 minutes
*/5 * * * * cd /chemin/absolu/vers/app_demo && ./bin/process-paddle-webhook-retries.sh prod >> /var/log/paddle-webhook-retry.log 2>&1
```

Un exemple de configuration crontab est disponible dans `docs/crontab-example.txt`.

## Bonnes pratiques et sécurité

1. **Idempotence**: Les événements sont traités une seule fois grâce à la vérification de l'ID d'événement
2. **Validation de signature**: Chaque webhook est vérifié avec le secret partagé Paddle
3. **Persistance du payload**: Le payload complet est sauvegardé pour permettre un retraitement fiable
4. **Logging**: Toutes les étapes importantes sont loguées pour faciliter le debugging
5. **Limitation des retries**: Un nombre maximum de tentatives évite les boucles infinies

## Dépannage

### Événements bloqués en statut "processing"

Si un événement reste bloqué en statut `processing` (par exemple après un crash du serveur), il faut:

```bash
# Identifier les événements bloqués
bin/console doctrine:query:sql "SELECT * FROM paddle_webhook_event WHERE status = 'processing'"

# Réinitialiser leur statut pour permettre un nouveau traitement
bin/console doctrine:query:sql "UPDATE paddle_webhook_event SET status = 'retry_scheduled', next_retry_at = NOW() WHERE status = 'processing'"
```

### Vérification des statistiques

Pour vérifier l'état global des événements webhook:

```bash
bin/console app:paddle:process-webhook-retries
```

La commande affichera un tableau avec le nombre d'événements par statut.

## Améliorations futures

### Monitoring et alertes

1. **Alertes pour les échecs définitifs**
   - Implémenter un système de notification (email, Slack, etc.) pour les événements qui atteignent le statut `failed`
   - Créer une commande dédiée pour générer des rapports d'échecs

2. **Dashboard de monitoring**
   - Développer une interface admin pour visualiser l'état des webhooks
   - Afficher des graphiques de tendances et statistiques
   - Permettre le retraitement manuel des événements échoués

### Optimisations de performance

1. **Indexation de la base de données**
   ```sql
   -- Ajouter un index sur next_retry_at pour améliorer les performances des requêtes de retry
   CREATE INDEX idx_paddle_webhook_next_retry ON paddle_webhook_event (next_retry_at);
   ```

2. **Purge automatique**
   - Implémenter une commande pour purger les événements traités avec succès après une période définie
   - Exemple de commande à développer :
   ```bash
   bin/console app:paddle:purge-processed-webhooks --older-than=30d
   ```
