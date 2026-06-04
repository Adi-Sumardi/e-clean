---
name: Clarity Clean
colors:
  surface: '#f8f9fa'
  surface-dim: '#d9dadb'
  surface-bright: '#f8f9fa'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f4f5'
  surface-container: '#edeeef'
  surface-container-high: '#e7e8e9'
  surface-container-highest: '#e1e3e4'
  on-surface: '#191c1d'
  on-surface-variant: '#414754'
  inverse-surface: '#2e3132'
  inverse-on-surface: '#f0f1f2'
  outline: '#727785'
  outline-variant: '#c1c6d6'
  surface-tint: '#005bc0'
  primary: '#005bbf'
  on-primary: '#ffffff'
  primary-container: '#1a73e8'
  on-primary-container: '#ffffff'
  inverse-primary: '#adc7ff'
  secondary: '#006e2c'
  on-secondary: '#ffffff'
  secondary-container: '#86f898'
  on-secondary-container: '#00722f'
  tertiary: '#9e4300'
  on-tertiary: '#ffffff'
  tertiary-container: '#c55500'
  on-tertiary-container: '#0e0200'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d8e2ff'
  primary-fixed-dim: '#adc7ff'
  on-primary-fixed: '#001a41'
  on-primary-fixed-variant: '#004493'
  secondary-fixed: '#89fa9b'
  secondary-fixed-dim: '#6ddd81'
  on-secondary-fixed: '#002108'
  on-secondary-fixed-variant: '#005320'
  tertiary-fixed: '#ffdbcb'
  tertiary-fixed-dim: '#ffb691'
  on-tertiary-fixed: '#341100'
  on-tertiary-fixed-variant: '#783100'
  background: '#f8f9fa'
  on-background: '#191c1d'
  surface-variant: '#e1e3e4'
typography:
  headline-lg:
    fontFamily: Atkinson Hyperlegible Next
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Atkinson Hyperlegible Next
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
  headline-sm:
    fontFamily: Atkinson Hyperlegible Next
    fontSize: 20px
    fontWeight: '700'
    lineHeight: 28px
  body-lg:
    fontFamily: Atkinson Hyperlegible Next
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Atkinson Hyperlegible Next
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-lg:
    fontFamily: Atkinson Hyperlegible Next
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: 0.02em
  headline-lg-mobile:
    fontFamily: Atkinson Hyperlegible Next
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  touch-target-min: 3rem
  stack-sm: 1rem
  stack-md: 1.5rem
  stack-lg: 2.5rem
  container-padding: 1.25rem
  gutter: 1rem
---

## Brand & Style

The design system is centered on extreme legibility, cognitive ease, and physical accessibility, specifically tailored for a demographic that prioritizes functional clarity over decorative trends. The brand personality is dependable, helpful, and transparent.

The visual style is a **refined Minimalism** combined with **High-Contrast** principles. It avoids all forms of visual noise—such as complex gradients, background blurs, or overlapping layers—in favor of a flat, structured interface. Every element serves a singular purpose: to guide the user toward the next step of their cleaning service with zero friction. The UI should evoke a sense of professional reliability and "clinical" cleanliness.

## Colors

The palette is restricted to high-contrast pairings to ensure AA and AAA accessibility standards are met. 

- **Primary Blue (#1A73E8):** Reserved exclusively for interactive elements like primary buttons and active states.
- **Success Green (#34A853):** Used for "Task Complete" confirmations and positive status indicators.
- **Clean White (#FFFFFF):** The primary surface color to maximize contrast with text.
- **Neutral Grey (#F8F9FA):** Used for large background areas to subtly separate the interface from the white component cards.
- **Text Primary (#202124):** A near-black grey used for all body and heading text to reduce eye strain while maintaining maximum contrast.

## Typography

This design system utilizes **Atkinson Hyperlegible Next**, a typeface specifically designed to increase character recognition and improve legibility for readers with low vision. 

The type scale is intentionally oversized. The minimum font size for any functional text is 16px. Headings use a heavy weight (700) to create a clear information hierarchy. Line heights are generous (1.5x) to prevent lines of text from blurring together for users with visual impairments. Avoid using all-caps for long strings of text as it reduces word-shape recognition.

## Layout & Spacing

The layout follows a **Fixed Grid** philosophy on desktop and a **Single Column Fluid** approach on mobile to eliminate horizontal scanning fatigue. 

- **Touch Targets:** A strict minimum of 48x48dp (3rem) is enforced for all interactive elements to accommodate users with limited motor dexterity.
- **Vertical Rhythm:** A heavy 8px-base spacing system is used. Sections are separated by large gaps (`stack-lg`) to clearly define content groups.
- **Margins:** Consistent 20px (`container-padding`) margins on mobile devices ensure content does not bleed into the edge of the screen or bezel.
- **Alignment:** Left-alignment is preferred for all text blocks to provide a consistent "anchor" for the eye.

## Elevation & Depth

To maintain high accessibility, this design system avoids complex shadows which can create visual "muddiness." 

Depth is communicated through **Low-contrast outlines** and **Tonal Layers**. 
1. **Background:** The base app canvas uses a light grey (#F8F9FA).
2. **Cards:** Individual content pieces sit on Pure White (#FFFFFF) cards.
3. **Borders:** Every card and input uses a 1px solid border (#DADCE0) to define its boundaries clearly.
4. **Active State:** When an element is focused or active, the border thickens to 3px and changes to the Primary Blue. Shadows are only used sparingly for "Primary Action Buttons" to give them a slight tactile lift.

## Shapes

The shape language uses **Rounded** (0.5rem) corners. This radius is large enough to feel friendly and safe, but sharp enough to maintain a structured, professional appearance. 

- **Buttons:** Use `rounded-lg` (1rem) to differentiate them from static cards.
- **Inputs:** Use standard `rounded` (0.5rem) corners.
- **Icons:** Should be encased in a circular or highly rounded container when used as primary navigational triggers.

## Components

### Buttons
Primary buttons must span the full width of their container on mobile to maximize the hit area. They use white text on a Primary Blue background. Secondary buttons use a thick 2px border of the Primary Blue with a white background.

### Cards
Cards are the primary container for information. They must include a minimum internal padding of 24px (`stack-md`). No more than two distinct pieces of information should be placed on a single card to prevent cognitive overload.

### Input Fields
Inputs must have a permanent label (no floating labels or placeholder-only labels). The border should be high-contrast. When focused, the border color changes to Primary Blue and increases in thickness.

### Status Indicators
Status is communicated through both color and icons (e.g., a green checkmark for "Done"). Never rely on color alone to convey meaning.

### Navigation
The primary navigation should use large labels accompanied by simple, thick-stroke icons. Avoid "hamburger" menus; use a bottom tab bar with no more than 4 items or a clear "Back" button at the top of the screen.