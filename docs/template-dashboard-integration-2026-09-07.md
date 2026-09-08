# Template selection and branding update

## Completed

- Added Dolce Vita, Blossom & Oud, and The Sacred Garden to the existing trusted renderer catalog and customer template selection. A dedicated, repeatable seeder added three rows to the existing templates table; it does not overwrite admin activation choices. No schema or migration changes.
- Customer dashboards display the saved design name. Admin upcoming-event cards and Event details display the selected design; Admin Templates lists all three additions, assignment counts, activation controls, and local demo links.
- Setup preview dialogs can show the populated local reference demo in an iframe. Reference fixtures/media are not added to Event records or publication snapshots.
- Registered the three Vue renderers and added a presentation adapter for existing Event details. Non-demo output uses the existing RSVP slot rather than a fake demo submission form. RSVP validation/persistence, families, and meals were not changed.
- Removed Digital Invitation footer logos and the Instagram link from templates 1–4, replacing them with FormalEvites text. Removed their logo roles from the local asset allowlist. Templates 5–7 contained no Digital Invitation footer branding.
- Anthony & Tina / Royal Plum remains demo-only.

## Verification

165 Laravel tests passed (2,007 assertions), including a new test covering all three customer selections, unauthorized access denial, selected names on both dashboards/admin Event details, Event preview payloads without demo media, and safe reseeding. TypeScript passed. Build passed in 14.28 seconds. `git diff --check` passed with line-ending conversion warnings.

Browser verification confirmed the eight admin catalog entries, the setup cards and Blossom & Oud iframe demo preview, and FormalEvites footer text with no old logo requests or Digital Invitation links on demos 1–4. Existing local Events were not reassigned during browser checks.

## Boundaries

This connects selection and review. Reference media remain local comparison assets and are not included in real/public Event output; missing production artwork remains marked. Exact fonts and full native RSVP presentation remain separate work. No demo names, dates, or guest responses are saved to customer Events. No existing Event selections, schema, payments, families, or meal rules were changed. No commit or push.

Important files: `database/seeders/WebgencyTemplateSeeder.php`, `app/Models/Template.php`, the existing setup/dashboard/admin controllers and views, `InvitationPreviewRenderer.vue`, `webgencyEventContent.ts`, and the template Vue files.
