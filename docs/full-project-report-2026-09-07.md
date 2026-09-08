# FormalEvites — consolidated development report

Report date: September 7, 2026. This report combines the current working tree, the September 3 reconstruction/owner-review reports, and the latest September 7 media/dashboard work. Earlier results are identified as historical. This reporting pass does not change application code or customer records.

## Current position

Seven reference invitation demos are available locally. Six reference designs are registered for customer selection, subject to activation and supported Event type. Anthony & Tina remains demo-only. The existing admin catalog also contains Elegant Classic and Modern Minimal, so the last verified admin catalog had eight records rather than seven.

The three new designs are connected to selection, saved Event template assignments, customer dashboards, and admin views. This is not final production approval: reference media stays local-only, exact fonts and some artwork remain unavailable, and complete native RSVP presentation remains unfinished.

## Websites and local pages

| Demo | Reference website | Local demo | Application renderer | Selection status |
| --- | --- | --- | --- | --- |
| 1 — Jack & Stephanie | https://digitalinvitation.me/jack-and-stephanie | http://127.0.0.1:8000/template-demos/1 | Romantic Floral / romantic-floral | Customer-selectable when active and supported |
| 2 — Justin & Maya | https://digitalinvitation.me/justin-and-maya-1 | http://127.0.0.1:8000/template-demos/2 | Editorial Luxury / editorial-luxury | Customer-selectable when active and supported |
| 3 — Joseph & Sarah | https://digitalinvitation.me/joseph-and-sarah | http://127.0.0.1:8000/template-demos/3 | Modern Cinematic / modern-cinematic | Customer-selectable when active and supported |
| 4 — Anthony & Tina | https://digitalinvitation.me/anthony-and-tina | http://127.0.0.1:8000/template-demos/4 | Royal Plum / royal-plum | Demo-only |
| 5 — Dolce Vita | https://webgencyinvitations.com/dolcevita | http://127.0.0.1:8000/template-demos/5 | Dolce Vita / dolce-vita | Newly customer-selectable; wedding/engagement |
| 6 — Blossom & Oud | https://webgencyinvitations.com/blossomoud | http://127.0.0.1:8000/template-demos/6 | Blossom & Oud / blossom-oud | Newly customer-selectable; wedding/engagement |
| 7 — The Sacred Garden | https://webgencyinvitations.com/thesacredgarden | http://127.0.0.1:8000/template-demos/7 | The Sacred Garden / sacred-garden | Newly customer-selectable; wedding/engagement |

Local gallery: http://127.0.0.1:8000/template-demos

Dashboard: http://127.0.0.1:8000/dashboard

Events: http://127.0.0.1:8000/events

Admin Templates: http://127.0.0.1:8000/admin/templates

These addresses require the local development servers. They are not deployed public websites. Reference sites were inspected; they were not modified.

## What changed in each template

### 1 — Jack & Stephanie

Reconstructed the coastal opening, paper backgrounds, invitation details, countdown/calendar, locations, celebration, dress, gifts, RSVP and ending. The owner-review pass preserved its composition and adjusted the mobile RSVP heading to 54.4px and the existing cream/blush form presentation. The latest pass removed the reference footer logo and replaced it with FormalEvites text.

Remaining: exact couple monogram and some ceremony, celebration and copy icons; final family/RSVP placement; remaining ending-section spacing differences. No pixel-identical approval has been claimed.

### 2 — Justin & Maya

Implemented the curtain opening, scratchable date, countdown, framed meeting details, locations, gifts and ending. Corrected concatenated times/addresses, restored burgundy/ivory RSVP typography and spacing, and preserved the measured frame proportions. Historical owner-review measurements recorded a 520 × 924.44px desktop frame and approximately one pixel of full-page desktop height difference. The latest pass replaced the footer branding.

Remaining: Gotham Office Regular and Chronicle Semibold, original control icons, soundtrack, and final native RSVP presentation. Historical close geometry does not establish current pixel equality after later branding edits.

### 3 — Joseph & Sarah

