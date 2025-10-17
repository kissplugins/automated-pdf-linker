# PROJECT: Folder File Listing Viewer — V2

## Overview
Build a robust, scalable filter/sort UX for the Folder File Listing Viewer with minimal disruption to existing code. We will preserve the current data source (IndexBuilder) and markup, introduce a clean client-side state (FSM-friendly), and optionally add a REST layer for performance and future growth.

We will deliver in 2 phases to reduce risk and ship value early.

## Goals
- Flexible single search field that filters results across filename, path, and optionally folder name.
- File name search with partial/fuzzy matching.
- Date search/filter (e.g., queries like `10-17-25`, `2025-10-17`, or ranges).
- Sort toggles: by Modified Date and by Alphabetical filename.
- Maintain DRY: reuse IndexBuilder and existing totals/index stats.
- Keep WordPress best practices: capability checks, nonces, accessibility, i18n.

## Non-Goals
- Changing the underlying indexing logic or storage format.
- Server-side pagination/virtualization in Phase 1 (considered in Phase 2 if needed).

## Architecture
- Source of truth: IndexBuilder with the saved index.
- Phase 1: Render a compact JSON payload (data attributes) alongside markup; client-side filtering/sorting via vanilla JS.
- Phase 2: Optional REST endpoint returning index entries with query params for filtering/sorting, plus optional virtualized rendering for large sets.
- State management: Encapsulate UI state transitions; if an FSM already exists, integrate with it; otherwise implement a small finite-state controller specific to the viewer.

## Phase 1 — Client-side controls (no REST)
- [/] Status: In Progress
Deliver value quickly by enhancing the current viewer with search/sort/filter using in-browser arrays. Suitable for up to a few thousand rows.

### Functional scope
- Single search box (debounced) that performs a broad filter:
  - Matches on filename, path, folder label (case-insensitive).
- Fuzzy matching for filenames:
  - Start with lightweight partial includes + simple scoring; pluggable to swap in Fuse.js later if needed.
- Date filter input:
  - Accepts multiple formats (MM-DD-YY, MM/DD/YY, YYYY-MM-DD). Parse into a canonical date. Support exact-date filter initially; optionally extend to ranges like `2025-10-01..2025-10-31`.
- Sort toggles:
  - Column UI affordance for Date and Filename; clicking toggles asc/desc.
- Respect existing CSS and dark admin theme. Add minimal styles only as needed.

### Data model (client)
- Extract the existing rows into an array of objects on load, e.g. `{ filename, folder, url, modified_ts, size_bytes }`.
- Preserve index totals from IndexBuilder::get_index_stats() for DRY.

### Accessibility
- Inputs labelled via `for`/`aria-label`.
- Keyboard focus order preserved; toggles are buttons with `aria-pressed`.
- Live region update for result count.

### Security
- No server writes. No new endpoints in Phase 1.

### Deliverables
- Search input, date input, and sort buttons added above the table.
- JS module that:
  - Reads DOM → builds in-memory dataset once.
  - Applies filters + sorts → re-renders tbody efficiently (minimal DOM ops).
  - Maintains state (search text, date filter, sort key/order) in a small FSM-like controller.
- CSS additions scoped under `.kapl-folder-viewer`.

### Acceptance criteria
- Typing in search filters results live (debounced ~150–250ms) for filename/folder/path.
- Partial/fuzzy search returns expected matches; case-insensitive.
- Date filter accepts `10-17-25` and `2025-10-17` and filters correctly by Modified Date.
- Sort toggles switch between asc/desc for Date and Name; visual indication of state.
- Total files pill remains DRY and consistent with index stats; visible count of filtered results is shown near the controls.
- No PHP warnings; no console errors.

### Checklist (Phase 1)
- [x] Add controls markup (search, date, sort) in AdminMenu renderer.
- [x] Expose row data as data attributes or build dataset from DOM.
- [x] Implement small state controller (FSM-friendly) for filters/sorts.
- [x] Implement debounced text filter and date parse/filter.
- [x] Implement sort toggles and visual states.
- [x] Update result count and ARIA live region.
- [x] Add minimal CSS for controls aligning with dark theme.
- [ ] Unit test: date parse util; integration smoke with sample rows.
- [ ] QA with 50–500 rows for responsiveness.

## Phase 2 — REST + scale (optional, if needed)
- [ ] Status: Not Started
Introduce a REST endpoint and (optional) virtualization for very large datasets (thousands+), preserving identical UX.

### Functional scope
- REST route: `/kapl/v1/files` with query params: `q` (text), `date`, `date_from`, `date_to`, `sort=modified|name`, `order=asc|desc`, `page`, `per_page`.
- Server-side filter/sort uses IndexBuilder’s index; no duplication of logic.
- Pagination UI if dataset is large, with total/filtered counts.

### Security
- Capability checks (`manage_options`) and nonces.
- Strict sanitize/validate for query args.

### Deliverables
- REST controller class with route registration.
- JS data layer that fetches based on UI state; same state controller as Phase 1.
- Optional virtualized rendering for performance.

### Acceptance criteria
- Filters/sorts produce consistent results with Phase 1 for the same inputs.
- Pagination/virtualization keeps UI smooth with large datasets.
- No PII leakage; capability checks enforced.

### Checklist (Phase 2)
- [ ] Add REST route (read-only) with capability checks.
- [ ] Implement server-side parse/validate for query params.
- [ ] Implement filter/sort on server reusing IndexBuilder output.
- [ ] Wire front-end data layer to fetch and render results.
- [ ] Add pagination controls (if needed) and live counts.
- [ ] Performance test with 5k–20k rows.

## Risks & Mitigations
- Parsing ambiguous dates → normalize and document accepted formats; fallback to locale-agnostic ISO.
- Fuzzy match performance → start with simple algorithm; upgrade to Fuse.js if necessary (via npm + build) in a later iteration.
- DOM reflow cost → batch DOM updates and use document fragments; consider virtualization in Phase 2.

## Rollout Plan
- Ship Phase 1 behind the existing page; no backend data changes.
- Add feature flags tied to settings (Debug already exists; can add Viewer V2 toggle if needed).
- Monitor console and logs (existing debug panel helps).

## Definition of Done
- Phase 1: All acceptance criteria met, tests pass, no regressions in existing viewer.
- Phase 2: REST + large-data perf validated; accessibility verified.

## Notes
- DRY: Continue to use IndexBuilder::get_index_stats() for totals near the controls and in the viewer.
- Admin hook detection: keep slug-based detection to support Tools and Settings screens.
- If an FSM already exists in the plugin, integrate with it; otherwise scope a minimal state machine to the viewer UI only.

