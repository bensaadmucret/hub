# Roadmap MVP MedCoach - Approche Sans IA

## Vue d'ensemble
**Objectif :** Lancer un MVP fonctionnel en 2-3 mois avec algorithmes classiques
**Positionnement :** "Coach IA" (marketing) + implémentation pragmatique (technique)
**Stratégie freemium :** 3 sessions/mois (gratuit) → Premium illimité (69,99€/an)

## Phase 1 - MVP Non-IA (12 semaines)

### Sprint 1-2 : Infrastructure + Mémorisation (Semaines 1-4)
**Livrables :**
- [ ] Architecture backend Node.js + Payload CMS + Redis
- [ ] Système d'authentification JWT
- [ ] Collections Payload CMS (utilisateurs + sessions)
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
Backend: Node.js/Express (logique métier + algorithmes)
CMS: Payload CMS (gestion contenu + utilisateurs)
Cache: Redis (sessions, calculs répétition espacée)
Authentification: JWT via Payload CMS
Déploiement: Docker + CI/CD
```

### Architecture API-First avec Payload CMS
```javascript
// Payload CMS Collections (gérées via API)

// Users (via Payload CMS Auth)
{
  id: string,
  email: string,
  planType: "free" | "premium",
  profile: {
    firstName: string,
    lastName: string,
    studyLevel: "PASS" | "LAS" | "ECN"
  },
  createdAt: Date,
  updatedAt: Date
}

// UserSessions (Collection Payload)
{
  id: string,
  user: relationship, // vers Users
  sessionType: "memorization" | "error_analysis" | "planning" | "anatomy",
  data: json,
  createdAt: Date,
  completedAt: Date
}

// SpacedRepetitionCards (Collection Payload)
{
  id: string,
  user: relationship,
  contentId: string,
  easeFactor: number, // 1.3 - 2.5
  interval: number,   // jours
  repetitions: number,
  nextReview: Date,
  lastReviewed: Date
}

// UserResponses (Collection Payload)
{
  id: string,
  user: relationship,
  questionId: string,
  response: string,
  isCorrect: boolean,
  responseTime: number, // ms
  session: relationship, // vers UserSessions
  createdAt: Date
}

// WeeklyPlans (Collection Payload)
{
  id: string,
  user: relationship,
  weekStart: Date,
  planData: json, // {subjects, schedule, totalHours}
  createdAt: Date
}

// AnatomySystems (Collection Payload - Admin managed)
{
  id: string,
  name: string,
  description: richText,
  difficultyLevel: number, // 1-5
  prerequisites: relationship[], // vers AnatomySystems
  estimatedDuration: number, // minutes
  slug: string
}

// AnatomyQuestions (Collection Payload - Admin managed)
{
  id: string,
  system: relationship, // vers AnatomySystems
  question: richText,
  options: array, // [{text: string, isCorrect: boolean}]
  difficulty: number, // 1-5
  tags: array, // [string]
  slug: string
}

// LearningPaths (Collection Payload)
{
  id: string,
  user: relationship,
  system: relationship, // vers AnatomySystems
  progress: number, // 0-100%
  completedQuestions: relationship[], // vers AnatomyQuestions
  startedAt: Date,
  completedAt: Date
}
```

### APIs principales (Dashboard-app ↔ Payload CMS)
```javascript
// Authentification (via Payload)
POST /api/users/login
POST /api/users/logout
GET  /api/users/me

// Fonctionnalités MVP (logique dans dashboard-app)
POST /api/spaced-repetition/review    // Calcul SuperMemo + update Payload
GET  /api/analytics/user-errors       // Analyse depuis Payload + Redis cache
POST /api/planning/generate           // Algorithme + sauvegarde Payload
GET  /api/anatomy/path/:system        // Récupère depuis Payload + logique parcours

// Collections Payload (CRUD standard)
GET    /api/anatomy-systems
GET    /api/anatomy-questions
POST   /api/user-sessions
PUT    /api/spaced-repetition-cards/:id
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

## Phase 2 - Optimisation SEO & Performance

### Problématique Vite/React SEO
**Défi :** Application SPA React avec Vite = mauvais SEO par défaut (contenu généré côté client)
**Impact :** Pages marketing (/marketing) non indexables correctement par Google

### Solutions techniques à évaluer
1. **Server-Side Rendering (SSR)**
   - Next.js migration ou Vite SSR
   - Rendu serveur pour pages marketing
   - Complexité : Élevée, refactoring complet

2. **Static Site Generation (SSG)**
   - Pré-génération pages marketing statiques
   - Astro, Next.js export, ou Vite SSG plugin
   - Complexité : Moyenne, pages spécifiques

3. **Pré-rendu (Prerendering)** ⭐ RECOMMANDÉ
   - `vite-plugin-prerender`, `vite-plugin-ssr`, ou `React Snap`
   - Génère HTML statique à la build pour pages fixes
   - Solution hybride : SPA dynamique + pages marketing statiques
   - Complexité : Faible, intégration Vite native
   - Avantages : SEO parfait + performance + garde la SPA

4. **React Helmet + Meta dynamiques**
   - Gestion meta tags côté client
   - Amélioration partielle SEO
   - Complexité : Très faible

### Recommandation : Approche progressive
**Phase 2A - Quick wins (2 semaines) :**
- [ ] React Helmet pour meta tags dynamiques
- [ ] Sitemap.xml et robots.txt optimisés
- [ ] Structure sémantique HTML5 (header, main, section, article)
- [ ] Schema.org markup pour pages marketing
- [ ] Core Web Vitals optimization

**Phase 2B - Solution Prerendering (3-4 semaines) :**
- [ ] Installation et configuration `vite-plugin-prerender`
- [ ] Configuration routes à pré-rendre : `/`, `/marketing`, `/login`, `/onboarding`
- [ ] Intégration React Helmet pour meta tags dynamiques
- [ ] Build process : génération HTML statique + hydratation React
- [ ] Tests SEO complets et validation indexation Google
- [ ] Monitoring Core Web Vitals et performance

**Implémentation technique recommandée :**
```javascript
// vite.config.ts
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { prerender } from 'vite-plugin-prerender'

export default defineConfig({
  plugins: [
    react(),
    prerender({
      routes: ['/', '/marketing', '/login', '/onboarding'],
      rendererOptions: {
        renderAfterDocumentEvent: 'render-event'
      }
    })
  ]
})
```

**Avantages solution Prerendering :**
- ✅ SEO parfait pour pages marketing (HTML statique)
- ✅ Performance excellente (pas de serveur SSR)
- ✅ Garde la SPA pour dashboard privé
- ✅ Intégration Vite native sans refactoring
- ✅ Coûts d'hébergement faibles (statique)
- ✅ Déploiement simple (CDN/Netlify/Vercel)

### Critères de déclenchement Phase 2
- [ ] MVP validé avec trafic organique insuffisant
- [ ] Pages marketing non indexées par Google
- [ ] Concurrence SEO identifiée
- [ ] Budget développement disponible

## Phase 3 - Évolution IA (Optionnelle)

### Fonctionnalités IA à ajouter si besoin
1. **Préparer examen blanc** - Génération contenu varié via LLM
2. **Coaching motivation** - IA conversationnelle personnalisée

### Critères de déclenchement Phase 3
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
