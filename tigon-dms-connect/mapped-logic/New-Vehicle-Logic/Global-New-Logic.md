# Global New Vehicle Logic

All new vehicles flow through `Abstract_Cart.php` and then `New/Cart.php` applies overrides.

Source files:
- `src/Abstracts/Abstract_Cart.php` (shared base)
- `src/Admin/New/Cart.php` (new-specific overrides)
- `src/Admin/New/New_Cart_Converter.php` (per-model template defaults)

---

## 1. SKU Generation (New/Cart.php `verify_data()`)

**If cart IS in stock AND NOT in boneyard:**
- Use VIN (`vinNo`) as SKU (highest priority)
- Else use serial number (`serialNo`) as SKU
- Flag `not_default = true`

**If cart is NOT in stock OR is in boneyard (template/placeholder cart):**
- SKU = first 3 chars of make + first 3 chars of model + first 3 chars of cartColor + first 3 chars of seatColor + first 3 chars of location city
- All uppercased, whitespace stripped
- `_id` is set to null (no monroney sticker generated)
- `in_stock` forced to `outofstock`
- `monroney_sticker` forced to `[pdf-embedder url=""]`

---

## 2. Product Method (create / update / delete)

Determined in `set_simple_fields()`:

| Condition | Method |
|---|---|
| `isInStock=true` AND `isInBoneyard=false` AND `needOnWebsite=true` AND product already exists | `update` |
| `isInStock=true` AND `isInBoneyard=false` AND `needOnWebsite=true` AND product does NOT exist | `create` |
| Any other combination | `delete` |

---

## 3. Name Generation (Abstract_Cart `create_name()`)

Format: `{MAKE(R)} {MODEL} {Color} In {City} {State}`

Example: `DENAGO(R) NOMAD XL Blue In Hatfield PA`

- Make gets (R) symbol appended
- Make and model are UPPERCASED
- Color is ucwords()
- If model == "Other", it becomes "Golf Cart"

---

## 4. Slug Generation (New/Cart.php `create_slug()`)

**If DMS provides `advertising.websiteUrl`:**
- Slug = last path segment of the URL
- `+` replaced with `-plus-`

**If no DMS URL:**
- Format: `{make}-{model}-{color}-seat-{seatColor}-{city}-{state}`
- All lowercased, spaces replaced with hyphens

---

## 5. Images (New/Cart.php `fetch_images()`)

New carts set `images = null` (placeholder only).
Image name: `woocommerce-placeholder`

This means new template carts do NOT sideload images. Only used/in-stock carts get real images via `Abstract_Cart.fetch_images()`.

---

## 6. Monroney Sticker (Abstract_Cart `fetch_monroney()`)

- Only generated if `_id` is set (i.e., real inventory, not a template)
- Sideloads PDF from `{file_source}/cart-window-stickers/{_id}.pdf`
- Stored as shortcode: `[pdf-embedder url="{uploaded_url}"]`
- For template carts (not_default=false): forced to `[pdf-embedder url=""]`

---

## 7. Categories & Tags (Abstract_Cart `attach_categories_tags()`)

All new vehicles get:

| Category | Condition |
|---|---|
| `{MAKE(R)}` | Always (brand category) |
| `{MAKE(R)} {MODEL}` | If category exists in system |
| `{N} SEATER` | Based on passenger count |
| `LIFTED` | If `isLifted=true` |
| `NEW` | Always (not used) |
| `ELECTRIC` | If `isElectric=true` |
| `GAS` | If `isElectric=false` |
| `ZERO EMISSION VEHICLES (ZEVS)` | If electric |
| `LITHIUM` | If electric + battery type = Lithium |
| `LEAD-ACID` | If electric + battery type = Lead |
| `{voltage} VOLT` | If electric (e.g., "48 VOLT") |
| `STREET LEGAL` | If electric + street legal |
| `NEIGHBORHOOD ELECTRIC VEHICLES (NEVS)` | If electric + street legal |
| `BATTERY ELECTRIC VEHICLES (BEVS)` | If electric + street legal |
| `LOW SPEED VEHICLES (LSVS)` | If electric + street legal |
| `MEDIUM SPEED VEHICLES (MSVS)` | If electric + street legal |
| `PERSONAL TRANSPORTATION VEHICLES (PTVS)` | If gas |
| `LOCAL NEW ACTIVE INVENTORY` | Always (new + not rental) |
| `LOCAL NEW RENTAL INVENTORY` | If `isRental=true` |
| `RENTAL` | If `isRental=true` |
| `GOLF CARTS` | Always |
| `2X4` | Always (drivetrain) |
| `TIGON DEALERSHIP` | Always |
| `TIGON GOLF CARTS {CITY} {STATE}` | Always (location-specific) |

