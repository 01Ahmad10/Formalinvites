# Owner visual review corrections — September 7, 2026

## Corrections made per template

| Template | This pass |
| --- | --- |
| 1 — Jack & Stephanie | Restored the RSVP heading's fluid sizing at intermediate mobile widths: 430px now produces **60.2px**, matching the live reference, instead of the previous fixed 54.4px. The 375px size remains 54.4px; tablet/desktop remains capped at 102.4px. Preserved the composition and existing cream/blush demo form. The desktop first seven sections remain 900px each at a 900px viewport. |
| 2 — Justin & Maya | **No new code change required after measurement.** Confirmed the earlier time/address separator fix, native burgundy/ivory demo styling, 520 × 924.44px desktop frame, and ending-card proportions remain present. Desktop location-section height is 774.09px versus 774.34px reference; entire page differs by about 1.22px. Retained the measured layout rather than compensating for missing fonts. |
| 3 — Joseph & Sarah | Restored the footer brand holder to **112 × 48px minimum**, bringing the footer from 82px back to the reference's **112px height**. Kept FormalEvites branding. Confirmed the existing desktop content width (480px), heading size (41.6px), schedule width (352px), map width (416px), gift and seal scale. Before the footer correction, corresponding desktop section heights already differed by less than 0.04px each; no global enlargement. |
| 4 — Anthony & Tina | **No new code change required after measurement.** Existing plum text hierarchy, translucent ivory cards, spacing and decorative placements were retained. The desktop ceremony section is still one 59.2px line taller because the fallback script wraps differently. Template 4 remains demo-only; no catalog changes. |

Only `PublicRomanticFloral.vue` and `ModernCinematic.vue` received presentation changes in this pass. No architecture, database, RSVP handling, family/member/meal logic, or publication behavior changed. Existing real-data integration from earlier work was preserved. No assets were downloaded or moved. No commit or push.

## Remaining reference differences

| Template | Status and differences |
| --- | --- |
| 1 | **MINOR DIFFERENCE / BLOCKED BY ASSET:** missing monogram and original icons, retained demo form structure/count placement/notice, and reconstructed leaf/reveal motion. Desktop final section is 978px versus 977.58px reference. Existing family presentation is unchanged. |
| 2 | **MINOR DIFFERENCE / BLOCKED BY FONT / BLOCKED BY ASSET:** font metrics and wrapping, control glyphs, soundtrack, and existing demo RSVP structure/notice. Opening and reveal motion remain clean reconstructions; no exact timing claim is made from still screenshots. Both `05:00 PM Beirut, Lebanon` and `06:00 PM Bsalim, Lebanon` retain explicit spaces. |
| 3 | **MINOR DIFFERENCE / BLOCKED BY ASSET:** missing monogram, retained family/form presentation, dynamic map content, petal phases and rose-follow motion. Garden schedule screenshots show closely matching scale; the measured footer-height regression is fixed at every requested width. |
| 4 | **MINOR DIFFERENCE / BLOCKED BY FONT / BLOCKED BY ASSET:** missing script changes swashes, text width, wrapping and downstream ceremony-section positions. The remaining monogram/control glyph substitutions are unchanged. |

FormalEvites footer text differs deliberately from the references, preserving the owner's earlier removal request. No Digital Invitation branding was reintroduced. This is not a claim that all four templates are visually complete.

## Remaining asset/font blockers

- Template 1: exact couple monogram and ceremony/celebration/copy icon exports.
- Template 2: explicitly licensed Gotham Office Regular and Chronicle Semibold; exact hand/scroll/music icon exports; permitted soundtrack.
- Template 3: exact couple monogram export.
- Template 4: explicitly licensed Sweet Fancy Script; exact monogram/copy/scroll artwork.

Existing fallbacks remain. The isolated local QA asset mapping and manifest are unchanged; no newly authorized files were provided in this pass.

## Fresh comparison paths

Viewer: `storage/app/qa/owner-review-20260907/comparison.html`.

**72 fresh screenshots / 36 pairs:** all four templates at **375, 430, 768 and 1366px** with 900px CSS height. Each has an opening pair and a focused-section pair: floral RSVP, editorial Wedding Locations, garden Schedule of Events, and plum The Celebration. Garden also has ending pairs at all four widths.

- `reference-N-WIDTH-opening.png` / `local-N-WIDTH-opening.png`
- `reference-N-WIDTH-detail.png` / `local-N-WIDTH-detail.png`
- `reference-3-WIDTH-ending.png` / `local-3-WIDTH-ending.png`

The viewer links original images. `comparison-manifest.json` records bitmap dimensions and file timestamps; `computed-styles.json` records DOM dimensions, fonts, colors, margins, padding, sections and media; `capture-checks.json` records actual viewport widths, overflow and observed broken-image checks. Repeated measurement labels represent before/after checks; the last matching label is the latest. Initial local garden measurements show the old 82px footer; `endingChecks` confirms the corrected 112px footer at all four widths.

No horizontal document overflow or broken images were detected in the 32 final template/width/side capture combinations. These are viewport captures, not full-page captures. Scroll positions are aligned around matching headings; some sections exceed the captured viewport. Videos/countdowns/particles/maps can differ between capture times. Screenshots and computed measurements do not prove identical animation behavior or every interaction.

## Test/build results

| Gate | Result |
| --- | --- |
| `php artisan test --compact` | **PASS — 167 tests, 2,401 assertions**, 29.76 seconds |
| `npx vue-tsc --noEmit` | **PASS**, exit 0 |
| `npm run build` | **PASS**, Vite 13.71 seconds; existing public-font runtime-resolution warnings remain |
| `git diff --check` | **PASS**, exit 0; existing CRLF normalization notices only |

Logs: `php-tests.log`, `typescript.log`, `build.log`, `diff-check.log` in the new QA folder. PNG validation passed for all 72 files. No demo or live RSVP responses were submitted during this pass.

## Git status

Current branch: `develop`. Existing substantial uncommitted work remains. `git-status.txt` contains the complete `git status --short`; `git-diff-stat.txt` contains the complete working-tree statistics. These include earlier work, not just the two CSS corrections above. `initial-status.txt` records the starting state.

No commit, push, staging, history changes or rollback. Stopped for owner review.
