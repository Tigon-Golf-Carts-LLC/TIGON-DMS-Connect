# Used Vehicle Model Logic

Source: `Abstract_Cart.php`, `Used/Cart.php`

---

## Important Note

Used vehicles have **NO pre-defined model templates**.

Unlike new vehicles (which have hardcoded defaults in `New_Cart_Converter.php`), all used vehicle specs come **entirely from the DMS data**. There is no template system - every field is populated from what the DMS sends.

---

## What the DMS Provides Per Used Cart

Each used cart document from the DMS contains:

```
cartType.make          -> Brand name (e.g., "Club Car", "EZGO", "Yamaha")
cartType.model         -> Model name (e.g., "DS", "Precedent", "Drive 2")
cartType.year          -> Year
cartAttributes.cartColor     -> Body color
cartAttributes.seatColor     -> Seat color
cartAttributes.tireRimSize   -> Rim size in inches
cartAttributes.tireType      -> "Street Tire", "All-Terrain", etc.
cartAttributes.hasSoundSystem -> true/false
cartAttributes.isLifted      -> true/false
cartAttributes.hasHitch      -> true/false
cartAttributes.hasExtendedTop -> true/false
cartAttributes.passengers    -> "2 Passenger", "4 Passenger", "6 Passenger", "Utility"
isElectric                   -> true/false
battery.*                    -> Battery specs (if electric)
engine.*                     -> Engine specs (if gas)
title.isStreetLegal          -> true/false
retailPrice                  -> List price
salePrice                    -> Sale price (if any)
vinNo                        -> VIN (used as SKU, highest priority)
serialNo                     -> Serial (fallback SKU)
imageUrls[]                  -> Array of image filenames
advertising.cartAddOns[]     -> Array of add-on strings
isInStock                    -> true/false
isInBoneyard                 -> true/false
advertising.needOnWebsite    -> true/false
isRental                     -> true/false
addedFeatures.*              -> Feature flags
```

---

## How Model-Specific Data Flows

Since there are no templates, the model name affects these things only:

### 1. Product Name
Format: `{MAKE(R)} {MODEL} {Color} In {City} {State}`

### 2. Category
If `{MAKE(R)} {MODEL}` exists as a category, it gets assigned.

### 3. Model Taxonomy
`{MAKE(R)} {MODEL}` gets assigned with special aliases:
- Club Car DS -> `CLUB CAR(R) DS ELECTRIC`
- Club Car Precedent -> `CLUB CAR(R) PRECEDENT ELECTRIC`
- Yamaha Drive 2 -> `YAMAHA(R) DRIVE2`
- Yamaha 4L -> `YAMAHA(R) CROWN 4 LIFTED`
- Yamaha 6L -> `YAMAHA(R) CROWN 6 LIFTED`
- Star models -> `STAR EV(R) {MODEL}`
- EZGO models -> `EZ-GO(R) {MODEL}`

### 4. Custom Tabs (model-specific)
Only Denago and Evolution used carts get model-specific tabs:

**Denago (used) tabs by model:**
- Nomad: "Denago(R) Nomad Vehicle Specs"
- Nomad XL: "Denago(R) Nomad XL Vehicle Specs", "Denago Nomad XL User Manual", year-specific image tabs
- Rover XL: "Denago(R) Rover XL Vehicle Specs", year-specific image tabs

**Evolution (used) tabs by model:**
- Classic 2 Pro: "EVolution Classic 2 Pro Images", "EVolution Classic 2 Pro Specs"
- Classic 2 Plus: "EVolution Classic 2 Plus Images", "EVolution Classic 2 Plus Specs"
- Classic 4 Pro: "EVolution Classic 4 Pro Images", "EVolution Classic 4 Pro Specs"
- Classic 4 Plus: "EVolution Classic 4 Plus Images", "EVolution Classic 4 Plus Specs"
- D5 Maverick 2+2: "EVolution D5-Maverick 2+2", "EVolution D5-Maverick 2+2 Images"
- D5 Ranger 2+2: "EVOLUTION D5 RANGER 2+2 IMAGES", "EVOLUTION D5 RANGER 2+2 SPECS"
- D5 Ranger 4: "EVOLUTION D5 RANGER 4 IMAGES", "EVOLUTION D5 RANGER 4 SPEC"
- D5 Ranger 4 Plus: "EVOLUTION D5 RANGER 4 PLUS IMAGES", "EVOLUTION D5 RANGER 4 PLUS SPECS"
- D5 Ranger 6: "EVOLUTION D5 RANGER 6 IMAGES", "EVOLUTION D5 RANGER 6 SPECS"

All other makes/models: no model-specific tabs (only "TIGON Warranty (USED GOLF CARTS)").

### 5. Custom Product Options (Add-Ons)
Used carts do NOT get model-level add-on lists. Instead, individual add-ons from `advertising.cartAddOns` are matched against the lookup table (see Global-Used-Logic.md for the full mapping).

### 6. Description Hyperlinks
Make link: `https://tigongolfcarts.com/{make-hyphenated}` (with `denago-ev` for Denago)
Model link: `https://tigongolfcarts.com/{make-hyphenated}/{model-hyphenated}`

Special Turfman model URL format: `turfman/u-{number}` (utility prefix)

---

## Summary

For used vehicles, the model name is essentially just a string that flows through to:
- The product title
- Category/taxonomy lookups
- Tab selection (Denago + Evolution only)
- Description hyperlinks

There is no model-level spec template - all specs come from the DMS cart document directly.
