# TAVRO DESIGN TOKENS

**Semantic design tokens for consistent implementation across web, mobile, POS, and print.**

---

## TOKEN STRUCTURE

Organized by category:

```
brand/
├── color/
│   ├── primary
│   ├── secondary
│   ├── neutral
│   ├── functional
│   └── semantic
├── typography/
│   ├── font-family
│   ├── font-size
│   ├── font-weight
│   ├── line-height
│   └── letter-spacing
├── spacing/
├── border/
├── shadow/
└── component/
```

---

## COLOR TOKENS

### Brand Colors (Primary)

```
--color-brand-primary:       #1F2937  (Charcoal)
--color-brand-primary-dark:  #111827  (Charcoal Dark)
--color-brand-primary-light: #374151  (Charcoal Light)

--color-brand-secondary:       #D97706  (Amber)
--color-brand-secondary-dark:  #B45309  (Amber Dark)
--color-brand-secondary-light: #FBBF24  (Amber Light)

--color-brand-tertiary: #F5DEB3  (Warm Stone)
```

### Neutral Colors

```
--color-neutral-50:   #FAFAFA  (Off-White)
--color-neutral-100:  #F9FAFB  (Light Surface)
--color-neutral-200:  #F3F4F6  (Dark Surface)
--color-neutral-300:  #E5E7EB  (Light Border)
--color-neutral-400:  #D1D5DB  (Medium Gray)
--color-neutral-500:  #9CA3AF  (Dark Gray)
--color-neutral-600:  #6B7280  (Medium Gray Text)
--color-neutral-700:  #374151  (Dark Gray Text)
--color-neutral-900:  #1F2937  (Charcoal - Text)
```

### Semantic Colors (Light Mode)

```
--color-background:        #FAFAFA
--color-surface:           #FFFFFF
--color-surface-secondary: #F9FAFB
--color-surface-tertiary:  #F3F4F6

--color-border-light:      #F0F0F0
--color-border:            #E5E7EB
--color-border-dark:       #D4D4D8

--color-text-primary:      #1F2937
--color-text-secondary:    #6B7280
--color-text-muted:        #9CA3AF
--color-text-inverse:      #FAFAFA
```

### Semantic Colors (Dark Mode)

```
--color-background-dark:       #0F172A
--color-surface-dark:          #1E293B
--color-surface-dark-secondary: #334155

--color-text-primary-dark:     #F8FAFC
--color-text-secondary-dark:   #CBD5E1
--color-text-muted-dark:       #94A3B8
```

### Functional Colors

```
--color-success:    #059669  (Emerald)
--color-warning:    #F97316  (Orange)
--color-error:      #DC2626  (Red)
--color-info:       #2563EB  (Blue)
```

### Component-Specific Colors

```
--color-button-primary-bg:      #D97706
--color-button-primary-text:    #1F2937
--color-button-primary-hover:   #B45309
--color-button-primary-active:  #92400E
--color-button-primary-disabled: #D1D5DB

--color-button-secondary-bg:    #FFFFFF
--color-button-secondary-border: #E5E7EB
--color-button-secondary-text:  #1F2937
--color-button-secondary-hover: #F9FAFB

--color-input-bg:       #FFFFFF
--color-input-border:   #E5E7EB
--color-input-focus:    #D97706
--color-input-text:     #1F2937
--color-input-disabled: #F3F4F6

--color-focus-ring: #D97706
```

---

## TYPOGRAPHY TOKENS

### Font Families

```
--font-family-primary:  'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif
--font-family-mono:     'IBM Plex Mono', 'Courier New', monospace
```

### Font Sizes

```
--font-size-11: 11px   (0.69rem)
--font-size-12: 12px   (0.75rem)
--font-size-13: 13px   (0.8125rem)
--font-size-14: 14px   (0.875rem)
--font-size-16: 16px   (1rem)
--font-size-18: 18px   (1.125rem)
--font-size-20: 20px   (1.25rem)
--font-size-24: 24px   (1.5rem)
--font-size-32: 32px   (2rem)
```

### Font Weights

```
--font-weight-regular:    400
--font-weight-medium:     500
--font-weight-semibold:   600
--font-weight-bold:       700
```

### Heading Scale

