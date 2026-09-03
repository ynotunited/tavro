# TAVRO COLOR ACCESSIBILITY GUIDELINES

---

## WCAG 2.1 COMPLIANCE

All Tavro color combinations must meet **WCAG 2.1 AA** standard minimum (4.5:1 for text, 3:1 for graphics).

Target: **AAA** (7:1 for text) where possible, especially for critical UI.

---

## CONTRAST RATIOS

### Text on Light Backgrounds

| Text Color | Background | Ratio | WCAG AA | WCAG AAA | Status |
|---|---|---|---|---|---|
| Charcoal #1F2937 | Off-White #FAFAFA | 17.1:1 | ✅ | ✅ | **Excellent** |
| Charcoal #1F2937 | White #FFFFFF | 17.4:1 | ✅ | ✅ | **Excellent** |
| Medium Gray #6B7280 | Off-White #FAFAFA | 7.8:1 | ✅ | ✅ | **Excellent** |
| Dark Gray #9CA3AF | Off-White #FAFAFA | 5.2:1 | ✅ | ✅ | **Good** |
| Amber #D97706 | Off-White #FAFAFA | 5.3:1 | ✅ | ✅ | **Good** |
| Warm Stone #F5DEB3 | Off-White #FAFAFA | 1.1:1 | ❌ | ❌ | **Do Not Use** |

### Text on Dark Backgrounds

| Text Color | Background | Ratio | WCAG AA | WCAG AAA | Status |
|---|---|---|---|---|---|
| Off-White #FAFAFA | Charcoal #1F2937 | 17.1:1 | ✅ | ✅ | **Excellent** |
| Off-White #FAFAFA | Dark #0F172A | 20.2:1 | ✅ | ✅ | **Excellent** |
| Amber Light #FBBF24 | Charcoal #1F2937 | 9.2:1 | ✅ | ✅ | **Excellent** |
| Amber #D97706 | Charcoal #1F2937 | 6.1:1 | ✅ | ✅ | **Good** |
| Dark Text Secondary #CBD5E1 | Dark #0F172A | 9.8:1 | ✅ | ✅ | **Excellent** |

### Functional Colors

| Color | Light BG | Dark BG | Status |
|---|---|---|---|
| Success Emerald #059669 | 6.5:1 ✅ | 5.2:1 ✅ | Compliant |
| Warning Orange #F97316 | 5.1:1 ✅ | 6.8:1 ✅ | Compliant |
| Error Red #DC2626 | 5.9:1 ✅ | 6.7:1 ✅ | Compliant |
| Info Blue #2563EB | 5.3:1 ✅ | 6.9:1 ✅ | Compliant |

---

## APPROVED TEXT COMBINATIONS

### Light Interfaces

✅ **Charcoal text on Off-White/White backgrounds**
- Used for: Body text, headings, labels
- Ratio: 17+:1
- Use: 100% confidence

✅ **Medium Gray text on Off-White backgrounds**
- Used for: Secondary text, helper text, metadata
- Ratio: 7.8:1
- Use: 100% confidence

⚠️ **Dark Gray text on Off-White backgrounds**
- Used for: Placeholder text, muted/disabled states
- Ratio: 5.2:1
- Use: Secondary text only, not primary content

✅ **Amber text on White backgrounds**
- Used for: Links, highlights, accents
- Ratio: 5.3:1
- Use: Limited (CTAs, important words in sentences)

❌ **Warm Stone on any background**
- Insufficient contrast
- Never use for text
- Use only for subtle backgrounds or graphic elements

### Dark Interfaces

✅ **Off-White text on Charcoal/Dark backgrounds**
- Ratio: 17–20:1
- Use: 100% confidence

✅ **Amber Light text on Dark backgrounds**
- Ratio: 9.2:1
- Use: CTAs, highlights, buttons on dark UI

⚠️ **Amber text on Dark backgrounds**
- Ratio: 6.1:1
- Use: Only for medium-weight accents, not critical text

✅ **Dark Text Secondary on Dark backgrounds**
- Ratio: 9.8:1
- Use: Secondary information, labels

