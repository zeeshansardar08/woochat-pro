# Update manifest

WooChat Pro's in-plugin updater (`includes/update-checker.php`) fetches a JSON
manifest from the URL returned by `wcwp_update_api_url()` and offers an update
when its `version` is greater than the installed `WCWP_VERSION`.

## Configuring the URL

The updater resolves the manifest URL in this order:

1. **`WCWP_UPDATE_API_URL` constant** — define in `wp-config.php` to point all
   sites at a real production endpoint:

   ```php
   define( 'WCWP_UPDATE_API_URL', 'https://example.com/woochat-pro/update.json' );
   ```

2. **`.local` short-circuit** — when no constant is set and the site is detected
   as local (`wp_get_environment_type() === 'local'` or hostname contains
   `.local`), the updater fetches `home_url('/woochat-pro-update.json')` so
   developers can drop a manifest at the site root for testing.

3. **`wcwp_update_api_url` filter** — wraps the resolved URL. Site/theme code
   can override or clear it programmatically:

   ```php
   add_filter( 'wcwp_update_api_url', function ( $url ) {
       return 'https://staging.example.com/woochat-pro/update.json';
   } );
   ```

If none of these yield a non-empty URL the updater is a no-op (no HTTP
request, no update offered).

## Manifest schema

See `update-manifest.sample.json`. Required fields:

| Field          | Type   | Notes                                                       |
| -------------- | ------ | ----------------------------------------------------------- |
| `version`      | string | Compared against `WCWP_VERSION` with `version_compare`.     |
| `download_url` | string | Direct ZIP URL passed to WordPress's plugin installer.      |

Optional fields surfaced in the "View details" modal via `plugins_api`:

| Field      | Type   | Notes                                                |
| ---------- | ------ | ---------------------------------------------------- |
| `name`     | string | Defaults to `WooChat Pro`.                           |
| `author`   | string | Defaults to `Zignite`.                               |
| `homepage` | string | Plugin homepage link.                                |
| `requires` | string | Minimum WordPress version.                           |
| `tested`   | string | Tested-up-to WordPress version.                      |
| `sections` | object | HTML strings keyed by tab name (`description`, etc). |

## Caching

Successful responses are cached in the `wcwp_update_info` transient for 6
hours. Clear it (or define `WP_DEBUG`) when iterating on the manifest.
