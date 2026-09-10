# Interview Q1 Score Correction (90 → 20)

Simple reference for correcting Question 1 when it was wrongly set to **max 90** instead of **max 20**.

---

## Formula

```
New score = Old score × (20 ÷ 90)
```

Same as:

```
New score = Old score × 0.2222
New score = Old score ÷ 4.5
```

---

## Why

Panelists scored on a **0–90** scale. The correct scale is **0–20**.

Keep the same proportion:

```
Old score / 90  =  New score / 20
```

---

## Examples

| Old score (max 90) | Calculation        | New score (max 20) |
|--------------------|--------------------|--------------------|
| 90                 | 90 × 20/90         | 20.0               |
| 72                 | 72 × 20/90         | 16.0               |
| 63                 | 63 × 20/90         | 14.0               |
| 45                 | 45 × 20/90         | 10.0               |
| 18                 | 18 × 20/90         | 4.0                |

---

## What the system did (2026-09-07 batch)

1. Set Q1 **max mark** to **20**
2. Scaled every Q1 panelist score: **× (20/90)**
3. Recalculated session **total**, **percentage**, and **pass/fail**

**Live command used:**

```bash
php artisan interview:fix-q1-max --date=2026-09-07 --from=90 --to=20 --dry-run
php artisan interview:fix-q1-max --date=2026-09-07 --from=90 --to=20
```

Single session:

```bash
php artisan interview:fix-q1-max --session=INT-20260907-WXMF --from=90 --to=20
```

---

## Quick mental check

- **90 → 20** (full marks)
- **45 → 10** (half marks)
- **18 → 4** (low score)

Always multiply by **2/9** or divide by **4.5**.
