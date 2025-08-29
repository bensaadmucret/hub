# Structure du Tunnel d'Onboarding

Ce document décrit la structure du tunnel d'onboarding pour collecter les informations nécessaires à la création de profils utilisateurs dans Payload CMS.

## Vue d'ensemble

Le tunnel d'onboarding est divisé en 4 étapes principales, chacune collectant un ensemble spécifique d'informations :

1. **Création de compte**
2. **Informations académiques**
3. **Objectifs d'étude**
4. **Confirmation et finalisation**

## Détail des étapes

### Étape 1 : Création de compte

**Objectif** : Collecter les informations de base pour créer un compte utilisateur.

**Champs** :
- Prénom (firstName) - Texte, obligatoire
- Nom (lastName) - Texte, obligatoire
- Email - Email, obligatoire
- Mot de passe - Password, obligatoire
- Confirmation du mot de passe - Password, obligatoire

**Actions** :
- Bouton "Continuer" pour passer à l'étape suivante
- Validation des champs obligatoires et du format d'email
- Vérification que les mots de passe correspondent

### Étape 2 : Informations académiques

**Objectif** : Collecter les informations sur le parcours académique de l'étudiant.

**Champs** :
- Année d'études (studyYear) - Sélection, obligatoire
  - Options : PASS (Parcours d'Accès Spécifique Santé), LAS (Licence avec option Accès Santé)
- Date de l'examen (examDate) - Date, obligatoire

**Actions** :
- Bouton "Précédent" pour revenir à l'étape 1
- Bouton "Continuer" pour passer à l'étape suivante
- Validation des champs obligatoires

### Étape 3 : Objectifs d'étude

**Objectif** : Collecter les informations sur les objectifs et habitudes d'étude.

**Champs** :
- Objectif de score (targetScore) - Nombre (0-100), obligatoire
- Heures d'étude par semaine (studyHoursPerWeek) - Nombre (1-80), obligatoire

**Actions** :
- Bouton "Précédent" pour revenir à l'étape 2
- Bouton "Continuer" pour passer à l'étape suivante
- Validation des champs obligatoires et des plages de valeurs

### Étape 4 : Confirmation et finalisation

**Objectif** : Récapituler les informations collectées et finaliser l'inscription.

**Affichage** :
- Récapitulatif des informations saisies aux étapes précédentes
- Conditions d'utilisation et politique de confidentialité

**Champs** :
- Acceptation des conditions (checkbox), obligatoire

**Actions** :
- Bouton "Précédent" pour revenir à l'étape 3
- Bouton "Finaliser l'inscription" pour compléter le processus
- Marquer onboardingComplete à true lors de la validation

## Flux de données

1. Les données sont collectées progressivement à travers les étapes du tunnel
2. À chaque étape, les données sont stockées temporairement (state local ou sessionStorage)
3. À la finalisation, toutes les données sont envoyées à l'API pour créer le profil utilisateur dans Payload CMS
4. L'utilisateur est automatiquement connecté après la création du compte

## Intégration avec Payload CMS

Les données collectées seront mappées aux champs correspondants dans la collection Users de Payload CMS :

```javascript
{
  firstName: "...",        // Étape 1
  lastName: "...",         // Étape 1
  email: "...",            // Étape 1
  password: "...",         // Étape 1
  role: "student",         // Valeur par défaut
  studyYear: "...",        // Étape 2
  examDate: "...",         // Étape 2
  studyProfile: {
    targetScore: 0,        // Étape 3
    studyHoursPerWeek: 0   // Étape 3
  },
  onboardingComplete: true // Étape 4
}
```

## Gestion des erreurs

- Validation côté client à chaque étape
- Validation côté serveur lors de la soumission finale
- Gestion des erreurs API (email déjà utilisé, etc.)
- Possibilité de reprendre le processus en cas d'interruption
