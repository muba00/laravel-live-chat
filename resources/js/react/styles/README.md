# CSS Architecture Documentation

## Overview

The Laravel Live Chat React component uses a **BEM (Block Element Modifier)** naming convention combined with **CSS Variables** for theming. This approach provides:

- Zero runtime overhead (no CSS-in-JS)
- Easy customization without touching JavaScript
- Runtime theme switching (light/dark mode)
- Framework-agnostic styling
- No naming conflicts (`.lc-` prefix)

## BEM Naming Convention

### Structure
```
.lc-block__element--modifier
```

### Prefix
All classes use the `.lc-` prefix (LiveChat) to avoid conflicts with existing styles.

### Examples

#### Block
```css
.lc-conversation { }
.lc-message { }
.lc-input { }
```

#### Element
```css
.lc-conversation__list { }
.lc-conversation__item { }
.lc-message__text { }
.lc-message__timestamp { }
```

#### Modifier
```css
.lc-message--sent { }
.lc-message--received { }
.lc-conversation__item--active { }
.lc-conversation__item--unread { }
```

## CSS Variables

### Color System

#### Light Mode (Default)
```css
:root {
  --lc-color-primary: #4f46e5;
  --lc-color-background: #ffffff;
  --lc-color-text: #111827;
  /* ... */
}
```

#### Dark Mode
```css
[data-theme="dark"] {
  --lc-color-primary: #6366f1;
  --lc-color-background: #111827;
  --lc-color-text: #f9fafb;
  /* ... */
}
```

### Design Tokens

#### Spacing Scale
```css
--lc-space-xs: 0.25rem;  /* 4px */
--lc-space-sm: 0.5rem;   /* 8px */
--lc-space-md: 0.75rem;  /* 12px */
--lc-space-lg: 1rem;     /* 16px */
--lc-space-xl: 1.5rem;   /* 24px */
--lc-space-2xl: 2rem;    /* 32px */
--lc-space-3xl: 3rem;    /* 48px */
```

#### Typography
```css
--lc-font-size-xs: 0.75rem;   /* 12px */
--lc-font-size-sm: 0.875rem;  /* 14px */
--lc-font-size-base: 1rem;    /* 16px */
--lc-font-size-lg: 1.125rem;  /* 18px */
--lc-font-size-xl: 1.25rem;   /* 20px */

--lc-font-weight-normal: 400;
--lc-font-weight-medium: 500;
--lc-font-weight-semibold: 600;
--lc-font-weight-bold: 700;
```

#### Border Radius
```css
--lc-radius-sm: 0.25rem;  /* 4px */
--lc-radius-md: 0.5rem;   /* 8px */
--lc-radius-lg: 0.75rem;  /* 12px */
--lc-radius-xl: 1rem;     /* 16px */
--lc-radius-full: 9999px;
```

#### Shadows
```css
--lc-shadow-xs: 0 1px 2px 0 rgb(0 0 0 / 0.05);
--lc-shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
--lc-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
--lc-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
--lc-shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
```

## Customization

### Override CSS Variables

Create a custom stylesheet after importing the component styles:

```css
/* Import component styles first */
@import '@muba00/laravel-live-chat-react/styles';

/* Override variables */
:root {
  --lc-color-primary: #7c3aed; /* Purple */
  --lc-sidebar-width: 24rem;   /* Wider sidebar */
  --lc-font-family: 'Inter', sans-serif;
}
```

### Theme Switching

Use the `data-theme` attribute on the container:

```jsx
<div data-theme="dark">
  <LiveChat userId={1} />
</div>
```

Or apply the class:

```jsx
<div className="lc-theme-dark">
  <LiveChat userId={1} />
</div>
```

### Override Specific Components

Use CSS specificity to override component styles:

```css
/* Make messages more rounded */
.lc-message__bubble {
  border-radius: var(--lc-radius-xl);
}

/* Change conversation item hover color */
.lc-conversation__item:hover {
  background-color: var(--lc-color-primary-light);
}
```

## Responsive Design

### Breakpoints

```css
/* Mobile: < 768px (default) */
/* Tablet: >= 768px */
@media (min-width: 768px) { }

/* Desktop: >= 1024px */
@media (min-width: 1024px) { }
```

### Mobile Behavior

On mobile (< 768px):
- Sidebar becomes a full-screen overlay
- Slides in from the left
- Uses `transform` for smooth animation
- Z-index: `var(--lc-z-modal)`

## Accessibility

### Focus Styles

All interactive elements include focus-visible styles:

```css
.lc-focus-ring:focus-visible {
  outline: 2px solid var(--lc-color-border-focus);
  outline-offset: 2px;
}
```

### Screen Reader Only Content

Use `.lc-sr-only` for visually hidden but accessible content:

```jsx
<span className="lc-sr-only">New message from John Doe</span>
```

### High Contrast Mode

The component respects user preferences for high contrast mode via CSS variables.

## Performance

### Optimizations

1. **CSS Variables**: Computed once, referenced many times
2. **Hardware Acceleration**: `transform` and `opacity` for animations
3. **Minimal Repaints**: Use `transform` instead of `top`/`left`
4. **Efficient Selectors**: Single class selectors (BEM), no deep nesting

### Scrollbar Optimization

Custom scrollbar styles are optional and don't affect layout:

```css
.lc-scrollbar::-webkit-scrollbar {
  width: 8px;
}
```

## Animation Guidelines

### Use CSS Transitions

```css
transition: var(--lc-transition-base); /* 200ms ease-in-out */
```

Available speeds:
- `--lc-transition-fast`: 150ms
- `--lc-transition-base`: 200ms
- `--lc-transition-slow`: 300ms

### Keyframe Animations

Pre-defined animations:
- `lc-fade-in`: Fade in element
- `lc-slide-up`: Slide up with fade
- `lc-slide-down`: Slide down with fade
- `lc-spin`: Rotation (loading spinners)
- `lc-pulse`: Opacity pulse

## Best Practices

1. **Always use CSS variables** for colors, spacing, and other design tokens
2. **Follow BEM naming** for all new components
3. **Prefix all classes** with `.lc-` to avoid conflicts
4. **Mobile-first approach** - write mobile styles first, then media queries
5. **Use semantic HTML** with appropriate ARIA attributes
6. **Minimize specificity** - single class selectors when possible
7. **Group related properties** - layout, typography, colors, transitions
8. **Comment complex styles** - explain why, not what

## File Organization

```
styles/
├── README.md              # This file
├── live-chat.css          # Main stylesheet (variables + base)
├── components/            # Future: Component-specific styles
│   ├── conversation.css
│   ├── message.css
│   └── input.css
└── utilities/             # Future: Utility classes
    └── helpers.css
```

Currently, all styles are in `live-chat.css` for simplicity. As the component grows, we may split into separate files.
