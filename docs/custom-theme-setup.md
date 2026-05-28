# Custom Theme Setup — ZoyicaTheme

A step-by-step record of how the `ZoyicaTheme` package was created, wired up, and fully configured to override the default Bagisto shop theme with its own compiled assets.

---

## Overview

Bagisto resolves shop views by checking the active channel's theme path first (`resources/themes/{theme}/views`), then falling back to the core `Shop` package views. The theme also owns its own Vite-compiled CSS/JS assets.

```
packages/Zoyica/ZoyicaTheme/        ← source of truth (edit here)
    src/Resources/assets/           ← full copy of Shop assets (CSS, JS, images, fonts, locales)
    src/Resources/views/            ← only the views you want to override
        ↓  npm run build
public/themes/zoyicatheme/default/build/   ← compiled CSS + JS (served to browser)
        ↓  vendor:publish
resources/themes/zoyicatheme/views/        ← Bagisto reads views from here at runtime
```

---

## Prerequisites

- Bagisto installed and running
- PHP 8.2+, Composer, Node.js

---

## Step 1 — Create the Package

The package was generated using Bagisto's package generator:

```bash
php artisan package:make Zoyica/ZoyicaTheme
```

This scaffolded the full directory structure at `packages/Zoyica/ZoyicaTheme/`.

---

## Step 2 — Fix Composer Autoload (typo correction)

The generator wrote a typo in `composer.json`. Corrected manually:

```json
// composer.json → autoload.psr-4
"Zoyica\\ZoyicaTheme\\": "packages/Zoyica/ZoyicaTheme/src"
```

> The generator had written `ZoyicaTheam` (wrong). Always verify the namespace after generation.

---

## Step 3 — Register the Service Provider

Added to `bootstrap/providers.php`:

```php
Zoyica\ZoyicaTheme\Providers\ZoyicaThemeServiceProvider::class,
```

---

## Step 4 — Update the Service Provider

Updated `ZoyicaThemeServiceProvider::boot()` to publish theme views to the path Bagisto reads at runtime:

```php
// packages/Zoyica/ZoyicaTheme/src/Providers/ZoyicaThemeServiceProvider.php

$this->publishes([
    __DIR__ . '/../Resources/views' => resource_path('themes/zoyicatheme/views'),
], 'zoyicatheme-views');
```

---

## Step 5 — Copy the Complete Shop Assets

> **This is the most important step.** The Bagisto docs explicitly state you must copy the Shop's complete assets into your theme package. If you don't, the Vite manifest will be missing entries the Shop's layout references (favicon, fonts, etc.) and the site will throw a `ViteException`.

```bash
cp -r packages/Webkul/Shop/src/Resources/assets/css     packages/Zoyica/ZoyicaTheme/src/Resources/assets/
cp -r packages/Webkul/Shop/src/Resources/assets/js      packages/Zoyica/ZoyicaTheme/src/Resources/assets/
cp -r packages/Webkul/Shop/src/Resources/assets/images  packages/Zoyica/ZoyicaTheme/src/Resources/assets/
cp -r packages/Webkul/Shop/src/Resources/assets/fonts   packages/Zoyica/ZoyicaTheme/src/Resources/assets/
cp -r packages/Webkul/Shop/src/Resources/assets/locales packages/Zoyica/ZoyicaTheme/src/Resources/assets/
```

Why each directory matters:

| Directory | Why needed |
|-----------|-----------|
| `css/` | Base Shop styles; Tailwind custom components/utilities |
| `js/` | Full Vue app (cart, checkout, components, plugins) |
| `locales/` | VeeValidate locale JSON files — missing this breaks the JS build |
| `images/` | Referenced by `@bagistoVite` in the Shop layout (favicon, placeholders, etc.) |
| `fonts/` | Bagisto icon font (`bagisto-shop.woff`) |

> **On Bagisto updates:** Re-run the copy commands if the Shop's assets change, then rebuild ZoyicaTheme.

---

## Step 6 — Update `package.json`

Match the Shop's runtime dependencies so the JS build has everything it needs:

```json
{
  "private": true,
  "scripts": {
    "dev": "vite",
    "build": "vite build"
  },
  "devDependencies": {
    "autoprefixer": "^10.4.14",
    "axios": "^1.4.0",
    "laravel-vite-plugin": "^0.7.2",
    "postcss": "^8.4.23",
    "tailwindcss": "^3.3.2",
    "vite": "^4.0.0",
    "vue": "^3.2.47"
  },
  "dependencies": {
    "@vee-validate/i18n": "^4.9.1",
    "@vee-validate/rules": "^4.9.1",
    "@vitejs/plugin-vue": "^4.2.3",
    "dotenv": "^16.4.7",
    "flatpickr": "^4.6.13",
    "mitt": "^3.0.1",
    "vee-validate": "^4.9.1",
    "vue-flatpickr": "^2.3.0"
  }
}
```

