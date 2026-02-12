# Used Vehicle Manufacturer Logic

Source: `Abstract_Cart.php`

---

## Important Note

Used vehicles have **NO manufacturer-specific templates** like new vehicles do.
There is no `Used_Cart_Converter.php` equivalent - all used cart data comes directly from the DMS.

The manufacturer-specific logic that applies to used carts is **identical** to new carts because it lives in `Abstract_Cart.php`, which is shared.

---

## Manufacturer Rules That Apply to Used Vehicles

### Denago (used)
- `pa_brush-guard` = YES
- `pa_led-accents` = YES + LIGHT BAR
- `pa_denago-cart-colors` / `pa_denago-seat-colors` (make-specific palettes)
- Custom tabs: model-specific spec tabs (NO "DENAGO Warranty" tab - gets "TIGON Warranty (USED GOLF CARTS)" instead)
- Add-on format: individual add-ons from DMS (not model-level list)

### Evolution (used)
- `pa_brush-guard` = YES
- `pa_led-accents` = NO
- `pa_evolution-cart-colors` / `pa_evolution-seat-colors`
- Custom tabs: model-specific spec/image tabs (NO "EVolution Warranty" tab - gets "TIGON Warranty (USED GOLF CARTS)" instead)
- Add-on format: individual add-ons from DMS

### Epic (used)
- `pa_brush-guard` = NO
- `pa_led-accents` = NO
- `pa_epic-cart-colors` / `pa_epic-seat-colors`
- No manufacturer-specific tabs
- Add-on format: individual add-ons from DMS

### Icon (used)
- `pa_brush-guard` = NO
- `pa_led-accents` = NO
- `pa_icon-cart-colors` / `pa_icon-seat-colors`
- No manufacturer-specific tabs
- Add-on format: individual add-ons from DMS

### Swift / Swift EV (used)
- `pa_brush-guard` = NO
- `pa_led-accents` = NO
- `pa_swift-cart-colors` / `pa_swift-seat-colors`
- No manufacturer-specific tabs
- Add-on format: individual add-ons from DMS

### Club Car (used)
- `pa_brush-guard` = NO
- `pa_led-accents` = NO
- `pa_club-car-cart-colors` / `pa_club-car-seat-colors`
- Model aliases: DS -> `{MAKE} DS ELECTRIC`, Precedent -> `{MAKE} PRECEDENT ELECTRIC`
- No manufacturer-specific tabs
- Add-on format: individual add-ons from DMS

### EZGO (used)
- `pa_brush-guard` = NO
- `pa_led-accents` = NO
- `pa_ezgo-cart-colors` / `pa_ezgo-seat-colors`
- Category/taxonomy name alias: `EZ-GO(R)` (not `EZGO(R)`)
- No manufacturer-specific tabs
- Add-on format: individual add-ons from DMS

### Yamaha (used)
- `pa_brush-guard` = NO
- `pa_led-accents` = NO
- `pa_yamaha-cart-colors` / `pa_yamaha-seat-colors`
- Model aliases: `Drive 2` -> `DRIVE2`, `4L` -> `CROWN 4 LIFTED`, `6L` -> `CROWN 6 LIFTED`
- No manufacturer-specific tabs
- Add-on format: individual add-ons from DMS

### All Other Brands (used)
- `pa_brush-guard` = NO
- `pa_led-accents` = NO
- Falls back to generic `pa_cart-color` / `pa_seat-color`
- No manufacturer-specific tabs
- Add-on format: individual add-ons from DMS

---

## Categories Specific to Used

| Category | Applied When |
|---|---|
| `USED` | Always |
| `LOCAL USED ACTIVE INVENTORY` | Default |
| `LOCAL USED RENTAL INVENTORY` | If `isRental=true` |
| `RENTAL` | If `isRental=true` |

All other categories (make, electric/gas, seating, lifted, street legal, etc.) are computed from the DMS data exactly like new vehicles.
