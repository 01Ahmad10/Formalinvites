# Template visual completion pass — 2–3 September 2026

The visual correction pass is implemented and locally reviewable. **None of the three templates is declared visually complete:** authorized imagery, artwork and media are still missing; Template 2 also lacks its two commercial fonts.

Review the running [FormalEvites demo gallery](http://127.0.0.1:8000/template-demos), then [Template 1](http://127.0.0.1:8000/template-demos/1), [Template 2](http://127.0.0.1:8000/template-demos/2), and [Template 3](http://127.0.0.1:8000/template-demos/3).

This pass changes only presentation in the existing renderers and three presentation helpers. No database, Builder, publication, authorization, domain, family/guest data, meal, or RSVP component changes were made. Existing uncommitted work is preserved. Nothing was committed or pushed.

## A–C. Visual match status and difference table

Reference pages: [Jack & Stephanie](https://digitalinvitation.me/jack-and-stephanie), [Justin & Maya](https://digitalinvitation.me/justin-and-maya-1), [Joseph & Sarah](https://digitalinvitation.me/joseph-and-sarah).

Statuses below describe the named aspect, not a claim that the whole template matches. Geometry and typography can match while a separate artwork row remains blocked.

| Template | Section/aspect | Reference | FormalEvites | Status |
| --- | --- | --- | --- | --- |
| 1 | Opening geometry/type | Full viewport; responsive script names; 173.5px mobile opening button | Measured name height, button width, date tracking and original gradient restored | MATCHED |
| 1 | Opening photograph | Original painted coast/couple image | Clearly labelled neutral image slot | BLOCKED BY ASSET |
| 1 | Opening/scroll | Smooth opening scroll; mandatory vertical page snapping | Same scroll behavior on standalone demo/public pages; authenticated preview shell excluded | VERY CLOSE |
| 1 | Wording/families | Centered 496px panel and original monogram | Panel width restored; family styles remain frozen, with responsive height differences recorded in the geometry evidence | VERY CLOSE |
| 1 | Monogram/paper backgrounds | Original monogram and four paper background files | Correctly positioned labelled slots | BLOCKED BY ASSET |
| 1 | Countdown/calendar | Responsive Georgia numbers; 254.72px calendar; italic caption and uppercase labels | Same measured boxes, spacing, caption, label case and 40.8px heart | MATCHED |
| 1 | Meeting/celebration cards | 680px column breakpoint; pink icon discs; side-by-side icon/text event rows | Original card geometry and breakpoint restored | MATCHED |
| 1 | Venue/gift icons | Original SVG artwork | Labelled icon slots / temporary copy glyph | BLOCKED BY ASSET |
| 1 | Dress/gift panels | Responsive attire type; original panel borders, shadows and spacing | Measured panel geometry restored | MATCHED |
| 1 | RSVP/ending | Original RSVP form and ending | RSVP frozen; ending badge/spacing restored; page-height residuals documented below | VERY CLOSE |
| 1 | Falling leaves/ending logo | Original leaf and logo | Labelled art slots; motion ranges tuned, random paths approximate | BLOCKED BY ASSET |
| 2 | Opening media | Closed-curtain photograph; 5.083333s video | Original full-viewport slots; labelled missing-media fallback | BLOCKED BY MEDIA |
| 2 | Opening sequence | .35s tap dismissal; .45s curtain fade; 1.8s identity fade; .9s scroll-cue reveal | Same measured durations; 1.8s pulse / 2s scroll cue retained | VERY CLOSE |
| 2 | Opening icons | Original tap-hand and scroll SVGs | Temporary Unicode glyphs | BLOCKED BY ASSET |
| 2 | Reveal/countdown geometry | Full-height reveal; responsive scratch tiles; measured countdown grid | Heading, row and grid positions matched in mobile measurements | MATCHED |
| 2 | Scratch gold/menu frame/gift image | Original coatings, illustrated frame and 112px gift image | Labelled slots with measured dimensions | BLOCKED BY ASSET |
| 2 | Body/display typography | Gotham Office / Chronicle Semibold | Existing licensed Montserrat and Georgia fallbacks; wrapping remains different | BLOCKED BY FONT |
| 2 | Venue/directions layout | Fluid venue typography; rounded outlined directions buttons | Sizes, border, padding, case and spacing restored; font differences remain | BLOCKED BY FONT |
| 2 | Thank-you card | 440px maximum card, 24px border/radius, fluid padding | Original geometry restored; body copy affected by missing font | VERY CLOSE |
| 2 | RSVP | Reference demonstration form | Existing FormalEvites demo/public RSVP kept frozen | VERY CLOSE |
| 2 | Music/footer artwork | Original soundtrack, speaker glyph, white logo | No substitute music; existing unavailable notice and labelled footer | BLOCKED BY MEDIA |
| 3 | Opening media | Swan poster and looping video | Full viewport, centered cover slots; video properties retained | BLOCKED BY MEDIA |
| 3 | Opening text | 20vh placement; script names; tracked Cinzel date; 400px breakpoint | Measured placement, tracking, sizes and reveal timing restored | MATCHED |
| 3 | Base section geometry | Original section order and heights | All four total page heights match; section starts within 0.12px | MATCHED |
| 3 | Typography/colors | Great Vibes/Ovo/Cinzel; original gold/cream palette | Exact available fonts; original heading weight and palette restored | VERY CLOSE |
| 3 | Schedule/location ornaments | Original monogram, flourishes and moving rose | Correct slots; location divider now uses the right-flourish role | BLOCKED BY ASSET |
| 3 | Maps | Two original map slots | Original dimensions/spacing; embedded map content is dynamic | VERY CLOSE |
| 3 | Dress/gifts geometry | Original detail bands and gift card dimensions | Section positions, card spacing, corner slots and opacity restored | MATCHED |
| 3 | Floral corners/RSVP seal/logo | Original artwork | Existing labelled slots; RSVP presentation remains frozen | BLOCKED BY ASSET |
| 3 | Reveals/petals | 1s/100px reveals; separate petal fall/fade/spin | Reveal timings matched; petal ranges tuned; exact fade/path and rose-follow offset remain approximate | VERY CLOSE |

**A — Template 1:** major CSS geometry corrected; blocked by artwork. Frozen family/RSVP presentation causes residual differences. Standalone demo/public scroll snapping is implemented; authenticated embedded previews are deliberately excluded from document-level snapping.

**B — Template 2:** geometry substantially corrected; blocked by artwork, curtain video, soundtrack and commercial fonts. Font-dependent wrapping remains visible. The media fallback is not presented as an equivalent curtain experience.

**C — Template 3:** measured section geometry matches at all required widths; blocked by artwork/video. The frozen RSVP heading retains its previous weight. Decorative motion is not claimed pixel-identical.

## D. Spacing/layout corrections

- Template 1: calendar reduced from 275.13px to the reference 254.72px; countdown/card margins, card borders, pink icon discs, event icon/text columns, dress panel padding, gift panel spacing and ending badge alignment restored.
- Template 2: scratch/countdown alignment, rounded directions buttons, menu overlay spacing, 112px gift illustration, venue address wrapping, thank-you padding and burgundy footer restored.
- Template 3: margin containment makes section boxes match the reference without moving family content; corrected location flourish asset role, divider opacity, gift card gap, footer holder dimensions and corner artwork dimensions.

## E. Typography corrections

Template 1 now uses the measured fluid heading/name/date/attire sizes, original ampersand proportions, Georgia countdown numbers, uppercase small labels, italic countdown caption and centered 40.8px calendar heart. Template 2 restores fluid name/venue sizes, menu capitalization, tracked button labels and display-font roles. Its body fallback uses the already-authorized Montserrat; Georgia remains the Chronicle fallback. Frozen family and RSVP areas retain their existing type. Template 3 restores 700-weight base script headings, 2.88px hero-date tracking and measured countdown tracking.

Font-by-font measured sizes, weights, styles, line heights and letter spacing are retained in `reference-N-style-inventory.json`. The full DOM measurements include the remaining text styles.

## F. Animation/opening corrections and limits

- Template 1: .75s opacity/22px reveals retained; mandatory vertical snapping added to standalone demos. Opening click scrolls to the wording page. Reload restores the opening state. Original overlay retained.
- Template 2: tap target dismisses over .35s with scale .96; curtain fades over .45s; original 5.083333s playback interval is preserved; identity fades over 1.8s and scroll cue over .9s. Pulse is 1.8s, scroll float 2s. Reload returns to the closed state. Reference scrolling remains available before opening. Canvas pointer/keyboard date reveal remains intact.
- Template 3: .1s date and .2s name delays, 1.2s name reveal and 1s/100px section reveals retained. Body/countdown/ending delays were matched where observed. No extra stagger was added to timeline rows. Reload returns to the hero. Video remains autoplay/muted/loop/playsinline when an authorized source is supplied.
- Leaf and petal effects now have separate observed counts, sizes, layers and timing ranges. Exact randomized trajectories, the petal fade curve and the timeline rose's precise follow offset remain approximations. The current shared IntersectionObserver threshold (.08) was retained; the source threshold could not be established exactly from rendered behavior. These limits are explicitly **not** reported as exact behavioral matches.

## G. Responsive verification

Matched browser viewports: **375 x 844, 430 x 844, 768 x 1024, 1366 x 900**. On this Windows browser the 15px vertical scrollbar leaves content widths of 360, 415, 753 and 1351px. Screenshot PNGs contain the rendered content area; both sides use the same viewport and scrollbar behavior.

Template 1's card breakpoint is 680px; Template 3's small-screen breakpoint is 400px. Template 2's title, tile, menu and card sizing were measured independently. Horizontal overflow checks passed on the final desktop demos and the mobile screenshot review. No customer-facing application shell was restyled.

Page-height comparison and measured per-section/card differences are in `geometry-comparison.json`. Countdown digits were captured at different times and cross midnight during this pass; they are not a pixel-comparison target. Existing fictitious demo payment values and frozen RSVP/family text remain unchanged.

## H–L. Exact missing assets

The exhaustive [asset checklist](template-visual-asset-checklist.csv) records **42 missing/substituted roles**, including repeated background placements and effect dependencies. Every row includes template, section, reference role, current fallback, exact measured slot dimensions, aspect ratio, crop, position, mobile behavior and status. It also names the required authorized file/export.

**H — Images/artwork:** Template 1's opening photograph, four paper background files, monogram, venue/copy SVGs, leaf and ending logo; Template 2's closed curtain, gold coating, menu frame, gift image, hand/scroll/music SVGs and white logo; Template 3's poster, monogram, flourishes, rose, floral corners, wax seal, petal and ending logo.

**I — Fonts:** `GothamOffice-Regular.otf` and `Chronicle-Semibold.otf`. Their exact reference URLs are in the checklist. Pinyon Script, Great Vibes, Montserrat, Ovo and Cinzel are already local with their license files. No new font or package was downloaded during this pass.

**J — Videos:** `curtain-video-BAKLj3Y5.mp4` and `swans.mp4`.

**K — Music:** the corresponding authorized `1783935699-BrunoMars-MarryYouLyricsVideo.mp3` soundtrack. No arbitrary track was added.

**L — Authorization:** exact authorized local files/exports listed in the checklist are still required. Reference files were inspected in the rendered browser; no proprietary source or third-party artwork/media binaries were copied or bulk-downloaded. No new remote media hotlinks were introduced. Existing map embeds were preserved. Temporary art remains labelled in its allocated slot.

## M. Screenshot comparison paths

Evidence folder: `C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-completion-20260902/`.

Open `comparison.html` in that folder for reference/local pairs. Each template has four matching full-page pairs:

- `reference-1-375.png` / `local-1-375.png`, and corresponding `430`, `768`, `1366` files.
- `reference-2-375.png` / `local-2-375.png`, and corresponding `430`, `768`, `1366` files.
- `reference-3-375.png` / `local-3-375.png`, and corresponding `430`, `768`, `1366` files.

Focused viewport evidence includes `reference-1-375-calendar.png` / `local-1-375-calendar.png`, `reference-2-375-reveal.png` / `local-2-375-reveal.png`, and both Template 2 closed-opening screenshots. `mobile-opening-comparison.png` presents all three opening pairs. Every full-page pair also has matching `.json` DOM/computed-style measurements. QA files are outside production assets and ignored by Git.

## N–Q. Regression gates

- **N — PHP:** `php artisan test --compact` passed: **163 tests, 1726 assertions**. Log: `php-tests.log`.
- **O — TypeScript:** `npx.cmd vue-tsc --noEmit` passed. Log: `typescript.log`.
- **P — Build:** `npm.cmd run build` passed, 921 modules. Log: `build.log`. A command-local Node heap limit was used to accommodate this machine; no project configuration was changed. Vite reports six pre-existing public-font runtime-resolution warnings; the local font files remain present and render in browser.
- **Q — Whitespace:** `git diff --check` passed. Log: `diff-check.log`. Line-ending notices are not whitespace failures.

## R. Files and Git state

Presentation files changed in this pass:

1. `resources/js/Components/InvitationTemplates/PublicRomanticFloral.vue`
2. `resources/js/Components/InvitationTemplates/EditorialLuxury.vue`
3. `resources/js/Components/InvitationTemplates/ModernCinematic.vue`
4. `resources/js/Components/InvitationTemplates/FallingAssetSlots.vue`
5. `resources/js/Components/InvitationTemplates/ReferenceGifts.vue`
6. `resources/js/Components/InvitationTemplates/ScratchDateTile.vue`

Documentation: this report, the asset CSV, and the existing handoff's current-pass pointer. Hash comparison against the start-of-pass baseline confirms all other scanned application/database/routes/tests/JavaScript/font files remain unchanged, including `PublicRsvpExperience.vue`, `ReferenceRsvpDemo.vue`, the Builder, demo data and publication/auth code. No database changes were made.

`final-git-status.txt` contains the complete `git status --short`; it includes substantial pre-existing work. `initial-status.txt`, `baseline-hashes.json` and `changed-this-pass.json` distinguish this pass from that earlier work. **No commit, push, reset, restore or clean was performed.**

The next review is asset authorization/delivery and visual approval. FormalEvites-specific customization has not begun.

### Captured git status --short

```text
M app/Http/Controllers/DashboardController.php
 M app/Http/Controllers/EventSetupController.php
 M app/Models/Event.php
 M app/Support/InvitationPresenter.php
 M app/Support/InvitationPublicationSnapshotBuilder.php
 M resources/js/Components/InvitationPreviewRenderer.vue
 M resources/js/Components/InvitationTemplates/EditorialLuxury.vue
 M resources/js/Components/InvitationTemplates/ModernCinematic.vue
 M resources/js/Components/InvitationTemplates/PublicRomanticFloral.vue
 M resources/js/Components/PublicRsvpExperience.vue
 M resources/js/Layouts/AuthenticatedLayout.vue
 M resources/js/Pages/Events/InvitationPreview.vue
 M resources/js/Pages/Events/Setup.vue
 M resources/js/Pages/Events/Show.vue
 M resources/js/Pages/PublicInvitation.vue
 M routes/web.php
 M tests/Feature/EventPublicationTest.php
?? app/Http/Controllers/EventBuilderController.php
?? app/Models/EventGiftMethod.php
?? app/Models/EventInvitationContent.php
?? database/migrations/2026_09_02_000001_create_event_invitation_contents_table.php
?? database/migrations/2026_09_02_000002_create_event_gift_methods_table.php
?? docs/
?? public/fonts/
?? resources/js/Components/InvitationTemplates/DateReveal.vue
?? resources/js/Components/InvitationTemplates/FallingAssetSlots.vue
?? resources/js/Components/InvitationTemplates/InvitationContentSections.vue
?? resources/js/Components/InvitationTemplates/ReferenceAsset.vue
?? resources/js/Components/InvitationTemplates/ReferenceGifts.vue
?? resources/js/Components/InvitationTemplates/ReferenceRsvpDemo.vue
?? resources/js/Components/InvitationTemplates/ScratchDateTile.vue
?? resources/js/Components/InvitationTemplates/referenceExperience.ts
?? resources/js/Components/InvitationTemplates/referenceFonts.css
?? resources/js/Pages/Events/Builder.vue
?? resources/js/Pages/TemplateDemo.vue
?? resources/js/demo/
?? tests/Feature/EventBuilderTest.php
?? tests/Feature/TemplateDemoTest.php
```
