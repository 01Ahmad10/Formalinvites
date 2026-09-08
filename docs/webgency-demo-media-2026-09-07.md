# Local demo media integration — September 7, 2026

Supersedes the missing-media status in the earlier additions report.

## Result

The three local Webgency comparison demos now display reference photos, decorative artwork, and videos with their existing reference content. Downloaded 73 media files from URLs observed in the rendered reference pages, with zero download failures. Full-resolution `data-original` image URLs were used instead of blurred thumbnails. Proprietary HTML, CSS, and JavaScript implementations were not copied.

- **5 — Dolce Vita:** villa background video, five envelope layers, letter artwork, schedule artwork, venue photo and pin, divider, all ten dress gallery photos, and ending portrait. Recreated the pale blue scratch coating independently as SVG. Raised the letter on reveal so its text can be read.
- **6 — Blossom & Oud:** envelope layers and seal, hero video, invitation frame and Arabic calligraphy, timeline ornaments, venue/dress illustrations, map border, and ending artwork/video.
- **7 — The Sacred Garden:** opening image/video, swan video, floral/paper layers, calligraphy, schedule and venue art, six floating decorations, map frame/ornaments, gift/dress artwork, RSVP seal, and ending photo/paper. Fixed hero artwork stacking and allowed the opening video to finish before dismissing it, with a fallback timeout/error exit.

Files remain under ignored `storage/app/qa/reference-template-assets/template-5`, `template-6`, and `template-7`. The existing allowlisted local-only endpoint serves them. No production media publication or asset-license claim is implied. No database, RSVP domain, family, meal, or customer-template selection changes were made in this pass.

## Validation

- Laravel: 164 tests passed, 1,820 assertions.
- TypeScript: `npx vue-tsc --noEmit` passed.
- Frontend: `npm run build` passed in 10.17 seconds.
- `git diff --check` passed; existing CRLF conversion warning for PublicRsvpExperience.vue remains.
- Browser checks: templates 5–7 at 375, 430, 768, and 1366 pixels; no horizontal document overflow, missing ReferenceAsset slots, or completed-but-broken images detected.
- All main background videos decoded in the local browser. Mobile envelope buttons were exercised for Dolce Vita and Blossom & Oud; Sacred Garden opening was exercised on desktop.
- Fresh screenshots were displayed inline during review (not saved as comparison files).

Evidence: `storage/app/qa/webgency-media-manifest.json`, `webgency-media-php-tests.log`, `webgency-media-typescript.log`, `webgency-media-build.log`, and `webgency-media-diff-check.log`.

## Remaining differences

Exact reference script fonts are still unavailable; the existing fallback remains. Fine animation/typography fidelity still needs owner review. Music was not imported. Optional video poster slots remain unused where a working video is displayed. Dolce Vita's scratch coating is a clean visual recreation rather than a downloaded canvas image.

Git remains on the existing dirty working tree. No commit, push, database change, or deployment was performed.
