# FormalEvites — final visual evidence and real-data integration

September 7, 2026. Prepared for owner review from the existing working tree. **No commit, push, reset, new template, new migration, or RSVP backend rewrite.** Existing uncommitted work was preserved.

The six designs now use real Event/family/member/meal data through the same template components, with shared RSVP presentation inside each design. This is **not pixel-perfect sign-off, production-media approval, or a claim of complete localization**. The remaining limits below need owner review.

## A. Six customer-selectable templates

| Design | Demo | Local QA Event |
| --- | --- | --- |
| Romantic Floral | /template-demos/1 | 20 |
| Editorial Luxury | /template-demos/2 | 21 |
| Modern Cinematic | /template-demos/3 | 22 |
| Dolce Vita | /template-demos/5 | 23 |
| Blossom & Oud | /template-demos/6 | 24 |
| The Sacred Garden | /template-demos/7 | 25 |

All six catalog records are currently active and customer-selectable. Exactly six were visible in the QA customer's wedding setup.

## B. Anthony & Tina

/template-demos/4 and its renderer remain available locally. There is no Royal Plum/Anthony customer catalog selection. The demo was not removed or integrated into the customer flow.

## C. Fresh visual comparison results

24 final local opening captures cover six templates at verified CSS widths 375, 430, 768 and 1366, with height 900. They are paired with 24 live reference captures. All final local widths had no horizontal document overflow. See comparison.html and capture-manifest.json.

| Template | Result and remaining differences |
| --- | --- |
| Romantic Floral | MATCHED color/media identity; MINOR DIFFERENCE in typography and spacing; ARTWORK BLOCKER for exact monogram and ceremony/celebration glyphs; ANIMATION DIFFERENCE in reveals/decorations. |
| Editorial Luxury | MATCHED burgundy/ivory identity; FONT BLOCKER for Gotham Office Regular and Chronicle Semibold; ARTWORK BLOCKER for exact control glyphs; ANIMATION DIFFERENCE in curtain/reveal timing; MUSIC BLOCKER. |
| Modern Cinematic | MATCHED garden/gold identity; MINOR DIFFERENCE in type/section proportions; ARTWORK BLOCKER for monogram; ANIMATION DIFFERENCE in falling decorations and timeline rose. |
| Dolce Vita | MATCHED main media/color identity; FONT BLOCKER for exact script; ANIMATION DIFFERENCE in the recreated envelope/letter and scratch reveal; MINOR DIFFERENCE in font-driven geometry. |
| Blossom & Oud | MATCHED main local artwork/color identity; FONT BLOCKER for exact script; ANIMATION DIFFERENCE in opening/reveals; MINOR DIFFERENCE in wrapping and form proportions. |
| The Sacred Garden | MATCHED main local artwork/color identity; FONT BLOCKER for exact script; ANIMATION DIFFERENCE in opening/floating artwork; MINOR DIFFERENCE in countdown/reference-date behavior. |

Measured Phase A corrections: Sacred Garden envelope image/video changed from cover to contain; Dolce Vita opening label uses the observed 478px position and #52839c. No domain changes occurred in Phase A. The public renderer retains each reference identity rather than dashboard colors.

Full-page invitation captures are **diagnostics only**. The browser alters viewport-relative geometry during full-page capture. Fixed-viewport images are the primary evidence. Screenshot bitmaps exclude scrollbars and can be scaled by the capture backend; the manifest distinguishes CSS viewport sizes from image dimensions. Later capture problems were corrected by verifying the actual tab width and visible greeting bounds before saving.

## D. Remaining assets and fonts

Commercial font files were not invented or downloaded. Gotham Office Regular, Chronicle Semibold, and the exact newer-template script remain unavailable. Sweet Fancy Script remains an acknowledged blocker for demo-only Anthony & Tina.

Romantic Floral still needs its exact couple monogram and ceremony/celebration icons. Modern Cinematic still needs its exact monogram. Editorial control glyphs and the permitted soundtrack remain different/unavailable. CSS motion is a reconstruction, not the original proprietary implementation.

