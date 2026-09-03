# TAVRO UI BRAND DIRECTION

**How Tavro's brand principles translate into actual interface design.**

---

## CORE UI PRINCIPLES

### 1. Clarity Over Decoration

- **Every element serves a purpose**
- No decorative shapes, gradients, or flourishes
- Whitespace is functional, not filled
- Remove complexity ruthlessly

**❌ Don't:**
- Rounded cards with shadows
- Gradient buttons
- Decorative icons or illustrations
- Glowing effects

**✅ Do:**
- Sharp borders, subtle shadows only
- Solid colors (from palette)
- Functional iconography
- Clear hierarchy through color and weight

### 2. Speed Over Beauty

- **POS interfaces must be fast**
- Minimal cognitive load
- Touch-friendly targets (minimum 44px)
- No loading delays

**❌ Don't:**
- Complex animations
- Intricate component states
- Hover effects on touch devices
- Heavy graphics

**✅ Do:**
- Direct, immediate feedback
- Clear touch targets
- Instant response
- Minimal visual transitions

### 3. Data Over Decoration

- **Numbers and information are the interface**
- Typography hierarchy makes meaning clear
- Color codes meaning (green = good, red = problem)
- Whitespace helps scanning

**❌ Don't:**
- Pretty charts that are hard to read
- Lots of color variation
- Small font sizes
- Dense layouts

**✅ Do:**
- Clear data visualization
- Strategic color use
- Large, legible type
- Generous spacing

### 4. Consistency Over Surprise

- **Predictable, recognizable interface**
- Same element means same behavior
- Standard UI patterns
- No hidden features

**❌ Don't:**
- Custom UI components
- Unusual layouts
- Hidden navigation
- Non-standard patterns

**✅ Do:**
- Standard buttons, inputs, cards
- Predictable navigation
- Visible controls
- Familiar patterns

---

## POS INTERFACE DIRECTION

### Goals
- **Speed:** Tap. Confirm. Done.
- **Clarity:** Clear visual feedback on every action
- **Touch-first:** Large targets, readable at distance
- **Dark-friendly:** Works in nightclub/low-light environments

### Key Characteristics

