# TAVRO TYPOGRAPHY SYSTEM

---

## STRATEGIC DIRECTION

Tavro's typography must:

1. **Support POS interfaces** — Clear, readable under stress, high contrast
2. **Handle financial data** — Numbers must be legible and scannable
3. **Work across platforms** — Web, mobile, desktop, dark mode, print
4. **Feel professional** — Trustworthy, not trendy, not cheap
5. **Be accessible** — High contrast, readable at small sizes

Typography is NOT decorative. It's operational infrastructure.

---

## FONT SELECTION

### Primary Typeface: Inter
**Purpose:** Headings, UI labels, body text
**Designer:** Rasmus Andersson
**License:** Open Font License (OFL) — Free for commercial use
**Available on:** Google Fonts, npm, foundries

**Why Inter:**

- **Legibility** — Engineered for screen display, not print
- **Neutral** — Professional, modern, doesn't distract
- **Optical Sizing** — Automatically optimizes at different sizes
- **Variable Font** — Supports entire weight range smoothly
- **International** — Supports extended Latin, Greek, Cyrillic, Arabic
- **Performance** — Small file size, great on slow networks
- **Proven** — Used by Stripe, Figma, GitHub, Microsoft
- **Versatile** — Works for UI and body copy equally well

**Variants to Use:**
- Regular (400) — Body text, standard UI
- Medium (500) — Labels, secondary headings
- Semi-Bold (600) — Headings, emphasis
- Bold (700) — Primary headings, strong emphasis

---

### Numeric Typeface: IBM Plex Mono
**Purpose:** Financial numbers, POS data, code/transactions
**Designer:** IBM
**License:** Open Font License (OFL) — Free
**Available on:** Google Fonts, npm

**Why IBM Plex Mono:**

- **Monowidth** — All characters same width (numbers align perfectly)
- **High Contrast** — Numbers are extremely legible
- **Financial-Ready** — Clear distinction between 0/O, 1/l, etc.
- **Professional** — Used in banking, fintech systems
- **Accessible** — Easy to read at small sizes on dark backgrounds
- **Complete** — Full weight range, all necessary characters

**Usage:** 
- Transaction amounts
- Totals and summaries
- Receipt printing
- Dashboard data tables
- POS screen figures
- Timestamps, dates, reference numbers

---

## TYPEFACE STACK

### For Web/Digital

```css
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 
             'Helvetica Neue', sans-serif;
```

### For Numeric Data

```css
font-family: 'IBM Plex Mono', 'Courier New', monospace;
```

### For Print

**Primary:** Inter (as above)
**Numeric:** IBM Plex Mono or use Inter with increased tracking for clarity

---

## HEADING SCALE

Professional hierarchy with breathing room. Not crowded, not spread out.

| Level | Size | Weight | Line Height | Letter Spacing | Use |
|---|---|---|---|---|---|
| H1 | 32px / 2rem | 700 (Bold) | 1.2 | -0.02em | Page titles, hero sections |
| H2 | 24px / 1.5rem | 700 (Bold) | 1.3 | -0.01em | Section headers, main headings |
| H3 | 20px / 1.25rem | 600 (Semi-Bold) | 1.4 | 0em | Subsection headers |
| H4 | 18px / 1.125rem | 600 (Semi-Bold) | 1.4 | 0em | Card titles, secondary headings |
| H5 | 16px / 1rem | 500 (Medium) | 1.5 | 0em | Labels, form titles |
| H6 | 14px / 0.875rem | 500 (Medium) | 1.5 | 0.01em | Small labels, utility text |

### Mobile Scaling

On screens < 640px, reduce by ~20%:

| Level | Mobile Size | Weight | Line Height |
|---|---|---|---|
| H1 | 24px | 700 | 1.3 |
| H2 | 20px | 700 | 1.3 |
| H3 | 18px | 600 | 1.4 |
| H4 | 16px | 600 | 1.4 |
| H5 | 14px | 500 | 1.5 |

---

## BODY TEXT SCALE

Optimized for readability and scanning.

