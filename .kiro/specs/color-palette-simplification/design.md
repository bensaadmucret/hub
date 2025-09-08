# Design Document - Simplification de la Palette de Couleurs

## Overview

Ce document décrit la conception d'un système de couleurs simplifié pour le dashboard-app, centré autour de la couleur verte marketing comme couleur principale. L'objectif est de réduire la complexité chromatique tout en maintenant une hiérarchie visuelle claire et une excellente accessibilité.

## Architecture

### Système de Couleurs Actuel
L'application utilise actuellement :
- Un système de design tokens centralisé dans `src/design-system/foundations/colors.ts`
- Des variables CSS personnalisées dans `src/index.css`
- Une configuration Tailwind étendue dans `tailwind.config.ts`
- Multiple couleurs : bleu (primaire), violet (secondaire), gris, plus les couleurs sémantiques

### Nouvelle Architecture Proposée
La nouvelle architecture simplifiera le système en :
1. **Couleur Principale** : Vert marketing (#059669 - green-600)
2. **Couleurs Secondaires** : Variations du vert + gris neutre
3. **Couleurs Sémantiques** : Rouge (erreur), Jaune (attention) uniquement
4. **Couleurs de Support** : Échelle de gris pour la hiérarchie

## Components and Interfaces

### 1. Système de Tokens de Couleurs

#### Structure des Tokens Simplifiés
```typescript
export const simplifiedColors = {
  // Couleur principale - Vert marketing
  primary: {
    50: '#ECFDF5',   // Très clair pour les arrière-plans
    100: '#D1FAE5',  // Clair pour les badges
    200: '#A7F3D0',  // 
    300: '#6EE7B7',  // 
    400: '#34D399',  // 
    500: '#10B981',  // Vert standard
    600: '#059669',  // Vert marketing (principal)
    700: '#047857',  // Vert foncé pour hover
    800: '#065F46',  // Très foncé
    900: '#064E3B',  // Le plus foncé
  },
  
  // Couleurs neutres (gris)
  neutral: {
    50: '#F9FAFB',
    100: '#F3F4F6',
    200: '#E5E7EB',
    300: '#D1D5DB',
    400: '#9CA3AF',
    500: '#6B7280',
    600: '#4B5563',
    700: '#374151',
    800: '#1F2937',
    900: '#111827',
  },
  
  // Couleurs sémantiques (limitées)
  semantic: {
    error: '#DC2626',    // Rouge pour les erreurs
    warning: '#D97706',  // Orange pour les avertissements
    white: '#FFFFFF',
    black: '#000000',
  }
}
```

### 2. Mapping des Variables CSS

#### Variables CSS Mises à Jour
```css
:root {
  /* Couleurs principales basées sur le vert marketing */
  --primary: 158 64% 52%;           /* green-600 */
  --primary-foreground: 0 0% 100%; /* white */
  
  /* Couleurs secondaires (variations du vert) */
  --secondary: 158 58% 45%;         /* green-700 */
  --secondary-foreground: 0 0% 100%;
  
  /* Couleurs d'accent (vert plus clair) */
  --accent: 158 76% 60%;            /* green-500 */
  --accent-foreground: 158 84% 15%; /* green-900 */
  
  /* Couleurs neutres */
  --muted: 210 20% 96%;             /* neutral-100 */
  --muted-foreground: 215 25% 27%;  /* neutral-700 */
  
  /* Couleurs destructives */
  --destructive: 0 84% 60%;         /* error red */
  --destructive-foreground: 0 0% 100%;
  
  /* Couleurs de fond et bordures */
  --background: 0 0% 100%;          /* white */
  --foreground: 222 84% 5%;         /* neutral-900 */
  --border: 214 32% 91%;            /* neutral-200 */
  --input: 214 32% 91%;             /* neutral-200 */
  --ring: 158 64% 52%;              /* primary green */
}
```

### 3. Composants Affectés

#### Boutons
- **Primaire** : Fond vert marketing, texte blanc
- **Secondaire** : Bordure verte, texte vert, fond transparent
- **Tertiaire** : Texte vert, fond gris très clair

#### Badges et Indicateurs
- **Succès** : Variations du vert (100, 600, 700)
- **Attention** : Jaune/orange uniquement pour les vrais avertissements
- **Erreur** : Rouge uniquement pour les erreurs
- **Neutre** : Échelle de gris

#### Navigation et Sidebar
- **Élément actif** : Fond vert clair (green-100), texte vert foncé (green-700)
- **Hover** : Fond gris très clair (neutral-50)
- **Texte par défaut** : Gris moyen (neutral-600)

## Data Models

### Configuration des Couleurs
```typescript
interface ColorPalette {
  primary: ColorScale;
  neutral: ColorScale;
  semantic: {
    error: string;
    warning: string;
    white: string;
    black: string;
  };
}

interface ColorScale {
  50: string;
  100: string;
  200: string;
  300: string;
  400: string;
  500: string;
  600: string;
  700: string;
  800: string;
  900: string;
}
```

### Mapping Sémantique
```typescript
interface SemanticColorMapping {
  action: {
    primary: string;        // green-600
    primaryHover: string;   // green-700
    secondary: string;      // green-100 avec bordure green-600
    disabled: string;       // neutral-300
  };
  
  status: {
    success: string;        // green-600
    warning: string;        // warning orange
    error: string;          // error red
    info: string;           // green-500
  };
  
  text: {
    primary: string;        // neutral-900
    secondary: string;      // neutral-600
    tertiary: string;       // neutral-400
    inverse: string;        // white
    accent: string;         // green-700
  };
}
```

## Error Handling

### Gestion des Couleurs Manquantes
- **Fallback** : Si une couleur spécifique n'est pas définie, utiliser la couleur neutre équivalente
- **Validation** : Vérifier que toutes les couleurs respectent les ratios de contraste WCAG AA (4.5:1)
- **Mode Dégradé** : En cas d'échec de chargement des couleurs personnalisées, revenir aux couleurs système

### Accessibilité
- **Contraste Minimum** : 4.5:1 pour le texte normal, 3:1 pour le texte large
- **Indicateurs Non-Chromatiques** : Utiliser des icônes et des motifs en plus des couleurs
- **Test Daltonisme** : Vérifier la lisibilité pour les différents types de daltonisme

## Testing Strategy

### Tests Visuels
1. **Regression Testing** : Captures d'écran avant/après pour tous les composants
2. **Contrast Testing** : Validation automatique des ratios de contraste
3. **Cross-Browser Testing** : Vérification sur Chrome, Firefox, Safari, Edge

### Tests d'Accessibilité
1. **WCAG Compliance** : Tests automatisés avec axe-core
2. **Screen Reader Testing** : Validation avec NVDA/JAWS
3. **Keyboard Navigation** : Test de la visibilité du focus

### Tests de Performance
1. **CSS Bundle Size** : Mesurer l'impact sur la taille du bundle
2. **Render Performance** : Vérifier que les changements n'affectent pas les performances
3. **Color Computation** : Optimiser les calculs de couleurs dynamiques

### Tests Unitaires
```typescript
describe('Color System', () => {
  test('should use green-600 as primary color', () => {
    expect(tokens.colors.primary).toBe('#059669');
  });
  
  test('should maintain contrast ratios', () => {
    const contrast = calculateContrast(tokens.colors.primary, tokens.colors.white);
    expect(contrast).toBeGreaterThanOrEqual(4.5);
  });
  
  test('should provide fallback colors', () => {
    expect(tokens.colors.fallback).toBeDefined();
  });
});
```

### Migration Strategy
1. **Phase 1** : Mise à jour des tokens de couleurs
2. **Phase 2** : Migration des composants de base (boutons, badges)
3. **Phase 3** : Migration des composants complexes (navigation, formulaires)
4. **Phase 4** : Nettoyage des anciennes couleurs et validation finale

Cette approche garantit une transition en douceur vers le nouveau système de couleurs tout en maintenant la fonctionnalité et l'accessibilité de l'application.