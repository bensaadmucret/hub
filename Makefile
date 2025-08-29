.PHONY: install cs-fix cs-check stan test test-coverage cache-clear help clean lint docker ngrok process-webhook-retries

## Affiche cette aide détaillée
help:
	@printf "\n"
	@printf "$(BOLD)Commandes disponibles :$(RESET)\n"
	@printf "\n"
	@printf "$(BOLD)Développement :$(RESET)\n"
	@printf "  $(CYAN)dev$(RESET)              Lance le serveur de développement Symfony\n"
	@printf "  $(CYAN)stop$(RESET)             Arrête le serveur de développement\n"
	@printf "  $(CYAN)tail-logs$(RESET)        Affiche les logs en temps réel\n"
	@printf "  $(CYAN)list-routes$(RESET)      Liste les routes disponibles\n"
	@printf "  $(CYAN)ngrok$(RESET)            Démarre un tunnel ngrok pour les webhooks Paddle\n"
	@printf "  $(CYAN)process-webhook-retries$(RESET) Exécute le traitement des retries de webhooks Paddle\n"
	@printf "\n"
	@printf "$(BOLD)Installation et maintenance :$(RESET)\n"
	@printf "  $(CYAN)install$(RESET)          Installe les dépendances Composer\n"
	@printf "  $(CYAN)clean$(RESET)            Supprime les fichiers générés et les caches\n"
	@printf "  $(CYAN)help$(RESET)             Affiche cette aide\n"
	@printf "\n"
	@printf "$(BOLD)Qualité du code :$(RESET)\n"
	@printf "  $(CYAN)cs-check$(RESET)         Vérifie le style de code (PSR-12)\n"
	@printf "  $(CYAN)cs-fix$(RESET)           Corrige automatiquement le style de code\n"
	@printf "  $(CYAN)stan$(RESET)             Exécute l'analyse statique avec PHPStan\n"
	@printf "  $(CYAN)lint$(RESET)             Vérifie les fichiers JavaScript avec ESLint\n"
	@printf "\n"
	@printf "$(BOLD)Tests :$(RESET)\n"
	@printf "  $(CYAN)test$(RESET)             Exécute les tests unitaires\n"
	@printf "  $(CYAN)test-coverage$(RESET)     Génère un rapport de couverture de code\n"
	@printf "\n"
	@printf "$(BOLD)Docker :$(RESET)\n"
	@printf "  $(CYAN)docker-build$(RESET)      Construit l'image Docker du projet\n"
	@printf "  $(CYAN)docker-run$(RESET)        Lance le conteneur Docker\n"
	@printf "  $(CYAN)docker-stop$(RESET)       Arrête le conteneur Docker en cours d'exécution\n"
	@printf "\n"
	@printf "$(BOLD)Cache :$(RESET)\n"
	@printf "  $(CYAN)cache-clear$(RESET)       Vide le cache de l'application\n"
	@printf "\n"
	@printf "Utilisation : $(YELLOW)make [commande]$(RESET)\n"
	@printf "Exemple :   $(YELLOW)make install$(RESET)\n"
	@printf "\n"

## Installe les dépendances
install:
	composer install

## Vérifie le style de code selon PSR-12
cs-check:
	./vendor/bin/phpcs --standard=PSR12 src/

## Corrige automatiquement le style de code
cs-fix:
	./vendor/bin/phpcbf --standard=PSR12 src/

## Exécute l'analyse statique avec PHPStan
stan:
	@if [ -f ./vendor/bin/phpstan ]; then \
		./vendor/bin/phpstan analyse -c phpstan.dist.neon --memory-limit=1G; \
	else \
		echo "PHPStan n'est pas installé. Exécutez : composer require --dev phpstan/phpstan"; \
		exit 1; \
	fi

## Exécute les tests unitaires
test:
	./vendor/bin/phpunit

## Génère un rapport de couverture de code
test-coverage:
	XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html=var/coverage

## Nettoie le cache
cache-clear:
	rm -rf var/cache/*

## Lance le serveur de développement
dev:
	symfony serve -d

## Arrête le serveur de développement
stop:
	symfony server:stop

## Affiche les logs
tail-logs:
	tail -f var/log/dev.log

## Liste les routes disponibles
list-routes:
	bin/console debug:router

## Supprime les fichiers générés
clean:
	rm -rf var/coverage/ generated/ *.class.php

## Vérifie les fichiers JavaScript avec ESLint
lint:
	composer lint

## Gestion Docker
docker-build:
	docker compose build

docker-run:
	docker compose up -d

docker-stop:
	docker compose down

cc:
	php bin/console cache:clear

serve:
	symfony server:start -d

## Démarre un tunnel ngrok pour les webhooks Paddle
ngrok:
	ngrok start --config=./ngrok.yml paddle_webhook

## Exécute le traitement des retries de webhooks Paddle
process-webhook-retries:
	./bin/process-paddle-webhook-retries.sh $(env)
