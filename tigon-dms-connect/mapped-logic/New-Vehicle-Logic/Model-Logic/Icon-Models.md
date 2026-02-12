# Icon Models - Complete Database_Object Mapping Per Model

Source: `New_Cart_Converter.php`

All Icon models inherit from `../Manufacturer-Logic/Icon.md` which inherits from `../Global-New-Logic.md`.
Below is the COMPLETE per-model mapping. Every meta key is listed with either the model-specific value or `inherit`.

Note: Icon has three distinct product lines:
- **C-Series** (Commercial): Brown seats, White only, many utility beds, NOT street legal, AGM battery
- **G-Series** (Gas): GAS powered (not electric), Black seats, limited colors, street legal
- **i-Series** (Consumer Electric): 2 Tone seats, full color palette, street legal, AGM battery

---

## Shared postmeta Keys (All Icon Models)

The following postmeta keys are `inherit` for ALL Icon models (from Global and Manufacturer levels). They are listed once here to avoid repetition in each model section:

### posts (wp_posts) - Shared

| Column | Value |
|---|---|
| `ID` | inherit |
| `post_title` | `ICON(R) {MODEL} {Color} In {City} {State}` (model name varies) |
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

### postmeta - WooCommerce - Shared

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
| `_product_attributes` | See Attributes per model |
| `_thumbnail_id` | inherit |
| `_product_image_gallery` | inherit |
| `_regular_price` | Model-specific (see below) |
| `_price` | inherit |

### postmeta - Yoast SEO - Shared (All Inherit)

| Meta Key | Value |
|---|---|
| `_yoast_wpseo_title` | inherit |
| `_yoast_wpseo_metadesc` | inherit |
| `_yoast_wpseo_primary_product_cat` | inherit |
| `_yoast_wpseo_primary_location` | inherit |
| `_yoast_wpseo_primary_models` | inherit |
| `_yoast_wpseo_primary_added-features` | inherit |
| `_yoast_wpseo_is_cornerstone` | inherit |
| `_yoast_wpseo_focus_kw` | inherit |
| `_yoast_wpseo_focus_keywords` | inherit |
| `_yoast_wpseo_bctitle` | inherit |
| `_yoast_wpseo_opengraph-title` | inherit |
| `_yoast_wpseo_opengraph-description` | inherit |
| `_yoast_wpseo_opengraph-image-id` | inherit |
| `_yoast_wpseo_opengraph-image` | inherit |
| `_yoast_wpseo_twitter-image-id` | inherit |
| `_yoast_wpseo_twitter-image` | inherit |

### postmeta - Product Tabs - Shared

| Meta Key | Value |
|---|---|
| `_yikes_woo_products_tabs` | inherit (no Icon-specific tabs defined) |

### postmeta - Custom Product Add-Ons - Shared

| Meta Key | Value |
|---|---|
| `wcpa_exclude_global_forms` | inherit |
| `_wcpa_product_meta` | `ICON(R) {Model} Add Ons` (model name varies) |

### postmeta - Google for WooCommerce - Shared

| Meta Key | Value |
|---|---|
| `_wc_gla_mpn` | inherit |
| `_wc_gla_condition` | inherit |
| `_wc_gla_brand` | inherit (`ICON(R)`) |
| `_wc_gla_color` | inherit |
| `_wc_gla_pattern` | Model-specific (see below) |
| `_wc_gla_gender` | inherit |
| `_wc_gla_sizeSystem` | inherit |
| `_wc_gla_adult` | inherit |

### postmeta - Pinterest for WooCommerce - Shared (All Inherit)

| Meta Key | Value |
|---|---|
| `_wc_pinterest_condition` | inherit |
| `_wc_pinterest_google_product_category` | inherit |

### postmeta - Facebook for WooCommerce - Shared

| Meta Key | Value |
|---|---|
| `_wc_facebook_enhanced_catalog_attributes_brand` | inherit (`ICON(R)`) |
| `_wc_facebook_enhanced_catalog_attributes_color` | inherit |
| `_wc_facebook_enhanced_catalog_attributes_pattern` | Model-specific (see below) |
| `_wc_facebook_enhanced_catalog_attributes_gender` | inherit |
| `_wc_facebook_enhanced_catalog_attributes_age_group` | inherit |
| `_wc_facebook_product_image_source` | inherit |
| `_wc_facebook_sync_enabled` | inherit |
| `_wc_fb_visibility` | inherit |