---

## Step 7 — Configure `tailwind.config.js`

> **Critical path gotcha:** ZoyicaTheme lives at `packages/Zoyica/ZoyicaTheme/`. To reach `packages/Webkul/Shop/` you need **two** `../` hops — one to exit `Zoyica/`, one to exit `packages/Zoyica/`. Using only one `../` silently resolves to `packages/Zoyica/Webkul/` which does not exist. Tailwind skips missing paths without warning, so the Shop's CSS classes simply won't compile.

```js
/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        // ZoyicaTheme's own views (package source + published)
        "./src/Resources/**/*.blade.php",
        "./src/Resources/**/*.js",
        "../../../resources/themes/zoyicatheme/**/*.blade.php",

        // Shop package views — MUST be included so all shop CSS classes compile
        // Note: ../../ not ../ — ZoyicaTheme is nested two levels inside packages/
        "../../Webkul/Shop/src/Resources/**/*.blade.php",
        "../../Webkul/Shop/src/Resources/**/*.js",
    ],

    theme: {
        container: {
            center: true,
            screens: { "2xl": "1440px" },
            padding: { DEFAULT: "90px" },
        },

        // Mirror the Shop's custom breakpoints exactly
        screens: {
            sm: "525px",
            md: "768px",
            lg: "1024px",
            xl: "1240px",
            "2xl": "1440px",
            1180: "1180px",
            1060: "1060px",
            991: "991px",
            868: "868px",
        },

        extend: {
            // Mirror the Shop's custom colors exactly
            colors: {
                navyBlue:    "#060C3B",
                lightOrange: "#F6F2EB",
                darkGreen:   "#40994A",
                darkBlue:    "#0044F2",
                darkPink:    "#F85156",
            },
            fontFamily: {
                poppins:  ["Poppins", "sans-serif"],
                dmserif:  ["DM Serif Display", "serif"],
            },
        },
    },

    plugins: [],
    safelist: [{ pattern: /icon-/ }],
};
```

---

## Step 8 — Configure the Theme in `config/themes.php`

Add the ZoyicaTheme entry pointing to its **own** build output, and set it as the shop default:

```php
'shop-default' => 'zoyicatheme',

'shop' => [
    'default' => [ /* unchanged */ ],

    'zoyicatheme' => [
        'name'        => 'Zoyica Theme',
        'assets_path' => 'public/themes/zoyicatheme/default',
        'views_path'  => 'resources/themes/zoyicatheme/views',

        'vite' => [
            'hot_file'                 => 'zoyicatheme-default-vite.hot',
            'build_directory'          => 'themes/zoyicatheme/default/build',
            'package_assets_directory' => 'src/Resources/assets',
        ],
    ],
],
```

> `assets_path` must match the parent of `build_directory`. Never point it to the default shop's build — the manifests are different and `@bagistoVite` will fail to resolve assets.

---

## Step 9 — Register in `config/bagisto-vite.php`

```php
'zoyicatheme' => [
    'hot_file'                 => 'zoyicatheme-default-vite.hot',
    'build_directory'          => 'themes/zoyicatheme/default/build',
    'package_assets_directory' => 'src/Resources/assets',
],
```

---

## Step 10 — Create Custom View Overrides

Only views that exist in the theme's package override the defaults. All others fall through to the core Shop package.

**Footer override** created at:

```
packages/Zoyica/ZoyicaTheme/src/Resources/views/components/layouts/footer/index.blade.php
```

Key UI changes from the default footer:
- Background: `bg-lightOrange` → `bg-navyBlue` (dark navy `#060C3B`)
- Added brand name section (store title from config)
- Footer links: `text-white/60 hover:text-white` + underline transition
- Mobile accordion: `bg-white/10` glass styling
- Newsletter input: `bg-white/10 border-white/20` glass style
- Subscribe button: orange `bg-[#F97316]` with `hover:bg-orange-400` transition
- Bottom bar: `border-t border-white/10` separator
- Copyright text: `text-white/40`

---

## Step 11 — Update Channel Theme in the Database

`config/themes.php → shop-default` is only a fallback for channels that have no theme set. The active channel's own `theme` column takes priority. Update via tinker:

```bash
php artisan tinker --execute="\Webkul\Core\Models\Channel::where('id',1)->update(['theme' => 'zoyicatheme']);"
```

> Also configurable via **Admin → Settings → Channels → Edit → Theme**.

---

## Step 12 — Run All Setup Commands

```bash
# 1. Regenerate autoload after namespace fix
composer dump-autoload

# 2. Install npm dependencies
cd packages/Zoyica/ZoyicaTheme
npm install

# 3. Build theme assets (CSS + JS)
npm run build

# 4. Go back to project root
cd ../../..

# 5. Publish theme views to resources/themes/zoyicatheme/views
php artisan vendor:publish \
  --provider="Zoyica\ZoyicaTheme\Providers\ZoyicaThemeServiceProvider" \
  --tag=zoyicatheme-views

# 6. Clear all caches
php artisan optimize:clear
```

