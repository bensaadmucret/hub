# Design Document - Composants de Formulaire Avancés

## Overview

Ce document décrit la conception d'un système complet de composants de formulaire pour l'application MedCoach, utilisant la nouvelle palette verte marketing. Le système comprend des composants de base (Input, Select, Checkbox, Radio) et des composants avancés (SearchInput, MultiSelect, FileUpload) avec validation en temps réel et accessibilité WCAG AA.

## Architecture

### Système de Composants Actuel
L'application dispose actuellement d'un composant Input basique dans `src/design-system/components/atoms/Input.tsx` qui utilise encore partiellement les anciennes couleurs.

### Nouvelle Architecture Proposée
La nouvelle architecture étendra le système existant avec :

1. **Composants de Base Améliorés**
   - Input avec validation en temps réel
   - Select avec recherche intégrée
   - Checkbox et Radio avec états visuels clairs
   - Textarea avec compteur de caractères

2. **Composants Avancés**
   - SearchInput avec suggestions
   - MultiSelect avec tags
   - FileUpload avec drag & drop
   - FormField wrapper avec label et validation

3. **Système de Validation**
   - Validation synchrone et asynchrone
   - Messages d'erreur contextuels
   - États visuels cohérents

## Components and Interfaces

### 1. Composant Input Amélioré

#### Interface TypeScript
```typescript
interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  variant?: 'default' | 'error' | 'success' | 'warning';
  size?: 'sm' | 'md' | 'lg';
  label?: string;
  helperText?: string;
  errorMessage?: string;
  isLoading?: boolean;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
  onValidate?: (value: string) => Promise<string | null>;
}
```

#### États Visuels
```css
/* État normal */
.input-default {
  border-color: theme('colors.neutral.300');
  focus:border-color: theme('colors.primary.500');
  focus:ring-color: theme('colors.primary.500');
}

/* État d'erreur */
.input-error {
  border-color: theme('colors.error.500');
  focus:border-color: theme('colors.error.500');
  focus:ring-color: theme('colors.error.500');
}

/* État de succès */
.input-success {
  border-color: theme('colors.success.500');
  focus:border-color: theme('colors.success.500');
  focus:ring-color: theme('colors.success.500');
}
```

### 2. Composant Select

#### Interface TypeScript
```typescript
interface SelectOption {
  value: string;
  label: string;
  disabled?: boolean;
  icon?: React.ReactNode;
}

interface SelectProps {
  options: SelectOption[];
  value?: string | string[];
  onChange: (value: string | string[]) => void;
  placeholder?: string;
  searchable?: boolean;
  multiple?: boolean;
  variant?: 'default' | 'error' | 'success';
  size?: 'sm' | 'md' | 'lg';
  isLoading?: boolean;
  label?: string;
  errorMessage?: string;
}
```

#### Fonctionnalités
- Recherche intégrée avec filtrage
- Multi-sélection avec tags
- Groupement d'options
- Virtualisation pour grandes listes
- Accessibilité clavier complète

### 3. Composants Checkbox et Radio

#### Interface Checkbox
```typescript
interface CheckboxProps {
  checked?: boolean;
  onChange: (checked: boolean) => void;
  label?: string;
  description?: string;
  variant?: 'default' | 'error' | 'success';
  size?: 'sm' | 'md' | 'lg';
  indeterminate?: boolean;
  disabled?: boolean;
}
```

#### Interface Radio
```typescript
interface RadioProps {
  name: string;
  value: string;
  checked?: boolean;
  onChange: (value: string) => void;
  label?: string;
  description?: string;
  variant?: 'default' | 'error' | 'success';
  size?: 'sm' | 'md' | 'lg';
  disabled?: boolean;
}

interface RadioGroupProps {
  name: string;
  value?: string;
  onChange: (value: string) => void;
  options: Array<{
    value: string;
    label: string;
    description?: string;
    disabled?: boolean;
  }>;
  orientation?: 'horizontal' | 'vertical';
  variant?: 'default' | 'error' | 'success';
}
```

### 4. Composant FileUpload

#### Interface TypeScript
```typescript
interface FileUploadProps {
  accept?: string;
  multiple?: boolean;
  maxSize?: number; // en bytes
  maxFiles?: number;
  onUpload: (files: File[]) => Promise<void>;
  onError?: (error: string) => void;
  variant?: 'default' | 'error' | 'success';
  dragAndDrop?: boolean;
  showPreview?: boolean;
  label?: string;
  helperText?: string;
}
```

#### Fonctionnalités
- Drag & drop avec zone de dépôt
- Validation de type et taille de fichier
- Prévisualisation d'images
- Barre de progression d'upload
- Support multi-fichiers avec gestion individuelle

### 5. Composant FormField Wrapper

#### Interface TypeScript
```typescript
interface FormFieldProps {
  label?: string;
  required?: boolean;
  helperText?: string;
  errorMessage?: string;
  successMessage?: string;
  children: React.ReactNode;
  htmlFor?: string;
  variant?: 'default' | 'error' | 'success' | 'warning';
}
```

## Data Models

### Système de Validation
```typescript
interface ValidationRule {
  type: 'required' | 'email' | 'minLength' | 'maxLength' | 'pattern' | 'custom';
  value?: any;
  message: string;
  validator?: (value: any) => boolean | Promise<boolean>;
}

interface FieldState {
  value: any;
  error: string | null;
  touched: boolean;
  validating: boolean;
  valid: boolean;
}

interface FormState {
  fields: Record<string, FieldState>;
  isValid: boolean;
  isSubmitting: boolean;
  submitCount: number;
}
```