### postmeta - Tigon Specific - Shared (All Inherit)

| Meta Key | Value |
|---|---|
| `monroney_sticker` | inherit |
| `_monroney_sticker` | inherit |
| `_tigonwm` | inherit |

### Shared Attributes (pa_*) - All Icon

| Attribute | Value |
|---|---|
| `pa_cargo-rack` | inherit (`NO`) |
| `pa_drivetrain` | inherit (`2X4`) |
| `pa_electric-bed-lift` | inherit (`NO`) |
| `pa_fender-flares` | inherit (`YES`) |
| `pa_brush-guard` | inherit (`NO`) |
| `pa_led-accents` | inherit (`NO`) |
| `pa_location` | inherit |
| `pa_receiver-hitch` | inherit (`NO`) |
| `pa_return-policy` | inherit (`90 DAY` + `YES`) |
| `pa_shipping` | inherit |
| `pa_year-of-vehicle` | inherit |

---

## C-Series (Commercial/Golf Course) - NOT Street Legal

All C-Series share: Seat=Brown, Battery=Icon AGM 165Ah 48V, Warranty=3yr vehicle / 2yr battery, Colors=White, Street Legal=NO, Sound=NO, isElectric=true

### Complete C-Series Model Summary Table

| Model | Price | Pass | Tires | Rims | Lifted | Ext Top | Utility Bed |
|---|---|---|---|---|---|---|---|
| C20S | $9,495 | 2 | Street | 10" | No | No | No |
| C20U | $12,995 | 2 | Street | 10" | No | No | **Yes** |
| C20UL | $23,000 | 2 | All-Terrain | 10" | **Yes** | No | **Yes** |
| C20V | $16,560 | 2 | Street | 10" | No | No | **Yes** |
| C30AMB | $17,460 | 2 | Street | 10" | No | No | **Yes** |
| C30AMBL | $18,995 | 2 | All-Terrain | 10" | **Yes** | No | **Yes** |
| C40 | $8,000 | 4 | Street | 10" | No | No | No |
| C40FLS | $11,429 | 4 | All-Terrain | 10" | **Yes** | No | No |
| C40FS | $14,669 | 4 | Street | 10" | No | No | No |
| C40L | $9,000 | 4 | All-Terrain | 12" | **Yes** | No | No |
| C60 | $11,069 | 6 | Street | 10" | No | No | No |
| C60FS | $14,669 | 6 | Street | 10" | No | No | No |
| C60L | $11,519 | 6 | All-Terrain | 10" | **Yes** | No | No |
| C70W | $18,900 | 6 | Street | 10" | No | No | No |
| C80 | $16,050 | **8** | Street | 10" | No | No | No |

### Per-Model C-Series Attributes (pa_*)

Each C-Series model has the following attribute table (values from summary above):

| Attribute | C20S | C20U | C20UL | C20V | C30AMB | C30AMBL | C40 | C40FLS | C40FS | C40L | C60 | C60FS | C60L | C70W | C80 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `pa_battery-type` | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM |
| `pa_battery-warranty` | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 |
| `pa_extended-top` | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO |
| `pa_lift-kit` | NO | NO | 3 INCH | NO | NO | 3 INCH | NO | 3 INCH | NO | 3 INCH | NO | NO | 3 INCH | NO | NO |
| `pa_icon-cart-colors` | White | White | White | White | White | White | White | White | White | White | White | White | White | White | White |
| `pa_icon-seat-colors` | Brown | Brown | Brown | Brown | Brown | Brown | Brown | Brown | Brown | Brown | Brown | Brown | Brown | Brown | Brown |
| `pa_sound-system` | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO |
| `pa_passengers` | 2 SEATER | 2 SEATER | 2 SEATER | 2 SEATER | 2 SEATER | 2 SEATER | 4 SEATER | 4 SEATER | 4 SEATER | 4 SEATER | 6 SEATER | 6 SEATER | 6 SEATER | 6 SEATER | 8 SEATER |
| `pa_rim-size` | 10 INCH | 10 INCH | 10 INCH | 10 INCH | 10 INCH | 10 INCH | 10 INCH | 10 INCH | 10 INCH | 12 INCH | 10 INCH | 10 INCH | 10 INCH | 10 INCH | 10 INCH |
| `pa_street-legal` | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO |
| `pa_tire-profile` | Street | Street | All-Terrain | Street | Street | All-Terrain | Street | All-Terrain | Street | All-Terrain | Street | Street | All-Terrain | Street | Street |
| `pa_vehicle-class` | inherit | inherit | inherit | inherit | inherit | inherit | inherit | inherit | inherit | inherit | inherit | inherit | inherit | inherit | inherit |
| `pa_vehicle-warranty` | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 |

