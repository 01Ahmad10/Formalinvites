# Reference template reconstruction — local review

> Current visual-completion pass (2–3 September 2026): see [the A–R report](template-visual-completion.md) and [exact asset checklist](template-visual-asset-checklist.csv). These supersede the older dimensions/fallback descriptions below. Visual completion remains blocked by missing authorized assets/media/fonts.

Review gallery: http://127.0.0.1:8000/template-demos

1. Jack & Stephanie: http://127.0.0.1:8000/template-demos/1
2. Justin & Maya: http://127.0.0.1:8000/template-demos/2
3. Joseph & Sarah: http://127.0.0.1:8000/template-demos/3

These local-only specimens use the existing three Vue template components. They are not a second invitation system. No customer records are created or modified. Demonstration RSVP forms never submit a request. Production public invitations still render the existing PublicRsvpExperience and submit through the existing token route.

## Scope and evidence

The three live pages were inspected in the browser at 390 × 844 and 1366 × 900, including rendered DOM, computed styles, image slots, typography, section geometry and opening interactions. Implementations are newly authored Vue/TypeScript/CSS. No reference JavaScript, stylesheets, SVG source, photos, artwork, videos or music were copied/downloaded. Reference names, dates, families and venues are isolated in resources/js/demo/referenceDemos.ts. Payment values are explicitly fictitious demo values.

Measurements and browser screenshots are in storage/app/qa/reconstruction-20260902. The observed-assets.json inventory contains the exact observed asset URLs; it contains no original asset binaries or source code. Earlier working-tree changes are preserved. No commit or push was made.

## Template 1 — Jack & Stephanie

Reference: https://digitalinvitation.me/jack-and-stephanie

Main width 820px maximum; mobile width fills the available viewport. Eight full-height pages: photo opening, invitation wording, countdown/calendar, meeting points, celebration, dress code, gifts, RSVP/ending. Background #f7e9df, body #654840, headings #a66c21. Pinyon Script headings, Georgia body, Montserrat small text. Smooth scrolling opening; .75s opacity/22px reveals; live countdown, calendar, copy controls and falling decoration slots.

At a 390 × 844 browser viewport, the first seven section starts are 0, 844, 1688, 2532, 3376, 4220 and 5064. The RSVP begins at 5908.

