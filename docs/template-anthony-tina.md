# Anthony & Tina — fourth local demo

Reference: https://digitalinvitation.me/anthony-and-tina
Local demo: http://127.0.0.1:8000/template-demos/4
Gallery: http://127.0.0.1:8000/template-demos
Date: 2026-09-03

## What was built

Added a Vue 3 renderer, `RoyalPlum.vue`, and a fourth specimen in the existing local gallery. It uses Anthony and Tina's reference details and the cream `#faf7f2` / plum `#3a1f48` palette.

The sections follow the reference: arch video opening, countdown, curtain invitation, meeting points, celebration, dress code, story, gifts, RSVP, ending and footer. The unusual desktop celebration grid is retained. The opening video plays once, muted; the letter curtains open on entering the viewport and close when leaving it. The map buttons open the reference destinations, and the gift buttons copy visibly marked demo values. The RSVP form is a local presentation specimen and does not submit requests or save records.

The renderer uses existing FormalEvites invitation-shaped data. No Event template was seeded or assigned to customer records.

## Inspection and visual limits

Inspected rendered DOM, computed styles, image dimensions, video playback, animation keyframes, pseudo-elements, section geometry and accept/decline states in an isolated headless Edge browser. The in-app Browser connection was unavailable. No existing browser profile or signed-in session was used, and no reference RSVP was submitted. The Vue implementation was written separately; proprietary source files were not copied.

Reference and local captures cover **375 × 844, 430 × 844, 768 × 1024 and 1366 × 900**. Screenshots, measurements and interaction evidence are in `storage/app/qa/anthony-tina-20260903/`; open `comparison.html` there for paired captures. Opening screenshots can show different video frames because page loading finishes at different playback positions.

**This is a working reconstruction with remaining font differences, not a pixel-identical sign-off.**

- `sweet-fancy-script` is a commercial reference font for the script headings, countdown, venue names and ending. No project license was supplied, so it was not downloaded. The existing OFL Pinyon Script fallback has different swashes and character widths. At 375px the ending wraps to two lines; at tablet/desktop widths a celebration card gains a line. The reference's font sizes and layout constraints are retained.
- The A ♥ T monogram is rebuilt as text. Copy and scroll controls use temporary glyphs in their measured slots instead of copied inline artwork.
- Curtain sway and reveal timing follow the observed rendered animation. Intersection activation is reconstructed from observed entry/exit behavior; it is not a copy of the reference JavaScript.
- Gift account numbers are clearly fictitious demo values. The reference's payment accounts are not reproduced.
- Reference music/audio was not present in the inspected page. No soundtrack was added.

## Files changed in this pass

- New renderer: `resources/js/Components/InvitationTemplates/RoyalPlum.vue`.
- Renderer registration: `resources/js/Components/InvitationPreviewRenderer.vue`.
- Fourth specimen and page background: `resources/js/demo/referenceDemos.ts`, `resources/js/Pages/TemplateDemo.vue`.
- Local route/asset allowlists: `app/Http/Controllers/TemplateDemoController.php`, `routes/web.php`, `config/template_demo_assets.php`.
- Local-only access coverage: `tests/Feature/TemplateDemoTest.php`.
- Cormorant Garamond, Inter and Italianno font files and their SIL OFL notices: `public/fonts/reference/`. Font family aliases are scoped to this renderer.

Baseline hashes confirm that the earlier three renderer files, shared RSVP implementation, domain models, publication code and database files were not changed by this pass. Earlier uncommitted work remains in the working tree.

## Demo media

Fourteen files are stored under the ignored `storage/app/qa/reference-template-assets/template-4/`: one 720 × 1280 opening video (8.708 seconds), its poster, three curtain layers, countdown artwork, three floral artwork files, two venue illustrations, dress artwork, story photography and the reference footer logo. Total: **9,727,578 bytes**. Repeated flower uses share these local files.

The existing local manifest now has 38 reference-media entries across four demos, including source URLs, roles, dimensions, sizes, SHA-256 hashes and demo-only status. It also records the three OFL font sources and the remaining font/artwork differences. Third-party ownership and a commercial production license have not been established; these files remain isolated for the requested local QA. They were not staged or committed.

All fourth-demo media is served through the existing local-only asset endpoint. The renderer does not hotlink reference media or fonts. The unchanged application shell still requests its existing Figtree stylesheet from Bunny Fonts.

## Database and production

No migrations, schema changes, customer records, real RSVP handling, families, meals or publication behavior were changed. The demo and its asset route return 404 outside the local environment. No commit, push or deployment was performed.

## How to review

1. Open `/template-demos/4` with the local Laravel and Vite servers running.
2. Watch the opening and use the scroll prompt. Continue through the curtain invitation and story.
3. Try a map link, copy a demo gift value, select either RSVP response and submit a test name. The confirmation says nothing was sent or saved.
4. Open `/template-demos` to switch among all four specimens.

The next step is visual review. An authorized Sweet Fancy Script font file and license are needed before the remaining typography differences can be closed.

## Validation

- Laravel suite: **164 tests passed, 1,765 assertions**.
- TypeScript: `npx vue-tsc --noEmit` passed.
- Frontend build: `npm run build` passed; Vite leaves public font URLs for runtime resolution. Browser checks confirmed those fonts load.
- Browser checks: all four target sizes; no horizontal overflow, missing images, failed HTTP responses or JavaScript page errors in the fourth demo. The muted video completed once without looping.
- Interaction checks passed: scroll prompt, curtain entry/exit, four map links, gift copy, accept/decline, local submit without a network request, and reduced motion poster/instant scroll.
- Gallery has four links; earlier demos 1–3 still render with their local images loaded.
- `git diff --check` passed. An existing CRLF normalization warning concerns the pre-existing `PublicRsvpExperience.vue` changes; this pass did not edit that file.

Reproducible browser scripts and JSON evidence are saved in the ignored QA directory alongside the screenshots.
