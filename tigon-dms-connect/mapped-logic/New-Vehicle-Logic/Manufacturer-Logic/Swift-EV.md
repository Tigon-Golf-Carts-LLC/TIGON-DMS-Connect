# Swift EV - New Vehicle Manufacturer Logic

Source: `Abstract_Cart.php`, `New_Cart_Converter.php`

---

## Brand-Specific Overrides

These rules apply to ALL Swift EV models when `make = 'Swift EV'`:

### Special Name Handling
- The make is stored as `Swift EV` in the DMS
- `make_with_symbol` becomes `Swift EV(R)`
- In categories/tags, mapped to `SWIFT(R)` (not `SWIFT EV(R)`)
- In manufacturer taxonomy, mapped to `SWIFT(R)`
- In sound system taxonomy, mapped to `SWIFT EV(R) SOUND SYSTEM`

### Attributes
| Attribute | Value | Source Line |
|---|---|---|
| `pa_brush-guard` | **NO** | Abstract_Cart.php:896 |
| `pa_led-accents` | **NO** | Abstract_Cart.php:947-951 |
| `pa_swift-cart-colors` | Cart color from Swift palette | Abstract_Cart.php:988 |
| `pa_swift-seat-colors` | Seat color from Swift palette | Abstract_Cart.php:994 |

Note: The attribute slug uses `swift` (not `swift-ev`) since `brand_hyphenated` = `Swift-EV` -> lowered to `swift-ev`. Actually the make in converter is `Swift EV` so `brand_hyphenated` = `Swift-EV`, lowered = `swift-ev`. This needs to match the `make_attrs` array which contains `"swift"` not `"swift-ev"`. This may cause a fallback to generic `pa_cart-color` / `pa_seat-color`.

### Available Cart Colors
Black, Blue, Champagne, Green, Grey, Lime, Orange, Pink, Purple, Red, Silver, Sky Blue, White, Yellow

### Custom Product Options (Add-Ons)
Format: `SWIFT EV(R) {Model} Add Ons`

Examples:
- `SWIFT EV(R) Mach 4 Add Ons`
- `SWIFT EV(R) Mach 4E Add Ons`
- `SWIFT EV(R) Mach 6 Add Ons`
- `SWIFT EV(R) Mach 6E Add Ons`

### Custom Tabs
No Swift-specific warranty or spec tabs defined in the current codebase.

### Description Hyperlinks
- Make dealer format: `swift-ev` (standard hyphenation)
- Links to: `https://tigongolfcarts.com/swift-ev`

### Sound System Taxonomy
- Maps to: `SWIFT EV(R) SOUND SYSTEM`

### Shared Defaults Across All Swift Models
| Field | Value |
|---|---|
| `isElectric` | true |
| `battery.brand` | CATL |
| `battery.type` | Lithium |
| `battery.packVoltage` | 48 |
| `battery.warrantyLength` | 5 |
| `battery.isDC` | false |
| `title.isStreetLegal` | true |
| `tireRimSize` | 14 |

Note: `battery.ampHours`, `seatColor`, `tireType`, `passengers`, `retailPrice`, and `warrantyLength` vary by model.

This means ALL Swift EV carts automatically get:
- Categories: ELECTRIC, ZERO EMISSION, LITHIUM, 48 VOLT, STREET LEGAL, NEV, BEV, LSV, MSV
- Tags: ELECTRIC, NEV, LSV, MSV, STREET LEGAL
- Attributes: `pa_battery-type` = Lithium, `pa_battery-warranty` = 5, `pa_street-legal` = YES