### Hook de Gestion de Formulaire
```typescript
interface UseFormOptions {
  initialValues: Record<string, any>;
  validationRules?: Record<string, ValidationRule[]>;
  onSubmit: (values: Record<string, any>) => Promise<void>;
  validateOnChange?: boolean;
  validateOnBlur?: boolean;
}

interface UseFormReturn {
  values: Record<string, any>;
  errors: Record<string, string | null>;
  touched: Record<string, boolean>;
  isValid: boolean;
  isSubmitting: boolean;
  handleChange: (name: string, value: any) => void;
  handleBlur: (name: string) => void;
  handleSubmit: (e: React.FormEvent) => void;
  setFieldValue: (name: string, value: any) => void;
  setFieldError: (name: string, error: string | null) => void;
  resetForm: () => void;
}
```

## Error Handling

### Gestion des Erreurs de Validation
```typescript
// Types d'erreurs
type ValidationError = {
  field: string;
  message: string;
  type: 'client' | 'server';
};

// Gestionnaire d'erreurs centralisé
class FormErrorHandler {
  static handleValidationError(error: ValidationError): string {
    switch (error.type) {
      case 'client':
        return this.getClientErrorMessage(error);
      case 'server':
        return this.getServerErrorMessage(error);
      default:
        return 'Une erreur est survenue';
    }
  }
  
  static getClientErrorMessage(error: ValidationError): string {
    // Messages d'erreur localisés
    const messages = {
      required: 'Ce champ est requis',
      email: 'Veuillez saisir une adresse email valide',
      minLength: `Minimum ${error.value} caractères requis`,
      maxLength: `Maximum ${error.value} caractères autorisés`,
      // ...
    };
    return messages[error.type] || error.message;
  }
}
```

### États d'Erreur Visuels
- **Bordure rouge** pour les champs en erreur
- **Icône d'erreur** dans le champ
- **Message d'erreur** sous le champ avec couleur error-600
- **Focus automatique** sur le premier champ en erreur lors de la soumission

## Testing Strategy

### Tests Unitaires
```typescript
describe('Input Component', () => {
  it('should display error state with red border', () => {
    render(<Input variant="error" errorMessage="Champ requis" />);
    expect(screen.getByRole('textbox')).toHaveClass('border-error-500');
    expect(screen.getByText('Champ requis')).toBeInTheDocument();
  });

  it('should display success state with green border', () => {
    render(<Input variant="success" />);
    expect(screen.getByRole('textbox')).toHaveClass('border-success-500');
  });

  it('should handle async validation', async () => {
    const mockValidate = jest.fn().mockResolvedValue('Email déjà utilisé');
    render(<Input onValidate={mockValidate} />);
    
    fireEvent.change(screen.getByRole('textbox'), { target: { value: 'test@test.com' } });
    fireEvent.blur(screen.getByRole('textbox'));
    
    await waitFor(() => {
      expect(screen.getByText('Email déjà utilisé')).toBeInTheDocument();
    });
  });
});
```

### Tests d'Accessibilité
```typescript
describe('Form Accessibility', () => {
  it('should have proper ARIA labels', () => {
    render(
      <FormField label="Email" required errorMessage="Email requis">
        <Input />
      </FormField>
    );
    
    const input = screen.getByRole('textbox');
    expect(input).toHaveAttribute('aria-required', 'true');
    expect(input).toHaveAttribute('aria-invalid', 'true');
    expect(input).toHaveAttribute('aria-describedby');
  });

  it('should support keyboard navigation', () => {
    render(<Select options={mockOptions} />);
    
    const select = screen.getByRole('combobox');
    fireEvent.keyDown(select, { key: 'ArrowDown' });
    
    expect(screen.getByRole('option')).toHaveFocus();
  });
});
```

### Tests de Régression Visuelle
```typescript
describe('Form Visual Regression', () => {
  it('should use green marketing colors for success states', () => {
    const { container } = render(<Input variant="success" />);
    expect(container.querySelector('.border-success-500')).toBeInTheDocument();
  });

  it('should not use deprecated blue colors', () => {
    const { container } = render(<Select options={mockOptions} />);
    expect(container.innerHTML).not.toMatch(/blue-\d+/);
  });
});
```

## Performance Considerations

### Optimisations
1. **Debouncing** pour la validation en temps réel
2. **Virtualisation** pour les listes longues dans Select
3. **Lazy loading** pour les composants complexes
4. **Memoization** des options et callbacks
5. **Code splitting** pour les composants avancés

### Métriques de Performance
- Temps de rendu initial < 100ms
- Temps de réponse validation < 200ms
- Taille du bundle < 50KB pour les composants de base
- Score Lighthouse > 90 pour l'accessibilité

## Migration Strategy

### Phase 1 - Composants de Base
1. Mettre à jour le composant Input existant
2. Créer les composants Checkbox et Radio
3. Implémenter le composant Select de base

### Phase 2 - Validation et États
1. Implémenter le système de validation
2. Ajouter les états visuels (erreur, succès, chargement)
3. Créer le hook useForm

### Phase 3 - Composants Avancés
1. Développer SearchInput et MultiSelect
2. Implémenter FileUpload avec drag & drop
3. Créer FormField wrapper

### Phase 4 - Tests et Documentation
1. Écrire les tests unitaires et d'accessibilité
2. Créer les stories Storybook
3. Rédiger la documentation d'utilisation

Cette approche garantit une implémentation progressive et testée de tous les composants de formulaire avec la nouvelle palette verte marketing.