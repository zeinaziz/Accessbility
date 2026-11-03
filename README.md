# Accessibility Lite

A lightweight WordPress accessibility toolbar — text resize, grayscale, high/negative contrast, light background, underline links, readable font, and a skip-to-content link. Built as a drop-in replacement for the old Pojo/Elementor "One Click Accessibility" widget, in ~5 KB of vanilla JS + CSS (no jQuery, no 50+ KB of duplicated selectors).

- **Requires:** WordPress 5.2+, PHP 7.0+
- **License:** GPL-2.0-or-later
- **Repo layout:** the repo root *is* the plugin root (see [Repo layout](#repo-layout) below) — clone/download and drop the whole folder into `wp-content/plugins/`.

## Features

- Text resize (100–200%, in 10% steps)
- Grayscale, high contrast, negative contrast, light background (mutually exclusive "schema" modes)
- Underline links, readable font (Verdana/Arial fallback stack)
- Skip-to-content link with a configurable target element ID and automatic fallback (`main`, `[role="main"]`, `#main`, `#primary`)
- Optional "remember state across pages" via `localStorage`, with a configurable expiry
- Toolbar position (left/right), independent vertical position for desktop and mobile (top/middle/bottom, in `vh`)
- Per-theme **Custom CSS** field (see below) — no code edits needed to fit a specific theme
- Exclude the toolbar by post type or specific post/page IDs
- Self-hosted auto-updates via GitHub Releases (no WordPress.org listing needed)

## Installation

1. Download the latest release zip from the [Releases page](https://github.com/zeinaziz/Accessbility/releases) (or clone this repo).
2. Upload/extract the whole folder to `wp-content/plugins/accessibility-lite` (or any folder name — the plugin doesn't depend on its own directory name).
3. Activate **Accessibility Lite** in **Plugins**.
4. Configure it under **Settings → Accessibility Lite**.

Once activated, the plugin checks this GitHub repo's Releases for updates and offers them through the normal WordPress "Update available" UI — no separate marketplace or license key involved.

## Settings reference

All settings live under **Settings → Accessibility Lite**, stored as a single array in the `al_a11y_options` option.

| Setting | Notes |
|---|---|
| Toolbar position | `left` or `right` |
| Vertical position (desktop/mobile) | `top` / `middle` / `bottom`, plus a `vh` offset. Desktop and mobile (≤768px) are independent — see `#al-toolbar.al-vert-*` / `.al-vert-m-*` in `assets/css/a11y.css`. |
| Toggle button color | Two color pickers: background (`--al-accent`) and icon/text color (`--al-accent-text`). Both cascade to the toggle button, panel body, and title bar. |
| Features | Checkboxes for each toggle-able feature; `reset` is always shown. |
| Skip to content link | Target element ID (without `#`); falls back automatically at runtime if the ID doesn't exist on the page. |
| Visible focus outline | Adds `body.al-focusable :focus { outline: ... }` globally, not just inside the toolbar. |
| Save user preferences | `localStorage`-backed, with an expiry in hours (1–8760). |
| Exclude on | By post type, and/or a comma-separated list of specific post/page IDs. |
| Custom CSS | Raw CSS, loaded via `wp_add_inline_style()` right after the plugin's own stylesheet. No `<style>` tags needed. |

## The Custom CSS field — what it's for, and gotchas

The Custom CSS field exists so a site can be visually matched to a previous toolbar (e.g. migrating off Pojo/Elementor) **without editing plugin code** — everything it can reach is also reachable by hand-writing selectors against the plugin's own markup. Useful hooks: `#al-toolbar`, `.al-slide`, `.al-toggle`, `.al-overlay`, `.al-title`, `.al-items`, `.al-item button`, `.al-txt`.

A few things that aren't obvious from the CSS alone, learned the hard way while integrating this against a real theme:

- **`#al-toolbar`'s own box does not shrink when the panel is closed.** Only the child `.al-slide` is translated off-screen (`transform: translateX(...)`); the parent `<nav>` keeps its natural, untransformed layout box — which is as tall as the *open* panel. Since it has `pointer-events: auto` (the default) and a high `z-index`, it silently intercepts real mouse clicks over that whole area, even where nothing is visibly drawn — e.g. other floating icons stacked near it in a theme's sidebar. If you're placing other clickable elements near the toolbar, add to your Custom CSS:
  ```css
  #al-toolbar { pointer-events: none; }
  #al-toolbar .al-toggle,
  #al-toolbar.al-open .al-overlay { pointer-events: auto; }
  ```
  This makes the nav click-through by default and only interactive where it should be.

- **The horizontal slide lives on `.al-slide`, not `#al-toolbar` itself**, on purpose. `#al-toolbar`'s own `transform` is already used for vertical positioning (`.al-vert-top/middle/bottom`), and CSS can't cleanly combine two independent `transform` rules on the same element. A CSS custom-property split (e.g. `translate(var(--x), var(--y))`) was tried and rejected — unregistered custom properties aren't reliably treated as animatable across browsers, so the slide didn't transition consistently. If you need to override the slide distance or direction, target `.al-slide`'s `transform`, not `#al-toolbar`'s.

- **The toggle button is deliberately attached to the panel** (it lives inside `.al-slide`, so it slides with the panel and ends up on the panel's open edge) rather than staying static like some other toolbars (e.g. Pojo's, which is a separate sibling element that never moves). This was a conscious design decision — don't "fix" it by giving the toggle its own counter-transform to keep it static; that was tried and explicitly reverted.

- Custom CSS is stripped with `wp_strip_all_tags()` on save and again before output, and rendered in the form with `esc_textarea()`. Editing it requires the `edit_css` capability, the same one core uses for Additional CSS. On multisite (non-super-admins) or with `DISALLOW_UNFILTERED_HTML`, the field is read-only and the saved CSS is kept.

## Architecture notes for future developers

- **Single-file-per-concern.** `a11y-lite.php` is the frontend/bootstrap (singleton `Accessibility_Lite`); `admin.php` (`Accessibility_Lite_Admin`) is only loaded `if ( is_admin() )`. No separate includes for CSS/JS logic — everything frontend-facing is the two static asset files.
- **No database access beyond the Options API.** Settings are one array under `al_a11y_options`, read via `wp_parse_args()` against defaults in `Accessibility_Lite::options()`. There's no custom table, no direct `$wpdb` usage anywhere.
- **Toolbar markup is printed twice on purpose**, on both `wp_body_open` (priority 1) and `wp_footer` (priority 1), guarded by a static `$done` flag so it only actually prints once. `wp_body_open` is opt-in per theme — themes that never call it would otherwise never render the toolbar, hence the `wp_footer` fallback. `assets/js/a11y.js` accounts for this: it waits for `DOMContentLoaded` rather than assuming the toolbar already exists in the DOM at script-parse time.
- **Icon SVGs are inline path data in PHP** (`Accessibility_Lite::icons()`), not separate `.svg` files — keeps the whole plugin to a handful of HTTP requests. **Never edit these path strings with `sed`.** A bad `sed -i 's/1.2.2/1.2.3/g'`-style version-bump command once matched digits inside the path data itself (SVG paths are dense with plain numbers) and silently corrupted two icons — it shipped and wasn't caught until a visual check. If you need to touch a path string, use an exact-match editor/string replace, never a regex substitution that isn't scoped to a clearly unique, non-numeric anchor.
- **No jQuery dependency, anywhere in the plugin.** `assets/js/a11y.js` is vanilla JS. Keep it that way — it's both the main size-savings pitch versus the Pojo/Elementor toolbar it replaces, and it means the plugin is unaffected by any future change to whether WordPress core ships jQuery by default.
- **CSS sanitization functions used**: `sanitize_hex_color()` for the two color pickers, `sanitize_html_class()` for the skip-link target ID, `wp_strip_all_tags()` for Custom CSS. Follow the same pattern (dedicated `sanitize_*` per field, not a blanket `sanitize_text_field()`) if you add new settings.
- **Numeric settings**: use `intval()` + explicit `min()`/`max()` clamping, not `absint()`. `absint()` flips negative input to positive instead of clamping it to a floor — that was a real bug here (a negative `save_exp` silently became a large positive number). If a field should never go below 0, clamp it; don't rely on `absint()` to do that for you.

## Auto-update mechanism

Updates ship via [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (bundled in `plugin-update-checker/`), pointed at this repo's GitHub Releases (`enableReleaseAssets()` in `a11y-lite.php`). To publish a new version:

1. Bump the version in **two places** — the `Version:` header docblock in `a11y-lite.php`, and the `AL_VERSION` constant right below it. They should always match.
2. Commit, tag, and push.
3. Create a GitHub Release for that tag, **and upload a zip of the plugin as a release asset** — `enableReleaseAssets()` means the update checker looks for an attached asset, not GitHub's auto-generated "Source code (zip)" link. The zip's top-level folder should be the plugin folder itself (matching the repo-root-as-plugin-root layout below).
4. Sites running the plugin will see the update in their normal WordPress admin within a few hours (or immediately if an admin visits Plugins → Add New → check for updates).

## Repo layout

The repo root **is** the plugin root — `a11y-lite.php` sits directly at the top level, not inside a subfolder. This is deliberate: it means `git archive` / a GitHub release zip can be extracted straight into `wp-content/plugins/<any-name>/` with no restructuring, which is what the release process above assumes.

```
a11y-lite.php          # bootstrap + frontend (Accessibility_Lite)
admin.php               # settings page (Accessibility_Lite_Admin), loaded only in wp-admin
uninstall.php           # deletes the al_a11y_options option on plugin deletion
assets/css/a11y.css     # ~3.5 KB, all toolbar + feature styles
assets/js/a11y.js       # ~3 KB, vanilla JS, no dependencies
plugin-update-checker/  # bundled third-party library (YahnisElsts), do not edit
```

## Security notes

- **Updates are the highest-risk path.** Every site installs whatever is published as a release on this repo, with no signature check, so write access to the repo amounts to code execution on all of those sites. Keep 2FA on every account with write access, and protect `main` and release tags.
- **Keep the `Update URI` header.** It stops WordPress from offering a same-named plugin from WordPress.org as an update to this one. It requires WP 5.8+ and is ignored on older versions.
- **Don't trust stored options at output.** `register_setting()` sanitization only runs through wp-admin, so WP-CLI, imports and DB edits bypass it. `Accessibility_Lite::options()` re-validates types and values. Add new settings there as well as in `sanitize()`.
- **`sanitize()` must be idempotent.** WordPress runs it twice the first time the option is saved, so it can receive its own output.
- **Treat `localStorage` as untrusted input** in `assets/js/a11y.js`: check values against a whitelist before using them in selectors or class names.

## Compatibility

No use of deprecated or removed PHP/WordPress APIs as of PHP 8.x / WP 6.x — the plugin sticks to long-stable Core APIs only (Settings API, enqueue API, Options API, `wp_body_open`/`wp_footer`). No direct database access. All output is escaped (`esc_attr`/`esc_html`/`esc_url`/`esc_textarea`). The `Requires PHP: 7.0` header is intentionally conservative for broad install compatibility, not a sign the code needs anything PHP-7-specific — it runs cleanly on current PHP 8.x too.

The one external dependency is Plugin Update Checker, a widely-used third-party library — keep it updated periodically by pulling the latest release from [its repo](https://github.com/YahnisElsts/plugin-update-checker) if WordPress core ever changes how the update transient / admin UI it hooks into works.
