# Global Used Vehicle Logic

All used vehicles flow through `Abstract_Cart.php` and then `Used/Cart.php` applies overrides.

Source files:
- `src/Abstracts/Abstract_Cart.php` (shared base - same as new)
- `src/Admin/Used/Cart.php` (used-specific overrides)

---

## Key Differences: Used vs New

Used carts go through the **exact same** `Abstract_Cart.convert()` pipeline as new carts.
The differences are:

### 1. SKU Generation (Used/Cart.php `verify_data()`)

- SKU = VIN number (`vinNo`) - highest priority
- Fallback: serial number (`serialNo`)
- If neither exists AND no `pid`: returns a `WP_Error` (cart is rejected)
- No generated/synthetic SKU like new carts have

### 2. No Field Overrides (Used/Cart.php `field_overrides()`)

The `field_overrides()` method is **empty** for used carts. This means:
- `published` stays as `publish` (not `draft` like new)
- `in_stock` stays as-is from DMS data (not forced to `outofstock`)
- `monroney_sticker` stays as-is (not forced to empty)

**Used carts go live immediately. New carts start as drafts.**

### 3. Images ARE Sideloaded

Unlike `New/Cart.php` which sets `images = null`, used carts inherit the default `Abstract_Cart.fetch_images()` which:
- Loops through `cart.imageUrls`
- Sideloads each image from `{file_source}/carts/{filename}`
- Image name format: `{full product name} For Sale{SKU} {index+1}`

### 4. Condition = "used"

In `set_simple_fields()`:
- `condition` = `used` (not `new`)
- This flows to: `_wc_gla_condition`, `_wc_pinterest_condition`

---

## What Stays THE SAME as New

Everything in `Abstract_Cart` that isn't overridden works identically:

### Categories & Tags
- Gets `USED` category and tag (instead of `NEW`)
- Gets `LOCAL USED ACTIVE INVENTORY` (instead of `LOCAL NEW ACTIVE INVENTORY`)
- If rental: `LOCAL USED RENTAL INVENTORY` and `RENTAL`
- All other categories (make, model, seating, lifted, electric/gas, street legal, etc.) are the same logic

### Attributes
- All `pa_*` attributes are computed the same way
- Brush guard, LED accents, colors, etc. follow the same make-based rules
- Battery, tires, lift kit, etc. come from DMS data

### Taxonomies
- Location, manufacturer, model, sound system, vehicle class, drivetrain - all same logic

### Descriptions
- Short description: only generated if product doesn't already exist (`already_exists` flag)
- Long description: same HTML table format
- Meta description: same format

### Simple Fields
- All WooCommerce, Yoast, Google, Facebook, Pinterest fields are identical
- `post_type = product`, `tax_status = taxable`, etc.

---

## Custom Product Options (Add-Ons) - DIFFERENT for Used

New carts get a single model-specific add-on list.
**Used carts** get individual add-ons based on `cart.advertising.cartAddOns` array.

Each add-on is checked individually:

| DMS Add-On Key | WooCommerce Option |
|---|---|
| `Golf cart enclosure 2 passenger 600` | 2 Passenger Golf Cart Enclosure (if 2-seater) |
| `Golf cart enclosure 4 Passenger 800` | 4 Passenger Golf Cart Enclosure (if 4-seater) |
| `Golf cart enclosure 6 passenger 1200` | 6 Passenger Golf Cart Enclosure (if 6-seater) |
| `120 Volt inverter 500` | 120 Volt Inverter |
| `32 inch light bar 220` | 32in LED Light Bar |
| `Cargo caddie 250` | Cargo Caddie |
| `Rear seat cupholders 80` | Rear Seat Cupholders |
| `Upgraded charger 210` | Upgraded Charger |
| `Breezeasy Fan System 400` | Breezeasy 3 Fan System |
| `Golf bag attachment 120` | Golf Bag Attachment |
| `Led light kit 350` | LED Cart Light Kit |
| `Led light kit with signals and horn 495` | LED Cart Light Kit With Signals & Horn |
| `Led under glow 400` | LED Under Glow Lights |
| `Led roof lights 400` | LED Roof Lights |
| `Rear seat kit 385` | Rear Seat Kit |
| `Basic 4 Passenger storage cover 150` | Basic 4 Passenger Storage Cover (if 4-seater) |
| `Premium 4 Passenger storage cover 300` | Premium 4 Passenger Storage Cover (if 4-seater) |
| `Premium 6 Passenger storage cover 385` | Premium 6 Passenger Storage Cover (if 6-seater) |
| `26 in sound bar 500` | 26" Sound Bar |
| `32 in Sound bar 600` | 32" Sound Bar |
| `EcoXGear subwoofer 745` | EcoXGear Subwoofer |
| `New tinted windshield 210` | Tinted Windshield |
| `Hitch 80` + `Hitch 300` + `Hitch 500` | Hitch Bolt On, Basic Hitch Weld On, Premium Hitch Weld On (all 3 if all present) |
| `Seat belts 4 Passenger 160` | Seat Belts 4 Passenger (if 4-seater) |
| `Seat belts 6 Passenger 240` | Seat Belts 6 Passenger (if 6-seater) |
| `Grab bar 85` | Grab Bar |
| `Deluxe Grab Bar 150` | Deluxe Grab Bar |
| `Side mirrors 65` | Side Mirrors |
| `Extended roof 500` | Extended Roof 84" |

---

## Custom Tabs - DIFFERENT for Used

Used carts get:
1. **"TIGON Warranty (USED GOLF CARTS)"** tab (always, first tab)
2. Then the same make/model-specific tabs as new (Denago specs, Evolution specs, etc.)

Note: Used carts do NOT get the manufacturer warranty tab (e.g., "DENAGO Warranty" or "EVolution Warranty").

---

## Database Object Meta Key Mapping

Identical to new vehicles - see `Global-New-Logic.md` section 15.
The only value differences are:
- `_wc_gla_condition` = `used` (not `new`)
- `_wc_pinterest_condition` = `used` (not `new`)
- `post_status` = `publish` (not `draft`)
- `_stock_status` = from DMS data (not forced to `outofstock`)
