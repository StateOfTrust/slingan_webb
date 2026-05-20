# Board Games theme (Slingan)

We use the **Board Games** theme for the **banner** (headline, intro, main CTA) and the **four coloured promo tiles** on the front page. There is **no WooCommerce** on this site.

A small **must-use plugin** (`slingan-frontpage`) swaps in a front-page template that **does not** call the theme’s product block (the “Från gemenskapen” / shop strip). Sync it with `./scripts/sync-local-mu-plugins.sh`.

## Where to edit (WordPress admin)

In **Utseende → Anpassa**:

| Panel / section | What it controls |
|-----------------|------------------|
| **Frontpage settings → Banner Settings** | Enable banner, all banner text and URLs, images for the four tiles |
| **Frontpage settings** (other sections) | Extra homepage blocks the theme provides |
| **Color settings** | Global / secondary colours, backgrounds (seed sets warmer defaults) |
| **Footer setting** | Footer columns and copyright |
| **General settings** | Logo, tagline visibility, header “top” button |

The CLI seed (`scripts/seed-content.php`) fills **Swedish** banner copy and sensible links to your seeded pages (spelträffar, vad-ar-slingan, join-us, blog, Discord placeholder). Run `./scripts/seed-local-content.sh` again to reset that copy for local dev.
