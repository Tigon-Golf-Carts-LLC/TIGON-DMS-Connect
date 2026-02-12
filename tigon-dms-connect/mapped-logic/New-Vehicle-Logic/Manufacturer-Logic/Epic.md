# Epic - New Vehicle Manufacturer Logic

Source: `Abstract_Cart.php`, `New_Cart_Converter.php`

---

## Brand-Specific Overrides

These rules apply to ALL Epic models when `make = 'Epic'`:

### Attributes
| Attribute | Value | Source Line |
|---|---|---|
| `pa_brush-guard` | **NO** | Abstract_Cart.php:896 |
| `pa_led-accents` | **NO** | Abstract_Cart.php:947-951 |
| `pa_epic-cart-colors` | Cart color from Epic palette | Abstract_Cart.php:988 |
| `pa_epic-seat-colors` | Seat color from Epic palette | Abstract_Cart.php:994 |

### Available Cart Colors
Black, Charcoal Gray, Dark Blue, Light Blue, Matte Black, Red Pearl, Silver, White, White Pearl

### Custom Product Options (Add-Ons)
Format: `EPIC(R) {Model} Add Ons`

Examples:
- `EPIC(R) E40L Add Ons`
- `EPIC(R) E60 Add Ons`
- `EPIC(R) E60L Add Ons`

### Custom Tabs
No Epic-specific warranty or spec tabs defined in the current codebase.
(Only Denago and Evolution have model-specific tabs.)

### Description Hyperlinks
- Make dealer format: `epic` (standard)
- Links to: `https://tigongolfcarts.com/epic`

### Sound System Taxonomy
- Maps to: `EPIC(R) SOUND SYSTEM`

### Shared Defaults Across All Epic Models
| Field | Value |
|---|---|
| `isElectric` | true |
| `battery.brand` | Leoch |
| `battery.type` | AGM |
| `battery.ampHours` | 210 |
| `battery.packVoltage` | 36 |
| `battery.warrantyLength` | 2 |
| `battery.isDC` | false |
| `title.isStreetLegal` | true |
| `seatColor` | Black |
| `warrantyLength` | 3 |

This means ALL Epic carts automatically get:
- Categories: ELECTRIC, ZERO EMISSION, 36 VOLT, STREET LEGAL, NEV, BEV, LSV, MSV
- Note: Battery type is AGM, NOT Lithium or Lead-Acid. The category mapping checks for "Lead" and "Lithium" only, so AGM does not get a battery-type category.
- Tags: ELECTRIC, NEV, LSV, MSV, STREET LEGAL
- Attributes: `pa_battery-type` = AGM, `pa_battery-warranty` = 2, `pa_street-legal` = YES