Missing authorized assets (base https://digitalinvitation.me unless stated):

| Asset | Preserved slot |
| --- | --- |
| /vendor/ethereal-coast/opening-couple.webp | Full viewport opening, cover/center crop, original gradient overlay |
| /vendor/ethereal-coast/paper-sea.webp | Details, meeting, dress, gifts; background extends 40px beyond section, cover/center |
| /vendor/ethereal-coast/paper-coast.webp | Countdown page, same background slot |
| /vendor/ethereal-coast/paper-coast-corner.webp | Celebration page, same background slot |
| /vendor/ethereal-coast/paper-coast-border.webp | RSVP page, same background slot |
| Original inline couple monogram | 88 × 88px, centered, contain |
| /images/templates/groom.svg and bride.svg | 27.5 × 27.5px venue icons |
| Original ceremony and celebration icons | Original icon positions retained as marked slots |
| Falling artwork realLeaf1.png | Fixed pointer-transparent layer, 15 animated 23–62px slots |
| Original ending logo | 136 × 55px centered slot |

Leaf URL: https://digitalinvitation.s3.eu-north-1.amazonaws.com/invitation_fallingImage/realLeaf1.png

## Template 2 — Justin & Maya

Reference: https://digitalinvitation.me/justin-and-maya-1

Full-width page, #5c2018 text and #faf8f5 paper alternating with white. Curtain opening, full-height scratch reveal, countdown, framed houses card, wedding locations, gifts, RSVP, thank-you card, footer. Separate canvas date tiles erase under pointer strokes and reveal independently. Enter/Space also reveal each tile. Original video duration measured 5.083333s; identity fades over 1.8s after the opening. Music control reports missing soundtrack instead of pretending to play music.

| Missing authorized asset | Preserved slot |
| --- | --- |
| /templates/template9/assets/curtain-closed-Bpkadld4.jpg | Full viewport, cover, centered |
| /templates/template9/assets/curtain-video-BAKLj3Y5.mp4 | Full viewport video/last-frame slot; 5.083s opening interval |
| /templates/template9/assets/menu-frame-BFE5kCs7.png | 9:16 frame, max width 520px, centered overlaid house details |
| /templates/template9/assets/scratch-gold-DQrdz0lH.png | Three circular scratch coatings; 128px desktop; up to 112px mobile |
| /templates/template9/assets/gift-icon-BssCdzah.png | Centered gift illustration slot |
| GothamOffice-Regular.otf | Commercial body font missing; Arial temporary fallback with measured sizes |
| Chronicle-Semibold.otf | Commercial display font missing; Georgia temporary fallback with measured sizes |
| Original ending logo | Centered footer slot |

Missing soundtrack: https://digitalinvitation.s3.eu-north-1.amazonaws.com/invitation_music/1783935699-BrunoMars-MarryYouLyricsVideo.mp3

The curtain's photographed folds and motion cannot be reproduced exactly without the authorized video; the labelled slot currently transitions into a neutral final-frame placeholder. No imitation curtain artwork has been invented.

## Template 3 — Joseph & Sarah

Reference: https://digitalinvitation.me/joseph-and-sarah

Full-width opening; following content max width 480px. Backgrounds #f9f0e0, #f6f1e8 and #e8ddcf; body #4f4638; script headings #b48c3d; hero gold #a67d2b. Great Vibes, Ovo and Cinzel. Autoplay/loop/muted video slot, scrolling opening, 1s/100px section reveals, staggered hero reveals, timeline with scrolling rose marker, embedded location maps, dress code, gifts, seal-operated RSVP and ending.

At 390 × 844, measured local starts match the reference through the schedule/location/dress sections: countdown 1613, schedule 1824, location 2372, dress 3400, gifts 3693 (rounded CSS pixels).

| Missing authorized asset | Preserved slot |
| --- | --- |
| /vendor/sacredgarden/swans.mp4 and swans-poster.jpg | Full viewport, cover/center; names at 20vh |
| Original couple monogram | 80 × 80px centered with 40px following margin |
| /vendor/sacredgarden/flourish-left.png and flourish-right.png | 48 × approximately 25px beside schedule heading |
| /vendor/sacredgarden/rose-bouquet.png | 128 × 67px location divider; 67.2 × 61.64px scrolling timeline marker |
| /vendor/sacredgarden/wax-seal.png | 120 × 90px image inside 176px RSVP opener |
| /vendor/sacredgarden/floral-a.png | 136 × 208px corner decorations, original flips/negative edge offsets |
| Falling petal.png | Fixed animated decorative slots |
| /images/logo-new-cropped.png | Centered ending logo slot |

Petal URL: https://digitalinvitation.s3.eu-north-1.amazonaws.com/invitation_fallingImage/petal.png

## Integration and limitations

PublicInvitation now lets each template own its opening and section order. The existing party recipient, original attendee names, additional guests, meals, dietary notes, guest message, accepted/declined response, edit/confirmation/closed states remain in PublicRsvpExperience. Its presentation colors now match the three references. Laravel authorization, publication snapshots, capacity and RSVP services are unchanged.

Story remains connected to the existing optional story fields. The three supplied demo pages have no story section, so the demo flag stays off. Template colors/fonts are deliberately fixed to reference identities for this reconstruction phase; subsequent builder customization of these choices is deferred.

Exact photos, artwork, videos, commercial fonts and music are still missing. Therefore this is a working reconstruction with marked asset gaps, not a claim of pixel-identical completion. Decorative paths and media-specific motion remain limited by those gaps. Replace labelled media through the existing component media props only after authorized files are supplied; do not fetch third-party art automatically.

Open-license fonts were obtained from https://github.com/google/fonts/tree/main/ofl, not from the reference site. Files and their SIL Open Font License notices are under public/fonts/reference (Pinyon Script, Great Vibes, Montserrat, Playfair Display, Ovo, Cinzel).

## Verification

Browser review covers local desktop/mobile renderers, opening interactions, scratch gestures and keyboard reveal, gift copying, RSVP seal expansion, maps, overflow and existing public RSVP content. Automated validation: Laravel suite, TypeScript check, production frontend build and git diff --check. TemplateDemoTest additionally verifies that demo routes return 404 outside local development.

No new database tables, columns or migrations were introduced for this reconstruction. No database writes are made by the demo gallery.

## Files changed in this reconstruction

- resources/js/Components/InvitationTemplates/PublicRomanticFloral.vue — Template 1.
- resources/js/Components/InvitationTemplates/EditorialLuxury.vue — Template 2.
- resources/js/Components/InvitationTemplates/ModernCinematic.vue — Template 3.
- ReferenceAsset.vue, FallingAssetSlots.vue, ScratchDateTile.vue, ReferenceGifts.vue, ReferenceRsvpDemo.vue, referenceExperience.ts and referenceFonts.css — small presentation components/helpers beside the renderers.
- resources/js/Pages/PublicInvitation.vue and resources/js/Components/PublicRsvpExperience.vue — renderer-owned openings and shared RSVP presentation.
- resources/js/Pages/TemplateDemo.vue, resources/js/demo/referenceDemos.ts and routes/web.php — local demo gallery.
- tests/Feature/TemplateDemoTest.php — development-only access checks.
- public/fonts/reference — licensed fonts and notices.

Final validation: 163 Laravel tests / 1,726 assertions passed; standalone vue-tsc passed; production build passed; git diff --check passed. Vite leaves public font URLs unchanged with informational resolution warnings; the fonts loaded successfully in the local browser. Final build also runs vue-tsc. Browser checks confirmed scratch pointer/keyboard interaction, gift copy feedback, seal expansion, all original names, additional guests, meal options, dietary notes, no desktop/mobile overflow, and no browser console errors on the checked pages. Public RSVP forms were inspected without submitting new responses. Existing backend business-rule tests passed.

Next: supply authorized missing artwork/media/commercial font files for exact asset replacement, then review the three pages before further template customization.

One final build attempt exhausted machine memory. The retry passed with command-scoped NODE_OPTIONS=--max-old-space-size=1024 --max-semi-space-size=8; no permanent environment setting was changed.
