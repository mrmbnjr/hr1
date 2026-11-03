# HR1 Warm Cozy Design System

> **Status: first draft.** This guide sets the direction for future UI work. The current CSS does not follow every rule yet. Treat the existing tokens as the starting palette, and update implementation only through focused, reviewed changes.

## 1. Product feel

HR1 should feel **warm, welcoming, people-centered, and dependable**. Warmth comes from a restrained cream, maroon, and gold palette, friendly typography, clear hierarchy, and comfortable spacing. Cozy does not mean cluttered, overly decorative, or difficult to scan.

The interface serves two related audiences:

- **Applicants and the public:** welcoming, clear, and encouraging.
- **HR staff, managers, and employees:** calm, readable, and efficient for completing work.

Both experiences should feel like one product. Internal tools may be denser than public pages, but should share the same tokens, controls, and visual language.

## 2. Visual principles

1. **People first:** use plain, respectful language and make the next useful action easy to find.
2. **Warmth with restraint:** use warm surfaces and occasional decorative details; keep work areas quiet.
3. **Clarity before decoration:** headings, labels, statuses, and actions must be easy to distinguish.
4. **Consistency over novelty:** reuse shared tokens and components before creating page-specific variants.
5. **Honest feedback:** show clear focus, hover, disabled, success, warning, and error states.

## 3. Color roles

The values below are the current palette in `public/assets/css/variables.css`, not a promise that every combination has been accessibility-tested. Use the CSS custom property in implementation rather than copying its hex value.

| Role | Existing token | Current value | Use |
| --- | --- | --- | --- |
| Main page background | `--cream` | `#faf6ee` | Preferred calm page canvas, especially for reading and forms. |
| Warm accent surface | `--bg` | `#f2dd9c` | Use sparingly for featured areas; avoid putting long text directly on it until contrast is checked. |
| Card surface | `--card-bg` | `#fbf3e6` | Cards, panels, and grouped content. |
| Primary brand | `--maroon` | `#6f1414` | Primary actions, selected navigation, and brand emphasis. |
| Deep brand | `--maroon-dark` | `#4a0d0d` | Hover/deep emphasis and dark brand surfaces. |
| Warm highlight | `--gold` | `#f2c14e` | Small accents, progress, and emphasis; not small text on light backgrounds. |
| Main text | `--ink` | `#241311` | Body text and headings on light surfaces. |
| Secondary text | `--ink-soft` | `#6b4a44` | Supporting copy when readable against its surface. |
| Dividers | `--border` | `#e6d5bd` | Quiet boundaries between related content. |

Status colors (`--green`, `--blue`, `--orange`, `--red` and their backgrounds) communicate meaning, not decoration. Always pair color with a word, icon, or other visible cue. Check foreground/background contrast before using a pairing for text.

**Color rules**

- Prefer semantic tokens (`--bg`, `--card-bg`, `--ink`, `--border`, and status tokens) over raw colors.
- Maroon is the default for a primary action. Gold highlights that action or key information; it does not compete as a second primary action.
- Keep large work surfaces mostly cream or card-colored. Reserve stronger color blocks for a clear purpose.
- Do not add a new brand color or page-local copy of the palette without documenting why a shared token cannot serve.
- Dark theme must use the token overrides in `variables.css`; page styles must not add their own theme selectors.

## 4. Typography

Current shared font tokens are `--font-body`, `--font-heading`, and `--font-display` in `variables.css`.

- **Body, forms, tables, and long content:** `--font-body` (currently Nunito). Prioritize legibility and consistent sizing.
- **Headings and short labels:** `--font-heading` (currently Baloo 2), used with restraint for a friendly character.
- **Display font:** `--font-display` (currently Luckiest Guy) is optional for a short public-facing decorative accent only. Do not use it in internal workflows, tables, forms, or paragraphs.
- Use a clear size hierarchy. Page title is the strongest text; section headings come next; labels and supporting text remain readable and should not rely on color alone.
- Avoid loading multiple competing font families on one screen. Use the shared font tokens rather than repeating font stacks in page CSS.

## 5. Spacing and shape

Use the shared spacing tokens in `variables.css` for new and refactored styles. Existing pages still contain fixed spacing values; migrate them gradually when touching those pages.

| Token | Value | Typical use |
| --- | ---: | --- |
| `--space-1` | 4px | Tight icon/label gaps. |
| `--space-2` | 8px | Compact control spacing. |
| `--space-3` | 12px | Related field or list-item spacing. |
| `--space-4` | 16px | Standard component padding and gaps. |
| `--space-6` | 24px | Card padding and section grouping. |
| `--space-8` | 32px | Large section gaps. |
| `--space-12` | 48px | Major page section spacing. |

- Existing radii: `--radius` (14px), `--radius-field` (12px), `--radius-btn` (14px), and `--radius-card` (26px). Prefer the component-specific token. The large card radius should be used selectively; data-dense panels can use a quieter radius.
- Use `--shadow` and `--shadow-lg` for elevation. Most cards should rely on surface contrast and a subtle border, not a heavy shadow.
- Borders separate content; they should not outline every nested element.

