

## Prérequis

- PHP 8.2 ou supérieur
- Composer
- Docker et Docker Compose
- Symfony CLI (recommandé)
- OpenSSL (pour la génération des clés JWT)

## Installation

1. Cloner le dépôt :
   ```bash
   git clone [URL_DU_DEPOT] app_demo
   cd app_demo
   ```

2. Installer les dépendances :
   ```bash
   composer install
   ```

3. Démarrer les conteneurs Docker :
   ```bash
   docker-compose up -d
   ```

4. Créer la base de données :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate -n
   ```


## Configuration JWT et Authentification

### Configuration initiale

1. **Générer les clés JWT** :
   ```bash
   # Exporter la phrase secrète (à stocker en sécurité dans .env.local)
   export JWT_PASSPHRASE='votre_phrase_secrete'
   
   # Générer la clé privée
   openssl genrsa -out config/jwt/private.pem -aes256 -passout env:JWT_PASSPHRASE 4096
   
   # Générer la clé publique
   openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem -passin env:JWT_PASSPHRASE
   
   # Définir les bonnes permissions
   chmod 600 config/jwt/private.pem
   chmod 644 config/jwt/public.pem
   ```

2. **Configuration des variables d'environnement** :
   - Créez un fichier `.env.local` à partir de `.env`
   - Définissez les variables JWT suivantes :
     ```
     JWT_SECRET_KEY='%kernel.project_dir%/config/jwt/private.pem'
     JWT_PUBLIC_KEY='%kernel.project_dir%/config/jwt/public.pem'
     JWT_PASSPHRASE='votre_phrase_secrete'
     JWT_TOKEN_TTL=3600  # Durée de validité du token en secondes (1h)
     JWT_REFRESH_TOKEN_TTL=2592000  # Durée de validité du refresh token (30j)
     ```

### Utilisation de l'API d'authentification

1. **Authentification** :
   ```bash
   curl -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email": "votre@email.com", "password": "votre_mot_de_passe"}'
   ```

   Réponse en cas de succès :
   ```json
   {
     "token": "votre_jwt_token"
   }
   ```

2. **Utilisation du token** :
   Inclure le token dans l'en-tête `Authorization` :
   ```
   Authorization: Bearer votre_jwt_token
   ```

### Bonnes pratiques de sécurité

- Ne jamais commiter le fichier `.env.local` ou les clés JWT
- Utiliser des phrases secrètes fortes (au moins 64 caractères aléatoires)
- Régénérer périodiquement les clés JWT en production
- Utiliser HTTPS en production
- Limiter la durée de validité des tokens


## Tests

Pour exécuter les tests :

```bash
php bin/phpunit
```

### Tests (SQLite + SchemaTool)

Pour des tests rapides et indépendants de l'infrastructure, la suite est configurée pour utiliser SQLite en environnement de test et initialiser le schéma Doctrine au besoin.

**Variables à définir dans `.env.test`**

```
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
PADDLE_WEBHOOK_SECRET="test_secret"
PAYLOAD_API_URL="http://localhost"
PAYLOAD_API_KEY="test_key"
MESSENGER_TRANSPORT_DSN="in-memory://"
# Optionnel (CI) pour ignorer les dépréciations issues des vendors
# SYMFONY_DEPRECATIONS_HELPER="weak_vendors"
```

**Pourquoi SQLite ?**

- Tests hermétiques sans dépendre de Docker/Postgres.
- Exécution plus rapide en locale et CI.

**Initialisation du schéma Doctrine**

- Le test fonctionnel `tests/Controller/Webhook/PaddleWebhookControllerTest.php` crée le schéma à la volée pour l'entité `App\Entity\PaddleWebhookEvent` via `Doctrine\ORM\Tools\SchemaTool`.
- Cela garantit l'idempotence et l'absence de dépendance aux migrations pour ce test.

**Mock des dépendances externes**

- `App\Integration\Payload\PayloadClientInterface` est mocké dans les tests pour éviter tout appel HTTP externe.

**Lancement des tests**

```
make test
```

Couverture de code :

```
make test-coverage
```

Exécuter uniquement le test webhook Paddle :

```
./vendor/bin/phpunit --filter PaddleWebhookControllerTest
```

Note: Le contrôleur `App\Controller\Webhook\PaddleWebhookController` renvoie `204 No Content` pour ACK rapide lorsque la signature est valide, même si le JSON est invalide.

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.