### Per-Model C-Series DMS Defaults

| DMS Field | All C-Series |
|---|---|
| `isElectric` | `true` |
| `isStreetLegal` | `false` |
| `battery.brand` | `Icon` |
| `battery.type` | `AGM` |
| `battery.ampHours` | `165` |
| `battery.packVoltage` | `48` |
| `battery.warrantyLength` | `2` |
| `seatColor` | `Brown` |
| `hasExtendedTop` | `false` |
| `hasSoundSystem` | `false` |
| `hasHitch` | `false` |
| `warrantyLength` | `3` |

Utility bed (`hasUtilityBed`): C20U=true, C20UL=true, C20V=true, C30AMB=true, C30AMBL=true; all others=false

### Per-Model C-Series Google/Facebook Pattern

| Model | `_wc_gla_pattern` / `_wc_facebook_enhanced_catalog_attributes_pattern` |
|---|---|
| C20S | `C20S` |
| C20U | `C20U` |
| C20UL | `C20UL` |
| C20V | `C20V` |
| C30AMB | `C30AMB` |
| C30AMBL | `C30AMBL` |
| C40 | `C40` |
| C40FLS | `C40FLS` |
| C40FS | `C40FS` |
| C40L | `C40L` |
| C60 | `C60` |
| C60FS | `C60FS` |
| C60L | `C60L` |
| C70W | `C70W` |
| C80 | `C80` |

### Per-Model C-Series Add-Ons

| Model | `_wcpa_product_meta` |
|---|---|
| C20S | `ICON(R) C20S Add Ons` |
| C20U | `ICON(R) C20U Add Ons` |
| C20UL | `ICON(R) C20UL Add Ons` |
| C20V | `ICON(R) C20V Add Ons` |
| C30AMB | `ICON(R) C30AMB Add Ons` |
| C30AMBL | `ICON(R) C30AMBL Add Ons` |
| C40 | `ICON(R) C40 Add Ons` |
| C40FLS | `ICON(R) C40FLS Add Ons` |
| C40FS | `ICON(R) C40FS Add Ons` |
| C40L | `ICON(R) C40L Add Ons` |
| C60 | `ICON(R) C60 Add Ons` |
| C60FS | `ICON(R) C60FS Add Ons` |
| C60L | `ICON(R) C60L Add Ons` |
| C70W | `ICON(R) C70W Add Ons` |
| C80 | `ICON(R) C80 Add Ons` |

---

## G-Series (Gas Powered) - Street Legal

All G-Series share: Seat=Black, Engine=Icon EFIA03 EFI 13.5HP 4-stroke, isElectric=false, Warranty=3yr, Colors=Black/Forest/Indigo/White, Street Legal=YES, Sound=NO, Utility=NO, Ext Top=NO

### Complete G-Series Model Summary Table

| Model | Price | Pass | Tires | Rims | Lifted |
|---|---|---|---|---|---|
| G40 | $10,498 | 4 | Street | 10" | No |
| G40L | $11,998 | 4 | All-Terrain | 12" | **Yes** |
| G60 | $12,999 | 6 | Street | 12" | No |
| G60L | $13,999 | 6 | All-Terrain | 12" | **Yes** |

### Per-Model G-Series Attributes (pa_*)

| Attribute | G40 | G40L | G60 | G60L |
|---|---|---|---|---|
| `pa_battery-type` | N/A (gas) | N/A (gas) | N/A (gas) | N/A (gas) |
| `pa_battery-warranty` | N/A (gas) | N/A (gas) | N/A (gas) | N/A (gas) |
| `pa_extended-top` | NO | NO | NO | NO |
| `pa_lift-kit` | NO | 3 INCH | NO | 3 INCH |
| `pa_icon-cart-colors` | `{cartColor}` | `{cartColor}` | `{cartColor}` | `{cartColor}` |
| `pa_icon-seat-colors` | Black | Black | Black | Black |
| `pa_sound-system` | NO | NO | NO | NO |
| `pa_passengers` | 4 SEATER | 4 SEATER | 6 SEATER | 6 SEATER |
| `pa_rim-size` | 10 INCH | 12 INCH | 12 INCH | 12 INCH |
| `pa_street-legal` | YES | YES | YES | YES |
| `pa_tire-profile` | Street | All-Terrain | Street | All-Terrain |
| `pa_vehicle-class` | inherit | inherit | inherit | inherit |
| `pa_vehicle-warranty` | 3 | 3 | 3 | 3 |

