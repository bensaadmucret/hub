# Feature: Tunnel d'Inscription (Onboarding)

**Objectif :** Permettre aux nouveaux utilisateurs de créer un compte rapidement, de personnaliser leur expérience initiale, et de comprendre immédiatement la valeur du produit.

**User Story :** "En tant que nouvel étudiant, je veux un processus d'inscription simple et rapide pour pouvoir commencer à utiliser le coach IA et préparer mon examen sans délai."

---

## Plan d'Action Détaillé

### Phase 1 : Création du Compte (Friction Minimale)

- [ ] **Backend :** Vérifier que l'API de création d'utilisateur (`POST /api/users`) accepte une requête avec seulement `email` et `password`. (Normalement, c'est le cas par défaut avec Payload).
- [ ] **Frontend :** Créer une première vue du formulaire demandant uniquement `email` et `password`.
- [ ] **Frontend :** Ajouter des boutons "S'inscrire avec Google" / "S'inscrire avec [Autre]" pour une inscription en un clic.
- [ ] **Frontend :** Gérer la soumission du formulaire pour créer le compte et connecter automatiquement l'utilisateur.

### Phase 2 : Personnalisation de l'Expérience (Après 1ère connexion)

Cette phase est déclenchée immédiatement après la création du compte.

- [ ] **Frontend :** Créer un composant "Questionnaire de Bienvenue" multi-étapes.
- [ ] **Frontend - Étape 1 (Identité) :** Demander `firstName` et `lastName`.
- [ ] **Frontend - Étape 2 (Contexte Académique) :** Demander `studyYear` et `examDate`.
- [ ] **Frontend - Étape 3 (Objectifs) :** Demander `targetScore` et `studyHoursPerWeek`.
- [ ] **Backend :** Créer un endpoint `PATCH /api/users/:id` pour mettre à jour le profil de l'utilisateur avec les informations collectées.
- [ ] **Frontend :** À la fin du questionnaire, appeler l'endpoint `PATCH` pour sauvegarder les informations de personnalisation.

### Phase 3 : Le "Aha! Moment"

Cette phase suit immédiatement la fin du questionnaire de personnalisation.

- [ ] **Frontend :** Afficher une vue de chargement avec un message engageant (ex: "Nous construisons votre plan de coaching personnalisé...").
- [ ] **Backend :** Créer un endpoint (ex: `POST /api/generate-initial-plan`) qui prend un ID utilisateur et génère une première version de son plan d'étude ou un quiz de positionnement.
- [ ] **Frontend :** Appeler ce nouvel endpoint.
- [ ] **Frontend :** Rediriger l'utilisateur vers son dashboard où le premier plan d'étude ou le quiz est visible et prêt à être utilisé.

---

## Critères d'Acceptation

- [ ] Un nouvel utilisateur peut créer un compte en moins de 30 secondes.
- [ ] L'expérience de personnalisation est fluide et ne peut pas être sautée lors de la première connexion.
- [ ] L'utilisateur voit un résultat concret et personnalisé (plan d'étude, quiz) immédiatement après avoir fourni ses informations.