Configured but absent optional media roles: Dolce Vita envelope/envelopeVideo/heroPoster; Blossom envelopeVideo/heroPoster/endingPoster; Sacred Garden heroPoster. Dolce's layered envelope and Blossom's CSS envelope supply the opening without those optional video files; actual hero videos work. These optional absent roles are distinct from missing visible monogram/icon artwork.

All reference imagery/video remains **local QA media**. Some artwork contains reference initials, portraits, or an embedded video watermark. It is permitted for this local comparison task, not approved for a real production invitation. Production rendering receives no local media URLs and therefore retains missing-asset placeholders until approved assets are supplied. No QA filesystem paths were saved in the database or snapshots.

## E. Customer selection

Step 1 showed precisely the six reference designs for the QA wedding. Active status, customer-selection flags and supported Event types remain enforced by the existing controllers. Elegant Classic is inactive/non-selectable; Modern Minimal is active but non-selectable. Existing assignment exceptions remain intact to avoid breaking an already assigned Event. Anthony is absent.

## F. Real Event data

The presenter supplies current protected-preview data or immutable publication data for public invitations. The three newer layouts now adapt Event wording, hosts, dates, active activities, venue/address, dress code, story, gift methods and ending without importing demo fixtures. Dates include a stable ISO day and a timezone-aware countdown instant. Builder date changes update the same presentation locally.

Local media is resolved from a hard-coded allowlist by template key only in the local environment. Demo source URLs were removed from client fixture metadata; reference links remain in developer documentation. No architecture or database schema change was required in this pass.

## G. Family personalization

Each template places the actual InvitationParty name in its native introduction: Romantic's paper panel, Editorial's invitation identity, Modern's garden greeting, Dolce's letter, Blossom's ornate frame, and Sacred's paper introduction. The generic public family banner was removed.

## H. PartyMembers

Builder and protected previews receive sanitized existing members and current RSVP state. No guest names are copied into template settings. Related members, RSVP person responses and meals are eagerly loaded. Public tokens retain the existing opaque member/meal identifiers and authorization rules.

## I. Native shared RSVP

One PublicRsvpExperience continues to handle responses. Skins use cream/blush, burgundy/ivory, cream/gold, blue/ivory, Moroccan gold/peach, and garden/burgundy respectively. The newer templates now place it in their original RSVP section before the ending; Dolce and Sacred use their existing opening controls. Builder submission is explicitly preview-only and cannot post a response.

All six actual public flows were exercised: accept, one attending original member, one non-attending member, an additional guest, decline, edit back to acceptance. A second verified 375px pass submitted updates successfully for all six.

## J. Meals and guest details

For every QA Event, Nour selected Garden menu with “No nuts”; additional guest Rima selected Vegetarian menu with “No dairy”; Karim remained non-attending. Guest messages persisted, including Arabic for Blossom. The database audit confirms these results. Existing meal validation, additional guest allowance and Event capacity rules were not changed.

## K. Optional sections

Story, Gift Registry and Ending were individually hidden, restored and edited without saving in all six Builders. The correct preview section disappeared and returned; restoring original values left the form clean. Automated coverage verifies saved disabled story data remains retained. Meeting Point visibility continues to use active EventActivity records through the existing schedule controls. No new toggle or domain was introduced.

Real content can grow inside the newer layouts. A measured Blossom introduction/venue overlap was corrected by giving real-Event flow styles sufficient CSS priority. Demo dimensions remain unchanged.

## L. Builder preview

All six were checked with both family choices, unsaved host/date/venue/story/gift/ending changes, and switching to another template and back. Real values updated in the same renderer without a save. Full-preview links now carry the selected party_id. Each final Builder mounted exactly one invitation renderer and had no horizontal document overflow at 1366px.

Fixed opening layers are contained inside the phone frame. Scroll reveals observe nested preview scrolling, and newly enabled content receives reveal registration. The phone is a constrained component preview, not a separate browser document; viewport-unit and window-breakpoint details can still differ from a physical mobile browser. Verified public 375px screenshots are the mobile approval evidence.

