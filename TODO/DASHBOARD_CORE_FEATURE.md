# Dashboard Core - Fonctionnalités Principales

## Vue d'ensemble
Implémentation des fonctionnalités principales du dashboard utilisateur après l'onboarding et le quiz de positionnement.

## Priorité : HAUTE ⭐

## Fonctionnalités à implémenter

### 1. Interface principale du Dashboard
- [ ] Page d'accueil du dashboard (`/dashboard`)
- [ ] Navigation principale avec menu latéral
- [ ] Header avec profil utilisateur et notifications
- [ ] Vue d'ensemble des statistiques personnelles
- [ ] Raccourcis vers les actions principales

### 2. Gestion des Sessions d'Étude
- [ ] **Backend** : Endpoint pour récupérer/créer une session quotidienne
- [ ] **Frontend** : Interface de démarrage de session
- [ ] **Logique** : Réutilisation des sessions existantes (éviter les doublons)
- [ ] **Affichage** : Progression de la session en cours
- [ ] **Timer** : Suivi du temps passé en étude

### 3. Navigation des Cours et Modules
- [ ] **Backend** : Endpoints pour lister les cours disponibles
- [ ] **Frontend** : Interface de sélection des cours
- [ ] **Progression** : Affichage du pourcentage de completion
- [ ] **Recommandations** : Suggestions basées sur le quiz de positionnement
- [ ] **Filtres** : Par difficulté, catégorie, progression

### 4. Système de Notifications
- [ ] **Backend** : Collection pour les notifications utilisateur
- [ ] **Frontend** : Centre de notifications
- [ ] **Types** : Rappels d'étude, nouveaux contenus, félicitations
- [ ] **Temps réel** : Mise à jour automatique des notifications

### 5. Profil Utilisateur
- [ ] **Page profil** : Affichage et modification des informations
- [ ] **Objectifs** : Gestion des objectifs d'étude personnalisés
- [ ] **Préférences** : Paramètres de l'application
- [ ] **Historique** : Résumé de l'activité récente

## Architecture technique

### Backend (Payload CMS)
```
/src/endpoints/
├── getDashboardData.ts      # Données principales du dashboard
├── getUserCourses.ts        # Cours disponibles pour l'utilisateur
├── getStudySession.ts       # Session d'étude actuelle
└── updateUserProgress.ts    # Mise à jour de la progression
```

### Frontend (Dashboard App)
```
/src/pages/
├── DashboardPage.tsx        # Page principale
├── ProfilePage.tsx          # Page profil utilisateur
└── CoursesPage.tsx          # Liste des cours

/src/components/
├── Dashboard/
│   ├── DashboardHeader.tsx
│   ├── SessionWidget.tsx
│   ├── ProgressOverview.tsx
│   └── QuickActions.tsx
└── Navigation/
    ├── Sidebar.tsx
    └── TopNavigation.tsx
```

## Dépendances
- ✅ Tunnel d'onboarding terminé
- ✅ Quiz de positionnement implémenté
- ✅ Collections Payload configurées
- ✅ Authentification utilisateur fonctionnelle

## Estimation
**Temps estimé** : 3-4 jours de développement
**Complexité** : Moyenne

## Notes techniques
- Réutiliser la logique de sessions existante (`simpleDailySession.ts`)
- Intégrer avec les résultats du quiz de positionnement
- Prévoir l'évolutivité pour les futures fonctionnalités IA
- Optimiser les performances avec React Query pour le cache