### Per-Model G-Series DMS Defaults

| DMS Field | All G-Series |
|---|---|
| `isElectric` | `false` |
| `isStreetLegal` | `true` |
| `engine` | `Icon EFIA03 EFI 13.5HP 4-stroke` |
| `seatColor` | `Black` |
| `hasExtendedTop` | `false` |
| `hasSoundSystem` | `false` |
| `hasHitch` | `false` |
| `warrantyLength` | `3` |

### Per-Model G-Series Google/Facebook Pattern

| Model | `_wc_gla_pattern` / `_wc_facebook_enhanced_catalog_attributes_pattern` |
|---|---|
| G40 | `G40` |
| G40L | `G40L` |
| G60 | `G60` |
| G60L | `G60L` |

### Per-Model G-Series Add-Ons

| Model | `_wcpa_product_meta` |
|---|---|
| G40 | `ICON(R) G40 Add Ons` |
| G40L | `ICON(R) G40L Add Ons` |
| G60 | `ICON(R) G60 Add Ons` |
| G60L | `ICON(R) G60L Add Ons` |

### G-Series Available Colors

Black, Forest, Indigo, White

---

## i-Series (Consumer Electric) - Street Legal

All i-Series share: Battery=Icon AGM 165Ah 48V, isElectric=true, Street Legal=YES, Sound=NO, Hitch=NO, Battery Warranty=2yr

### Complete i-Series Model Summary Table

| Model | Price | Pass | Seat | Tires | Rims | Lifted | Ext Top | Utility | Warranty | Colors |
|---|---|---|---|---|---|---|---|---|---|---|
| i20 | $7,000 | 2 | 2 Tone | Street | 10" | No | No | No | 3yr | Full 13-color |
| i20L | $11,000 | 2 | 2 Tone | All-Terrain | 12" | **Yes** | No | No | 3yr | Full 13-color |
| i20S-HD | $15,599 | 2 | 2 Tone | Street | 10" | No | No | No | 3yr | White |
| i20U-HD | $11,900 | 2 | 2 Tone | Street | 10" | No | No | **Yes** | 3yr | White |
| i20UL-HD | $20,299 | 2 | 2 Tone | All-Terrain | 12" | **Yes** | No | **Yes** | 3yr | White |
| i40 | $6,500 | 4 | 2 Tone | Street | 12" | No | No | No | 3yr | Full 13-color |
| i40-ECO | $7,999 | 4 | 2 Tone | Street | 8" | No | No | No | 3yr | Black, Champagne, Forest, White |
| i40F | $12,298 | 4 | 2 Tone | Street | 10" | No | No | No | 3yr | Full 13-color |
| i40FL | $13,498 | 4 | 2 Tone | All-Terrain | 12" | **Yes** | No | No | 3yr | Full 13-color |
| i40FS-HD | $21,599 | 4 | 2 Tone | Street | 10" | No | No | **Yes** | 3yr | White |
| i40L | $7,000 | 4 | 2 Tone | All-Terrain | 12" | **Yes** | No | No | 3yr | Full 13-color |
| i40L-ECO | $8,999 | 4 | 2 Tone | All-Terrain | 12" | **Yes** | No | No | 3yr | Black, Champagne, Forest, White |
| i60 | $10,000 | 6 | 2 Tone | Street | 10" | No | No | No | 3yr | Full 13-color |
| i60-HD | $20,599 | 6 | Brown | Street | 10" | No | No | **Yes** | 3yr | White |
| i60FS-HD | $22,599 | 6 | Brown | Street | 10" | No | No | **Yes** | 3yr | White |
| i60L | $10,500 | 6 | 2 Tone | All-Terrain | 12" | **Yes** | No | No | 3yr | Full 13-color |

### Per-Model i-Series Attributes (pa_*)