Tags added for all new vehicles:
- `{MAKE(R)}`, `{MAKE(R)} {MODEL}`, `{MAKE(R) MODEL COLOR}`, full name
- `{COLOR}`, `{N} SEATS`
- `LIFTED` or `NON LIFTED`
- `NEW`
- `{CITY}`, `{CITY STATE}`, `{STATE}`, location dealership tags
- `GOLF CART`, `ELECTRIC` or `GAS`
- `NEV`, `LSV`, `MSV`, `STREET LEGAL` (if applicable)
- `TIGON`, `TIGON GOLF CARTS`

---

## 8. Product Attributes (Abstract_Cart `attach_attributes()`)

All new vehicles get these WooCommerce product attributes (`pa_*`):

| Attribute Slug | Value Source |
|---|---|
| `pa_battery-type` | `battery.type` (only if electric) |
| `pa_battery-warranty` | `battery.warrantyLength` (only if electric) |
| `pa_brush-guard` | YES for Denago/Evolution, NO for all others |
| `pa_cargo-rack` | NO (always) |
| `pa_drivetrain` | 2X4 (always) |
| `pa_electric-bed-lift` | NO (always) |
| `pa_extended-top` | YES/NO from `hasExtendedTop` |
| `pa_fender-flares` | YES (always) |
| `pa_led-accents` | YES + LIGHT BAR for Denago, NO for all others |
| `pa_lift-kit` | 3 INCH if lifted, NO if not |
| `pa_location` | City+State from location data |
| `pa_{make}-cart-colors` | Cart color (make-specific palette for known brands) |
| `pa_{make}-seat-colors` | Seat color (make-specific palette for known brands) |
| `pa_cart-color` | Cart color (fallback for unknown brands) |
| `pa_seat-color` | Seat color (fallback for unknown brands) |
| `pa_sound-system` | Make-specific sound system name, or YES |
| `pa_passengers` | N SEATER |
| `pa_receiver-hitch` | NO (always) |
| `pa_return-policy` | 90 DAY + YES |
| `pa_rim-size` | `tireRimSize` INCH |
| `pa_shipping` | 1 TO 3 DAYS LOCAL, 3 TO 7 DAYS OTR, 5 TO 9 DAYS NATIONAL |
| `pa_street-legal` | YES/NO from `title.isStreetLegal` |
| `pa_tire-profile` | `tireType` (Street Tire, All-Terrain, etc.) |
| `pa_vehicle-class` | Computed list (Golf Cart, NEV, ZEV, LSV, MSV, PTV, UTV) |
| `pa_vehicle-warranty` | `warrantyLength` |
| `pa_year-of-vehicle` | `cartType.year` |

Known brands with dedicated color palettes:
`bintelli, club-car, denago, epic, evolution, ezgo, icon, navitas, polaris, royal-ev, star-ev, swift, tomberlin, yamaha`

---

## 9. Taxonomies (Abstract_Cart `attach_taxonomies()`)

| Taxonomy | Value |
|---|---|
| Location (city) | Location city term ID |
| Location (state) | Location state term ID |
| Manufacturer | Make-specific term (with name aliases: Swift EV -> SWIFT, Star -> STAR EV, etc.) |
| Model | `{MAKE} {MODEL}` term (with aliases for DS, Precedent, 4L, 6L, Drive 2, Star, EZGO) |
| Sound System | `{MAKE} SOUND SYSTEM` term |
| Added Features | From `addedFeatures` flags: static stock, brush guard, clay basket, fender flares, LEDs, light bar, under glow, lift kit, tow hitch, stock options |
| Vehicle Class | Golf Cart + NEV/ZEV/LSV/MSV/PTV/UTV based on electric/street legal/utility |
| Inventory Status | LOCAL NEW ACTIVE INVENTORY or LOCAL NEW RENTAL INVENTORY |
| Drivetrain | From `driveTrain` field, defaults to 2X4 |

---

## 10. Custom Product Options / Add-Ons (Abstract_Cart `attach_custom_options()`)

**New vehicles** get a single model-specific add-on list:
- Format: `{Make} {Model} Add Ons`
- Special formatting per brand:
  - Denago: `Denago(R) EV {Model} Add Ons`
  - Epic: `EPIC(R) {Model} Add Ons`
  - Evolution: `EVolution(R) {Model} Add Ons` (D5 models get hyphenated: `D5-Maverick`)
  - Icon: `ICON(R) {Model} Add Ons`
  - Swift EV: `SWIFT EV(R) {Model} Add Ons`

---

## 11. Custom Tabs (Abstract_Cart `attach_custom_tabs()`)

New vehicles get tabs based on make and model. See Manufacturer-Logic and Model-Logic files for specifics per brand.

