# Plan de Migration : de Symfony vers Next.js & Payload CMS (v2 - Mis à jour)

**Objectif :** Unifier l'architecture technique sur une stack JavaScript moderne (Next.js, Payload CMS, React) pour simplifier le développement, l'hébergement et la maintenance, tout en améliorant les performances et le SEO.

---

## État des Lieux (Analyse)

*   **Stack Technique Confirmée :** Le projet `payload-cms` est une application **Next.js** utilisant Payload CMS comme backend. Il est configuré pour une base de données **PostgreSQL**.
*   **Backend Prêt pour l'Onboarding :** La collection `Users` dans Payload contient déjà tous les champs nécessaires (`firstName`, `studyYear`, etc.) pour l'inscription. **Aucune modification du backend n'est requise pour cette fonctionnalité.**
*   **Frontend Prêt pour Développement :** La structure de fichiers pour le tunnel d'inscription (`/onboarding/page.tsx` et les composants d'étape) est déjà en place dans l'application Next.js.

---

## Feuille de Route du Développement

### Étape 1 : Développement des Fonctionnalités Clés

L'objectif est de rendre l'application Next.js/Payload autonome en y intégrant les fonctionnalités de l'application Symfony.

1.  **Implémenter le Tunnel d'Inscription (Onboarding) :**
    *   Voir le plan d'action détaillé dans le document : [`TODO/ONBOARDING_TUNNEL_FEATURE.md`](../TODO/ONBOARDING_TUNNEL_FEATURE.md)

2.  **Implémenter l'Intégration des Paiements (Paddle) :**
    *   Voir le plan d'action détaillé dans le document : [`TODO/PADDLE_PAYMENT_FEATURE.md`](../TODO/PADDLE_PAYMENT_FEATURE.md)

3.  **Construire les Pages Marketing (Vitrine) :**
    *   Voir le plan d'action détaillé dans le document : [`TODO/MARKETING_PAGES_FEATURE.md`](../TODO/MARKETING_PAGES_FEATURE.md)

### Étape 2 : Préparation au Déploiement

Cette phase sera entamée une fois les fonctionnalités de l'Étape 1 développées et validées.

1.  **Mise en place de la Base de Données de Production :**
    *   **Action :** Créer un projet sur un fournisseur de PostgreSQL compatible "serverless" (ex: **Neon** ou **Vercel Postgres**).
    *   **Objectif :** Obtenir une chaîne de connexion optimisée pour Vercel.

2.  **Configuration du Déploiement sur Vercel :**
    *   **Action :** Configurer les variables d'environnement dans le dashboard Vercel (`DATABASE_URI`, `PAYLOAD_SECRET`, etc.).
    *   **Fait :** Le fichier de configuration `vercel.json` a déjà été créé.

### Étape 3 : Migration des Données et Bascule

1.  **Migration des Données (Optionnel) :**
    *   Si nécessaire, écrire des scripts pour migrer les données existantes (utilisateurs, etc.) de la base de données Symfony vers la nouvelle base de données PostgreSQL de production.

2.  **Déploiement et Tests :**
    *   Déployer l'application sur Vercel.
    *   Effectuer des tests de bout en bout sur l'environnement de production.

3.  **Configuration DNS et Mise hors service de Symfony :**
    *   Faire pointer le nom de domaine sur Vercel.
    *   Archiver et arrêter l'application Symfony.
