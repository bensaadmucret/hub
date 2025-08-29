#!/bin/bash

# Chemin vers le projet
PROJECT_DIR="$(dirname "$(dirname "$0")")"

# Se déplacer dans le répertoire du projet
cd "$PROJECT_DIR" || exit 1

# Définir l'environnement (prod par défaut)
ENV=${1:-prod}

# Exécuter la commande
php bin/console app:paddle:process-webhook-retries --env="$ENV" --limit=50
