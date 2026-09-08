# FormalEvites — consolidated project status
Date: 3 September 2026

## Current position

FormalEvites has its existing application foundation, a three-step invitation creation workflow, a Builder with eight sections, and four reference invitation demos available locally. We are currently finishing **reference reconstruction and visual review**. Full visual approval and production readiness have not been established.

The intended sequence remains: reconstruct the reference experiences, approve their appearance and behavior, then refine their connection to FormalEvites families, guests and RSVP. Adding Anthony & Tina expanded the local demo gallery to four designs; it did not add a fourth customer-selectable database template.

This report consolidates the earlier Builder/integration QA, the three-template reconstruction and asset passes, and the Anthony & Tina work. Historical screenshots and tests are labelled below so earlier evidence is not mistaken for a fresh check of every current screen. This reporting pass changed documentation only.

## 1. Existing application foundation

The existing stack remains Laravel/PHP, PostgreSQL, Vue 3, TypeScript, Inertia and Tailwind. The project still uses one Laravel application and its existing Vue frontend.

The implemented foundation includes:

- Admin and Customer access, Customer-owned Events, and Event memberships.
- Event packages and exact guest-capacity enforcement.
- Manual payments, payment transactions and coupons.
- InvitationParty for a person/family recipient and PartyMember for the original individual guest names.
- Secure invitation-party links, accepted/declined RSVP, additional guest responses and per-attendee meals.
- Dietary notes, guest messages, RSVP deadlines and closed-response behavior.
- Event activities and venues.
- Versioned EventPublication snapshots: the published invitation has its own saved version.

These systems were preserved during the recent visual-only passes. Their presence and existing regression tests do not constitute a new comprehensive production or security audit.

## 2. Invitation creation and Builder

| Area | Implemented behavior | Evidence and remaining limit |
| --- | --- | --- |
| Step 1 — Choose design | Existing wedding template cards, preview, selection and progression | Earlier authenticated browser QA passed. The customer gallery previously offered three designs; demo #4 has not been added to it. |
| Step 2 — Information | Names, date, start/end time, timezone and venue; validation; overnight end-time handling | Earlier browser and automated QA passed. Updates the same Event rather than creating another Event. |
| Final Builder | Ready-draft/live editing, current design, Browse, explicit Save Changes, saved/unsaved state and Customer completion | Existing workflow and tests remain. Schedule editing uses the existing management page. |
| Phone preview | Actual selected renderer, generic/family preview, unsaved preview, full-invitation/current-section modes | Earlier desktop/mobile QA passed. It should be rechecked with the final approved renderer layouts. |
| Completion | Preview & Complete; Customer completion without an Admin review step | Existing publication workflow retained. |
| Presentation settings | Color/font settings exist in the Builder | Reference renderers currently preserve their fixed reference identity. These controls are not a claim that arbitrary customization has been completed. |

The Builder's eight sections are:

1. Personal Details
2. Meeting Point
3. Ceremony & Venue
4. Our Story
5. Gift Registry
6. Ending Page
7. Languages
8. Preview & Complete

Earlier QA fixed the hidden desktop phone preview, setup validation/progression, preview defaults, narrow RSVP controls and optional-section visibility. Later template reconstruction changed the public visual layouts; therefore the earlier Builder screenshots demonstrate the workflow at that time, not final visual sign-off.

## 3. Four local invitation demos

