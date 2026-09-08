# Three additional local invitation demos — 7 September 2026

The local gallery now contains seven demos. The three additions are working Vue specimens with reference text and layouts, but **are not visually complete**: their exact artwork, video and custom fonts are unavailable locally. No new reference assets or proprietary source code were downloaded. Existing templates, customer selection, database, families, meals and production RSVP logic were not changed. No commit or push.

| Demo | Local page | Reference |
| --- | --- | --- |
| 5 — Dolce Vita · Alexa & Richard | [Open demo](http://127.0.0.1:8000/template-demos/5) | [Dolce Vita](https://webgencyinvitations.com/dolcevita) |
| 6 — Blossom & Oud · Amira & Yusuf | [Open demo](http://127.0.0.1:8000/template-demos/6) | [Blossom & Oud](https://webgencyinvitations.com/blossomoud) |
| 7 — The Sacred Garden · Zohan & Rose | [Open demo](http://127.0.0.1:8000/template-demos/7) | [The Sacred Garden](https://webgencyinvitations.com/thesacredgarden) |

[Open all seven demos](http://127.0.0.1:8000/template-demos).

## What was added

- **Dolce Vita:** centered 440px hero canvas, 782px hero, cream `#fffdfb`, blue `#9bc9e1` RSVP button, three scratchable date tiles, letter/envelope slots, alternating six-event timeline, venue photo slot, ten-photo dress inspiration strip, measured five-color palette, RSVP dialog and couple-photo ending. The mobile envelope opens to the invitation. Keyboard Enter/Space also reveals the date. Carousel arrows are a local accessibility convenience; the reference carousel's complete motion behavior is not reproduced yet.
- **Blossom & Oud:** peach `#f9e6d4`, gold `#866739`, 440px hero, Moroccan arch video slot, 356 × 734px invitation frame, Arabic invitation with right-to-left paragraphs, French headings and RSVP presentation, four-event schedule, venue/dress illustrations, four-color palette, 341px map frame, ending illustration and video slots. The zeroed clock matches the current specimen; the printed May 2027 date and original Arabic wording are retained as reference demo data.
- **The Sacred Garden:** cream `#f9f0e0`, gold `#a67d2b`, 450px canvas, 1.4s envelope fade, swan-video and floral slots, blessing/introduction, live countdown to the fixture's September 2026 date, five-event schedule, location and map, dress/gift preferences, seal-triggered RSVP dialog and portrait ending. The exact envelope video/reveal sequence and decorative motion remain asset/measurement blockers; the local clock uses the event date rather than copying a stale reference counter.

RSVP forms are local UI demonstrations. Submitting shows that no response was sent or saved. They do not call a backend RSVP route or introduce any new domain validation rules. The modal versions trap focus through the native dialog element, close with Escape, and restore page scrolling. The real InvitationParty/PartyMember/meal integration remains a later task.

## Reference inspection and limits

Inspected rendered hierarchy, section order, text, colors, typography metrics, image dimensions/positions, overlays, date tiles, RSVP forms and map dimensions. Compared viewport sizes 375, 430, 768 and 1366. The reference uses custom font-family aliases `NewFonts` and `Webgency`, with `Rufina` on Dolce Vita date tiles. Authorized matching font files were not found. The existing OFL Pinyon Script file is explicitly a temporary script fallback; Georgia provides temporary body/date typography.

This affects letter widths, wrapping and section heights. Measured heading sizes were retained. Where the fallback wraps, the local section can grow to prevent text or buttons overlapping the next section. This fixes an observed mobile Dolce Vita RSVP hit-area problem, but means those section heights are not exact until fonts are supplied. Some image slots are clipped at narrow widths as in the fixed-width reference canvas. Placeholder labels are intentionally visible.

The reference pages also contain mobile-only envelope layers, popup forms, and animated/media elements. Some reference layout metrics change while animation or lazy media settles. This is not a pixel-perfect or behavior-complete signoff. Exact decorative motion, carousel behavior, envelope video timing, and reference control glyphs still need another comparison pass once the assets are present.

## Files changed in this task

- New renderer files: `DolceVita.vue`, `BlossomOud.vue`, `SacredGarden.vue` under `resources/js/Components/InvitationTemplates/`.
- New local helpers: `DemoEnvelope.vue`, `WebgencyRsvpDemo.vue`, `webgencyDemos.css` in the same directory.
- New fixture data: `resources/js/demo/webgencyDemos.ts`; appended to `referenceDemos.ts`.
- `resources/js/Pages/TemplateDemo.vue`: demo-only component map and gallery labels. The production renderer registry was not expanded.
- `app/Http/Controllers/TemplateDemoController.php`: allows demo numbers 5–7 only in the local environment.
- `config/template_demo_assets.php`: explicit missing-file slots for owner-supplied assets, served through the existing local-only allowlist.
- `routes/web.php`: permits 5–7 and numbered roles such as `outfit10` in the existing demo asset route.
- `tests/Feature/TemplateDemoTest.php`: seven-demo coverage, production denial, asset allowlisting and traversal protection.

No new dependencies or database migrations were added. The hash audit found **zero changes to the pre-existing template component/helper files**.

## Validation

- `php artisan test --compact`: **164 tests passed, 1,820 assertions**, 32.07s.
- Targeted demo-route tests: **3 passed, 151 assertions**.
- `npx vue-tsc --noEmit`: passed, exit 0.
- Final production build: **passed — 941 modules, 10.40s**, exit 0. Existing public-font runtime-resolution notices remain; the local Pinyon fallback file is present.
- `git diff --check`: passed; the pre-existing CRLF notice for PublicRsvpExperience.vue remains.
- All 12 new demo/viewport combinations: no horizontal page overflow, no broken image elements, and RSVP controls stay within their sections. Missing media are marked placeholders, not loaded assets.
- Browser checks passed for date reveal, three local form submissions, envelope dismissal, popup close/Escape and scroll restoration. No console errors/warnings were reported by the inspected demo tab.
- Screenshots were visually reviewed in the browser tool. This task did not create a new set of saved screenshot files; older screenshot folders do not represent these new demos.
- Physical devices, Safari, and real customer RSVP persistence were not exercised in this demo-only pass.

Logs, responsive results and the asset checklist are in `storage/app/qa/webgency-20260907/`.

## Remaining asset slots

All paths below are relative to the repository. They deliberately point into ignored local comparison storage. Supplying an authorized file at its allowlisted path makes that slot available on the next page load; it does not publish the file or add the template to customer selection. Video/poster entries may be alternatives, not separate required artwork.

| Demo | Role | Expected authorized file | Present |
| --- | --- | --- | --- |
| 5 | `envelope` | `storage/app/qa/reference-template-assets/template-5/envelope.png` | No |
| 5 | `envelopeVideo` | `storage/app/qa/reference-template-assets/template-5/envelope-opening.mp4` | No |
| 5 | `heroPoster` | `storage/app/qa/reference-template-assets/template-5/italian-villa-terrace.jpg` | No |
| 5 | `heroVideo` | `storage/app/qa/reference-template-assets/template-5/italian-villa-terrace.mp4` | No |
| 5 | `scratchTexture` | `storage/app/qa/reference-template-assets/template-5/scratch-coating.png` | No |
| 5 | `letterBack` | `storage/app/qa/reference-template-assets/template-5/letter-back.png` | No |
| 5 | `letterFront` | `storage/app/qa/reference-template-assets/template-5/letter-front.png` | No |
| 5 | `letterFlower` | `storage/app/qa/reference-template-assets/template-5/letter-flower.png` | No |
| 5 | `scheduleLeft` | `storage/app/qa/reference-template-assets/template-5/schedule-left.png` | No |
| 5 | `scheduleRight` | `storage/app/qa/reference-template-assets/template-5/schedule-right.png` | No |
| 5 | `venuePhoto` | `storage/app/qa/reference-template-assets/template-5/venue.jpg` | No |
| 5 | `locationIcon` | `storage/app/qa/reference-template-assets/template-5/location-icon.png` | No |
| 5 | `dressDivider` | `storage/app/qa/reference-template-assets/template-5/dress-divider.png` | No |
| 5 | `outfit1` | `storage/app/qa/reference-template-assets/template-5/outfit-1.jpg` | No |
| 5 | `outfit2` | `storage/app/qa/reference-template-assets/template-5/outfit-2.jpg` | No |
| 5 | `outfit3` | `storage/app/qa/reference-template-assets/template-5/outfit-3.jpg` | No |
| 5 | `outfit4` | `storage/app/qa/reference-template-assets/template-5/outfit-4.jpg` | No |
| 5 | `outfit5` | `storage/app/qa/reference-template-assets/template-5/outfit-5.jpg` | No |
| 5 | `outfit6` | `storage/app/qa/reference-template-assets/template-5/outfit-6.jpg` | No |
| 5 | `outfit7` | `storage/app/qa/reference-template-assets/template-5/outfit-7.jpg` | No |
| 5 | `outfit8` | `storage/app/qa/reference-template-assets/template-5/outfit-8.jpg` | No |
| 5 | `outfit9` | `storage/app/qa/reference-template-assets/template-5/outfit-9.jpg` | No |
| 5 | `outfit10` | `storage/app/qa/reference-template-assets/template-5/outfit-10.jpg` | No |
| 5 | `endingPhoto` | `storage/app/qa/reference-template-assets/template-5/ending-couple.jpg` | No |
| 6 | `envelope` | `storage/app/qa/reference-template-assets/template-6/envelope.png` | No |
| 6 | `envelopeVideo` | `storage/app/qa/reference-template-assets/template-6/envelope-opening.mp4` | No |
| 6 | `heroPoster` | `storage/app/qa/reference-template-assets/template-6/moroccan-arch.jpg` | No |
| 6 | `heroVideo` | `storage/app/qa/reference-template-assets/template-6/moroccan-arch.mp4` | No |
| 6 | `invitationFrame` | `storage/app/qa/reference-template-assets/template-6/invitation-frame.png` | No |
| 6 | `calligraphy` | `storage/app/qa/reference-template-assets/template-6/calligraphy.png` | No |
| 6 | `invitationOrnament` | `storage/app/qa/reference-template-assets/template-6/invitation-ornament.png` | No |
| 6 | `flowerLine` | `storage/app/qa/reference-template-assets/template-6/flower-line.png` | No |
| 6 | `divider` | `storage/app/qa/reference-template-assets/template-6/divider.png` | No |
| 6 | `timelineMarker` | `storage/app/qa/reference-template-assets/template-6/timeline-marker.png` | No |
| 6 | `timelineFlower` | `storage/app/qa/reference-template-assets/template-6/timeline-flower.png` | No |
| 6 | `locationIcon` | `storage/app/qa/reference-template-assets/template-6/location-icon.png` | No |
| 6 | `venueArt` | `storage/app/qa/reference-template-assets/template-6/venue.png` | No |
| 6 | `dressArt` | `storage/app/qa/reference-template-assets/template-6/dress.png` | No |
| 6 | `mapFrame` | `storage/app/qa/reference-template-assets/template-6/map-frame.svg` | No |
| 6 | `endingArt` | `storage/app/qa/reference-template-assets/template-6/ending-art.png` | No |
| 6 | `endingDivider` | `storage/app/qa/reference-template-assets/template-6/ending-divider.png` | No |
| 6 | `endingFlower` | `storage/app/qa/reference-template-assets/template-6/ending-flower.png` | No |
| 6 | `endingVideo` | `storage/app/qa/reference-template-assets/template-6/ending.mp4` | No |
| 6 | `endingPoster` | `storage/app/qa/reference-template-assets/template-6/ending.jpg` | No |
| 7 | `envelope` | `storage/app/qa/reference-template-assets/template-7/envelope.png` | No |
| 7 | `envelopeVideo` | `storage/app/qa/reference-template-assets/template-7/envelope-opening.mp4` | No |
| 7 | `heroPoster` | `storage/app/qa/reference-template-assets/template-7/swans.jpg` | No |
| 7 | `heroVideo` | `storage/app/qa/reference-template-assets/template-7/swans.mov` | No |
| 7 | `heroLeft` | `storage/app/qa/reference-template-assets/template-7/hero-left.png` | No |
| 7 | `heroRight` | `storage/app/qa/reference-template-assets/template-7/hero-right.png` | No |
| 7 | `paper` | `storage/app/qa/reference-template-assets/template-7/paper.png` | No |
| 7 | `calligraphy` | `storage/app/qa/reference-template-assets/template-7/calligraphy.png` | No |
| 7 | `flourishLeft` | `storage/app/qa/reference-template-assets/template-7/flourish-left.png` | No |
| 7 | `flourishRight` | `storage/app/qa/reference-template-assets/template-7/flourish-right.png` | No |
| 7 | `scheduleTop` | `storage/app/qa/reference-template-assets/template-7/schedule-top.png` | No |
| 7 | `scheduleBottom` | `storage/app/qa/reference-template-assets/template-7/schedule-bottom.png` | No |
| 7 | `timelineRose` | `storage/app/qa/reference-template-assets/template-7/timeline-rose.png` | No |
| 7 | `venueDivider` | `storage/app/qa/reference-template-assets/template-7/venue-divider.png` | No |
| 7 | `venueArt` | `storage/app/qa/reference-template-assets/template-7/venue.png` | No |
| 7 | `butterfly1` | `storage/app/qa/reference-template-assets/template-7/butterfly-1.png` | No |
| 7 | `butterfly2` | `storage/app/qa/reference-template-assets/template-7/butterfly-2.png` | No |
| 7 | `butterfly3` | `storage/app/qa/reference-template-assets/template-7/butterfly-3.png` | No |
| 7 | `butterfly4` | `storage/app/qa/reference-template-assets/template-7/butterfly-4.png` | No |
| 7 | `butterfly5` | `storage/app/qa/reference-template-assets/template-7/butterfly-5.png` | No |
| 7 | `butterfly6` | `storage/app/qa/reference-template-assets/template-7/butterfly-6.png` | No |
| 7 | `mapFrame` | `storage/app/qa/reference-template-assets/template-7/map-frame.svg` | No |
| 7 | `mapTop` | `storage/app/qa/reference-template-assets/template-7/map-top.png` | No |
| 7 | `mapBottom` | `storage/app/qa/reference-template-assets/template-7/map-bottom.png` | No |
| 7 | `preferencePaper` | `storage/app/qa/reference-template-assets/template-7/preference-paper.png` | No |
| 7 | `preferenceRight` | `storage/app/qa/reference-template-assets/template-7/preference-right.png` | No |
| 7 | `preferenceLeft` | `storage/app/qa/reference-template-assets/template-7/preference-left.png` | No |
| 7 | `rsvpSeal` | `storage/app/qa/reference-template-assets/template-7/rsvp-seal.png` | No |
| 7 | `endingPhoto` | `storage/app/qa/reference-template-assets/template-7/ending-couple.jpg` | No |
| 7 | `endingPaper` | `storage/app/qa/reference-template-assets/template-7/ending-paper.png` | No |

The visible placeholder labels name the corresponding scene or reference artwork (for example `frsame_1`, `Group_222`, `wax_seal_1`, and the Italian villa terrace). Exact fonts and the source artwork remain necessary for final visual approval.
