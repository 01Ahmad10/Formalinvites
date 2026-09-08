# Demo asset integration recheck — September 7, 2026

This is a verification of the existing asset integration, not a new download or redesign pass. The repeated asset-pass instructions were checked against the current working tree and newer QA evidence. No application, database, RSVP, family, meal, publication, or template code was changed in this recheck. No commit or push.

## A–C. Assets integrated per template

| Template | Currently connected local files | Archived files |
| --- | --- | --- |
| 1 — Jack & Stephanie | Opening photograph, four paper backgrounds, groom/bride SVGs, falling leaf (8 files) | One former reference footer logo |
| 2 — Justin & Maya | Curtain still/video, menu frame, scratch coating, gift illustration (5 files) | One former reference footer logo |
| 3 — Joseph & Sarah | Swan poster/video, two flourishes, rose bouquet, floral corner, wax seal, petal (8 files) | One former reference footer logo |

All 24 downloaded files are present and their SHA-256 hashes match the existing manifest. Twenty-one remain mapped to demo roles. Three archived footer logos remain on disk but are intentionally disconnected following the owner's later request to remove Digital Invitation branding. They have not been reintroduced.

## D–H. Images, artwork, videos, audio, fonts

D/E: 22 existing image/artwork files, including the three unused footer logos. Exact existing bytes preserved; no substitute images introduced.

F: Exact local curtain video (1072 × 1928, approximately 5.084 seconds) and swan video (720 × 1280, approximately 8.084 seconds) remain integrated. No transcoding or playback changes. The current browser check confirmed Template 2 changes from its closed curtain to the final frame and scroll control after opening.

G: No audio integrated. The manifest still records missing permission for the reference Bruno Mars soundtrack. The music control explicitly reports the unavailable asset.

H: No new font supplied. Template 2 retains Montserrat/Georgia in place of Gotham Office Regular/Chronicle Semibold. Existing open-license font files remain unchanged. The attachment permits exact commercial files only when explicitly licensed; it does not supply those files or licensing evidence.

## I–K. Storage and protection

I: `storage/app/qa/reference-template-assets/template-1/`, `template-2/`, `template-3/`.

J: `storage/app/qa/reference-template-assets/manifest.json`. Records original URL, role/section, dimensions, type, byte size, hash, authorization note and demo-only status. Integrity results: `storage/app/qa/demo-assets-recheck-20260907/asset-integrity.json`.

K: `git check-ignore` confirms the manifest is ignored. Root `.gitignore` excludes `/storage/app/qa/`. No files staged. Existing local-only role mapping and guarded asset delivery preserved. No reference media written into Event records or publication snapshots during this pass.

## L–O. Match status and remaining differences

| Template | Status | Remaining differences |
| --- | --- | --- |
| 1 | BLOCKED BY ASSET; MINOR DIFFERENCE | Exact inline couple monogram, ceremony/celebration/copy icons; typography/spacing residuals; reconstructed leaf/reveal motion; existing RSVP differences. |
| 2 | BLOCKED BY FONT; BLOCKED BY ASSET; MINOR DIFFERENCE | Two commercial fonts; exact hand/scroll/music glyph exports; permitted soundtrack; font-driven wrapping/spacing and curtain/reveal timing differences; existing RSVP differences. |
| 3 | BLOCKED BY ASSET; MINOR DIFFERENCE | Exact inline monogram; type/section proportions, randomized petals and timeline rose motion; existing RSVP differences. |

The attachment does not provide the missing authorized artwork exports or licensed music/fonts. Existing source-copy restrictions remain applicable. No template is declared visually complete. FormalEvites footer branding differs from the references intentionally because of the owner's subsequent branding-removal request.

## P. Comparison evidence

Current post-integration viewer: `storage/app/qa/visual-integration-20260907/comparison.html`.

For templates 1, 2, 3, use `reference-viewport-N-WIDTH.png` and `local-viewport-N-WIDTH.png`, at WIDTH 375, 430, 768, 1366. All 12 pairs exist; verified in `storage/app/qa/demo-assets-recheck-20260907/evidence-check.json`. Their capture metadata is in the viewer folder's `capture-manifest.json`.

These are the September 7 captures from the preceding completed visual pass, reused unchanged here because no asset/rendering changes were made. They were not newly captured during this recheck. Opening captures are viewport evidence; existing full-page captures are diagnostic only because the browser distorts viewport-height geometry. They do not certify an exact match in every section or animation.

The September 2 viewer is historical pre-integration evidence and must not be used as the current asset-completion result. Further fresh captures are needed after the missing files are supplied and integrated.

## Q–T. Regression verification

Q: PHP passed: 167 tests, 2,401 assertions (85.08 seconds). R: standalone TypeScript passed, exit 0. S: build passed, 947 modules, Vite 47.63 seconds; existing public-font runtime-resolution warnings remain. T: git diff --check passed; only existing CRLF normalization notices. These commands were rerun in this recheck.

## U–V. Git state

Full `git status --short`: `storage/app/qa/demo-assets-recheck-20260907/git-status.txt`.

Full `git diff --stat`: `storage/app/qa/demo-assets-recheck-20260907/git-diff-stat.txt`.

The working tree contains substantial earlier work: 28 modified tracked files, 466 insertions and 465 deletions, plus untracked implementation/documentation files. That total is not the change size of this recheck. Only this report and ignored verification artifacts were written during this recheck. No commit, push, migration, customization, or rollback.
