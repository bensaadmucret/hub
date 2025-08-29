# Guide de test des webhooks Paddle

Ce document explique comment configurer et tester les webhooks Paddle avec le mécanisme de retry dans un environnement de développement.

## Prérequis

1. Un compte Paddle avec accès au dashboard
2. Un token ngrok configuré dans le fichier `ngrok.yml`
3. Le serveur Symfony en cours d'exécution

## Étapes de configuration

### 1. Démarrer l'environnement de développement

```bash
# Démarrer le serveur Symfony
make dev

# Vérifier que le serveur est bien démarré
symfony server:status
```

### 2. Démarrer le tunnel ngrok

```bash
# Démarrer ngrok avec la configuration du projet
make ngrok
```

Une fois ngrok démarré, notez l'URL générée qui ressemble à :
```
https://xxxx-xxxx-xxxx.ngrok.io
```

Vous pouvez également accéder à l'interface d'inspection ngrok sur http://localhost:4040

### 3. Configurer le webhook dans le dashboard Paddle

1. Connectez-vous à votre [dashboard Paddle](https://vendors.paddle.com)
2. Accédez à la section "Developer Tools" > "Webhooks"
3. Configurez l'URL de destination avec l'URL ngrok suivie du chemin webhook :
   ```
   https://xxxx-xxxx-xxxx.ngrok.io/webhooks/paddle
   ```
4. Assurez-vous que la signature HMAC est activée et que vous utilisez le même secret que celui configuré dans votre `.env.local` (`PADDLE_WEBHOOK_SECRET`)
5. Sélectionnez les événements que vous souhaitez recevoir (par exemple, `subscription.created`, `subscription.updated`, etc.)
6. Enregistrez la configuration

## Test des webhooks

### Option 1 : Déclencher un événement réel

1. Créez une transaction ou une action dans Paddle qui déclenchera un webhook (par exemple, créer un abonnement de test)
2. Observez l'événement dans l'interface ngrok (http://localhost:4040)
3. Vérifiez que l'événement a été correctement enregistré dans la base de données :
   ```bash
   bin/console doctrine:query:sql "SELECT * FROM paddle_webhook_event ORDER BY created_at DESC LIMIT 5"
   ```

### Option 2 : Utiliser l'outil de test de Paddle

1. Dans le dashboard Paddle, accédez à "Developer Tools" > "Webhooks" > "Test"
2. Sélectionnez le type d'événement à tester
3. Cliquez sur "Send Test Alert"
4. Observez l'événement dans l'interface ngrok et vérifiez la base de données

### Option 3 : Simuler un webhook manuellement

Vous pouvez simuler un webhook avec curl :

```bash
curl -X POST https://xxxx-xxxx-xxxx.ngrok.io/webhooks/paddle \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "subscription.created",
    "event_id": "evt_test_'$(date +%s)'",
    "occurred_at": "'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'",
    "data": {
      "id": "sub_test_'$(date +%s)'",
      "status": "active",
      "customer_id": "cus_test_01",
      "address_id": "add_test_01",
      "business_id": null,
      "currency_code": "EUR",
      "created_at": "'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'",
      "updated_at": "'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'",
      "started_at": "'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'",
      "first_billed_at": "'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'",
      "next_billed_at": "'$(date -u -d "+30 days" +"%Y-%m-%dT%H:%M:%SZ")'",
      "paused_at": null,
      "canceled_at": null,
      "collection_mode": "automatic",
      "billing_details": null,
      "current_billing_period": {
        "ends_at": "'$(date -u -d "+30 days" +"%Y-%m-%dT%H:%M:%SZ")'",
        "starts_at": "'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'"
      },
      "items": [
        {
          "id": "itm_test_01",
          "price": {
            "id": "pri_test_01",
            "description": "Test Subscription",
            "product_id": "pro_test_01",
            "billing_cycle": {
              "interval": "month",
              "frequency": 1
            },
            "unit_price": {
              "amount": "9.99",
              "currency_code": "EUR"
            }
          }
        }
      ]
    }
  }'
```

## Test du mécanisme de retry

Pour tester le mécanisme de retry, vous pouvez :

### 1. Simuler une erreur dans le traitement

Modifiez temporairement le `PaddleEventRouter` pour qu'il génère une exception pour un type d'événement spécifique :

```php
// src/Webhook/PaddleEventRouter.php
public function route(string $eventType, array $payload): void
{
    // Simuler une erreur pour les tests
    if ($eventType === 'subscription.created') {
        throw new \RuntimeException('Erreur simulée pour tester le mécanisme de retry');
    }
    
    // Code existant...
}
```

### 2. Déclencher un webhook

Utilisez l'une des méthodes ci-dessus pour déclencher un webhook de type `subscription.created`.

### 3. Vérifier le statut de l'événement

```bash
# Vérifier que l'événement est en statut retry_scheduled
bin/console doctrine:query:sql "SELECT * FROM paddle_webhook_event WHERE event_type = 'subscription.created' ORDER BY created_at DESC LIMIT 1"
```

### 4. Exécuter la commande de retry

```bash
# Exécuter la commande de retry
make process-webhook-retries env=dev

# Ou directement
./bin/process-paddle-webhook-retries.sh dev
```

### 5. Vérifier le résultat

Si vous laissez l'erreur simulée, l'événement devrait rester en échec mais avec un compteur de tentatives incrémenté.

Pour voir l'événement réussir, supprimez le code qui simule l'erreur et exécutez à nouveau la commande de retry.

## Dépannage

### Vérifier les logs

```bash
# Logs Symfony
tail -f var/log/dev.log | grep webhook

# Logs ngrok
http://localhost:4040/inspect/http
```

### Vérifier les statistiques des événements

```bash
# Afficher les statistiques des événements par statut
bin/console app:paddle:process-webhook-retries
```

### Réinitialiser les événements bloqués

```bash
# Réinitialiser les événements bloqués en statut "processing"
bin/console doctrine:query:sql "UPDATE paddle_webhook_event SET status = 'retry_scheduled', next_retry_at = NOW() WHERE status = 'processing'"
```