Implemented the swan opening, cream/gold garden design, countdown, schedule, maps, dress, gifts and seal-operated RSVP presentation. Section measurements confirmed a 480px content area, 41.6px standard headings, 352px timeline and 416px map frames, so the owner-review pass did not enlarge everything globally. Corrected the RSVP heading weight, gold script prompt, chevron and seal shadow. Replaced footer branding.

Remaining: exact monogram, decorative motion fidelity, and final family/RSVP presentation. Live maps, video frames and particle positions vary between captures.

### 4 — Anthony & Tina

Added RoyalPlum.vue with the arch opening video, countdown, opening curtains, meeting points, celebration, dress, story, gifts, demo RSVP and ending. Retained the cream/plum palette and reference desktop composition. Corrected muted text hierarchy and RSVP label/selected-state styling. Removed the Digital Invitation Instagram link and logo.

Remaining: Sweet Fancy Script, exact monogram/control artwork and font-related wrapping. It remains outside customer selection and has not received complete real-data/RSVP integration.

### 5 — Dolce Vita

Added the Italian villa background video, five-piece envelope with gold seal, pale-blue scratch date, letter, schedule, venue, dress section, ten-photo outfit gallery, palette, demo RSVP and ending portrait. Connected full-resolution reference media. Recreated the canvas scratch coating independently as SVG and raised the letter on reveal so its wording can be read. Demo content uses Alexa & Richard and the reference date/venue.

### 6 — Blossom & Oud

Added the Moroccan arch video, layered envelope and seal, ornate invitation frame, Arabic calligraphy and wording, French headings, countdown, timeline, location, dress illustrations/palette, demo RSVP, map frame and ending video/artwork. Demo content uses Amira & Yusuf and Beldi Country Club. The reference's zero countdown remains a demo fixture; real Event output uses the existing countdown helper.

### 7 — The Sacred Garden

Added the envelope image/opening video, swan hero, floral/paper layers, calligraphy, countdown, schedule, venue illustration, six floating decorations, map ornaments, dress/gift artwork, RSVP seal and ending photo. Corrected hero layer stacking and opening-video dismissal timing. Demo content uses Zohan & Rose and the reference venue/date.

The last three designs still use fallback script fonts. Fine animation/typography comparison and owner approval remain outstanding.

## Media, source handling and branding

The earlier four-demo manifest recorded 38 files. The latest three-demo pass downloaded 73 additional reference media files with zero failures, using observed full-resolution image URLs rather than blurred thumbnails. A separately authored scratch-coating SVG was added. These are file/manifest counts, not the number of unique visible images or a page-transfer measurement. Old logo files can remain on disk, but their serving roles and rendered uses have been removed.

Media is isolated under storage/app/qa/reference-template-assets. The existing allowlisted endpoint checks that files stay inside that directory and returns 404 outside local development. Reference media was not copied into permanent Event assets or publication snapshots. Provenance is recorded in local manifests; production ownership/licenses are not established by this work.

Rendered DOM, computed styles, dimensions, section order and interactions informed the Vue reconstruction. Proprietary page implementations were not copied wholesale. Reference music was not imported. Existing open-font files remain; missing commercial fonts are explicitly outstanding.

Digital Invitation footer logos and its Instagram link were removed from templates 1–4 and replaced with FormalEvites text. Browser verification found zero old logo requests and zero Digital Invitation links on those demos. Templates 5–7 did not contain that footer branding. Source URLs remain in documentation as provenance.

## Dashboard, catalog and real-data connection

The existing selection flow continues to save Event.template_id. Three catalog rows were added by WebgencyTemplateSeeder; repeated execution preserves an admin's existing activation decisions. No Event was reassigned by the seeder.

Customers can browse/select the new active supported designs. The setup preview dialog shows the populated local reference demo separately in an iframe. Customer dashboards show the saved template name for one or multiple invitations.

Admins can see the additions, assignment counts, activation controls and local demo links in Admin Templates. Upcoming-event cards and Event details show the selected design. Existing authorization checks remain in place.

InvitationPreviewRenderer recognizes the new component keys. A small frontend presentation adapter maps the existing Event presenter into their expected display fields, without importing reference fixtures. Real Event output uses customer/event details and the existing real RSVP slot rather than the demo submission form.

