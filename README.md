

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

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.
