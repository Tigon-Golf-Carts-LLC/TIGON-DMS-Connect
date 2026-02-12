# Icon - New Vehicle Manufacturer Logic

Source: `Abstract_Cart.php`, `New_Cart_Converter.php`

---

## Brand-Specific Overrides

These rules apply to ALL Icon models when `make = 'Icon'`:

### Attributes
| Attribute | Value | Source Line |
|---|---|---|
| `pa_brush-guard` | **NO** | Abstract_Cart.php:896 |
| `pa_led-accents` | **NO** | Abstract_Cart.php:947-951 |
| `pa_icon-cart-colors` | Cart color from Icon palette | Abstract_Cart.php:988 |
| `pa_icon-seat-colors` | Seat color from Icon palette | Abstract_Cart.php:994 |

### Available Cart Colors
Black, Caribbean, Champagne, Forest, Indigo, Lime, Orange, Purple, Sangria, Silver, Torch, White, Yellow

### Custom Product Options (Add-Ons)
Format: `ICON(R) {Model} Add Ons`

Examples:
- `ICON(R) i40 Add Ons`
- `ICON(R) i40L Add Ons`
- `ICON(R) i60L Add Ons`

### Custom Tabs
No Icon-specific warranty or spec tabs defined in the current codebase.

### Description Hyperlinks
- Make dealer format: `icon` (standard)
- Links to: `https://tigongolfcarts.com/icon`

### Sound System Taxonomy
- Maps to: `ICON(R) SOUND SYSTEM`

### Shared Defaults Across All Icon Models
| Field | Value |
|---|---|
| `isElectric` | true |
| `battery.type` | Lithium |
| `battery.packVoltage` | 48 |
| `battery.isDC` | false |
| `title.isStreetLegal` | true |

Note: `battery.brand`, `battery.ampHours`, `battery.warrantyLength`, `seatColor`, `tireType`, `passengers`, `retailPrice`, and `warrantyLength` vary by model.

This means ALL Icon carts automatically get:
- Categories: ELECTRIC, ZERO EMISSION, LITHIUM, 48 VOLT, STREET LEGAL, NEV, BEV, LSV, MSV
- Tags: ELECTRIC, NEV, LSV, MSV, STREET LEGAL
- Attributes: `pa_battery-type` = Lithium, `pa_street-legal` = YES