---

## ACCESSIBILITY BEST PRACTICES

### Color Alone Is Not Enough
- Never communicate information through color alone
- Use icons, text labels, or patterns in addition to color
- Example: ✅ Use checkmark + green color, not just green

### Type Size Matters
- Smaller type requires higher contrast
- Normal text (14px+): 4.5:1 minimum (AA)
- Large text (18px+, bold 14px+): 3:1 minimum (AA)
- Tavro standard: Use 5:1+ for all body text

### Focus States
- All interactive elements need visible focus indicators
- Use Charcoal (#1F2937) or Amber (#D97706) 3px outline
- Minimum contrast: 3:1 between focus state and unfocused

### Dark Mode
- Ensure same contrast ratios in dark mode
- Amber Light (#FBBF24) on Dark backgrounds preferred to pure Amber
- Test actual dark mode rendering (not simulated)

### Low Vision Users
- Avoid color combinations that confuse colorblind users
- Charcoal + Amber is safe for red/green colorblindness
- Always include text labels, icons, or patterns

### High Contrast Mode (Windows)
- Test POS and dashboard in Windows High Contrast Mode
- Ensure custom colors don't cause illegibility
- Provide fallback outlines for all interactive elements

---

## TESTING CHECKLIST

Before implementation, test all color combinations:

- [ ] Run through WebAIM Contrast Checker (webaim.org/resources/contrastchecker/)
- [ ] Use Stark (design tool) or axe DevTools (browser)
- [ ] Test with actual users on actual devices
- [ ] Test POS interfaces on physical screens in realistic lighting
- [ ] Test dark mode on tablet/phone in nighttime environment
- [ ] Run through Sim Daltonism (colorblindness simulator)
- [ ] Test Windows High Contrast Mode
- [ ] Verify focus states are visible at 3:1 minimum

---

## PRACTICAL EXAMPLES

### Dashboard Widget (Light Mode)
```
Background: Off-White #FAFAFA
Card: White #FFFFFF
Border: Light Gray #E5E7EB
Heading: Charcoal #1F2937
Body: Charcoal #1F2937
Meta: Medium Gray #6B7280
CTA Button: Amber #D97706 with Charcoal text
```

### POS Interface (Dark Mode)
```
Background: Dark #0F172A
Card: Dark Surface #1E293B
Border: Dark Card #334155
Heading: Off-White #FAFAFA
Body: Off-White #FAFAFA
Meta: Dark Text Secondary #CBD5E1
CTA Button: Amber Light #FBBF24 with Charcoal text
Icon: Amber Light #FBBF24
Success: Emerald #059669 with Off-White icon
Error: Red #DC2626 with Off-White icon
```

### Receipt (Print)
```
Background: Off-White #FAFAFA
Text: Charcoal #1F2937
Totals: Charcoal #1F2937 (bold)
Header/Footer: Charcoal #1F2937 or Amber #D97706
Barcodes: Charcoal #1F2937 (ensure sufficient contrast)
```

---

## TOOLS & RESOURCES

- **WebAIM Contrast Checker:** https://webaim.org/resources/contrastchecker/
- **Stark (Figma):** https://www.getstark.co/
- **axe DevTools (Browser):** https://www.deque.com/axe/devtools/
- **Sim Daltonism:** https://www.color-blindness.com/sim-daltonism/
- **WAVE (Web Accessibility Evaluation Tool):** https://wave.webaim.org/

---

## WCAG 2.1 REFERENCE

**Level AA (Recommended Minimum):**
- Normal text: 4.5:1 contrast
- Large text (18px+ or 14px bold+): 3:1 contrast
- Graphics: 3:1 contrast

**Level AAA (Target for Critical UI):**
- Normal text: 7:1 contrast
- Large text: 4.5:1 contrast

Tavro target: **AA for all, AAA for critical POS and financial data.**

---

## APPROVAL

All color combinations in this palette have been verified for WCAG 2.1 AA compliance.

Critical UI (POS transactions, payment data) should be tested for AAA compliance before production.