```
--text-h1: {
  font-family: var(--font-family-primary);
  font-size: var(--font-size-32);
  font-weight: var(--font-weight-bold);
  line-height: 1.2;
  letter-spacing: -0.02em;
}

--text-h2: {
  font-family: var(--font-family-primary);
  font-size: var(--font-size-24);
  font-weight: var(--font-weight-bold);
  line-height: 1.3;
  letter-spacing: -0.01em;
}

--text-h3: {
  font-family: var(--font-family-primary);
  font-size: var(--font-size-20);
  font-weight: var(--font-weight-semibold);
  line-height: 1.4;
  letter-spacing: 0em;
}

--text-h4: {
  font-family: var(--font-family-primary);
  font-size: var(--font-size-18);
  font-weight: var(--font-weight-semibold);
  line-height: 1.4;
  letter-spacing: 0em;
}

--text-h5: {
  font-family: var(--font-family-primary);
  font-size: var(--font-size-16);
  font-weight: var(--font-weight-medium);
  line-height: 1.5;
  letter-spacing: 0em;
}

--text-h6: {
  font-family: var(--font-family-primary);
  font-size: var(--font-size-14);
  font-weight: var(--font-weight-medium);
  line-height: 1.5;
  letter-spacing: 0.01em;
}
```

### Body Text Scale

```
--text-body-lg: {
  font-family: var(--font-family-primary);
  font-size: var(--font-size-16);
  font-weight: var(--font-weight-regular);
  line-height: 1.6;
  letter-spacing: 0em;
}

--text-body: {
  font-family: var(--font-family-primary);
  font-size: var(--font-size-14);
  font-weight: var(--font-weight-regular);
  line-height: 1.6;
  letter-spacing: 0.01em;
}

--text-body-sm: {
  font-family: var(--font-family-primary);
  font-size: var(--font-size-12);
  font-weight: var(--font-weight-regular);
  line-height: 1.5;
  letter-spacing: 0.01em;
}

--text-caption: {
  font-family: var(--font-family-primary);
  font-size: var(--font-size-11);
  font-weight: var(--font-weight-regular);
  line-height: 1.4;
  letter-spacing: 0.02em;
}
```

### Numeric Text

```
--text-numeric: {
  font-family: var(--font-family-mono);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.05em;
  line-height: 1.2;
}

--text-numeric-lg: {
  font-size: var(--font-size-24);
  font-family: var(--font-family-mono);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.05em;
}

--text-numeric-md: {
  font-size: var(--font-size-16);
  font-family: var(--font-family-mono);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.05em;
}
```

---

## SPACING TOKENS

All based on 8px base unit:

```
--spacing-1:  4px   (micro)
--spacing-2:  8px   (standard padding)
--spacing-3:  12px  (small padding)
--spacing-4:  16px  (standard padding)
--spacing-5:  20px  (larger padding)
--spacing-6:  24px  (large spacing)
--spacing-8:  32px  (section spacing)
--spacing-10: 40px  (large section)
--spacing-12: 48px  (extra large)
```

### Component Spacing

```
--padding-button:        12px 20px
--padding-input:         10px 12px
--padding-card:          16px
--padding-card-lg:       24px
--margin-bottom-text:    var(--spacing-4)
--margin-bottom-heading: var(--spacing-6)
--gap-grid:              var(--spacing-4)
--gap-form:              var(--spacing-6)
```

---

## BORDER TOKENS

```
--border-width-1:   1px
--border-width-2:   2px

--border-radius-0:   0px (sharp edges)
--border-radius-sm:  2px (minimal rounding)
--border-radius-md:  4px (slight rounding)
--border-radius-lg:  8px (use sparingly)

--border-light:   1px solid var(--color-border-light)
--border:         1px solid var(--color-border)
--border-dark:    1px solid var(--color-border-dark)
--border-focus:   2px solid var(--color-focus-ring)
```

**Rule:** Tavro defaults to `--border-radius-0` (sharp edges). Only use rounding where specifically intentional.

---

## SHADOW TOKENS

Keep shadows subtle and functional:

```
--shadow-sm:
  box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.05);

--shadow-md:
  box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1);

--shadow-lg:
  box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);

--shadow-focus:
  box-shadow: 0px 0px 0px 3px rgba(217, 119, 6, 0.1);
```

**Rule:** Use shadows sparingly. Never use more than 2–3 shadow levels. Heavy shadows look cheap.

---

## COMPONENT TOKENS

### Button

```
--button-height-sm:      32px
--button-height-md:      40px
--button-height-lg:      48px
--button-padding-x:      20px
--button-padding-y:      12px
--button-border-radius:  0px
--button-font-weight:    500
--button-transition:     background-color 150ms ease-in-out;
```