## M. Public renderer

All six public paths use InvitationPreviewRenderer directly, with one trusted component per design. The newer three no longer pass through the unrelated generic public opening. Public Event content still comes from EventPublication, and family context comes from the secure token. Protected previews use current Event content and preview-only RSVP.

## N. Snapshots and capacity

Automated six-template coverage verifies preview reads create no version, changed Save creates one version, identical Save creates none, and the first snapshot hash stays unchanged. Real QA Events have two versions each, except Blossom with three because its Arabic-language change was separately saved. Preview-only edits and RSVP updates did not add versions. All have purchased capacity 10 and allocated family capacity 6. No finance logic or existing Event data was rewritten.

## O. Admin

The eight existing catalog entries remain. The catalog shows status, assignment count, local demo link and supported Event types. Actual assignment counts during review: Romantic 6; Editorial 3; Modern 3; Dolce 1; Blossom 1; Sacred 1; Elegant 1; Minimal 0. Admin mounted no invitation renderer or video. Existing Editorial “gala” metadata remains historical; this pass did not expand the approved Event type list.

## P. Branding

No Digital Invitation or Webgency footer/link was found in the real public page text. FormalEvites footer branding remains in the existing original designs. Newer designs preserve their unbranded endings. Local reference media is still separated from production and may contain embedded reference initials/watermarks as noted above.

## Q. Arabic / RTL

Blossom's real QA Event uses Arabic host names, Arabic UI labels and an RTL RSVP form, with mixed English Event/member/meal values retained as entered. Family greeting, buttons, attendance choices, additional guests and saved Arabic message were verified at 375px. Countdown direction is isolated, and Arabic headings avoid Latin script-font assumptions.

This is not complete interface localization: some accessibility labels, server validation/confirmation text and timestamp formatting remain English; user-authored English content is not automatically translated. These are explicit remaining language-polish limits, not a claim that right alignment alone is localization.

## R. Performance and network

Renderers now load asynchronously on demand; Step 1 uses lightweight cards and one selected preview. Reference images use lazy loading except background images, with asynchronous decoding. Customer Dashboard had zero heavy invitation renderers, zero videos and zero QA images. Its asset inventory showed no new asset URLs over a five-second idle sample. Admin also mounted zero renderers/videos; sampled app console logs had no warnings/errors.

Measured Builder controller payloads are listed below. Each used 16 queries with two families, with family/member/RSVP relationships eager-loaded. This is a bounded local controller measurement, not an end-to-end production latency benchmark. The asset inventory does not expose packet-level transfer counts, so duplicate network transfers and ongoing video traffic are not certified absent.

| Template demo | Builder JSON bytes | Queries | Snapshot versions |
| --- | ---: | ---: | ---: |
| 1 | 25666 | 16 | 2 |
| 2 | 25524 | 16 | 2 |
| 3 | 25694 | 16 | 2 |
| 5 | 26645 | 16 | 2 |
| 6 | 26480 | 16 | 3 |
| 7 | 26860 | 16 | 2 |

Total allowlisted QA assets per design (file inventory, not initial network transfer):

- Demo 5: 10.75 MiB.
- Demo 6: 11.75 MiB.
- Demo 7: 23.35 MiB.
- Demo 1: 1.32 MiB.
- Demo 2: 2.44 MiB.
- Demo 3: 5.24 MiB.

These video-heavy reference assets still need approved production versions and a separate production payload review.

## S. Evidence and local records

Folder: storage/app/qa/visual-integration-20260907/

- comparison.html — paired current/reference viewer plus real-data screenshots.
- capture-manifest.json — 24 pairs and required integration image paths.
- local-viewport-{1,2,3,5,6,7}-{375,430,768,1366}.png — 24 final demo captures.
- reference-viewport-{number}-{width}.png — 24 reference captures.
- customer-step-1.png — six customer choices.
- builder-{number}.png — six final authenticated previews.
- public-family-{number}.png — six 375px family greetings with visible bounds checked.
- rsvp-meals-{number}.png and rsvp-confirmation-{number}.png — six forms and six saved responses.
- admin-catalog.png — Admin metadata review.
- integration-contact-sheet.png — overview.
- audit.json — persisted RSVP, snapshots, capacity, catalog, media and payload results.
- events.json — local QA Event/party IDs and public links. These are local test tokens; do not publish this QA folder.
- phase-a.md — detailed visual status matrix.

