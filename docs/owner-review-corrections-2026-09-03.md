# Owner visual review corrections — 3 September 2026

## Corrections made per template

| Template | Corrections and measured decisions |
| --- | --- |
| **1 — Jack & Stephanie** | Kept the composition. Corrected the small-screen RSVP heading from 54.6px to the reference's 54.4px. Gave the existing local demo form the measured cream/blush panel, border, shadow, field typography and radii; made its existing name label visible and styled the existing response choices. The first seven sections' geometry remains unchanged. |
| **2 — Justin & Maya** | Fixed the joined time/address strings using explicit spaces: `05:00 PM Beirut, Lebanon` and `06:00 PM Bsalim, Lebanon`. Restored the RSVP deadline's responsive size, burgundy/ivory typography, panel padding/radius, label spacing, person-count position and button style. The existing control structure and response handling were retained. Confirmed the desktop frame is **520 × 924.44px**, with its 9:16 ratio intact; retained the measured frame and ending-card proportions. The final desktop page height is **5920px**, versus **5921px** for the reference. |
| **3 — Joseph & Sarah** | Measured each desktop section instead of enlarging the page globally. The reference and local both use **480px** content, **41.6px** standard section headings, **352px** timeline width and **416px** map frames. Body sizes, section padding, gift scale and ending geometry already agree. Corrected the RSVP heading weight, the **24px gold script** opening prompt, chevron typography and the seal's measured **0px 8px 16px** drop shadow. |
| **4 — Anthony & Tina** | Restored the reference's muted-plum text hierarchy for card labels, gift copy and ending copy. Corrected RSVP label tracking, the gold attendee number and the ivory selected-option text. Card backgrounds, borders, asset dimensions and decorative placements already matched and were retained. The script fallback remains unchanged pending an authorized font. Template 4 is still **demo only**. |

This pass changed only five Vue files: the four reference renderers and ReferenceRsvpDemo.vue. It added no database or architecture changes. RSVP domain logic, families, meals, Builder behavior, template registration, and the production/public RSVP component were preserved. Local demo form controls still function without posting or saving responses. Reference media and DIGITAL comparison branding remain in their existing local-review arrangement; production branding replacement remains for the production-integration pass.

## Remaining reference differences

| Template | Remaining visible differences |
| --- | --- |
| 1 | Missing monogram and original icons; existing family styling; the retained local demo form structure, person-count placement and demo notice differ from the reference. The last section's height still differs at some widths. |
| 2 | Commercial-font metrics, original control glyphs and soundtrack are unavailable. The local demo retains its existing radio controls, wording and demo notice. Final page-height differences are approximately **−4px, −4px, +3px and −1px**, at 375, 430, 768 and 1366px respectively. |
| 3 | Monogram is missing. Existing family/form styling remains for the later integration pass. Live map content, particle positions and ending-name reveal state can differ between runs. Desktop content was not enlarged because its measured dimensions already match the live reference. |
| 4 | The missing script font changes heading widths and line wrapping. The desktop ceremony card is one **59.2px** text line taller than the reference, shifting subsequent sections; smaller widths also have font-related wrapping differences. Monogram/control artwork remains substituted. |

No family greeting, PartyMember list, attendee/guest-limit behavior, per-attendee meals, dietary notes or guest-message integration was rebuilt. The local demo skins are presentation corrections; complete production RSVP visual integration is still deferred.

## Remaining asset/font blockers

- **Template 1:** exact couple monogram; ceremony, celebration and copy icons.
- **Template 2:** licensed **Gotham Office Regular** and **Chronicle Semibold**; original hand, scroll and music icons; authorized soundtrack file/permission.
- **Template 3:** exact couple monogram.
- **Template 4:** licensed **Sweet Fancy Script**; exact monogram, copy and scroll artwork.

No new authorized files for these missing assets/fonts were found in the project. No third-party source or new reference media was downloaded during this pass. Existing authorized/open-font files and the isolated demo media were reused.

## Fresh comparison paths

[Open the new comparison viewer](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison.html>) · [Open all four working demos](http://127.0.0.1:8000/template-demos)

Fresh paired captures cover all four templates at **375, 430, 768 and 1366px**. The viewer offers each section, opening states and full pages; clicking an image opens the original resolution. Use section views for scroll-revealed content, since full-page captures can include hidden offscreen text. Swan/arch opening frames were synchronized at 2 seconds for the comparison stills.

| Template | 375px | 430px | 768px | 1366px |
| --- | --- | --- | --- | --- |
| 1 | [375px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-1-375.png>) | [430px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-1-430.png>) | [768px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-1-768.png>) | [1366px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-1-1366.png>) |
| 2 | [375px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-2-375.png>) | [430px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-2-430.png>) | [768px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-2-768.png>) | [1366px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-2-1366.png>) |
| 3 | [375px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-3-375.png>) | [430px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-3-430.png>) | [768px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-3-768.png>) | [1366px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-3-1366.png>) |
| 4 | [375px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-4-375.png>) | [430px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-4-430.png>) | [768px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-4-768.png>) | [1366px](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-4-1366.png>) |

[Capture manifest](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/comparison-manifest.json>) · [Desktop measurements before correction](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/desktop-before-metrics.json>)

## Test/build results

| Check | Result |
| --- | --- |
| `php artisan test --compact` | **PASS — 164 tests, 1,765 assertions**, 22.37s. |
| `npx vue-tsc --noEmit` | **PASS**, exit 0, no diagnostics. |
| `npm run build` | **PASS — 924 modules**, Vite build 11.49s, exit 0. |
| `git diff --check` | **PASS**, exit 0. The existing CRLF-normalization notice for PublicRsvpExperience.vue remains; that file was unchanged during this pass. |

[PHP test log](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/php-tests.log>) · [TypeScript log](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/typescript.log>) · [Build log](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/build.log>) · [Diff check log](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/diff-check.log>)

All 16 final local capture runs returned HTTP 200 with no horizontal overflow, broken images, failed HTTP responses or JavaScript page errors. Targeted browser checks verified the time/address separators, desktop frame dimensions, the existing local demo submissions for Templates 1/2/4 and the Template 3 seal opening. **No POST requests were sent.** [Interaction results](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/interaction-results.json>).

The build's existing public-font runtime-resolution notices remain; the used fonts loaded during browser capture. The unavailable commercial fonts are reported separately above. Browser comparisons used isolated Edge contexts; physical-device/Safari testing was not performed.

## Git status

Branch: **develop**. No staging, reset, commit or push. The output below includes the preserved earlier working-tree changes, not only this visual pass. The [baseline audit](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/owner-review-20260903/freeze-audit.json>) confirms which five application files changed during this pass.

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

Stopped for owner review.
