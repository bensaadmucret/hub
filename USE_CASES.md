# Cas d'Usage de l'Application MedCoach

## Objectif et Vision du SaaS MedCoach

### Mission

**MedCoach** est une plateforme SaaS conçue pour transformer l'expérience d'apprentissage des étudiants en médecine en devenant leur coach personnel intelligent. Notre mission est de réduire le stress et d'optimiser l'efficacité des études médicales grâce à une approche personnalisée, adaptative et basée sur l'intelligence artificielle.

### Problématique adressée

Les étudiants en médecine font face à des défis considérables :
- Volume massif de connaissances à assimiler (plus de 15 000 pages de cours)
- Difficulté à identifier et combler efficacement leurs lacunes (70% des étudiants déclarent ne pas savoir par où commencer leurs révisions)
- Gestion complexe du temps entre cours, stages hospitaliers et révisions (60-80h de travail hebdomadaire)
- Stress chronique et risque d'épuisement professionnel (43% des étudiants en médecine présentent des symptômes de burnout)
- Manque d'accompagnement personnalisé dans leur parcours d'apprentissage

### Notre approche

MedCoach se distingue par :
1. **Personnalisation intelligente** : Adaptation du contenu et des recommandations au profil unique de chaque étudiant
2. **Planification optimisée** : Génération d'emplois du temps d'étude tenant compte des contraintes personnelles et des priorités pédagogiques
3. **Analyse prédictive** : Identification précoce des difficultés potentielles pour une intervention ciblée
4. **Coaching cognitif** : Recommandations de techniques d'apprentissage adaptées au style cognitif de l'étudiant
5. **Assistance IA continue** : Accompagnement conversationnel pour répondre aux questions et guider l'apprentissage

### Intégration de l'Intelligence Artificielle

L'IA est au cœur de notre solution et intervient à plusieurs niveaux :

#### 1. Moteur de personnalisation cognitive
- **Analyse comportementale** : Identification du style d'apprentissage de l'étudiant par l'analyse de ses interactions
- **Modélisation cognitive** : Cartographie des connaissances et compétences de l'étudiant
- **Adaptation dynamique** : Ajustement en temps réel du contenu et des recommandations

#### 2. Assistant pédagogique conversationnel
- **Compréhension contextuelle** : Capacité à interpréter les questions dans le contexte médical spécifique
- **Réponses basées sur l'evidence-based medicine** : Informations vérifiées et à jour
- **Socratique intelligent** : Pose des questions pour stimuler la réflexion plutôt que de simplement donner des réponses

#### 3. Système prédictif de performance
- **Analyse des patterns d'erreurs** : Identification des schémas récurrents dans les erreurs commises
- **Prédiction des difficultés** : Anticipation des concepts qui poseront problème
- **Recommandations préventives** : Suggestions de ressources avant même que les difficultés n'apparaissent

#### 4. Générateur de contenu adaptatif
- **Création de quiz personnalisés** : Questions générées spécifiquement pour cibler les lacunes identifiées
- **Synthèses intelligentes** : Résumés adaptés au niveau de l'étudiant et à ses besoins
- **Cas cliniques sur mesure** : Scénarios adaptés aux objectifs d'apprentissage actuels

### Public cible

- Étudiants en médecine de tous niveaux (PACES à internat)
- Préparateurs aux ECN (Épreuves Classantes Nationales)
- Médecins en formation continue ou en spécialisation
- Facultés de médecine souhaitant offrir un outil complémentaire à leurs étudiants

### Indicateurs de succès

#### Métriques académiques
- Amélioration de 15-25% des scores aux examens blancs après 3 mois d'utilisation
- Réduction de 30% du temps nécessaire pour maîtriser un nouveau concept médical
- Augmentation de 40% du taux de rétention des informations à long terme

#### Métriques d'engagement
- Taux d'utilisation quotidien > 70% chez les utilisateurs actifs
- Durée moyenne de session > 45 minutes
- Taux de complétion des plans d'étude recommandés > 65%

#### Métriques de bien-être
- Réduction de 25% des scores d'anxiété liée aux études (mesurée par questionnaires standardisés)
- Amélioration de 20% de la qualité du sommeil rapportée
- Augmentation de 30% du sentiment d'auto-efficacité académique

#### Métriques business
- Taux de conversion du freemium vers premium > 15%
- Taux de rétention à 12 mois > 80%
- Net Promoter Score > 60

---