The six new Events belong to the separate “Visual Integration QA 20260907” customer. They deliberately remain for owner review. Existing customer Events were not replaced. The screenshot helper was local to 127.0.0.1 and did not modify Laravel routing.

## T. PHP verification

Final run: **167 tests passed, 2,401 assertions**, 15.84 seconds. Existing tests remain. Added coverage exercises all six real-data paths, preview isolation, RSVP/meal/update behavior, optional retention, snapshot semantics, and production exclusion of local reference media. Existing capacity and cross-customer authorization tests passed in the full suite.

## U. TypeScript

npx vue-tsc --noEmit — passed, exit 0.

## V. Build

npm run build — passed, 11.08 seconds. Vite still reports runtime-resolved public font URLs; the referenced local font files exist. Licensed substitute fonts do not remove the commercial-font blockers above.

## W. Whitespace verification

git diff --check — passed. Git reports existing CRLF-to-LF normalization warnings; no whitespace errors. This command covers tracked diffs; new files are additionally covered by the PHP/TypeScript/build checks where applicable.

## X. Git status

Branch: develop. No commit, push, staging, reset or history change. This status includes work from earlier passes as well as this task.

```text
 M .gitignore
 M app/Http/Controllers/AdminTemplateController.php
 M app/Http/Controllers/DashboardController.php
 M app/Http/Controllers/EventController.php
 M app/Http/Controllers/EventInvitationController.php
 M app/Http/Controllers/EventSetupController.php
 M app/Models/Event.php
 M app/Models/Template.php
 M app/Support/AdminDashboardAnalytics.php
 M app/Support/InvitationPresenter.php
 M app/Support/InvitationPublicationSnapshotBuilder.php
 M database/seeders/DatabaseSeeder.php
 M resources/js/Components/InvitationPreviewRenderer.vue
 M resources/js/Components/InvitationTemplates/EditorialLuxury.vue
 M resources/js/Components/InvitationTemplates/ModernCinematic.vue
 M resources/js/Components/InvitationTemplates/PublicRomanticFloral.vue
 M resources/js/Components/PublicRsvpExperience.vue
 M resources/js/Layouts/AuthenticatedLayout.vue
 M resources/js/Pages/Admin/Templates.vue
 M resources/js/Pages/CustomerDashboard.vue
 M resources/js/Pages/Dashboard.vue
 M resources/js/Pages/Events/InvitationPreview.vue
 M resources/js/Pages/Events/Setup.vue
 M resources/js/Pages/Events/Show.vue
 M resources/js/Pages/PublicInvitation.vue
 M routes/web.php
 M tests/Feature/EventPublicationTest.php
 M tests/Feature/InvitationTemplateTest.php
?? app/Http/Controllers/EventBuilderController.php
?? app/Http/Controllers/TemplateDemoController.php
?? app/Models/EventGiftMethod.php
?? app/Models/EventInvitationContent.php
?? app/Support/InvitationPreviewContext.php
?? app/Support/LocalInvitationMedia.php
?? config/template_demo_assets.php
?? database/migrations/2026_09_02_000001_create_event_invitation_contents_table.php
?? database/migrations/2026_09_02_000002_create_event_gift_methods_table.php
?? database/seeders/WebgencyTemplateSeeder.php
?? docs/
?? public/fonts/
?? resources/js/Components/InvitationTemplates/BlossomOud.vue
?? resources/js/Components/InvitationTemplates/DateReveal.vue
?? resources/js/Components/InvitationTemplates/DemoEnvelope.vue
?? resources/js/Components/InvitationTemplates/DolceVita.vue
?? resources/js/Components/InvitationTemplates/FallingAssetSlots.vue
?? resources/js/Components/InvitationTemplates/InvitationContentSections.vue
?? resources/js/Components/InvitationTemplates/NativeEventSections.vue
?? resources/js/Components/InvitationTemplates/ReferenceAsset.vue
?? resources/js/Components/InvitationTemplates/ReferenceGifts.vue
?? resources/js/Components/InvitationTemplates/ReferenceRsvpDemo.vue
?? resources/js/Components/InvitationTemplates/RoyalPlum.vue
?? resources/js/Components/InvitationTemplates/SacredGarden.vue
?? resources/js/Components/InvitationTemplates/ScratchDateTile.vue
?? resources/js/Components/InvitationTemplates/WebgencyRsvpDemo.vue
?? resources/js/Components/InvitationTemplates/invitationText.ts
?? resources/js/Components/InvitationTemplates/referenceExperience.ts
?? resources/js/Components/InvitationTemplates/referenceFonts.css
?? resources/js/Components/InvitationTemplates/webgencyDemos.css
?? resources/js/Components/InvitationTemplates/webgencyEventContent.ts
?? resources/js/Pages/Events/Builder.vue
?? resources/js/Pages/TemplateDemo.vue
?? resources/js/demo/
?? tests/Feature/EventBuilderTest.php
?? tests/Feature/TemplateDemoTest.php
```