| Element | Size | Weight | Line Height | Letter Spacing | Use |
|---|---|---|---|---|---|
| Body Large | 16px / 1rem | 400 (Regular) | 1.6 | 0em | Primary body text |
| Body | 14px / 0.875rem | 400 (Regular) | 1.6 | 0.01em | Standard body copy |
| Body Small | 12px / 0.75rem | 400 (Regular) | 1.5 | 0.01em | Secondary text, captions |
| Caption | 11px / 0.69rem | 400 (Regular) | 1.4 | 0.02em | Footnotes, metadata |

### Leading (Line Height)

- **Body text:** 1.6 (generous for readability)
- **Headings:** 1.2–1.4 (tighter, more impactful)
- **Labels:** 1.4–1.5 (functional)

Line height ensures comfortable reading on screens and in high-stress POS environments.

---

## NUMERIC TYPOGRAPHY

Numbers are critical in Tavro. They must be instantly legible.

### Financial Figures

```
Size: 16px–24px (depending on context)
Font: IBM Plex Mono (monowidth)
Weight: 500–600
Line Height: 1.2
Letter Spacing: 0.05em (slightly wider for clarity)
```

**Example:**
```
Total Revenue:    ₦245,670.50
Items Sold:       156
Closing Time:     11:45 PM
Table 07:         4 covers
```

### Transaction Display (POS)

```
Amount: 24px, Semi-Bold, IBM Plex Mono
Description: 14px, Regular, Inter
Timestamp: 12px, Regular, IBM Plex Mono, Medium Gray
```

### Dashboard Tables

```
Header: 12px, Semi-Bold, Inter, Charcoal
Values: 14px, Regular, IBM Plex Mono, Charcoal
Summaries: 16px, Semi-Bold, IBM Plex Mono, Charcoal
```

---

## WEIGHT USAGE

### Inter Weights

- **300 (Light)** — Use sparingly, only for very large display text or deemphasis
- **400 (Regular)** — Body text, standard UI, default weight
- **500 (Medium)** — Labels, secondary headings, emphasis
- **600 (Semi-Bold)** — Primary headings, UI emphasis
- **700 (Bold)** — H1, strong emphasis, important labels
- **800+ (Extra Bold)** — Avoid in Tavro (too heavy, unprofessional)

### Rule
- Most text: 400 or 500
- Headings: 600 or 700
- Never: 300 for body text (too light, hard to read)
- Never: 800+ (looks cheap/amateur)

---

## LETTER SPACING (TRACKING)

### Standard Tracking

| Element | Tracking | Reason |
|---|---|---|
| H1–H3 | -0.02 to -0.01em | Headings tighter for impact |
| H4–H6 | 0em | Neutral |
| Body text | 0 to 0.01em | Slight opening for readability |
| Numbers (Mono) | 0.05em | Extra width for financial clarity |
| Labels | 0.02em | Slight opening for UI clarity |

### Rule
- **Never:** Negative tracking on body text (reduces legibility)
- **Always:** Increase tracking on small, monospace numbers (improves scanning)

---

## COLOR + TEXT COMBINATIONS

**See `colors/accessibility.md` for contrast ratios.**

### Light Backgrounds

| Text Element | Color | Contrast |
|---|---|---|
| Body text | Charcoal #1F2937 | 17:1 on Off-White |
| Secondary text | Medium Gray #6B7280 | 7.8:1 on Off-White |
| Muted text | Dark Gray #9CA3AF | 5.2:1 on Off-White |
| Links/Emphasis | Amber #D97706 | 5.3:1 on Off-White |

### Dark Backgrounds

| Text Element | Color | Contrast |
|---|---|---|
| Body text | Off-White #FAFAFA | 17:1 on Charcoal |
| Secondary text | Dark Text Secondary #CBD5E1 | 9.8:1 on Dark |
| Muted text | Medium Gray (inverted) | 6:1 on Dark |
| Links/Emphasis | Amber Light #FBBF24 | 9.2:1 on Dark |

---

## SPECIAL CASES

### Emphasis (Bold, Italic)

**Bold:** Use weight changes instead
```
Instead of: <b>important</b>
Use: <strong style="font-weight: 600">important</strong>
```

**Italic:** Use sparingly, only for:
- Legal disclaimers
- Foreign language terms
- Very occasional emphasis
```
<em style="font-style: italic">amount due</em>
```

