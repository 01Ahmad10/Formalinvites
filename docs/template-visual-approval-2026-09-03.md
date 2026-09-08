# FormalEvites — visual approval report

3 September 2026. Continued from the existing working tree on `develop`, HEAD `294e803`.

The fresh 12-pair review matrix is complete. The four local demos are available for owner review. This pass corrected renderer details using the existing assets; **owner visual approval is still pending**. Missing artwork, unavailable fonts/music and the explicitly frozen family/RSVP differences prevent a claim of complete visual equivalence.

Open the [four-demo gallery](http://127.0.0.1:8000/template-demos), or open the [fresh comparison viewer](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison.html>).

## What changed in this pass

- Template 1: restored the opening date's font weight, responsive quotation size and request-text weight; corrected leaf travel, rotation, drift and spin timing.
- Template 2: reconstructed the ending card's cut-paper edge; restored responsive script text and countdown tile sizing; made the temporary hand glyph monochrome; restored uppercase gift details. The scratch brush is now 36px and the tile clears after approximately half its coating is removed, as observed on the reference.
- Template 3: corrected petal travel/fade stages, the rose's position along the timeline, scroll-reveal threshold/reversal, opening reveal direction and transformed-text rendering.
- Demo countdowns: added explicit reference instants to the three local demo fixtures. The references count down to 2027-11-11 18:00 UTC, 2027-06-10 17:00 UTC and 2026-10-10 18:00 UTC. Their visible clocks were measured; no reference application source was copied. The helper's existing real-Event date/time path remains unchanged.

Seven application files changed during this pass:

| File | Purpose |
| --- | --- |
| [PublicRomanticFloral.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationTemplates/PublicRomanticFloral.vue>) | Template 1 typography corrections |
| [EditorialLuxury.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationTemplates/EditorialLuxury.vue>) | Template 2 paper edge, responsive sizing and small presentation details |
| [ModernCinematic.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationTemplates/ModernCinematic.vue>) | Template 3 timeline/reveal corrections |
| [FallingAssetSlots.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationTemplates/FallingAssetSlots.vue>) | Measured leaf/petal motion |
| [ScratchDateTile.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationTemplates/ScratchDateTile.vue>) | Scratch brush and clearing threshold |
| [referenceExperience.ts](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationTemplates/referenceExperience.ts>) | Demo clock override and Template 3 reveal timing |
| [referenceDemos.ts](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/demo/referenceDemos.ts>) | Three local-only countdown instants |

No database schema, customer records, template registration, Builder workflow or domain files were changed in this pass. A 240-file baseline comparison confirms the domain/Builder files stayed unchanged, including InvitationPresenter, InvitationPreviewRenderer, publication logic and both shared RSVP components. Their older uncommitted changes remain visible in Git and were preserved. See the [freeze audit](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/freeze-audit.json>).

## Review method

Fresh live-reference and local captures were made at 375 × 844, 430 × 844, 768 × 1024 and 1366 × 900, after the existing exact demo media had been installed. Inspection covered rendered elements, computed styles, geometry, fonts, media crop, animation keyframes, scrolling and controls. No proprietary source files were copied and no new third-party media was downloaded in this pass.

The in-app browser tools were unavailable, so the checks used isolated Playwright/Edge contexts without a user profile or signed-in session. Final local screenshots were captured after restarting a development server that had retained an older helper module. All 12 final local runs returned HTTP 200 with no broken images, failed responses, horizontal overflow or JavaScript page errors.

`MATCHED` below means the inspected section's layout, available artwork and stated behavior agree at the four tested widths. It is not a numerical pixel-similarity score. Moving decorations, countdown seconds and external map content vary between captures. Both swan opening videos were paused at 2 seconds for the opening stills; playback was tested separately. Physical-phone/Safari testing was not performed.

## A. Template 1 — Jack & Stephanie

[Live reference](https://digitalinvitation.me/jack-and-stephanie) · [Local demo](http://127.0.0.1:8000/template-demos/1)

**Overall: ASSET REQUIRED**, with the frozen family/RSVP differences listed below.

| SECTION | REFERENCE | LOCAL | STATUS | BLOCKER |
| --- | --- | --- | --- | --- |
| Opening | Full-height couple photo, warm overlay, script names and date | Existing exact photo, crop, overlay and measured typography | MATCHED | None in the measured opening composition |
| Paper/backgrounds | Sea, coast, corner and border artwork | Existing exact files in the same section/background slots | MATCHED | None |
| Monogram | Couple monogram in the centered details slot | Marked temporary 88 × 88 slot | ASSET REQUIRED | Exact couple monogram artwork |
| Quotation/request | Responsive italic quote; weighted request text | Responsive sizing and weights corrected | MATCHED | None |
| Families | Gold script family surnames with responsive sizes | Existing FormalEvites demo family styling preserved | MINOR DIFFERENCE | Family visual styling explicitly frozen for this phase |
| Countdown/calendar | Four cards, month grid and heart date marker | Geometry retained; reference countdown instant corrected | MATCHED | Clock seconds differ with capture time |
| Meeting points | Two cards and groom/bride artwork | Existing exact groom/bride assets, placement and card geometry | MATCHED | None |
| Ceremony/celebration | Original inline icons beside venue details | Correct slots/cards; temporary icon artwork | ASSET REQUIRED | Exact ceremony and celebration icons |
| Dress code | Centered panel with title, copy and pill | Available background and measured panel styling | MATCHED | None |
| Gifts | Gift panel, payment details and copy controls | Panel retained; fictitious demo accounts and substitute copy glyph | ASSET REQUIRED | Original copy icon; demo account text intentionally differs |
| RSVP placement | Reference response form and ending share the last section | Existing shared form and footer spacing preserved | MINOR DIFFERENCE | RSVP visuals frozen; last-section height differs by approximately 0.4–64.9px across widths |
| Ending | Script ending, logo and reference placement below RSVP | Existing exact logo and ending content; vertical position follows preserved form | MINOR DIFFERENCE | Upstream RSVP height difference |
| Motion/mobile behavior | Full-height sections; 18 drifting/spinning leaves; opening scroll | Travel −10vh to 120vh, 520° rotation and observed timing ranges; button scroll verified | MATCHED | Individual particle positions are not synchronized across live runs |

The first seven section heights match at every required width. The remaining page-height difference is in the frozen RSVP/ending section.

## B. Template 2 — Justin & Maya

[Live reference](https://digitalinvitation.me/justin-and-maya-1) · [Local demo](http://127.0.0.1:8000/template-demos/2)

**Overall: FONT REQUIRED / ASSET REQUIRED / MEDIA PERMISSION REQUIRED.** The available curtain, frame and gift artwork are integrated; family/RSVP styling remains frozen.

| SECTION | REFERENCE | LOCAL | STATUS | BLOCKER |
| --- | --- | --- | --- | --- |
| Curtain/media | Closed curtain; 5.083-second opening video; name reveal | Existing exact still/video, cover crop and settled reveal | MATCHED | None for available curtain media |
| Opening controls | Original hand, scroll and music icons | Monochrome temporary hand and existing substitute glyphs | ASSET REQUIRED | Exact control artwork |
| Names/parents/body headings | Great Vibes plus commercial display/body font roles | Great Vibes available; existing font fallbacks retained | FONT REQUIRED | Gotham Office Regular and Chronicle Semibold; family styling frozen |
| Scratch date | Gold coating; 36px brush; clearing at about 50% | Existing exact texture, measured tile geometry and corrected threshold | FONT REQUIRED | Pointer/keyboard behavior matches; underlying commercial display font is unavailable |
| Countdown | Responsive 64–80px tiles and numeral sizing | Tile and numeral scaling corrected; reference instant restored | FONT REQUIRED | Commercial numeral font remains unavailable |
| Framed meeting points | 9:16 frame, directions and centered venue information | Existing exact frame with matching crop/dimensions | FONT REQUIRED | Commercial heading/font metrics |
| Wedding locations | Reference heading sizes and directions layout | Existing data and measured layout; approximately −1.2 to +1.8px section-height variation | FONT REQUIRED | Font metrics and wrapping |
| Gifts | Exact gift illustration, large responsive script and bordered account block | Existing exact art; responsive script and uppercase details corrected | MINOR DIFFERENCE | Fictitious demo account text is intentionally different |
| RSVP | Reference labels, choices, inputs and submit layout | Existing local/shared RSVP styling preserved | MINOR DIFFERENCE | RSVP visuals frozen; approximately 53.0–80.2px section-height difference |
| Ending | Burgundy rounded card, notched white paper, responsive script names | Paper edge recreated cleanly; widths, padding and script scaling restored | MATCHED | None in the measured card composition |
| Soundtrack | Reference soundtrack and playing-state control | Existing unavailable-music notice; no replacement soundtrack | MEDIA PERMISSION REQUIRED | Authorized soundtrack file/permission |
| Responsive behavior | Four required viewport layouts | No overflow; opening, scratch, framed sections and ending checked at all four sizes | MATCHED | Listed fonts/artwork/RSVP exceptions still apply |

After correction, all section-height differences outside RSVP are limited to the location text's font-related variation. No reference RSVP was submitted.

## C. Template 3 — Joseph & Sarah

[Live reference](https://digitalinvitation.me/joseph-and-sarah) · [Local demo](http://127.0.0.1:8000/template-demos/3)

**Overall: ASSET REQUIRED**, with explicit frozen family/RSVP and external-map differences.

| SECTION | REFERENCE | LOCAL | STATUS | BLOCKER |
| --- | --- | --- | --- | --- |
| Swan opening | Full-height swan video, gold typography and cream composition | Existing exact poster/video and measured crop; opening reveal direction corrected | MATCHED | None; video playback checked separately from synchronized stills |
| Tagline/typography | Great Vibes, Ovo and Cinzel | Available fonts loaded; measured sizes and colors | MATCHED | None |
| Monogram/greeting | Monogram above invitation copy | Marked temporary 80 × 80 slot; existing greeting retained | ASSET REQUIRED | Exact couple monogram |
| Families | Parent names and spaced uppercase lead-in | Existing family treatment retained | MINOR DIFFERENCE | Family typography/letter spacing frozen |
| Countdown | Gold/cream counters and colon spacing | Same geometry; reference countdown instant restored | MATCHED | Capture-time seconds vary |
| Schedule | Ornament heading, timeline, nodes and scroll-following rose | Exact flourishes/rose; measured rose travel restored | MATCHED | Checked rose offsets differ by at most 0.1px in sampled positions |
| Maps/locations | Two framed Google map embeds | Matching frame size/placement with existing venue queries | MINOR DIFFERENCE | External map tiles, labels and provider rendering can vary |
| Dress code | Script heading and measured copy hierarchy | Existing exact text, including the reference's “oin us” typo | MATCHED | None |
| Gifts | Mirrored floral corners and two account cards | Existing exact flowers, crop and card geometry | MINOR DIFFERENCE | Fictitious demo account text is intentionally different |
| RSVP seal/form | Wax seal and script opening prompt | Existing exact seal in its slot; preserved prompt, heading and form treatment | MINOR DIFFERENCE | RSVP visuals frozen; prompt font/color and heading weight still differ |
| Ending | Cream/taupe ending, script message, names and logo | Existing exact logo and measured section geometry | MATCHED | None in the inspected ending composition |
| Decorative motion | Petal fall/fade, timeline rose and reversible scroll reveals | Corrected fall −12vh to 115vh, fade stages, 120px reveal threshold and reversal | MATCHED | Random particle positions vary between captures |
| Responsive behavior | All twelve section heights across four widths | All measured section heights match within 0.3px | MATCHED | Listed missing/frozen/dynamic items still apply |

## D. Template 4 — Anthony & Tina

[Local demo](http://127.0.0.1:8000/template-demos/4) · [Existing fourth-template comparison viewer](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/anthony-tina-20260903/comparison.html>)

**DEMO ONLY.** It remains in the local gallery. No production Template row was added and customer setup still has its existing selection behavior. RoyalPlum.vue, the renderer registry and registration/domain files were unchanged in this pass.

A fresh availability/opening check confirmed loaded images/video, no horizontal overflow and no JavaScript page errors. Its full comparison matrix is the earlier fourth-template evidence linked above; it is not counted among the 12 fresh comparisons in this report.

| SECTION | REFERENCE | LOCAL | STATUS | BLOCKER |
| --- | --- | --- | --- | --- |
| Opening/media | Anthony & Tina opening | Existing demo media loads in the fresh check | FONT REQUIRED | Opening script uses the existing fallback; full Template 4 section matrix was not rerun |
| Script typography | Sweet Fancy Script | Existing fallback from the earlier pass | FONT REQUIRED | Licensed Sweet Fancy Script file |
| Monogram/control artwork | Original monogram/copy/scroll glyphs | Existing temporary artwork/glyphs | ASSET REQUIRED | Exact authorized assets |
| Family/RSVP integration | Reference presentation | Existing demo-only flow | MINOR DIFFERENCE | Owner approval must precede customer-template registration and integration polish |

## E. Remaining blockers and deliberate differences

1. **Template 1:** exact monogram; ceremony, celebration and copy icons.
2. **Template 2:** Gotham Office Regular; Chronicle Semibold; original hand/scroll/music icons; authorized soundtrack file/permission. No music was invented or downloaded.
3. **Template 3:** exact monogram.
4. **Template 4:** Sweet Fancy Script and exact monogram/copy/scroll artwork, carried forward from its earlier report.
5. **Frozen presentation:** family treatment and RSVP-related differences shown in A–D await the owner's base-design decision. Guest lists, meals, dietary notes, RSVP states and backend/domain integration were not redesigned.
6. **Demo/runtime differences:** payment details remain fictitious. Clocks, decorative particle positions and live map content change over time. Existing reference media remains local-review material; production use has not been approved.

No new asset or font files were added during this pass. The existing 38 media files remain in the ignored local QA asset directory.

## F. Fresh screenshot comparison viewer

[Open comparison.html](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison.html>)

The viewer contains 12 reference/local pairs, filters for template and width, opening/opened/full-page views and section selectors. Each image links to its original-resolution PNG. Template 3's offscreen text can be hidden in full-page captures because its reveal behavior resets; use the section views to inspect revealed content.

The viewer itself was opened and tested: 12 paired PNGs generated, filters/section switching worked, no broken viewer images and no JavaScript errors. [Viewer verification](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/viewer-validation.json>).

## G. Twelve fresh paired screenshot paths

| Template | Width | Paired PNG |
| --- | --- | --- |
| 1 | 375px | [comparison-1-375.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-1-375.png>) |
| 1 | 430px | [comparison-1-430.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-1-430.png>) |
| 1 | 768px | [comparison-1-768.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-1-768.png>) |
| 1 | 1366px | [comparison-1-1366.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-1-1366.png>) |
| 2 | 375px | [comparison-2-375.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-2-375.png>) |
| 2 | 430px | [comparison-2-430.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-2-430.png>) |
| 2 | 768px | [comparison-2-768.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-2-768.png>) |
| 2 | 1366px | [comparison-2-1366.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-2-1366.png>) |
| 3 | 375px | [comparison-3-375.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-3-375.png>) |
| 3 | 430px | [comparison-3-430.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-3-430.png>) |
| 3 | 768px | [comparison-3-768.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-3-768.png>) |
| 3 | 1366px | [comparison-3-1366.png](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-3-1366.png>) |

All raw reference/local PNGs, section images and computed-style measurements are in the same dated folder. [Capture manifest](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/comparison-manifest.json>).

## H. PHP tests and assertions

Fresh final-source command: `php artisan test --compact` using the installed PHP runtime.

**164 tests passed; 1,765 assertions. Duration: 45.52 seconds. Exit code 0.**

[PHP test log](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/php-tests.log>).

## I. TypeScript

Fresh `npx vue-tsc --noEmit`: **passed, exit code 0**, no diagnostics.

## J. Frontend build

Fresh `npm run build`: **passed, exit code 0**. Vite transformed **924 modules**, build phase **13.27 seconds**. The command also runs the configured TypeScript check.

The existing public font URLs produced runtime-resolution notices. Used local fonts loaded in the browser captures; these notices did not fail the build. [Build log](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/build.log>).

Additional browser verification passed: all four gallery links; three demo clocks in Beirut and New York timezones; Template 1 opening scroll; Template 2 curtain playback and pointer/keyboard scratch; missing-music notice; Template 3 muted looping video, reveal threshold/reversal, sampled rose positions and seal opening; reduced-motion decoration hiding; Template 4 media availability. [Interaction results](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/interaction-results.json>).

The initial interaction harness waited for a continuously animated scroll button to become stable. It was adjusted to click the visible button coordinates, and the check passed; no application workaround was added.

## K. git diff --check

Fresh `git diff --check`: **passed, exit code 0**, no whitespace errors. Git printed CRLF-to-LF normalization notices for working-tree files. [Whitespace-check log](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-approval-20260903/diff-check.log>).

## L. git status --short

This is the complete existing working tree, including earlier work. It is not a list of files edited only during this pass. Nothing was staged, reset, committed or pushed.

```text
 M .gitignore
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
?? app/Http/Controllers/TemplateDemoController.php
?? app/Models/EventGiftMethod.php
?? app/Models/EventInvitationContent.php
?? config/template_demo_assets.php
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
?? resources/js/Components/InvitationTemplates/RoyalPlum.vue
?? resources/js/Components/InvitationTemplates/ScratchDateTile.vue
?? resources/js/Components/InvitationTemplates/referenceExperience.ts
?? resources/js/Components/InvitationTemplates/referenceFonts.css
?? resources/js/Pages/Events/Builder.vue
?? resources/js/Pages/TemplateDemo.vue
?? resources/js/demo/
?? tests/Feature/EventBuilderTest.php
?? tests/Feature/TemplateDemoTest.php
```

## M. git diff --stat

This reports tracked working-tree changes against HEAD, including the previous Builder/reconstruction work. Git's default diffstat excludes the untracked files listed above.

```text
 .gitignore                                         |   2 +
 app/Http/Controllers/DashboardController.php       |   6 +-
 app/Http/Controllers/EventSetupController.php      |  21 ++-
 app/Models/Event.php                               |   2 +
 app/Support/InvitationPresenter.php                |  28 +++-
 .../InvitationPublicationSnapshotBuilder.php       |  20 ++-
 .../js/Components/InvitationPreviewRenderer.vue    |   8 +-
 .../InvitationTemplates/EditorialLuxury.vue        | 168 ++++++-------------
 .../InvitationTemplates/ModernCinematic.vue        | 181 ++++++---------------
 .../InvitationTemplates/PublicRomanticFloral.vue   | 152 +++++++----------
 resources/js/Components/PublicRsvpExperience.vue   |  72 ++++----
 resources/js/Layouts/AuthenticatedLayout.vue       |   2 +-
 resources/js/Pages/Events/InvitationPreview.vue    |   2 +-
 resources/js/Pages/Events/Setup.vue                |  65 +++++---
 resources/js/Pages/Events/Show.vue                 |   4 +-
 resources/js/Pages/PublicInvitation.vue            |  32 ++--
 routes/web.php                                     |   8 +
 tests/Feature/EventPublicationTest.php             |   4 +-
 18 files changed, 341 insertions(+), 436 deletions(-)
```

## Owner review and next step

Open the gallery, view each demo, and use the fresh comparison viewer to inspect the same section at each width. Template 2 opens by tapping the curtain and reveals its date by scratching; Template 3 opens its preserved local RSVP form through the seal. Local demo forms do not send real responses.

Work stops here for owner visual review. Family/guest/RSVP/meal/dietary visual integration, production approval and Template 4 customer registration have not begun in this pass.
