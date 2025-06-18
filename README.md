# Application d'import CSV avec Symfony 7.2

Cette application permet d'importer des fichiers CSV volumineux (>100 000 lignes) en utilisant les bonnes pratiques de performance et de mémoire avec Symfony 7.2.

## Fonctionnalités

- Import de fichiers CSV volumineux avec gestion de la mémoire
- Validation des données avec le composant Validator de Symfony
- Utilisation de DTO pour représenter chaque ligne du fichier
- Gestion des erreurs avec une exception personnalisée
- Traitement par lots (batch) pour optimiser les performances
- Commande console pour exécuter l'import

## Prérequis

- PHP 8.2 ou supérieur
- Composer
- Docker et Docker Compose
- Symfony CLI (recommandé)

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

## Utilisation

### Préparation du fichier CSV

Le fichier CSV doit avoir les colonnes suivantes (séparées par des points-virgules) :
- name : Nom de l'utilisateur (obligatoire)
- email : Adresse email (obligatoire, doit être valide)
- amount : Montant (optionnel, doit être numérique)

Exemple de contenu :
```
name;email;amount
John Doe;john@example.com;100.50
Jane Smith;jane@example.com;200.75
```

### Commande d'import

Pour importer un fichier CSV, utilisez la commande suivante :

```bash
php bin/console app:import:csv /chemin/vers/votre/fichier.csv
```

Options disponibles :
- `-d, --delimiter` : Délimiteur du fichier CSV (par défaut: `;`)
- `-b, --batch-size` : Taille des lots pour la sauvegarde en base (par défaut: 100)

Exemple avec options :
```bash
php bin/console app:import:csv /chemin/vers/votre/fichier.csv -d , -b 200
```

### Exemple avec le fichier de test

Un fichier d'exemple est fourni dans le répertoire `import/` :

```bash
php bin/console app:import:csv import/example.csv
```

## Structure du projet

```
src/
├── Command/ImportCsvCommand.php     # Commande console pour l'import
├── Service/
│   ├── CsvImporter.php            # Service principal d'import
│   └── Dto/
│       └── ImportRowDto.php       # DTO pour représenter une ligne du CSV
├── Entity/ImportRecord.php          # Entité de stockage
└── Exception/InvalidCsvRowException.php # Exception personnalisée
```

## Bonnes pratiques implémentées

- Utilisation de DTO pour la validation des données
- Traitement par lots pour éviter les fuites de mémoire
- Gestion propre des erreurs avec rapport détaillé
- Configuration via des paramètres (taille des lots, etc.)
- Documentation complète

## Tests

Pour exécuter les tests :

```bash
php bin/phpunit
```

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.