**Layout:**
- Large, clear button targets (minimum 48px)
- Grid-based (4–6 columns max)
- Generous padding (16px minimum)
- Dark background by default (#0F172A or #1E293B)

**Colors:**
- Dark backgrounds (charcoal, dark blue)
- Off-white/light text (#FAFAFA, #F8FAFC)
- Amber (#FBBF24) for CTAs and key actions
- Emerald for confirmations, success states
- Red only for errors/critical alerts

**Typography:**
- Body text: 14px minimum
- Large numbers: 24px+ (monowidth)
- Labels: 12px, Medium Gray
- High contrast always (white text on dark)

**Interaction:**
- Instant visual feedback (button press, checkmark)
- Confirmation for destructive actions
- No complex gestures
- Sound cues optional but useful

### Example POS Layout
```
┌─────────────────────────────────┐
│  Order #047  |  Table 05        │  ← Header (charcoal bg)
├─────────────────────────────────┤
│ Item Name        Qty    Price    │  ← Order list
│ Jollof Rice       1    ₦2,500   │     (clear, scannable)
│ Grilled Chicken   1    ₦4,200   │
│ Soft Drink        1    ₦1,500   │
├─────────────────────────────────┤
│ Subtotal             ₦8,200    │  ← Totals (bold, clear)
│ Tax                  ₦984      │
│ TOTAL                ₦9,184    │
├─────────────────────────────────┤
│  [CANCEL] [SAVE] [CONFIRM PAY]  │  ← CTAs (amber bg, charcoal text)
└─────────────────────────────────┘
```

---

## MANAGER DASHBOARD DIRECTION

### Goals
- **Control:** See entire operation at a glance
- **Actionability:** Find what needs attention
- **Density:** More information, still legible
- **Speed:** Load and navigate quickly

### Key Characteristics

**Layout:**
- Dashboard grid (12-column, flexible)
- Card-based sections
- Dense but organized
- Scannable at a glance

**Colors:**
- Light background (#FAFAFA) primary
- White cards (#FFFFFF)
- Charcoal text (#1F2937)
- Amber for key metrics/CTAs
- Functional colors for status (green/red/orange)

**Typography:**
- Body text: 14px
- Numbers: 16px–18px, monowidth, semi-bold
- Labels: 12px, Medium Gray
- Headings: 18px–20px, semi-bold

**Interaction:**
- Hover states for interactivity
- Click to drill down
- Real-time updates
- No delays

### Example Dashboard Layout
```
┌────────────────────────────────────────────────┐
│ TODAY'S PERFORMANCE                             │
├────────────────┬────────────────┬──────────────┤
│ REVENUE        │ ITEMS SOLD     │ AVG CHECK    │
│ ₦245,670      │ 156            │ ₦1,575       │
│ ↑ 12% vs yesterday                              │
├────────────────┬────────────────┬──────────────┤
│ TOP ITEMS      │ LOW STOCK      │ ISSUES       │
│ 1. Jollof Rice │ • Pepper       │ • Register 2 │
│ 2. Fish Stew   │ • Chicken      │ • Cook down  │
│ 3. Plantains   │ • Oil          │ • Missing X  │
└────────────────────────────────────────────────┘
```

**No:**
- Fancy charts (unless data-dense)
- Gradients or decorative colors
- Animations
- Overly rounded corners

**Yes:**
- Clear data visualization
- High contrast
- Fast loading
- Consistent card design

---

## OWNER INTELLIGENCE VIEW

### Goals
- **Strategic insight:** What's actually happening
- **Growth focus:** Where are opportunities
- **Profitability:** Real margin picture
- **Simplicity:** Clear takeaways

### Key Characteristics

**Layout:**
- Summary cards at top
- Key metrics prominent
- Trend visualization
- Actionable insights

**Colors:**
- Light background
- Strategic use of Amber for highlights
- Green for growth, Red for warning
- Whitespace for breathing room

**Typography:**
- Larger headlines (24px)
- Prominent numbers (20px, monowidth)
- Clear secondary information
- Generous line height

### Example Owner View
```
┌──────────────────────────────────────┐
│ MONTHLY PERFORMANCE                  │
├──────────────┬──────────────┬────────┤
│ REVENUE      │ MARGIN       │ GROWTH │
│ ₦2,456,700   │ 34%          │ ↑ 8%   │
│ (vs ₦2.1M)   │ (vs 31%)     │        │
├──────────────────────────────────────┤
│ WHERE TO FOCUS THIS MONTH            │
│ • Chicken prices up 15% - margin hit │
│ • Jollof demand strong - push more   │
│ • Staff efficiency up 12%            │
└──────────────────────────────────────┘
```

---

## MOBILE APP DIRECTION

### Goals
- **Responsive:** Works on phones and tablets
- **Touch-first:** Larger targets, no hover
- **Simple:** Limited screen space
- **Quick:** Check key metrics in seconds

### Key Characteristics

**Layout:**
- Full-width cards
- Vertical scrolling
- Bottom navigation or hamburger menu
- Safe areas for notches/home indicator

**Colors:**
- Same palette as web
- High contrast for outdoor visibility
- Functional colors prominent

**Typography:**
- Slightly larger than web (minimum 14px body)
- Touch targets minimum 44px
- Clear focus states

**Interaction:**
- Tap, don't hover
- Clear button feedback
- No complex gestures
- Loading states visible

### Bottom Navigation Tabs
```
  [Dashboard] [Orders] [Inventory] [Settings]
  (Icons + labels, charcoal, Amber for active)
```

---

## DARK MODE CONSIDERATIONS

### When & Why
- **POS interfaces:** Nighttime venue environment
- **Dashboard:** Optional, user preference
- **Mobile:** System preference or toggle

### Implementation
```
Light Mode Background: #FAFAFA
Light Mode Text: #1F2937

Dark Mode Background: #0F172A
Dark Mode Text: #F8FAFC
Dark Mode Cards: #1E293B
Dark Mode Accents: #FBBF24 (Amber Light)
```

**Maintain same contrast ratios in dark mode.**

---

## COMPONENT DESIGN STANDARDS

### Buttons

**Primary CTA Button**
```
Background: #D97706 (Amber)
Text: #1F2937 (Charcoal)
Padding: 12px 20px
Border Radius: 0px (sharp)
Font: 14px, 500 weight, Inter
```

**Secondary Button**
```
Background: Transparent
Border: 1px #E5E7EB
Text: #1F2937
```

**Danger Button**
```
Background: #DC2626 (Red)
Text: #FFFFFF
```

### Forms

**Input Field**
```
Background: #FFFFFF
Border: 1px #E5E7EB
Border Radius: 0px
Focus: 2px #D97706 outline
Label: 12px, Medium Gray
Help text: 11px, Dark Gray
```

**Validation**
- Success: Green checkmark, #059669 text
- Error: Red border, #DC2626 text
- Warning: Orange border, #F97316 text

### Cards

**Standard Card**
```
Background: #FFFFFF
Border: 1px #E5E7EB
Border Radius: 0px
Padding: 16px
Box Shadow: 0px 1px 3px rgba(0,0,0,0.1)
```

**No gradients, no heavy shadows.**

### Tables

**Table Header**
```
Background: #F9FAFB
Text: #1F2937, Medium weight
Border Bottom: 1px #E5E7EB
```

**Table Row**
```
Background: #FFFFFF (alternate: #FAFAFA)
Text: #1F2937, Regular weight
Border Bottom: 1px #E5E7EB
```

**Hover:** Slight background change (#F3F4F6)

---

## SPACING SYSTEM

**Use 8px base unit:**

```
4px   - micro spacing
8px   - padding/margins
12px  - small padding
16px  - standard padding
20px  - larger sections
24px  - major sections
32px  - page sections
```

Never use arbitrary spacing. Always align to 8px grid.

---

## FOCUS & ACCESSIBILITY

### Focus Indicators

**All interactive elements must have visible focus:**
```
Focus Outline: 2px solid #D97706 (Amber)
Outline Offset: 2px
```

**Never remove focus indicator.** It's not ugly—it's necessary for keyboard navigation and accessibility.

### Keyboard Navigation

- Tab through interactive elements in logical order
- Enter/Space to activate
- Arrow keys for multi-option controls
- Escape to close modals
- No tabindex="0" unless necessary

---

## INTERACTION PATTERNS

### Loading State
- Loading spinner (charcoal or amber)
- Skeleton screens for data
- Loading text if > 3 seconds

### Error Handling
- Clear error message (not error code)
- Highlight the problematic field
- Suggest fix or next step
- Use red (#DC2626) color

### Confirmation
- For destructive actions (delete, refund, etc.)
- Clear yes/no buttons
- Default to "Cancel"
- Explain what will happen

### Notifications
- Toast messages for transient info
- Banners for persistent alerts
- Color-coded (green/orange/red)
- Clear dismiss action

---

## WHAT NOT TO DO

❌ **Glassmorphism** — Trendy, reduces contrast, hard to read
❌ **Neumorphism** — Weird shadows, hard to tell what's clickable
❌ **3D effects** — Cheap-looking, not professional
❌ **Gradient backgrounds** — Trendy, reduces legibility
❌ **Excessive rounded corners** — Looks cheap, not premium
❌ **Lots of motion/animation** — Slows down task completion
❌ **Decorative icons** — Adds visual noise
❌ **Bright neon colors** — Looks cheap
❌ **Small font sizes** — Hard to read, especially POS
❌ **Complex gestures** — Most users don't know them

---

## NEXT STEPS

1. Create detailed component library based on these principles
2. Develop high-fidelity mockups for POS, Dashboard, Mobile
3. Test with actual hospitality business users
4. Iterate based on feedback
5. Document in component system (Figma, Storybook, etc.)

---

**UI is not decoration. UI is infrastructure for operations.**

Make it functional. Make it fast. Make it clear.