This adapter is a first connection, not full personalization completion. Production artwork, every optional section/field, map presentation and complete template-native RSVP styling still need a dedicated pass. Reference photos, videos, names and dates are not silently substituted into customer invitations. Missing production artwork remains visibly marked.

## Existing Builder and business systems

Earlier project work established the three-step creation flow: choose design, enter essential information, then personalize in the Builder. Its sections are Personal Details, Meeting Point, Ceremony & Venue, Our Story, Gift Registry, Ending Page, Languages, and Preview & Complete.

The existing system includes Customer-owned Events and authorized Event members, package capacity, manual payments/coupons, InvitationParty families, PartyMember names, secure invitation links, RSVP acceptance/decline, attendee limits, additional guests, per-attendee meals, dietary notes, guest messages and deadlines.

Earlier QA verified those flows, optional-content retention and versioned publication snapshots. The latest changes preserve that backend. Native presentation of all family/member/meal states within every final template is not yet complete. Local demo forms only show a confirmation and do not save responses.

## Database and architecture

The approved Laravel/PHP, PostgreSQL, Vue 3, TypeScript, Inertia and Tailwind stack remains. No new major dependency or architectural replacement was introduced in the recent passes.

Earlier Builder work added event_invitation_contents and event_gift_methods and their models/migrations. Those files remain in the uncommitted working tree. The dashboard connection added three records to the existing templates table only; it added no schema migration. No destructive migration, database reset or production edit was performed.

## Validation and evidence

Latest completed implementation checks, September 7:

| Check | Result |
| --- | --- |
| Laravel suite | 165 passed; 2,007 assertions; 30.28 seconds |
| TypeScript | npx vue-tsc --noEmit passed |
| Frontend build | npm run build passed; 14.28 seconds |
| Whitespace | git diff --check passed; CRLF/LF conversion warnings remain |

New automated coverage verifies selection of all three templates, denial of unauthorized Event changes, selected names on customer/admin views, Event preview payloads without demo fixtures/media, and idempotent seeding that preserves activation state.

Browser checks covered the three new demos at 375, 430, 768 and 1366 pixels, with no document overflow, missing image slots or completed-but-broken images detected. Main videos decoded. Mobile envelope controls were exercised for Dolce Vita and Blossom & Oud; Sacred Garden opening was exercised on desktop. Admin catalog and setup iframe preview were inspected. No existing Event was reassigned during browser verification.

Earlier paired screenshots for templates 1–4 are in storage/app/qa/owner-review-20260903/comparison.html and storage/app/qa/anthony-tina-20260903/comparison.html. They predate the latest branding changes. September 7 screenshots were displayed inline rather than saved as a new complete comparison set. Physical-device and Safari release testing has not been established.

Latest logs: storage/app/qa/template-dashboard-tests.log, template-dashboard-typescript.log, template-dashboard-build.log and template-dashboard-diff-check.log. The reporting pass read existing logs; it did not rerun the suite or claim a fresh browser audit.

## Files and Git state

Main changes span template Vue components/helpers, the demo page/fixtures/asset allowlist, the trusted renderer registry, Template model/catalog seeder, setup/builder/invitation controllers, dashboard/admin/Event views, Event content/publication support, and related feature tests.

Current branch: develop. At report inspection, Git showed 28 modified tracked files, plus untracked additions and documentation. The tracked-only diff was 410 insertions and 453 deletions; it excludes untracked files and includes earlier work, so it is not the size of the latest pass alone.

No staging, commit, push, deployment or history rewrite was performed. Local demo media remains ignored by Git. The complete working-tree inventory is saved alongside the QA records.

## Recommended next work — not performed by this report

1. Owner approval of the seven local visual demos, with clear remaining differences recorded.
2. Resolve missing font/artwork permissions and prepare permanent production media.
3. Complete real Event content and native family/guest/RSVP/meal presentation for the approved designs; review whether Template 4 should become selectable separately.
4. Recheck end-to-end customer and guest workflows, publication isolation, mobile browsers and final visual comparisons.
5. Review the full uncommitted diff and commit/deploy only after authorization.