---

## 12. Descriptions (Abstract_Cart `generate_descriptions()`)

**Meta Description:**
`{MAKE MODEL COLOR} At TIGON Golf Carts in {Location}. Call Now {Phone} Get 0% Financing, and Shipping Options Today!`

**Short Description (only generated for new products, not updates):**
- Auto-generated with randomized adjectives and intro/outro sentences
- Contains make/model hyperlinks to tigongolfcarts.com
- Includes voltage specs for electric, HP specs for gas, utility bed callout

**Long Description:**
- HTML table with: Make, Model, Year, Street Legal, Color, Seat Color, Tires, Rims
- Battery/engine specs section
- Additional features list from cart add-ons
- "CALL TIGON GOLF CARTS" link shortcode with location phone number

---

## 13. Simple/Static Fields (Abstract_Cart `set_simple_fields()`)

These are the same for ALL vehicles (new and used):

| Field | Value |
|---|---|
| `post_type` | product |
| `published` | publish |
| `comment_status` | open |
| `ping_status` | closed |
| `menu_order` | 0 |
| `comment_count` | 0 |
| `post_author` | 3 |
| `price` | `retailPrice` from DMS |
| `sale_price` | `salePrice` from DMS |
| `tax_status` | taxable |
| `tax_class` | standard |
| `manage_stock` | no |
| `backorders_allowed` | no |
| `sold_individually` | no |
| `is_virtual` | no |
| `downloadable` | no |
| `download_limit` | -1 |
| `download_expiry` | -1 |
| `bit_is_cornerstone` | 1 |
| `attr_exclude_global_forms` | 1 |
| `stock` | 10000 |
| `condition` | new |
| `google_brand` | MAKE(R) uppercased |
| `google_color` | cart color uppercased |
| `google_pattern` | model name |
| `google_size_system` | US |
| `gender` | unisex |
| `adult_content` | no |
| `age_group` | all ages |
| `google_category` | Vehicles & Parts > Vehicles > Motor Vehicles > Golf Carts |
| `product_image_source` | product |
| `facebook_sync` | yes |
| `facebook_visibility` | yes |
| `monroney_container_id` | field_66e3332abf481 |
| Shipping class term | 665 |

---

## 14. New-Specific Overrides (New/Cart.php `field_overrides()`)

After all the above, `New/Cart.php` forces:
- `published = 'draft'` (new carts always start as draft)
- If `not_default = false` (template/no serial): `in_stock = 'outofstock'`, `monroney_sticker = '[pdf-embedder url=""]'`

---

## 15. Database Object Meta Key Mapping

All the above fields map to these wp_postmeta keys:

| Property | Meta Key |
|---|---|
| sku | `_sku` |
| tax_status | `_tax_status` |
| tax_class | `_tax_class` |
| manage_stock | `_manage_stock` |
| backorders_allowed | `_backorders` |
| sold_individually | `_sold_individually` |
| is_virtual | `_virtual` |
| downloadable | `_downloadable` |
| download_limit | `_download_limit` |
| download_expiry | `_download_expiry` |
| stock | `_stock` |
| in_stock | `_stock_status` |
| gui | `_global_unique_id` |
| attributes | `_product_attributes` |
| featured image | `_thumbnail_id` |
| gallery images | `_product_image_gallery` |
| price | `_regular_price` |
| sale_price | `_price` |
| yoast_seo_title | `_yoast_wpseo_title` |
| meta_description | `_yoast_wpseo_metadesc` |
| primary_category | `_yoast_wpseo_primary_product_cat` |
| primary_location | `_yoast_wpseo_primary_location` |
| primary_model | `_yoast_wpseo_primary_models` |
| name | `_yoast_wpseo_focus_kw`, `_yoast_wpseo_focus_keywords`, `_yoast_wpseo_bctitle`, `_yoast_wpseo_opengraph-title` |
| custom_tabs | `_yikes_woo_products_tabs` |
| custom_product_options | `_wcpa_product_meta` |
| condition | `_wc_gla_condition`, `_wc_pinterest_condition` |
| google_brand | `_wc_gla_brand`, `_wc_facebook_enhanced_catalog_attributes_brand` |
| google_color | `_wc_gla_color`, `_wc_facebook_enhanced_catalog_attributes_color` |
| google_pattern | `_wc_gla_pattern`, `_wc_facebook_enhanced_catalog_attributes_pattern` |
| gender | `_wc_gla_gender`, `_wc_facebook_enhanced_catalog_attributes_gender` |
| google_category | `_wc_pinterest_google_product_category` |
| monroney_sticker | `monroney_sticker` |
| monroney_container_id | `_monroney_sticker` |
| tigonwm_text | `_tigonwm` |