## Y. Git diff statistics

Tracked working-tree diff only; untracked files listed above are not counted by git diff --stat.

```text
 .gitignore                                         |   2 +
 app/Http/Controllers/AdminTemplateController.php   |   2 +-
 app/Http/Controllers/DashboardController.php       |  13 +-
 app/Http/Controllers/EventController.php           |   4 +-
 app/Http/Controllers/EventInvitationController.php |  11 +-
 app/Http/Controllers/EventSetupController.php      |  23 ++-
 app/Models/Event.php                               |   2 +
 app/Models/Template.php                            |   9 +-
 app/Support/AdminDashboardAnalytics.php            |   3 +-
 app/Support/InvitationPresenter.php                |  39 ++++-
 .../InvitationPublicationSnapshotBuilder.php       |  20 ++-
 database/seeders/DatabaseSeeder.php                |   1 +
 .../js/Components/InvitationPreviewRenderer.vue    |  31 +++-
 .../InvitationTemplates/EditorialLuxury.vue        | 176 +++++++-------------
 .../InvitationTemplates/ModernCinematic.vue        | 183 ++++++---------------
 .../InvitationTemplates/PublicRomanticFloral.vue   | 156 +++++++-----------
 resources/js/Components/PublicRsvpExperience.vue   |  91 +++++-----
 resources/js/Layouts/AuthenticatedLayout.vue       |   2 +-
 resources/js/Pages/Admin/Templates.vue             |   2 +-
 resources/js/Pages/CustomerDashboard.vue           |   8 +-
 resources/js/Pages/Dashboard.vue                   |   4 +-
 resources/js/Pages/Events/InvitationPreview.vue    |   2 +-
 resources/js/Pages/Events/Setup.vue                |  68 +++++---
 resources/js/Pages/Events/Show.vue                 |   6 +-
 resources/js/Pages/PublicInvitation.vue            |  31 ++--
 routes/web.php                                     |   8 +
 tests/Feature/EventPublicationTest.php             |   4 +-
 tests/Feature/InvitationTemplateTest.php           |  30 ++++
 28 files changed, 466 insertions(+), 465 deletions(-)
```

Important modules changed in this pass: InvitationPresenter, LocalInvitationMedia, InvitationPreviewContext, EventBuilderController, EventInvitationController, InvitationPreviewRenderer, PublicInvitation, PublicRsvpExperience, the six existing template components, the three-template Event adapter, reference experience helpers, Builder preview links/containment, Admin type display, and EventBuilderTest. No new database migration was created or executed.

Owner review should use the comparison viewer and the six QA Events. Remaining visual/font/media/localization limits are recorded above. Work is stopped for owner review; nothing has been committed or pushed.