Ce document formalise les fonctionnalités clés de l'application, les acteurs impliqués, leurs objectifs et les interactions avec le système via l'API.

---

## 1. Gestion des Utilisateurs

### Cas d'Usage : Inscription d'un nouvel utilisateur

- **ID :** `UC-01`
- **Acteur :** Visiteur non authentifié
- **Objectif :** Créer un nouveau compte pour accéder à l'application.
- **Endpoint API :** `POST /register`

#### Schémas de Données

- **Requête (Request Body) :**

  ```json
  {
    "email": "user@example.com",
    "password": "Str0ngP@ssw0rd!",
    "firstName": "John",
    "lastName": "Doe"
  }
  ```

- **Réponse en cas de succès (201 Created) :**

  ```json
  {
    "message": "User created successfully",
    "user": {
      "id": 1,
      "email": "user@example.com",
      "firstName": "John",
      "lastName": "Doe",
      "roles": [
        "ROLE_USER"
      ]
    }
  }
  ```

- **Réponse en cas d'erreur (400 Bad Request / 409 Conflict) :**

  ```json
  {
    "error": "Validation Failed",
    "details": {
      "email": "This email is already used."
    }
  }
  ```

#### Scénario Principal (Happy Path)

1.  Le **visiteur** envoie une requête `POST` à `/register` avec un email, un mot de passe, un prénom et un nom valides.
2.  Le **système** valide les données (format de l'email, complexité du mot de passe).
3.  Le **système** vérifie que l'adresse email n'est pas déjà utilisée dans la base de données.
4.  Le **système** hache le mot de passe de manière sécurisée.
5.  Le **système** crée une nouvelle entité `User` et la sauvegarde en base de données.
6.  Le **système** retourne une réponse `201 Created` avec un message de succès et les informations de l'utilisateur créé (sans les données sensibles).

#### Scénarios Alternatifs (erreurs)

-   **Email déjà utilisé :**
    -   Si l'email fourni existe déjà, le système retourne une réponse `409 Conflict` avec un message d'erreur clair.
-   **Données invalides :**
    -   Si l'email n'est pas valide, que le mot de passe est trop court ou que d'autres contraintes de validation ne sont pas respectées, le système retourne une réponse `400 Bad Request` avec la liste des champs en erreur et les messages correspondants.

---

### Cas d'Usage : Démarrer une session d'étude quotidienne guidée

- **ID :** `UC-02`
- **Titre :** Session d'étude quotidienne guidée par le coach
- **Acteur :** Étudiant authentifié
- **Objectif :** Compléter le programme d'étude du jour, optimisé par l'IA pour maximiser l'apprentissage et cibler les points faibles.
- **Vision :** C'est la fonctionnalité clé qui incarne notre valeur ajoutée de "coach personnel intelligent".

#### Scénario Principal (La boucle d'étude vertueuse)

1.  **L'étudiant** se connecte à son tableau de bord. (app)
2.  Le **Coach IA** lui présente sa mission du jour via un endpoint `GET /api/v1/session/today`.

    -   **Réponse du Coach (Exemple) :**
        ```json
        {
          "title": "Session du jour : Focus sur la Cardiologie",
          "estimatedTime": "55min",
          "steps": [
            { "stepId": 1, "type": "quiz", "resourceId": "quiz-123", "title": "Quiz d'évaluation", "questionCount": 20 },
            { "stepId": 2, "type": "review", "sourceQuizId": "quiz-123", "title": "Analyse de vos erreurs" },
            { "stepId": 3, "type": "flashcards", "resourceId": "deck-abc", "title": "Renforcement ciblé", "cardCount": 15 },
            { "stepId": 4, "type": "video", "resourceId": "vid-xyz", "title": "Leçon : L'arythmie", "duration": "10min" }
          ]
        }
        ```

3.  **L'étudiant** commence le **quiz**. Pour chaque question, il soumet sa réponse.
4.  Une fois le quiz terminé, le **Coach IA** analyse les résultats en temps réel.
5.  Le **Coach IA** génère un paquet de **flashcards** (`deck-abc`) basé **uniquement sur les concepts où l'étudiant a fait des erreurs**.
6.  L'étudiant complète sa session de flashcards (basée sur la répétition espacée).
7.  L'étudiant regarde la **vidéo** recommandée pour approfondir un sujet clé où il a montré des difficultés.
8.  À la fin de la session, le **Coach IA** met à jour le profil de compétences de l'étudiant et ajuste le plan d'étude global pour le lendemain.
9.  Le tableau de bord de l'étudiant est mis à jour avec des statistiques motivantes (progression, points forts, etc.).

#### Scénarios Alternatifs

-   **L'étudiant n'a pas le temps de tout faire :** La session est sauvegardée. Le lendemain, le coach propose de reprendre là où il s'est arrêté ou de commencer une nouvelle session plus courte.
-   **L'étudiant réussit parfaitement le quiz initial :** Le coach félicite l'étudiant et lui propose un "Mode Turbo" pour sauter les étapes de renforcement et passer directement à un sujet plus avancé ou à un quiz plus difficile.

---

### Cas d'Usage : Connexion d'un utilisateur

- **ID :** `UC-03`
- **Acteur :** Visiteur (avec un compte existant)
- **Objectif :** S'authentifier pour accéder à son tableau de bord et à son coach personnel.
- **Endpoint API :** `POST /login_check` (standard Symfony)

#### Schémas de Données

- **Requête (Request Body) :**

  ```json
  {
    "username": "user@example.com",
    "password": "Str0ngP@ssw0rd!"
  }
  ```

- **Réponse en cas de succès (200 OK) :**

  ```json
  {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
  ```

- **Réponse en cas d'erreur (401 Unauthorized) :**

  ```json
  {
    "code": 401,
    "message": "Invalid credentials."
  }
  ```

#### Scénario Principal (Happy Path)

1.  L'**utilisateur** envoie une requête `POST` à `/login_check` avec son email et son mot de passe.
2.  Le **système** vérifie que l'email et le mot de passe correspondent à un utilisateur existant.
3.  Le **système** génère un JSON Web Token (JWT) sécurisé avec une durée de validité définie.
4.  Le **système** retourne une réponse `200 OK` contenant le JWT.
5.  Le **client** (application front-end) stocke ce token de manière sécurisée et l'inclut dans les en-têtes des requêtes suivantes pour authentifier l'utilisateur.

#### Scénarios Alternatifs

-   **Identifiants incorrects :**
    -   Si l'email n'existe pas ou si le mot de passe est incorrect, le système retourne une réponse `401 Unauthorized` avec un message d'erreur générique ("Invalid credentials.") pour ne pas indiquer si c'est l'email ou le mot de passe qui est incorrect.

---

### Cas d'Usage : Réinitialisation du mot de passe

- **ID :** `UC-04`
- **Acteur :** Visiteur (ayant oublié son mot de passe)
- **Objectif :** Récupérer l'accès à son compte en définissant un nouveau mot de passe.

#### Étape 1 : Demande de réinitialisation

- **Endpoint API :** `POST /reset-password-request`

- **Requête (Request Body) :**
  ```json
  {
    "email": "user@example.com"
  }
  ```

- **Réponse (dans tous les cas) :**
  ```json
  {
    "message": "If an account with this email exists, a password reset link has been sent."
  }
  ```

- **Scénario :**
  1. L'utilisateur soumet son adresse email.
  2. Le système vérifie si un compte est associé à cet email.
  3. **Si oui,** il génère un token de réinitialisation unique et à durée de vie limitée, puis envoie un email à l'utilisateur contenant un lien vers la page de réinitialisation (avec le token).
  4. **Pour des raisons de sécurité,** le système retourne **toujours** le même message de succès, que l'email ait été trouvé ou non, pour empêcher de deviner les adresses email des utilisateurs.

#### Étape 2 : Définition du nouveau mot de passe

- **Endpoint API :** `POST /reset-password`

- **Requête (Request Body) :**
  ```json
  {
    "token": "le-token-unique-recu-par-email",
    "newPassword": "MonNouveauMotDePasseSuperSecurise!"
  }
  ```

- **Réponse en cas de succès (200 OK) :**
  ```json
  {
    "message": "Password has been reset successfully."
  }
  ```

- **Réponse en cas d'erreur (400 Bad Request) :**
  ```json
  {
    "error": "Invalid or expired token."
  }
  ```

- **Scénario :**
  1. L'utilisateur clique sur le lien dans son email et entre son nouveau mot de passe.
  2. Le système valide le token (est-il valide ? n'a-t-il pas expiré ?).
  3. Si le token est valide, le système hache le nouveau mot de passe, met à jour l'utilisateur en base de données et invalide le token pour qu'il ne puisse plus être utilisé.
  4. Le système retourne un message de succès.
