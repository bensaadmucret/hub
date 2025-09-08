# Implementation Plan - Simplification de la Palette de Couleurs

- [x] 1. Mettre à jour le système de tokens de couleurs
  - Modifier le fichier `src/design-system/foundations/colors.ts` pour intégrer la nouvelle palette centrée sur le vert marketing
  - Remplacer les couleurs bleues et violettes par les variations de vert
  - Conserver uniquement les couleurs sémantiques essentielles (rouge erreur, orange attention)
  - _Requirements: 1.1, 2.1, 6.1, 6.3_

- [x] 2. Configurer les variables CSS avec la nouvelle palette
  - Mettre à jour les variables CSS dans `src/index.css` pour utiliser les nouvelles couleurs vertes
  - Modifier les variables `--primary`, `--secondary`, `--accent` pour utiliser les teintes de vert marketing
  - Ajuster les variables de thème sombre pour maintenir la cohérence
  - _Requirements: 1.1, 2.2, 6.1_

- [x] 3. Mettre à jour la configuration Tailwind
  - Modifier `tailwind.config.ts` pour intégrer les nouveaux tokens de couleurs
  - Supprimer les références aux anciennes couleurs bleues et violettes
  - Configurer les nouvelles classes utilitaires basées sur le vert marketing
  - _Requirements: 2.1, 2.2, 6.1_

- [x] 4. Migrer les composants de base - Boutons
  - Mettre à jour le composant Button pour utiliser les nouvelles couleurs vertes
  - Modifier les variantes primary, secondary, et outline pour utiliser la palette verte
  - Ajuster les états hover, focus, et disabled avec les nouvelles couleurs
  - Créer des tests unitaires pour valider les nouvelles couleurs
  - _Requirements: 1.1, 4.1, 4.2, 4.3, 6.2, 6.4_

- [x] 5. Migrer les composants Badge et indicateurs
  - Mettre à jour le composant Badge dans `src/design-system/components/atoms/Badge.tsx`
  - Remplacer les variantes blue et purple par des variations de vert
  - Conserver uniquement les badges sémantiques nécessaires (success, warning, error)
  - Tester toutes les variantes de badges avec la nouvelle palette
  - _Requirements: 1.1, 1.2, 6.3_

- [ ] 6. Migrer les composants Input et formulaires
  - Mettre à jour le composant Input pour utiliser les couleurs vertes pour les états success et focus
  - Modifier les indicateurs de validation pour utiliser la nouvelle palette
  - Ajuster les couleurs des labels et messages d'aide
  - _Requirements: 1.1, 3.1, 4.1, 4.2_

- [x] 7. Mettre à jour la navigation et sidebar
  - Modifier les couleurs de la sidebar pour utiliser le vert marketing pour les éléments actifs
  - Ajuster les couleurs de hover et focus dans la navigation
  - Mettre à jour les indicateurs d'état dans le menu
  - _Requirements: 1.1, 1.2, 4.1, 4.2, 6.1_

- [x] 8. Migrer les pages de quiz et résultats
  - Mettre à jour `src/pages/QuizResultPage.tsx` pour utiliser les nouvelles couleurs vertes pour les réponses correctes
  - Modifier `src/pages/StudySessionPage.tsx` pour les indicateurs de succès
  - Ajuster `src/pages/PlacementQuizPage.tsx` pour les boutons d'action
  - _Requirements: 1.1, 1.2, 6.3_

- [x] 9. Migrer les pages du tableau de bord
  - Mettre à jour `src/pages/TeacherDashboard.tsx` pour les indicateurs de statut
  - Modifier `src/pages/QuizPage.tsx` pour les badges de score
  - Ajuster les couleurs des cartes et éléments interactifs
  - _Requirements: 1.1, 1.2, 5.1, 5.2_

- [x] 10. Mettre à jour les composants marketing
  - Vérifier que `src/components/marketing/Hero.tsx` utilise bien la couleur verte de référence
  - Ajuster `src/components/marketing/PricingCard.tsx` et `PricingGrid.tsx` si nécessaire
  - S'assurer de la cohérence entre les pages marketing et le dashboard
  - _Requirements: 6.1, 6.2_

- [x] 11. Créer des tests de contraste et d'accessibilité
  - Implémenter des tests automatisés pour vérifier les ratios de contraste WCAG AA
  - Créer des tests pour valider que les couleurs ne sont pas le seul moyen de transmettre l'information
  - Tester la compatibilité avec les lecteurs d'écran
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 12. Nettoyer les anciennes couleurs
  - Supprimer les références aux couleurs bleues et violettes non utilisées
  - Nettoyer les classes CSS obsolètes
  - Mettre à jour la documentation des couleurs dans le design system
  - _Requirements: 2.1, 2.2_

- [x] 13. Créer des tests de régression visuelle
  - Implémenter des tests de capture d'écran pour tous les composants modifiés
  - Créer des tests Storybook pour valider les nouvelles couleurs
  - Tester sur différents navigateurs et appareils
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 14. Valider la hiérarchie visuelle
  - Tester que la hiérarchie de l'information est maintenue avec les nouvelles couleurs
  - Vérifier que les éléments importants restent bien visibles
  - Ajuster les variations de saturation et luminosité si nécessaire
  - _Requirements: 5.1, 5.2, 5.3_

- [x] 15. Documentation et formation
  - Mettre à jour la documentation du design system avec les nouvelles couleurs
  - Créer un guide de migration pour les futurs développements
  - Documenter les bonnes pratiques d'utilisation de la nouvelle palette
  - _Requirements: 2.1, 2.2_