## 6. Components

Build and refine common components in `public/assets/css/components.css`. Page CSS should arrange components and define genuinely unique content; it should not redefine the shared component's colors, typography, or behavior.

- **Buttons:** one clear primary action per area; secondary actions are visually quieter. Include visible keyboard focus and disabled states. Reuse existing `.btn-primary`, `.btn-secondary`, and `.btn-danger` patterns where appropriate.
- **Cards and panels:** use shared surface, border, radius, and shadow tokens. Keep headings and actions in predictable positions.
- **Forms:** labels stay visible; help, required, error, and success messages are explicit. Inputs use shared field tokens, have a clear focus state, and do not depend on placeholder text as a label.
- **Tables:** use shared table styles, readable row spacing, clear headings, responsive overflow, and a visible empty state. Keep status and row actions easy to scan.
- **Badges and statuses:** use existing semantic badge/status tokens. Include text labels and preserve the meaning of established HR workflow statuses.
- **Navigation:** active location is clear; labels remain understandable without relying only on icons.

## 7. Page composition

- Give every page one clear title and a short purpose or description when it helps orientation.
- Put the most important information and next action near the start of the content.
- Group related information; use whitespace and headings before adding decorative dividers or illustrations.
- Public careers pages may use larger imagery and warmer storytelling. Keep application requirements and calls to action direct.
- Internal pages should favor task completion, readable data, and clear workflow state. Keep decoration out of dense operational areas.
- Empty, loading, success, warning, and error states are part of the design, not afterthoughts.

## 8. Responsive and accessible behavior

- Layouts must work on narrow screens without clipped controls or page-wide horizontal scrolling. Wide tables may scroll within their own container.
- Keep controls usable by touch and keyboard. Every interactive element needs a visible `:focus-visible` state.
- Use semantic HTML, associated form labels, descriptive link text, and accessible names for icon-only controls.
- Do not use color as the only signal for status, errors, or selection.
- Respect reduced-motion preferences for nonessential animation.
- Verify text contrast for actual foreground/background pairs, including hover, disabled, and dark-theme states.

## 9. CSS ownership and reuse

- `public/assets/css/variables.css` owns shared color, typography, radius, shadow, layout, and theme tokens.
- `public/assets/css/components.css` owns reusable controls, tables, badges, and common content patterns.
- A page stylesheet owns only that page's layout and unique elements. It should consume shared tokens and components.
- Avoid a second token system such as page-local color aliases when the shared token already represents the same role. If a page needs a true semantic exception, document it and keep it scoped.
- Do not add inline style blocks or duplicate global component rules for convenience. Dynamic values (such as a progress percentage) may be provided inline when they are data-driven and safely validated.
- Keep the current PHP, HTML, CSS, and JavaScript architecture. This design effort does not require a CSS framework migration.

## 10. Refactoring sequence

1. Review this guide against the existing palette and adjust the decisions before broad implementation.
2. Audit tokens and identify duplicate or unclear names; do not change token values without checking their usage across pages and dark theme.
3. Standardize shared components, beginning with buttons, fields, cards, tables, and status badges.
4. Use the Applicants page as the first internal visual reference (see below). Refactor the public careers landing page separately for its public audience, then review the HR dashboard and another data-heavy workflow page.
5. Compare each updated page in desktop and mobile layouts, light and dark themes, and relevant interactive states before moving to the next page.
6. Extend the established patterns to remaining pages; do not combine visual work with changes to authorization, recruitment decisions, or employee lifecycle rules.

## 11. Internal visual reference: Applicants

`resources/views/applicants/index.php` with `public/assets/css/applicants.css` is the current reference for the **internal Warm Cozy direction**. It is a useful example because it uses the shared warm palette, cream cards, maroon emphasis, shared font tokens, visible focus styling, and a clear pipeline summary above the applicant work area.

Use its visual language and information hierarchy as a guide when refactoring other internal pages:

- warm card surfaces and restrained maroon/gold accents;
- a strong page-level summary followed by the main task area;
- clear separation between pipeline statuses, filters, and applicant records;
- shared tokens for most colors, typography, focus rings, and surfaces.

Treat it as a **reference, not a copy-and-paste template**. Keep the layout suited to each workflow and reuse shared components instead of copying applicant-specific CSS. The page also has a few local radii, shadows, and raw color values, so it does not define the final token standard by itself. As the design system is implemented, migrate those exceptions toward shared tokens and preserve the applicant page's useful hierarchy and warmth.

The public landing page remains a separate first visual reference for applicants and visitors. It should share the same design tokens while using a more welcoming, editorial layout than internal tools.

## 12. Page review checklist

- Does the page use the shared palette, font, radius, and shadow tokens?
- Is there one clear visual priority and a clear next action?
- Are shared controls actually reused instead of restyled copies?
- Can users read text and understand statuses without relying on color alone?
- Are focus, hover, disabled, error, empty, and success states covered where relevant?
- Does the page work on mobile and in both themes?
- Did the change preserve routes, permissions, data behavior, and established HR workflow rules?
