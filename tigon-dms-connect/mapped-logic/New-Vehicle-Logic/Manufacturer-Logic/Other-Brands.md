# Other Brands - New Vehicle Manufacturer Logic

These brands are referenced in the attribute system but do NOT have templates in `New_Cart_Converter.php`.
They appear when inventory is manually entered or comes from the DMS with these makes.

Source: `Abstract_Cart.php`

---

## Brands With Dedicated Color Palettes

The following brands have make-specific color attributes (`pa_{make}-cart-colors` and `pa_{make}-seat-colors`):

| Brand | Attribute Slug | Notes |
|---|---|---|
| Bintelli | `pa_bintelli-cart-colors` / `pa_bintelli-seat-colors` | |
| Club Car | `pa_club-car-cart-colors` / `pa_club-car-seat-colors` | |
| EZGO | `pa_ezgo-cart-colors` / `pa_ezgo-seat-colors` | Category mapped as `EZ-GO(R)` |
| Navitas | `pa_navitas-cart-colors` / `pa_navitas-seat-colors` | |
| Polaris | `pa_polaris-cart-colors` / `pa_polaris-seat-colors` | |
| Royal EV | `pa_royal-ev-cart-colors` / `pa_royal-ev-seat-colors` | |
| Star EV | `pa_star-ev-cart-colors` / `pa_star-ev-seat-colors` | Manufacturer taxonomy mapped as `STAR EV(R)` |
| Tomberlin | `pa_tomberlin-cart-colors` / `pa_tomberlin-seat-colors` | |
| Yamaha | `pa_yamaha-cart-colors` / `pa_yamaha-seat-colors` | |

## Brands WITHOUT Dedicated Color Palettes

Any brand not in the list above falls back to generic attributes:
- `pa_cart-color` (generic)
- `pa_seat-color` (generic)

---

## EZGO Special Handling

- Category: uses `EZ-GO(R)` instead of `EZGO(R)`
- Model taxonomy: prefixed with `EZ-GO(R)` (e.g., `EZ-GO(R) TXT`)
- Make model category: uses `EZ-GO(R) {MODEL}`

## Star EV Special Handling

- Manufacturer taxonomy: mapped to `STAR EV(R)`
- Model taxonomy: prefixed with `STAR EV(R)` (e.g., `STAR EV(R) Sirius`)

## Club Car Special Handling

- Model aliases: `DS` becomes `{MAKE} DS ELECTRIC`, `Precedent` becomes `{MAKE} PRECEDENT ELECTRIC`

## Yamaha Special Handling

- Model aliases: `Drive 2` becomes `{MAKE} DRIVE2`
- Model aliases: `4L` becomes `{MAKE} CROWN 4 LIFTED`, `6L` becomes `{MAKE} CROWN 6 LIFTED`

---

## Default Attributes for All Non-Denago/Non-Evolution Brands

| Attribute | Value |
|---|---|
| `pa_brush-guard` | NO |
| `pa_led-accents` | NO |

All other attributes follow the global logic in Global-New-Logic.md.