### Monospace (Code/Data)

Use IBM Plex Mono for:
- API responses
- Receipt printouts
- Reference codes
- Transaction IDs
- System messages

```html
<span style="font-family: 'IBM Plex Mono'">TXN-2024-08-045</span>
```

### All Caps

Use sparingly. Only for:
- Short labels (POS buttons)
- System status (OPEN, CLOSED, PENDING)
- Abbreviations

Never use all caps for body text (reduces readability).

---

## IMPLEMENTATION: CSS CUSTOM PROPERTIES

```css
:root {
  /* Font Families */
  --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-mono: 'IBM Plex Mono', 'Courier New', monospace;
  
  /* Heading Styles */
  --text-h1: 32px / 1.2 700 var(--font-primary);
  --text-h2: 24px / 1.3 700 var(--font-primary);
  --text-h3: 20px / 1.4 600 var(--font-primary);
  --text-h4: 18px / 1.4 600 var(--font-primary);
  
  /* Body Text Styles */
  --text-body-lg: 16px / 1.6 400 var(--font-primary);
  --text-body: 14px / 1.6 400 var(--font-primary);
  --text-body-sm: 12px / 1.5 400 var(--font-primary);
  --text-caption: 11px / 1.4 400 var(--font-primary);
  
  /* Numeric Text */
  --text-numeric: 'IBM Plex Mono', monospace;
}

/* Example Usage */
h1 { font: var(--text-h1); }
body { font: var(--text-body); }
.amount { font: 16px / 1.2 600 var(--font-mono); }
```

---

## RESPONSIVE TYPOGRAPHY

### Breakpoints

```
Mobile: < 640px — Reduce heading sizes by 20%
Tablet: 640px–1024px — Standard sizes
Desktop: > 1024px — Full scale
```

### Fluid Typography (Optional, Advanced)

For smooth scaling across all screen sizes:

```css
h1 {
  font-size: clamp(24px, 5vw, 32px);
  line-height: 1.2;
}

body {
  font-size: clamp(14px, 1.5vw, 16px);
  line-height: 1.6;
}
```

This scales smoothly without breakpoint jumps.

---

## FONT LOADING & PERFORMANCE

### Google Fonts Import

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
```

### Font Display Strategy

```css
@font-face {
  font-family: 'Inter';
  src: url('inter.woff2') format('woff2');
  font-display: swap; /* Show fallback while loading */
}
```

Use `font-display: swap` to avoid invisible text while fonts load.

### Variable Font (Optimized)

```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
```

Variable fonts reduce file size and load faster.

---

## ACCESSIBILITY IN TYPOGRAPHY

### Font Size Minimum

- Body text: 12px minimum (never smaller)
- Labels: 11px minimum
- Captions: 10px minimum (only for non-critical information)

### Line Height Minimum

- All text: 1.4x minimum
- Body text: 1.6x recommended for comfort

### Contrast

- All text must meet WCAG AA minimum (4.5:1)
- Critical data (financial): Aim for AAA (7:1)
- See `colors/accessibility.md` for detailed contrast ratios

### Focus States

- Linked text must have clear focus indicator
- Use outline or underline (minimum 2px, 2:1 contrast)
- Never remove focus indicator

---

## TESTING CHECKLIST

Before deployment, verify:

- [ ] All fonts load correctly (no fallbacks visible)
- [ ] Numbers in POS interface are legible at 50cm distance
- [ ] Headings maintain visual hierarchy across devices
- [ ] Body text comfortable to read for 30+ minutes
- [ ] Contrast ratios meet WCAG AA (AAA for financial data)
- [ ] Dark mode text equally readable as light mode
- [ ] Print preview matches screen appearance
- [ ] Mobile sizes don't require horizontal scrolling
- [ ] Font loading doesn't cause layout shift (CLS < 0.1)

---

## NEXT STEPS

1. Integrate typography into design tokens (`design-tokens.md`)
2. Update component library with type styles
3. Test across all products (POS, dashboard, web)
4. Gather feedback from actual users
5. Refine based on real-world usage

---

**Typography is infrastructure, not decoration.**

Make it work, make it legible, make it professional.
