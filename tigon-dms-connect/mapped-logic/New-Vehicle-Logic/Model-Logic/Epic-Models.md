# Epic Models - Complete Database_Object Mapping Per Model

Source: `Abstract_Cart.php`, `New_Cart_Converter.php`

All Epic models inherit from `../Manufacturer-Logic/Epic.md` which inherits from `../Global-New-Logic.md`.
Epic HAS dedicated color palette attributes (`pa_epic-cart-colors` / `pa_epic-seat-colors`).

**Common across all Epic models:**
- Electric, 36V AGM battery (Leoch, 210 Ah), 2-year battery warranty
- 3-year vehicle warranty
- Street legal
- Black seats, NO brush guard, NO LED accents, NO hitch
- 8-color palette: Black, Charcoal Gray, Dark Blue, Light Blue, Matte Black, Red Pearl, Silver, White Pearl

**Model differences:**
- **E40L** — 4 Passenger, Lifted (14" All-Terrain), HAS sound system, $14,500
- **E60** — 6 Passenger, Non-lifted (12" Street Tire), NO sound system, $14,500
- **E60L** — 6 Passenger, Lifted (14" All-Terrain), NO sound system, $15,500

---

## Epic E40L

> **4 Passenger, Lifted, Sound System**

### posts (wp_posts)

| Column | Value |
|---|---|
| `ID` | inherit |
| `post_title` | `EPIC(R) E40L {Color} In {City} {State}` |
| `post_excerpt` | inherit |
| `post_content` | inherit |
| `post_status` | inherit |
| `comment_status` | inherit |
| `ping_status` | inherit |
| `menu_order` | inherit |
| `post_type` | inherit |
| `comment_count` | inherit |
| `post_author` | inherit |
| `post_name` | inherit |

### postmeta - WooCommerce

| Meta Key | Value |
|---|---|
| `_sku` | inherit |
| `_tax_status` | inherit |
| `_tax_class` | inherit |
| `_manage_stock` | inherit |
| `_backorders` | inherit |
| `_sold_individually` | inherit |
| `_virtual` | inherit |
| `_downloadable` | inherit |
| `_download_limit` | inherit |
| `_download_expiry` | inherit |
| `_stock` | inherit |
| `_stock_status` | inherit |
| `_global_unique_id` | inherit |
| `_product_attributes` | See Attributes below |
| `_thumbnail_id` | inherit |
| `_product_image_gallery` | inherit |
| `_regular_price` | `14500` |
| `_price` | inherit |

### postmeta - Yoast SEO

All values inherit.

### postmeta - Product Tabs

| Meta Key | Value |
|---|---|
| `_yikes_woo_products_tabs` | **No Epic-specific tabs.** No tabs are injected for Epic models. |

### postmeta - Custom Product Add-Ons

| Meta Key | Value |
|---|---|
| `wcpa_exclude_global_forms` | inherit |
| `_wcpa_product_meta` | `EPIC(R) E40L Add Ons` |

### postmeta - Google for WooCommerce

| Meta Key | Value |
|---|---|
| `_wc_gla_brand` | `EPIC(R)` |
| `_wc_gla_pattern` | `E40L` |
| All other keys | inherit |

### postmeta - Facebook for WooCommerce

| Meta Key | Value |
|---|---|
| `_wc_facebook_enhanced_catalog_attributes_brand` | `EPIC(R)` |
| `_wc_facebook_enhanced_catalog_attributes_pattern` | `E40L` |
| All other keys | inherit |

### postmeta - Pinterest / Tigon Specific

All values inherit.

### Attributes (pa_*)

| Attribute | Value |
|---|---|
| `pa_battery-type` | `AGM` |
| `pa_battery-warranty` | From converter (`2`) |
| `pa_brush-guard` | **NO** |
| `pa_cargo-rack` | inherit (`NO`) |
| `pa_drivetrain` | inherit (`2X4`) |
| `pa_electric-bed-lift` | inherit (`NO`) |
| `pa_extended-top` | `YES` |
| `pa_fender-flares` | inherit (`YES`) |
| `pa_led-accents` | **NO** |
| `pa_lift-kit` | `3 INCH` |
| `pa_location` | inherit |
| `pa_epic-cart-colors` | From 8-color palette |
| `pa_epic-seat-colors` | `Black` |
| `pa_sound-system` | `EPIC(R) SOUND SYSTEM` |
| `pa_passengers` | `4 SEATER` |
| `pa_receiver-hitch` | inherit (`NO`) |
| `pa_return-policy` | inherit (`90 DAY` + `YES`) |
| `pa_rim-size` | `14 INCH` |
| `pa_shipping` | inherit |
| `pa_street-legal` | `YES` |
| `pa_tire-profile` | `All-Terrain` |
| `pa_vehicle-class` | inherit |
| `pa_vehicle-warranty` | `3` |
| `pa_year-of-vehicle` | inherit |

### Converter Defaults

| Field | Value |
|---|---|
| `isElectric` | `true` |
| `battery.brand` | `Leoch` |
| `battery.type` | `AGM` |
| `battery.ampHours` | `210` |
| `battery.packVoltage` | `36` |
| `battery.isDC` | `false` |
| `title.isStreetLegal` | `true` |
| `seatColor` | `Black` |
| `tireType` | `All-Terrain` |
| `tireRimSize` | `14` |
| `isLifted` | `true` |
| `hasSoundSystem` | `true` |
| `hasHitch` | `false` |
| `hasExtendedTop` | `true` |
| `passengers` | `4 Passenger` |

### Available Colors

Black, Charcoal Gray, Dark Blue, Light Blue, Matte Black, Red Pearl, Silver, White Pearl

---

## Epic E60

> **6 Passenger, Non-lifted, No sound system**

### posts (wp_posts)

| Column | Value |
|---|---|
| `ID` | inherit |
| `post_title` | `EPIC(R) E60 {Color} In {City} {State}` |
| `post_excerpt` | inherit |
| `post_content` | inherit |
| All other columns | inherit |

### postmeta - Custom Product Add-Ons

| Meta Key | Value |
|---|---|
| `wcpa_exclude_global_forms` | inherit |
| `_wcpa_product_meta` | `EPIC(R) E60 Add Ons` |

### postmeta - Google / Facebook pattern

`E60`

### All other postmeta

Inherit from Epic manufacturer defaults.

### Attributes (pa_*)

| Attribute | Value |
|---|---|
| `pa_extended-top` | `YES` |
| `pa_lift-kit` | `NO` |
| `pa_sound-system` | `NO` |
| `pa_passengers` | `6 SEATER` |
| `pa_rim-size` | `12 INCH` |
| `pa_tire-profile` | `Street Tire` |
| `pa_vehicle-warranty` | `3` |
| All other attributes | Inherit from Epic manufacturer defaults |

### Converter Defaults

| Field | Value |
|---|---|
| `tireType` | `Street Tire` |
| `tireRimSize` | `12` |
| `isLifted` | `false` |
| `hasSoundSystem` | `false` |
| `hasExtendedTop` | `true` |
| `passengers` | `6 Passenger` |
| All other fields | Same as E40L shared defaults |

### Available Colors

Black, Charcoal Gray, Dark Blue, Light Blue, Matte Black, Red Pearl, Silver, White Pearl

---

## Epic E60L

> **6 Passenger, Lifted, No sound system**

### posts (wp_posts)

| Column | Value |
|---|---|
| `ID` | inherit |
| `post_title` | `EPIC(R) E60L {Color} In {City} {State}` |
| `post_excerpt` | inherit |
| `post_content` | inherit |
| All other columns | inherit |

### postmeta - Custom Product Add-Ons

| Meta Key | Value |
|---|---|
| `wcpa_exclude_global_forms` | inherit |
| `_wcpa_product_meta` | `EPIC(R) E60L Add Ons` |

### postmeta - Google / Facebook pattern

`E60L`

### postmeta - WooCommerce (price difference)

| Meta Key | Value |
|---|---|
| `_regular_price` | `15500` |

### All other postmeta

Inherit from Epic manufacturer defaults.

### Attributes (pa_*)

| Attribute | Value |
|---|---|
| `pa_extended-top` | `YES` |
| `pa_lift-kit` | `3 INCH` |
| `pa_sound-system` | `NO` |
| `pa_passengers` | `6 SEATER` |
| `pa_rim-size` | `14 INCH` |
| `pa_tire-profile` | `All-Terrain` |
| `pa_vehicle-warranty` | `3` |
| All other attributes | Inherit from Epic manufacturer defaults |

### Converter Defaults

| Field | Value |
|---|---|
| `tireType` | `All-Terrain` |
| `tireRimSize` | `14` |
| `isLifted` | `true` |
| `hasSoundSystem` | `false` |
| `hasExtendedTop` | `true` |
| `passengers` | `6 Passenger` |
| `retailPrice` | `15500` |
| All other fields | Same as E40L shared defaults |

### Available Colors

Black, Charcoal Gray, Dark Blue, Light Blue, Matte Black, Red Pearl, Silver, White Pearl
