# Denago - New Vehicle Manufacturer Logic

Source: `Abstract_Cart.php`, `New_Cart_Converter.php`

---

## Brand-Specific Overrides

These rules apply to ALL Denago models when `make = 'Denago'`:

### Attributes
| Attribute | Value | Source Line |
|---|---|---|
| `pa_brush-guard` | **YES** | Abstract_Cart.php:889 |
| `pa_led-accents` | **YES** + **LIGHT BAR** | Abstract_Cart.php:939-945 |
| `pa_denago-cart-colors` | Cart color from Denago palette | Abstract_Cart.php:988 |
| `pa_denago-seat-colors` | Seat color from Denago palette | Abstract_Cart.php:994 |

### Available Cart Colors
Black, Blue, Champagne, Gray, Lava, White, Verdant

### Custom Product Options (Add-Ons)
Format: `Denago(R) EV {Model} Add Ons`

Examples:
- `Denago(R) EV Nomad Add Ons`
- `Denago(R) EV Nomad XL Add Ons`
- `Denago(R) EV Rover XL Add Ons`

### Custom Tabs
All Denago carts get:
1. **"DENAGO Warranty"** tab (new only, not used)
2. **"VIDEO DENAGO 2024"** tab (if year = 2024)
3. Model-specific spec tab (see Model-Logic files)

### Description Hyperlinks
- Make dealer format: `denago-ev` (not just `denago`)
- Links to: `https://tigongolfcarts.com/denago-ev`
- Model links to: `https://tigongolfcarts.com/denago-ev/{model}`

### Sound System Taxonomy
- Maps to: `DENAGO(R) SOUND SYSTEM`

### Shared Defaults Across All Denago Models
| Field | Value |
|---|---|
| `isElectric` | true |
| `battery.brand` | Denago |
| `battery.type` | Lithium |
| `battery.ampHours` | 105 |
| `battery.packVoltage` | 48 |
| `battery.warrantyLength` | 5 |
| `battery.isDC` | false |
| `title.isStreetLegal` | true |
| `seatColor` | Stone |
| `tireRimSize` | 14 |
| `warrantyLength` | 2 |

This means ALL Denago carts automatically get:
- Categories: ELECTRIC, ZERO EMISSION, LITHIUM, 48 VOLT, STREET LEGAL, NEV, BEV, LSV, MSV
- Tags: ELECTRIC, NEV, LSV, MSV, STREET LEGAL
- Attributes: `pa_battery-type` = Lithium, `pa_battery-warranty` = 5, `pa_street-legal` = YES
