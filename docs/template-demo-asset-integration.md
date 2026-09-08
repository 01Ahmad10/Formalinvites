# Demo asset integration report — 3 September 2026

Status at the user-requested interruption: **24 exact image/video files downloaded and connected in source; final build and browser verification remain unfinished. None of the templates is declared visually complete.**

This report covers only the latest asset pass. The substantial earlier working-tree changes remain preserved.

## A–C. Assets connected per template

| Template | Files | Connected roles |
| --- | ---: | --- |
| 1 — Jack & Stephanie | 9 | Opening image; four paper backgrounds; groom and bride icons; falling leaf; footer logo |
| 2 — Justin & Maya | 6 | Closed-curtain image; exact curtain video; menu frame; gold scratch coating; gift illustration; white footer logo |
| 3 — Joseph & Sarah | 9 | Swan poster and video; left/right flourishes; timeline rose; floral corners; wax seal; falling petal; footer logo |

These roles use the existing renderer slots. The local-only controller sends available asset URLs to TemplateDemo. No reference media URLs were written to Event data.

## D–H. Media and fonts

- **D — Images:** 22 image/artwork files, including two standalone SVG files. Original downloaded bytes are retained.
- **E — Artwork:** Existing placeholder slots now accept the local reference files. The menu frame and loaded garden artwork lose their temporary dashed borders. Footer images use their measured slots. Gold scratch texture is drawn into the existing scratch canvas; pointer/keyboard reveal rules are retained.
- **F — Video:** Curtain: 1072 × 1928, approximately 5.083 seconds of browser playback (5.084-second container duration). Swans: 720 × 1280, 8.084-second container duration. Existing muted/autoplay/loop settings are retained; no re-encoding.
- **G — Audio:** None downloaded or integrated. Permission for the reference Bruno Mars track was not supplied; no substitute track added.
- **H — Fonts:** No new fonts. Existing local open-license fonts remain. Template 2 keeps Montserrat/Georgia fallbacks because explicit FormalEvites licenses for Gotham Office Regular and Chronicle Semibold were not supplied.

## I–K. Storage, manifest and Git protection

- **I — Folder:** `storage/app/qa/reference-template-assets/`, organized by template and images/artwork/video. Total installed media: **9,514,013 bytes** (about 9.51 MB).
- **J — Manifest:** `storage/app/qa/reference-template-assets/manifest.json`. Each file records its template, sections, role, original URL, local path, MIME type, dimensions, byte size, kind, SHA-256, authorization note and DEMO ONLY status. The manifest records user-requested local QA use; it does not claim third-party ownership or verified commercial licensing.
- **K — Protection:** `/storage/app/qa/` was added explicitly to the root `.gitignore`; the existing `storage/app/.gitignore` also ignores these files. No media was staged or committed.

The new asset route is restricted to local development, an explicit role allowlist and files inside the QA asset directory. Missing files remain placeholders. Tests cover production blocking, unknown roles, missing files and path escape rejection.

## L–O. Visual status and remaining mismatches

| Item | Status | Remaining work |
| --- | --- | --- |
| L — Template 1 | BLOCKED BY ASSET | Original inline monogram and ceremony/celebration/copy icon exports remain missing. Post-integration browser review is pending. |
| M — Template 2 | BLOCKED BY FONT / BLOCKED BY ASSET | Commercial fonts, permitted soundtrack and original hand/scroll/music icon exports remain missing. Post-integration curtain/scratch review is pending. |
| N — Template 3 | BLOCKED BY ASSET | Original inline monogram export remains missing. Post-integration video/artwork review is pending. |
| O — All three, fresh visual evidence | BLOCKED BY BROWSER BEHAVIOR | Browser input stalled and the browser-control tool then became unavailable. Required new reference/local screenshots at 375, 430, 768 and 1366 were not captured. |
| O — Previously documented residuals | MINOR DIFFERENCE | Frozen family/RSVP presentation, randomized decorative motion and the rose-follow approximation remain as documented in the prior visual report. They were not modified in this pass. |

Exact downloaded file selection is confirmed; visible crops, media playback and final responsive rendering have **not** been reverified after integration. No blanket MATCHED claim is made.

## P. Screenshot viewer

The existing viewer remains at `storage/app/qa/visual-completion-20260902/comparison.html`. Its screenshots are **pre-integration evidence**. They have not been replaced or relabelled as current screenshots.

Working notes and logs for this pass are in `storage/app/qa/demo-assets-20260903/`. No new comparison viewer was completed.

## Q–T. Verification

- **Q — PHP:** Passed: **164 tests, 1,749 assertions**. Log: `storage/app/qa/demo-assets-20260903/php-tests.log`.
- **R — TypeScript:** `npx.cmd vue-tsc --noEmit` passed with exit code 0; no diagnostics.
- **S — Build:** **Not completed/verified in this pass.** The build request was interrupted; no new build log exists. The running demo may still use the previous compiled frontend until rebuilt.
- **T — Whitespace:** `git diff --check` passed during the reporting check. Git printed only the existing line-ending notice for PublicRsvpExperience.vue.

## Files changed by this asset pass

1. `.gitignore` — explicit QA exclusion.
2. `app/Http/Controllers/TemplateDemoController.php` — local demo rendering and guarded asset responses.
3. `config/template_demo_assets.php` — 24 local media role mappings.
4. `routes/web.php` — routes to the local demo controller and asset endpoint.
5. `resources/js/Pages/TemplateDemo.vue` — combines demo details with local media props.
6. `resources/js/Components/InvitationTemplates/PublicRomanticFloral.vue` — venue/footer asset hooks.
7. `resources/js/Components/InvitationTemplates/EditorialLuxury.vue` — scratch/footer asset hooks.
8. `resources/js/Components/InvitationTemplates/ModernCinematic.vue` — footer hookup and loaded-art presentation.
9. `resources/js/Components/InvitationTemplates/ScratchDateTile.vue` — optional image coating with load/error handling.
10. `tests/Feature/TemplateDemoTest.php` — local asset access checks.

This report and ignored QA media/scripts/logs are additional artifacts. A baseline hash comparison confirms that existing domain, publication, family/meal/RSVP components, database files and demo event details were unchanged by this pass. **No database changes, migrations, commits or pushes.**

## U–V. Repository state

**U — `git status --short`:** The full current snapshot is saved in `storage/app/qa/demo-assets-20260903/report-git-status.txt`. It includes earlier unfinished work, not only this pass.

**V — `git diff --stat`:** **18 tracked files changed, 328 insertions, 435 deletions** across the entire existing working tree. This includes prior work and excludes untracked files. Full output: `storage/app/qa/demo-assets-20260903/report-diff-stat.txt`.

## Next required work

Complete the frontend build, restore browser access, capture and review all 12 reference/local viewport pairs, resolve remaining authorized artwork/font/audio gaps, and obtain visual approval before customization. No feature customization was started.
