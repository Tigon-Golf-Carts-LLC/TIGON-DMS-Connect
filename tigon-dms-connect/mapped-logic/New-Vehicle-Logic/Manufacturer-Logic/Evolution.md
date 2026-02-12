# Evolution - New Vehicle Manufacturer Logic

Source: `Abstract_Cart.php`, `New_Cart_Converter.php`

---

## Brand-Specific Overrides

These rules apply to ALL Evolution models when `make = 'Evolution'`:

### Attributes
| Attribute | Value | Source Line |
|---|---|---|
| `pa_brush-guard` | **YES** | Abstract_Cart.php:889 |
| `pa_led-accents` | **NO** | Abstract_Cart.php:947-951 |
| `pa_evolution-cart-colors` | Cart color from Evolution palette | Abstract_Cart.php:988 |
| `pa_evolution-seat-colors` | Seat color from Evolution palette | Abstract_Cart.php:994 |

### Available Cart Colors
Artic grey, Black, Black sapphire, Blue, Candy apple, Copper, Flamenco red, Green, Lime green, Mediterranean blue, Midnight blue, Mineral white, Navy blue, Portimao blue, Red, Silver, Sky blue, White

### Custom Product Options (Add-Ons)
Format: `EVolution(R) {Model} Add Ons`

Special handling for D5 models: the first space in the model name becomes a hyphen.
- Example: `D5 Maverick 4 Plus` becomes `EVolution(R) D5-Maverick 4 Plus Add Ons`

Other examples:
- `EVolution(R) Carrier 6 Plus Add Ons`
- `EVolution(R) Classic 4 Pro Add Ons`

### Custom Tabs
All Evolution carts get:
1. **"EVolution Warranty"** tab (new only, not used)
2. Model-specific spec/image tabs (see Model-Logic files for each model)

### Description Hyperlinks
- Make dealer format: `evolution` (standard)
- Links to: `https://tigongolfcarts.com/evolution`

### Sound System Taxonomy
- Maps to: `EVOLUTION(R) SOUND SYSTEM`

### Shared Defaults Across All Evolution Models
| Field | Value |
|---|---|
| `isElectric` | true |
| `battery.brand` | HDK |
| `battery.type` | Lithium |
| `battery.packVoltage` | 48 |
| `battery.warrantyLength` | 5 |
| `battery.isDC` | false |
| `title.isStreetLegal` | true |
| `tireRimSize` | 14 |

Note: `seatColor`, `ampHours`, `passengers`, `retailPrice`, and `warrantyLength` vary by model.

This means ALL Evolution carts automatically get:
- Categories: ELECTRIC, ZERO EMISSION, LITHIUM, 48 VOLT, STREET LEGAL, NEV, BEV, LSV, MSV
- Tags: ELECTRIC, NEV, LSV, MSV, STREET LEGAL
- Attributes: `pa_battery-type` = Lithium, `pa_battery-warranty` = 5, `pa_street-legal` = YES, `pa_brush-guard` = YES