---

## Development Workflow (Option B — Direct Package Development)

Edit views or assets in the package source, then rebuild and republish:

```bash
# After editing a view:
php artisan vendor:publish \
  --provider="Zoyica\ZoyicaTheme\Providers\ZoyicaThemeServiceProvider" \
  --tag=zoyicatheme-views --force \
  && php artisan view:clear

# After editing CSS or JS:
cd packages/Zoyica/ZoyicaTheme
npm run build
cd ../../..
php artisan view:clear
```

> After a CSS/JS build the manifest hash changes. Do a **hard refresh** (`Ctrl+Shift+R`) in the browser to bypass the old cached file.

---

## File Reference

| File | Purpose |
|------|---------|
| `packages/Zoyica/ZoyicaTheme/src/Providers/ZoyicaThemeServiceProvider.php` | Registers routes, views, translations; publishes theme views |
| `packages/Zoyica/ZoyicaTheme/src/Resources/views/` | Package view source — **edit here** |
| `packages/Zoyica/ZoyicaTheme/src/Resources/assets/` | Package asset source — full copy of Shop assets |
| `packages/Zoyica/ZoyicaTheme/tailwind.config.js` | Tailwind config scanning both ZoyicaTheme and Shop views |
| `packages/Zoyica/ZoyicaTheme/package.json` | npm dependencies matching Shop's runtime deps |
| `packages/Zoyica/ZoyicaTheme/vite.config.js` | Vite build config outputting to `public/themes/zoyicatheme/default/build/` |
| `public/themes/zoyicatheme/default/build/` | Compiled assets — served to browser |
| `resources/themes/zoyicatheme/views/` | Published views — read by Bagisto at runtime |
| `config/themes.php` | Theme registry; `shop-default` sets the fallback theme |
| `config/bagisto-vite.php` | Vite manifest registry for `@bagistoVite` helper |
| `bootstrap/providers.php` | Service provider registration |
| `composer.json → autoload.psr-4` | Namespace → path mapping |

---

## How View Override Resolution Works

```
Request hits storefront
        ↓
Theme middleware reads channel.theme from DB  →  'zoyicatheme'
        ↓
@bagistoVite reads manifest from:
  public/themes/zoyicatheme/default/build/manifest.json
        ↓
Looks for view in:  resources/themes/zoyicatheme/views/
        ↓ (found)     ↓ (not found — falls through to)
Renders ZoyicaTheme   Core Shop package views
view                  (packages/Webkul/Shop/src/Resources/views/)
```

Only views that exist in the theme path are overridden. All other shop views come from the core package automatically.

---

## Adding More View Overrides

Mirror the core view path under the package views directory:

```
Core path:
  packages/Webkul/Shop/src/Resources/views/home/index.blade.php

Override path in this package:
  packages/Zoyica/ZoyicaTheme/src/Resources/views/home/index.blade.php
```

Then republish and clear:

```bash
php artisan vendor:publish \
  --provider="Zoyica\ZoyicaTheme\Providers\ZoyicaThemeServiceProvider" \
  --tag=zoyicatheme-views --force \
  && php artisan view:clear
```

---

## Common Pitfalls

| Pitfall | Cause | Fix |
|---------|-------|-----|
| `ViteException: Unable to locate file in manifest` | `assets_path` pointed to default shop build instead of ZoyicaTheme's own build | Set `assets_path` to `public/themes/zoyicatheme/default` and build ZoyicaTheme assets |
| Header/page CSS missing after switching theme | Tailwind `content` path used `../Webkul/` (wrong, resolves to `Zoyica/Webkul/`) | Use `../../Webkul/` — ZoyicaTheme is two directory levels inside `packages/` |
| JS build fails with `Could not resolve "mitt"` | Shop assets were imported cross-package; dependencies resolved from wrong directory | Copy Shop's entire assets directory into ZoyicaTheme; build locally |
| JS build fails with `Could not resolve "../../locales/ro.json"` | `locales/` directory not copied from Shop assets | `cp -r packages/Webkul/Shop/src/Resources/assets/locales packages/Zoyica/ZoyicaTheme/src/Resources/assets/` |
| Theme changes not reflected | Browser cached old hashed CSS file | Hard refresh (`Ctrl+Shift+R`) after every `npm run build` |
| View changes not reflected | Published views in `resources/themes/zoyicatheme/views/` are stale | Re-run `vendor:publish --force && php artisan view:clear` |
| Channel still uses old theme | `shop-default` config is only a fallback; DB value wins | Update channel via Admin panel or tinker |