| Attribute | i20 | i20L | i20S-HD | i20U-HD | i20UL-HD | i40 | i40-ECO | i40F | i40FL | i40FS-HD | i40L | i40L-ECO | i60 | i60-HD | i60FS-HD | i60L |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `pa_battery-type` | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM | AGM |
| `pa_battery-warranty` | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 | 2 |
| `pa_extended-top` | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO |
| `pa_lift-kit` | NO | 3 INCH | NO | NO | 3 INCH | NO | NO | NO | 3 INCH | NO | 3 INCH | 3 INCH | NO | NO | NO | 3 INCH |
| `pa_icon-seat-colors` | 2 Tone | 2 Tone | 2 Tone | 2 Tone | 2 Tone | 2 Tone | 2 Tone | 2 Tone | 2 Tone | 2 Tone | 2 Tone | 2 Tone | 2 Tone | Brown | Brown | 2 Tone |
| `pa_sound-system` | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO | NO |
| `pa_passengers` | 2 | 2 | 2 | 2 | 2 | 4 | 4 | 4 | 4 | 4 | 4 | 4 | 6 | 6 | 6 | 6 |
| `pa_rim-size` | 10" | 12" | 10" | 10" | 12" | 12" | 8" | 10" | 12" | 10" | 12" | 12" | 10" | 10" | 10" | 12" |
| `pa_street-legal` | YES | YES | YES | YES | YES | YES | YES | YES | YES | YES | YES | YES | YES | YES | YES | YES |
| `pa_tire-profile` | Street | AT | Street | Street | AT | Street | Street | Street | AT | Street | AT | AT | Street | Street | Street | AT |
| `pa_vehicle-warranty` | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 | 3 |

(AT = All-Terrain)

### Per-Model i-Series DMS Defaults

| DMS Field | All i-Series (except noted) |
|---|---|
| `isElectric` | `true` |
| `isStreetLegal` | `true` |
| `battery.brand` | `Icon` |
| `battery.type` | `AGM` |
| `battery.ampHours` | `165` |
| `battery.packVoltage` | `48` |
| `battery.warrantyLength` | `2` |
| `seatColor` | `2 Tone` (except i60-HD=Brown, i60FS-HD=Brown) |
| `hasExtendedTop` | `false` |
| `hasSoundSystem` | `false` |
| `hasHitch` | `false` |
| `warrantyLength` | `3` |

Utility bed: i20U-HD=true, i20UL-HD=true, i40FS-HD=true, i60-HD=true, i60FS-HD=true; all others=false

### Per-Model i-Series Google/Facebook Pattern

| Model | `_wc_gla_pattern` / `_wc_facebook_enhanced_catalog_attributes_pattern` |
|---|---|
| i20 | `i20` |
| i20L | `i20L` |
| i20S-HD | `i20S-HD` |
| i20U-HD | `i20U-HD` |
| i20UL-HD | `i20UL-HD` |
| i40 | `i40` |
| i40-ECO | `i40-ECO` |
| i40F | `i40F` |
| i40FL | `i40FL` |
| i40FS-HD | `i40FS-HD` |
| i40L | `i40L` |
| i40L-ECO | `i40L-ECO` |
| i60 | `i60` |
| i60-HD | `i60-HD` |
| i60FS-HD | `i60FS-HD` |
| i60L | `i60L` |

### Per-Model i-Series Add-Ons

| Model | `_wcpa_product_meta` |
|---|---|
| i20 | `ICON(R) i20 Add Ons` |
| i20L | `ICON(R) i20L Add Ons` |
| i20S-HD | `ICON(R) i20S-HD Add Ons` |
| i20U-HD | `ICON(R) i20U-HD Add Ons` |
| i20UL-HD | `ICON(R) i20UL-HD Add Ons` |
| i40 | `ICON(R) i40 Add Ons` |
| i40-ECO | `ICON(R) i40-ECO Add Ons` |
| i40F | `ICON(R) i40F Add Ons` |
| i40FL | `ICON(R) i40FL Add Ons` |
| i40FS-HD | `ICON(R) i40FS-HD Add Ons` |
| i40L | `ICON(R) i40L Add Ons` |
| i40L-ECO | `ICON(R) i40L-ECO Add Ons` |
| i60 | `ICON(R) i60 Add Ons` |
| i60-HD | `ICON(R) i60-HD Add Ons` |
| i60FS-HD | `ICON(R) i60FS-HD Add Ons` |
| i60L | `ICON(R) i60L Add Ons` |

---

## Full 13-Color Palette (i-Series)

Black, Caribbean, Champagne, Forest, Indigo, Lime, Orange, Purple, Sangria, Silver, Torch, White, Yellow