### Input

```
--input-height:        40px
--input-padding-x:     12px
--input-padding-y:     10px
--input-border-radius: 0px
--input-font-size:     14px
--input-placeholder-color: var(--color-text-muted);
```

### Card

```
--card-border-radius: 0px
--card-padding:       16px
--card-shadow:        var(--shadow-sm)
--card-border:        var(--border)
--card-bg:            var(--color-surface)
```

### Modal

```
--modal-overlay-bg:       rgba(0, 0, 0, 0.5)
--modal-backdrop-blur:    blur(4px)
--modal-border-radius:    0px
--modal-max-width:        600px
--modal-padding:          24px
```

---

## ANIMATION/TRANSITION TOKENS

Keep animations minimal and purposeful:

```
--transition-fast:        150ms
--transition-base:        200ms
--transition-slow:        300ms
--easing-standard:        ease-in-out
--easing-decelerate:      cubic-bezier(0.0, 0.0, 0.2, 1)
```

**Rule:** No animations just for decoration. Only animate:
- Button hover state (150ms)
- Loading spinner (continuous)
- Fade in/out modals (200ms)
- Transitions between states (150–200ms)

---

## BREAKPOINTS

```
--breakpoint-mobile:      640px   (< 640px: mobile)
--breakpoint-tablet:      1024px  (640px–1024px: tablet)
--breakpoint-desktop:     1280px  (> 1280px: desktop)
```

### Responsive Scaling

```
@media (max-width: 640px) {
  --font-size-h1: 24px;  /* Mobile: 20% smaller */
  --font-size-h2: 20px;
  --padding-card: 12px;
}
```

---

## IMPLEMENTATION EXAMPLES

### CSS Variables

```css
:root {
  /* Colors */
  --color-primary: #1F2937;
  --color-secondary: #D97706;
  --color-text: #1F2937;
  --color-text-secondary: #6B7280;
  --color-border: #E5E7EB;
  
  /* Typography */
  --font-family: 'Inter', sans-serif;
  --font-size-body: 14px;
  --font-weight-regular: 400;
  --font-weight-bold: 700;
  
  /* Spacing */
  --spacing-unit: 8px;
  --spacing-sm: calc(var(--spacing-unit) * 2);
  --spacing-md: calc(var(--spacing-unit) * 3);
  --spacing-lg: calc(var(--spacing-unit) * 4);
}

.button-primary {
  background-color: var(--color-secondary);
  color: var(--color-primary);
  padding: 12px 20px;
  font-family: var(--font-family);
  font-size: var(--font-size-body);
  font-weight: var(--font-weight-bold);
}
```

### TailwindCSS Config

```js
module.exports = {
  theme: {
    colors: {
      primary: {
        DEFAULT: '#1F2937',
        dark: '#111827',
        light: '#374151',
      },
      secondary: {
        DEFAULT: '#D97706',
        dark: '#B45309',
        light: '#FBBF24',
      },
      neutral: {
        50: '#FAFAFA',
        100: '#F9FAFB',
        // ... etc
      },
    },
    spacing: {
      0: '0',
      1: '4px',
      2: '8px',
      3: '12px',
      4: '16px',
      5: '20px',
      6: '24px',
      8: '32px',
      // ... etc
    },
    fontFamily: {
      primary: ['Inter', 'sans-serif'],
      mono: ['IBM Plex Mono', 'monospace'],
    },
  },
}
```

### React/TypeScript

```ts
export const tokens = {
  colors: {
    primary: '#1F2937',
    secondary: '#D97706',
    // ...
  },
  spacing: {
    sm: '8px',
    md: '16px',
    lg: '24px',
    // ...
  },
  typography: {
    h1: {
      fontSize: '32px',
      fontWeight: 700,
      lineHeight: 1.2,
    },
    // ...
  },
} as const;

export type Token = typeof tokens;
```

---

## TOKEN VERSIONING

**Current Version:** 1.0.0 (August 2026)

When updating tokens:
1. Maintain backward compatibility where possible
2. Document breaking changes
3. Provide migration guide
4. Update all implementations simultaneously
5. Test across all products

---

## NEXT STEPS

1. Generate token files for your platform (CSS, Tailwind, Figma, etc.)
2. Implement in design system/component library
3. Apply to all products (web, mobile, POS)
4. Test across different environments and devices
5. Gather feedback and iterate

---

**Tokens are the single source of truth for consistency.**

Use them everywhere. Break them nowhere.