[Open the local gallery](http://127.0.0.1:8000/template-demos).

| Demo | Implemented reference experience | Local media | Remaining visual work |
| --- | --- | ---: | --- |
| [1 — Jack & Stephanie](http://127.0.0.1:8000/template-demos/1) | Coastal opening, paper backgrounds, invitation wording, countdown/calendar, meeting points, celebration, dress, gifts and RSVP/ending | 9 files | Original inline monogram and ceremony/celebration/copy artwork; frozen family/RSVP differences; final comparison after media integration |
| [2 — Justin & Maya](http://127.0.0.1:8000/template-demos/2) | Curtain opening, scratchable date tiles, countdown, framed meeting details, venues, gifts and ending | 6 files | Gotham Office Regular and Chronicle Semibold fonts; permitted reference soundtrack; original hand/scroll/music artwork; final media/interaction comparison |
| [3 — Joseph & Sarah](http://127.0.0.1:8000/template-demos/3) | Swan opening, cream/gold garden composition, countdown, schedule, maps, dress, gifts and seal-operated RSVP | 9 files | Original inline monogram; approximate decorative motion/rose-follow behavior; final comparison after media integration |
| [4 — Anthony & Tina](http://127.0.0.1:8000/template-demos/4) | Arch opening video, countdown, opening curtains, invitation, meeting points, celebration, dress, story, gifts, RSVP and ending | 14 files | Sweet Fancy Script commercial font; temporary monogram/copy/scroll glyphs; final user review |

All four URLs returned **HTTP 200 during this reporting pass**. This is a fresh availability check; the browser interaction results come from the completed development checks described below.

Each renderer keeps its reference palette and composition. Public invitations were not recolored to match the authenticated FormalEvites shell.

The reference image/video slots now contain the locally stored demo files. Earlier reports saying the curtain, swan video, paper backgrounds or opening images are all still missing are historical and no longer describe the installed asset state.

None of the four designs has been declared pixel-identical. Missing font metrics affect character widths, wrapping and section height. Anthony & Tina, for example, wraps its ending at 375px and adds a line to a celebration card at wider sizes with the current fallback.

## 4. Reference media, fonts and storage

There are **38 reference image/video files**, totaling **19,241,591 bytes** (about 19.24 MB across all four demos). This is an installed-file total, not a measured page-load transfer size.

Files are isolated under the ignored local QA directory and are served by the existing local-only allowlisted route. The manifest records source URLs, roles, dimensions, byte sizes, hashes and demo-only status. The last integrity check confirmed all 38 files matched the manifest and were ignored by Git.

- Template 2 uses the exact local curtain video.
- Template 3 uses the exact local swan video.
- Template 4 uses the exact local 720 × 1280 opening video, approximately 8.708 seconds, playing once and muted.
- Cormorant Garamond, Inter and Italianno were added for Template 4 with SIL Open Font License notices.
- The missing commercial fonts were not downloaded without a supplied project license.
- No replacement music was invented. The Bruno Mars track for Template 2 remains unavailable for this implementation.
- Demo gift/payment account values are explicitly fictitious.

Third-party ownership and commercial production permissions have not been established. The reference media is retained for the requested local review and has not been staged, committed, published or mixed into permanent Event assets.

The template renderers do not hotlink reference media. The unchanged application shell still requests its existing Figtree stylesheet from Bunny Fonts.

Manifest: [Local media manifest](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/reference-template-assets/manifest.json>).

## 5. Families, guest names, RSVP and meals

Earlier authenticated QA exercised the real shared RSVP flow and recorded these results:

| Feature | Earlier verified behavior | Current interpretation |
| --- | --- | --- |
| Family recipient | Event-scoped family preview and personalized greeting | Preserved; visual placement still needs approval in final designs |
| Original guests | Names remain PartyMember-owned | Existing data ownership preserved |
| Additional guests | Additional RSVP person responses do not create permanent PartyMembers | Existing flow preserved |
| Accept / Decline | Submit, confirmation, edit, decline and return to acceptance | Real backend exists; local demo forms are presentation-only |
| Guest limits | Add Guest stopped at the tested party limit; backend capacity/tampering tests passed | Retest the final approved invitation flow before release |
| Meals | Separate meal choices saved per attendee | Existing behavior preserved |
| Notes/messages | Dietary notes and host message survived a saved response | Existing behavior preserved |
| Deadline | Expired RSVP showed closed behavior | Existing behavior preserved |

These are completed historical integration checks, not new submissions made during the template asset or fourth-demo pass.

The new Anthony & Tina page currently demonstrates its reference form locally. It has **not** been fully integrated and validated as a customer-selected template with every family, attendee and meal state. That is part of the post-visual-approval work.

## 6. Optional content, gifts and publication versions

The Builder stores story, gift registry and ending content as Event-owned invitation content. Earlier tests and browser QA verified hiding a section without deleting its stored content, then restoring it.

Gift registry content is separate from FormalEvites payments, transactions, coupons and revenue. Earlier integration audits found the finance records unchanged.

Publication QA previously verified:

- Unsaved name/date/venue/story changes do not create a publication.
- Changing the preview family or preview template does not create a publication.
- Saving a real live-invitation change creates the next version.
- Saving unchanged content does not create a duplicate version.
- Older snapshot data remains unchanged.

The latest full automated suite still includes Builder/publication isolation, data retention and version-deduplication tests. A final manual check against the approved renderer set remains part of release QA.

## 7. Database state

Two additive tables from the earlier Builder work exist:

| Table | Purpose |
| --- | --- |
| event_invitation_contents | Stores language, story, gift introduction and ending fields for an Event |
| event_gift_methods | Stores an Event's displayed gift methods |

Both September migration files show **Ran, batch 13** in a fresh read-only migration-status check. All listed local migrations are applied.

The later visual correction, demo-media and Anthony & Tina passes added no migrations or database schema changes. This reporting pass ran no migration and made no database writes.

Earlier integration QA created and retained sample Events 16–18 and related guest/response records for review. Those historical sample-record counts were not re-audited for this report; their inventory remains in the earlier QA folder.

## 8. Validation and its scope

| Check | Latest completed result |
| --- | --- |
| Laravel suite | **164 tests passed, 1,765 assertions** |
| TypeScript | vue-tsc --noEmit passed |
| Frontend build | npm run build passed; 924 modules transformed |
| Whitespace check | git diff --check passed |
| Template 4 browser sizes | 375 × 844, 430 × 844, 768 × 1024, 1366 × 900 |
| Template 4 rendering | No horizontal overflow, broken images, failed HTTP responses or JavaScript page errors in captured checks |
| Template 4 interactions | Scroll, curtain entry/exit, four map links, gift copy, accept/decline and local-only submission passed |
| Reduced motion | Paused opening/poster and instant scroll passed |
| Earlier three demos | Still rendered with their local images loaded during the fourth-demo checks |
| Fresh availability | All four demo pages returned HTTP 200 during report preparation |

The development test suite was not rerun simply to write this report. The table records the latest completed gate after implementation; this reporting pass only inspected evidence, source, Git state, migration status and local availability.

Vite reported public font URLs would resolve at runtime. Runtime checks confirmed the used fonts loaded. This was a successful build, not an outstanding build failure.

Earlier QA also covered widths 320, 360, 375, 390, 412, 430, 768 and 1366, including Builder RTL and a public Arabic flow. That evidence predates the later renderer reconstruction.

Remaining QA limits:

- A complete fresh screenshot/interaction matrix for demos 1–3 after media integration is still needed.
- Arabic localization remains partial. Layout-direction work exists, but many labels and date displays are still English.
- Template 4's Arabic/family/meal integration has not been signed off.
- Physical iPhone/Safari and Android testing is not established by desktop viewport checks.
- Earlier Builder payload/SQL diagnostics exist; complete browser HTTP duplication/idle-network and current media-performance checks remain incomplete.
- Automated tests do not establish visual equivalence.

## 9. Evidence and reports

| Evidence | Location and age |
| --- | --- |
| Original Builder/integration report | [Stage 7.3D report](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/stage-7-3d-20260902/REPORT.md>) — historical, before the later reconstruction |
| Step 1 screenshot | [Choose design](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/stage-7-3d-20260902/01-step-1.png>) — historical |
| Step 2 screenshot | [Enter information](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/stage-7-3d-20260902/02-step-2.png>) — historical |
| Builder screenshot | [Phone preview visible](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/stage-7-3d-20260902/03-builder.png>) — historical |
| First three visual comparisons | [Comparison viewer](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/visual-completion-20260902/comparison.html>) — before media integration |
| Fourth demo comparisons | [Anthony & Tina comparison viewer](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/anthony-tina-20260903/comparison.html>) — includes local reference media |
| Latest validation | [Validation summary](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/anthony-tina-20260903/validation-summary.json>) |
| Latest interaction evidence | [Interaction results](<C:/Users/Lenovo/Documents/Formal Invites System/storage/app/qa/anthony-tina-20260903/interaction-results.json>) |
| Fourth-template details | [Anthony & Tina report](<C:/Users/Lenovo/Documents/Formal Invites System/docs/template-anthony-tina.md>) |

The older demo-asset report stopped at an interrupted build/browser session. The build and basic rendering availability were subsequently resolved. Its remaining font/artwork gaps and missing full post-integration comparison remain relevant.

## 10. Important source files

| Area | Main files |
| --- | --- |
| Setup and Builder | [Setup.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Pages/Events/Setup.vue>), [Builder.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Pages/Events/Builder.vue>), [EventBuilderController.php](<C:/Users/Lenovo/Documents/Formal Invites System/app/Http/Controllers/EventBuilderController.php>) |
| Template selection/rendering | [InvitationPreviewRenderer.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationPreviewRenderer.vue>) |
| Four designs | [PublicRomanticFloral.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationTemplates/PublicRomanticFloral.vue>), [EditorialLuxury.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationTemplates/EditorialLuxury.vue>), [ModernCinematic.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationTemplates/ModernCinematic.vue>), [RoyalPlum.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/InvitationTemplates/RoyalPlum.vue>) |
| Local demos | [TemplateDemo.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Pages/TemplateDemo.vue>), [referenceDemos.ts](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/demo/referenceDemos.ts>), [TemplateDemoController.php](<C:/Users/Lenovo/Documents/Formal Invites System/app/Http/Controllers/TemplateDemoController.php>), [Asset mappings](<C:/Users/Lenovo/Documents/Formal Invites System/config/template_demo_assets.php>) |
| Existing RSVP | [PublicRsvpExperience.vue](<C:/Users/Lenovo/Documents/Formal Invites System/resources/js/Components/PublicRsvpExperience.vue>) |
| Existing content/publication | [InvitationPresenter.php](<C:/Users/Lenovo/Documents/Formal Invites System/app/Support/InvitationPresenter.php>), [InvitationPublicationSnapshotBuilder.php](<C:/Users/Lenovo/Documents/Formal Invites System/app/Support/InvitationPublicationSnapshotBuilder.php>) |
| Regression coverage | [EventBuilderTest.php](<C:/Users/Lenovo/Documents/Formal Invites System/tests/Feature/EventBuilderTest.php>), [EventPublicationTest.php](<C:/Users/Lenovo/Documents/Formal Invites System/tests/Feature/EventPublicationTest.php>), [TemplateDemoTest.php](<C:/Users/Lenovo/Documents/Formal Invites System/tests/Feature/TemplateDemoTest.php>) |

No application source files were removed in the recent template passes. Old generated build files are replaced normally by Vite.

## 11. Git and delivery state

- Branch: **develop**.
- HEAD: **294e803 — Build customer dashboard and invitation navigation**.
- Significant later work remains in the local working tree, including new untracked source files.
- Current tracked diff: **18 files, 330 insertions, 436 deletions**. This includes earlier work and excludes untracked files.
- Nothing has been staged, committed or pushed during these passes.
- No deployment or production update has been performed.
- The local demo routes and reference-media endpoint are blocked outside the local environment.

Do not treat these changes as saved to GitHub merely because they work locally. Review and an explicitly authorized Git checkpoint still remain.

## 12. Where we go next

1. **Review all four local demos with the owner.** Check each opening, section order, typography, images and mobile behavior. Record specific corrections per template.
2. **Close the remaining visual gaps.** Obtain the authorized font/artwork files, or record an explicit decision about remaining fallbacks. Finish current reference/local comparison captures for demos 1–3. Approval is still required before declaring reference reconstruction complete.
3. **Connect and refine the approved designs for real FormalEvites use.** Reuse the existing InvitationParty, PartyMember, RSVP, attendee-meal and publication systems. Add Template 4 to customer selection only when that integration is requested and ready.
4. **Run final end-to-end QA.** Creation, live editing, customer isolation, recipient/guest states, capacity, deadline, optional sections, snapshots, RTL, physical-device behavior and browser network/media performance.
5. **Review, save in Git and prepare deployment.** Exclude local reference-only media from a production release; use approved production assets. Commit, push and deployment remain separate actions requiring the owner's instruction.

The immediate milestone is approved base template visuals. The next implementation phase is FormalEvites-specific integration and polish; expanding into unrelated features is outside the current plan.
