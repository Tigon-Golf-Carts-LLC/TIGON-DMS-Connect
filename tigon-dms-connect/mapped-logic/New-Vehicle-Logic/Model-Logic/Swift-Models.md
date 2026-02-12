# Swift Models - New Vehicle Model Logic

Source: `New_Cart_Converter.php`

All Swift models share the manufacturer defaults in `../Manufacturer-Logic/Swift-EV.md`.
Below are the model-specific differences.

Note: In `New_Cart_Converter.php` the make is stored as `Swift` (not `Swift EV`).
Battery brand is `ECO` (not CATL as mentioned in manufacturer doc - the converter has the actual values).

---

## Swift Mach 4

| Field | Value |
|---|---|
| **Retail Price** | $11,500 |
| **Passengers** | 4 Passenger |
| **Seat Color** | 2 Tone |
| **Tire Type** | All-Terrain |
| **Tire/Rim Size** | 14" |
| **Lifted** | Yes |
| **Sound System** | No |
| **Hitch** | No |
| **Extended Top** | Yes |
| **Battery** | ECO Lithium 105Ah 48V |
| **Battery Warranty** | 5 years |
| **Vehicle Warranty** | 2 years |
| **Street Legal** | Yes |
| **Colors** | Black, Blue, Champagne, Red, White |

---

## Swift Mach 4E

| Field | Value |
|---|---|
| **Retail Price** | $7,500 |
| **Passengers** | 4 Passenger |
| **Seat Color** | 2 Tone |
| **Tire Type** | Street Tire |
| **Tire/Rim Size** | 12" |
| **Lifted** | No |
| **Sound System** | No |
| **Hitch** | No |
| **Extended Top** | Yes |
| **Battery** | ECO Lithium 105Ah 48V |
| **Battery Warranty** | 5 years |
| **Vehicle Warranty** | 2 years |
| **Street Legal** | Yes |
| **Colors** | Black, Blue, Champagne, Red, Yellow |

### Key Differences from Mach 4
- Street Tire (not All-Terrain)
- 12" rims (not 14")
- Not lifted
- Much lower price ($7,500 vs $11,500)
- Yellow replaces White in color options

---

## Swift Mach 6

| Field | Value |
|---|---|
| **Retail Price** | $14,000 |
| **Passengers** | 6 Passenger |
| **Seat Color** | 2 Tone |
| **Tire Type** | All-Terrain |
| **Tire/Rim Size** | 14" |
| **Lifted** | Yes |
| **Sound System** | No |
| **Hitch** | No |
| **Extended Top** | Yes |
| **Battery** | ECO Lithium 105Ah 48V |
| **Battery Warranty** | 5 years |
| **Vehicle Warranty** | 2 years |
| **Street Legal** | Yes |
| **Colors** | Black, Blue, Champagne, Red, Yellow |

---

## Swift Mach 6E

| Field | Value |
|---|---|
| **Retail Price** | $9,500 (approx - from converter) |
| **Passengers** | 6 Passenger |
| **Seat Color** | 2 Tone |
| **Tire Type** | Street Tire |
| **Tire/Rim Size** | 12" |
| **Lifted** | No |
| **Sound System** | No |
| **Hitch** | No |
| **Extended Top** | Yes |
| **Battery** | ECO Lithium 105Ah 48V |
| **Battery Warranty** | 5 years |
| **Vehicle Warranty** | 2 years |
| **Street Legal** | Yes |
| **Colors** | Black, Blue, Champagne, Red, Yellow |

### Key Differences from Mach 6
- Street Tire (not All-Terrain)
- 12" rims (not 14")
- Not lifted
- Lower price

---

## Pattern Summary

| Model | Passengers | Lifted | Tires | Rims | Price |
|---|---|---|---|---|---|
| Mach 4 | 4 | Yes | All-Terrain | 14" | $11,500 |
| Mach 4E | 4 | No | Street | 12" | $7,500 |
| Mach 6 | 6 | Yes | All-Terrain | 14" | $14,000 |
| Mach 6E | 6 | No | Street | 12" | ~$9,500 |

The "E" suffix = economy/entry-level (street tires, not lifted, smaller rims, lower price).
