# Roadmap MVP MedCoach - Approche Sans IA

## Vue d'ensemble
**Objectif :** Lancer un MVP fonctionnel en 2-3 mois avec algorithmes classiques
**Positionnement :** "Coach IA" (marketing) + implémentation pragmatique (technique)
**Stratégie freemium :** 3 sessions/mois (gratuit) → Premium illimité (69,99€/an)

## Phase 1 - MVP Non-IA (12 semaines)

### Sprint 1-2 : Infrastructure + Mémorisation (Semaines 1-4)
**Livrables :**
- [ ] Architecture backend Node.js + MongoDB + Redis
- [ ] Système d'authentification JWT
- [ ] Base de données utilisateurs + sessions
- [ ] **Fonctionnalité : Optimiser mémorisation**
  - [ ] Algorithme SuperMemo SM-2
  - [ ] Interface révision (Facile/Moyen/Difficile)
  - [ ] Calcul intervalles de répétition
  - [ ] Dashboard progression utilisateur

**APIs développées :**
```
POST /api/auth/login
POST /api/spaced-repetition/review
GET  /api/spaced-repetition/due-cards
PUT  /api/spaced-repetition/update-card
```

### Sprint 3-4 : Analyse d'erreurs + Dashboard (Semaines 5-8)
**Livrables :**
- [ ] **Fonctionnalité : Analyser mes erreurs**
  - [ ] Système de tracking réponses utilisateur
  - [ ] Algorithmes détection patterns d'erreurs
  - [ ] Règles métier recommandations
  - [ ] Dashboard analytics avec graphiques
- [ ] Interface utilisateur pour consultation analyses
- [ ] Système de notifications/alertes

**APIs développées :**
```
POST /api/analytics/track-response
GET  /api/analytics/user-errors
GET  /api/analytics/recommendations
GET  /api/analytics/dashboard-data
```

### Sprint 5-6 : Planning + Révision Anatomie (Semaines 9-12)
**Livrables :**
- [ ] **Fonctionnalité : Planifier ma semaine**
  - [ ] Algorithme optimisation contraintes
  - [ ] Interface saisie disponibilités/objectifs
  - [ ] Génération planning personnalisé
  - [ ] Ajustements manuels possibles
- [ ] **Fonctionnalité : Réviser l'anatomie**
  - [ ] Base de données questions anatomie structurée
  - [ ] Système parcours adaptatifs par règles
  - [ ] Interface révision par système (cardio, respiratoire, etc.)
  - [ ] Progression par difficulté

**APIs développées :**
```
POST /api/planning/generate
PUT  /api/planning/update
GET  /api/anatomy/systems
GET  /api/anatomy/path/:system
POST /api/anatomy/complete-session
```

## Architecture technique

### Stack validé
```
Frontend: React/TypeScript (existant dashboard-app)
Backend: Node.js/Express
Base de données: MongoDB
Cache: Redis (sessions, résultats)
Authentification: JWT
Déploiement: Docker + CI/CD
```

### Structure base de données MongoDB
```javascript
// Collections principales

// Users
{
  _id: ObjectId,
  email: String,
  passwordHash: String,
  planType: "free" | "premium",
  createdAt: Date,
  profile: {
    firstName: String,
    lastName: String,
    studyLevel: "PASS" | "LAS" | "ECN"
  }
}

// UserSessions
{
  _id: ObjectId,
  userId: ObjectId,
  sessionType: "memorization" | "error_analysis" | "planning" | "anatomy",
  data: Object,
  createdAt: Date,
  completedAt: Date
}

// SpacedRepetitionCards
{
  _id: ObjectId,
  userId: ObjectId,
  contentId: ObjectId,
  easeFactor: Number, // 1.3 - 2.5
  interval: Number,   // jours
  repetitions: Number,
  nextReview: Date,
  lastReviewed: Date
}

// UserResponses
{
  _id: ObjectId,
  userId: ObjectId,
  questionId: ObjectId,
  response: String,
  isCorrect: Boolean,
  responseTime: Number, // ms
  createdAt: Date,
  sessionId: ObjectId
}

// WeeklyPlans
{
  _id: ObjectId,
  userId: ObjectId,
  weekStart: Date,
  planData: {
    subjects: Array,
    schedule: Object,
    totalHours: Number
  },
  createdAt: Date
}

// AnatomySystems
{
  _id: ObjectId,
  name: String,
  description: String,
  difficultyLevel: Number, // 1-5
  prerequisites: [ObjectId],
  estimatedDuration: Number // minutes
}

// AnatomyQuestions
{
  _id: ObjectId,
  systemId: ObjectId,
  question: String,
  options: [String],
  correctAnswer: String,
  difficulty: Number, // 1-5
  tags: [String]
}

// LearningPaths
{
  _id: ObjectId,
  userId: ObjectId,
  systemId: ObjectId,
  progress: Number, // 0-100%
  completedQuestions: [ObjectId],
  startedAt: Date,
  completedAt: Date
}
```

## Métriques de succès MVP

### Techniques
- [ ] Temps de réponse API < 200ms
- [ ] Disponibilité > 99%
- [ ] 0 erreurs critiques en production

### Produit
- [ ] 100 utilisateurs actifs/semaine
- [ ] Taux de rétention J7 > 40%
- [ ] 3+ sessions par utilisateur actif
- [ ] Conversion gratuit→premium > 5%

### Fonctionnalités
- [ ] Mémorisation : 80% utilisateurs utilisent la répétition espacée
- [ ] Erreurs : 70% consultent leurs analyses d'erreurs
- [ ] Planning : 60% génèrent un planning hebdomadaire
- [ ] Anatomie : 50% complètent un parcours système

## Phase 2 - Évolution IA (Optionnelle)

### Fonctionnalités IA à ajouter si besoin
1. **Préparer examen blanc** - Génération contenu varié via LLM
2. **Coaching motivation** - IA conversationnelle personnalisée

### Critères de déclenchement Phase 2
- [ ] MVP validé avec 500+ utilisateurs actifs
- [ ] Taux de conversion > 8%
- [ ] Demande utilisateur forte pour fonctionnalités avancées
- [ ] Budget disponible pour coûts IA (~1000€/mois)

## Timeline et jalons

**Semaine 4 :** Demo mémorisation espacée
**Semaine 8 :** Demo analyse d'erreurs + dashboard
**Semaine 12 :** MVP complet avec 4 fonctionnalités
**Semaine 16 :** Déploiement production + premiers utilisateurs

## Risques et mitigation

**Risque technique :** Algorithmes moins "sexy" que IA
**Mitigation :** Focus sur UX exceptionnelle et résultats concrets

**Risque marché :** Concurrence avec vraie IA
**Mitigation :** Time-to-market rapide, validation utilisateurs, ajout IA si nécessaire

**Risque produit :** Fonctionnalités trop basiques
**Mitigation :** Algorithmes optimisés, personnalisation poussée, interface moderne

---

**Prochaine étape :** Validation de cette roadmap et début Sprint 1 (Infrastructure + Mémorisation)
