# Mafunzo Licensing API

API for the warehouse **licensing application** to list WRRB-trained staff, nominate them for license seats, and report license lifecycle events back to Mafunzo.

Base path: `/api/v1/licensing`

---

## Authentication

All endpoints require a shared API token.

| Header | Value |
|--------|--------|
| `Authorization` | `Bearer <LICENSING_API_TOKEN>` |
| `Accept` | `application/json` |
| `Content-Type` | `application/json` (for POST bodies) |

Alternative header (same token): `X-Licensing-Token: <LICENSING_API_TOKEN>`

Configure the token in Mafunzo `.env`:

```env
LICENSING_API_TOKEN=your-long-random-secret
LICENSING_NOMINATION_EXPIRES_DAYS=7
```

### Auth errors

| Status | Meaning |
|--------|---------|
| `401` | Missing or invalid token |
| `503` | `LICENSING_API_TOKEN` not configured on Mafunzo |

```json
{ "message": "Unauthenticated." }
```

---

## Lifecycle overview

```
pending  →  accepted  →  licensed
   │            │            │
   │            │            └─→ revoked (cancel after issue)
   │            └─→ cancelled (license app rejects / cancels)
   ├─→ rejected (trainee rejects)
   └─→ expired  (no response within 7 days)
```

| Stage | Who acts | Effect |
|-------|----------|--------|
| **Pending** | Licensing app nominates; trainee Accept/Reject | Multiple companies may nominate the same person |
| **Accepted** | Trainee accepts | Person is **reserved**; cannot accept another nomination; hidden from trained-staff list |
| **Licensed** | Licensing app calls `issue` | Remains reserved until `license_valid_until` (or revoke) |
| **Cancelled / Revoked / Expired / Rejected** | Trainee, licensing app, or timeout | Person becomes available again |

---

## Eligible positions

| Key (send this) | Label | Aliases accepted |
|-----------------|-------|------------------|
| `manager` | Manager | — |
| `documentation` | Documentation | — |
| `quality_assurance` | Quality Assurance | `qa` |
| `weight_assistant` | Weight Assistant | `weight_clerk`, `weight` |
| `store_keeper` | Store Keeper | `storekeeper` |

---

## Endpoints

### 1. List trained staff

```http
GET /api/v1/licensing/trained-staff
```

Returns staff who completed training/payment, passed the exam, have a registration number, hold an **earned** eligible final position (`assigned_position`), and are **not** currently reserved or under an active license.

**Gated positions:** `manager` and `quality_assurance` appear only when exam score and education requirements are met (Manager: degree + score ≥ 70; Quality Assurance: agriculture diploma + score ≥ 50). Other eligible positions use `assigned_position` only.

#### Query parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `position` | string | no | Filter by one or more positions (comma/space separated). Example: `manager,quality_assurance` |
| `registration_number` | string | no | Exact registration number |
| `q` | string | no | Search name or registration number |
| `course_id` | integer | no | Filter by course |
| `session_year` | integer | no | Filter by course session year |
| `per_page` | integer | no | Page size (1–100) |
| `page` | integer | no | Page number |

#### Example

```http
GET /api/v1/licensing/trained-staff?position=store_keeper&q=Kinabo&per_page=20
Authorization: Bearer <token>
```

#### Response `200`

```json
{
  "data": [
    {
      "registration_number": "WRRB/2026/1/0004",
      "full_name": "Mary A Kinabo",
      "email": "mary.kinabo@example.com",
      "final_position": "store_keeper",
      "final_position_label": "Store Keeper",
      "course_id": 5,
      "session_year": 2026
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1,
    "eligible_positions": {
      "manager": "Manager",
      "documentation": "Documentation",
      "quality_assurance": "Quality Assurance",
      "weight_assistant": "Weight Assistant",
      "store_keeper": "Store Keeper"
    }
  }
}
```

---

### 2. Create nomination

```http
POST /api/v1/licensing/nominations
```

Creates a nomination and notifies the trainee in Mafunzo (Accept / Reject). Pending nominations expire after **7 days** (configurable).

Idempotent: repeating the same `licensing_application_id` + `registration_number` + `final_position` while still pending returns the existing nomination.

#### Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `registration_number` | string | yes | WRRB registration number |
| `final_position` | string | yes | Eligible position key (or alias) |
| `licensing_application_id` | string | yes | Your license application ID |
| `organization_name` | string | no | Shown to the trainee |
| `callback_url` | url | no | Mafunzo POSTs status updates here |

#### Example

```http
POST /api/v1/licensing/nominations
Authorization: Bearer <token>
Content-Type: application/json

{
  "registration_number": "WRRB/2026/1/0004",
  "final_position": "store_keeper",
  "licensing_application_id": "LA-2026-100",
  "organization_name": "Demo Warehouse Ltd",
  "callback_url": "https://licensing.example.com/hooks/mafunzo"
}
```

#### Response `201`

```json
{
  "message": "Nomination created. The trainee has been notified.",
  "data": {
    "id": 12,
    "registration_number": "WRRB/2026/1/0004",
    "final_position": "store_keeper",
    "final_position_label": "Store Keeper",
    "licensing_application_id": "LA-2026-100",
    "organization_name": "Demo Warehouse Ltd",
    "status": "pending",
    "is_reserved": false,
    "license_number": null,
    "license_issued_at": null,
    "license_valid_from": null,
    "license_valid_until": null,
    "expires_at": "2026-08-08T10:00:00+00:00",
    "responded_at": null,
    "created_at": "2026-08-01T10:00:00+00:00"
  }
}
```

#### Errors

| Status | When |
|--------|------|
| `422` | Invalid position, person not eligible, or person already reserved/licensed |

---

### 3. List nominations for a license application

```http
GET /api/v1/licensing/nominations?licensing_application_id=LA-2026-100
```

| Parameter | Required | Description |
|-----------|----------|-------------|
| `licensing_application_id` | yes | Your license application ID |

#### Response `200`

```json
{
  "data": [
    {
      "id": 12,
      "registration_number": "WRRB/2026/1/0004",
      "final_position": "store_keeper",
      "final_position_label": "Store Keeper",
      "licensing_application_id": "LA-2026-100",
      "organization_name": "Demo Warehouse Ltd",
      "status": "accepted",
      "is_reserved": true,
      "license_number": null,
      "license_issued_at": null,
      "license_valid_from": null,
      "license_valid_until": null,
      "expires_at": "2026-08-08T10:00:00+00:00",
      "responded_at": "2026-08-02T09:15:00+00:00",
      "created_at": "2026-08-01T10:00:00+00:00"
    }
  ]
}
```

---

### 4. Get one nomination

```http
GET /api/v1/licensing/nominations/{id}
```

Poll trainee response and license state.

#### Response `200`

```json
{
  "data": { "...same nomination object as above..." }
}
```

---

### 5. Issue license (licensing system → Mafunzo)

```http
POST /api/v1/licensing/nominations/{id}/issue
```

Call this when the licensing application **issues** the warehouse license. Nomination must be `accepted`. Staff remain reserved until validity ends (or revoke).

#### Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `license_number` | string | no | External license number |
| `license_valid_from` | date | yes | `YYYY-MM-DD` |
| `license_valid_until` | date | yes | `YYYY-MM-DD` (≥ `license_valid_from`) |

#### Example

```http
POST /api/v1/licensing/nominations/12/issue
Authorization: Bearer <token>
Content-Type: application/json

{
  "license_number": "LIC-2026-001",
  "license_valid_from": "2026-08-01",
  "license_valid_until": "2027-07-31"
}
```

#### Response `200`

```json
{
  "message": "License recorded. Staff member remains reserved until validity ends.",
  "data": {
    "id": 12,
    "status": "licensed",
    "is_reserved": true,
    "license_number": "LIC-2026-001",
    "license_issued_at": "2026-08-01T12:00:00+00:00",
    "license_valid_from": "2026-08-01",
    "license_valid_until": "2027-07-31",
    "...": "..."
  }
}
```

#### Errors

| Status | When |
|--------|------|
| `422` | Nomination is not in `accepted` status, or validation failed |

---

### 6. Cancel nomination or revoke license

```http
POST /api/v1/licensing/nominations/{id}/cancel
```

| Current status | Result |
|----------------|--------|
| `pending` | → `cancelled` |
| `accepted` | → `cancelled` (releases reservation) |
| `licensed` (still valid) | → `revoked` (staff available again) |
| other | `422` — nothing to cancel |

#### Response `200`

```json
{
  "message": "Nomination cancelled. Reservation released if it was accepted.",
  "data": {
    "id": 12,
    "status": "cancelled",
    "is_reserved": false
  }
}
```

---

### 7. Submit change request (company → WRRB approval)

Position / organization updates and early release require **WRRB staff approval** in Mafunzo Application Management. Either the company (this API) or the trainee (Mafunzo UI) can submit a request. Only **one pending** request per nomination is allowed.

Allowed when nomination is `accepted` or actively `licensed`.

```http
POST /api/v1/licensing/nominations/{id}/change-requests
```

#### Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `change_type` | string | yes | `update` or `release` |
| `proposed_final_position` | string | for update* | New eligible position key |
| `proposed_organization_name` | string | for update* | New organization name |
| `reason` | string | no | Why the change is needed |

\* For `update`, at least one of position or organization must differ from current values.

#### Example — change position

```http
POST /api/v1/licensing/nominations/12/change-requests
Authorization: Bearer <token>
Content-Type: application/json

{
  "change_type": "update",
  "proposed_final_position": "manager",
  "reason": "Promoted to warehouse manager seat"
}
```

#### Example — request release

```json
{
  "change_type": "release",
  "reason": "Staff left the warehouse"
}
```

#### Response `201`

```json
{
  "message": "Change request submitted for WRRB approval.",
  "data": {
    "id": 3,
    "license_nomination_id": 12,
    "requested_by": "company",
    "change_type": "update",
    "current_final_position": "store_keeper",
    "proposed_final_position": "manager",
    "current_organization_name": "Demo Warehouse Ltd",
    "proposed_organization_name": "Demo Warehouse Ltd",
    "reason": "Promoted to warehouse manager seat",
    "status": "pending",
    "review_notes": null,
    "reviewed_at": null,
    "created_at": "2026-08-01T16:00:00+00:00",
    "summary": "Position: Store Keeper → Manager"
  }
}
```

---

### 8. List change requests for a nomination

```http
GET /api/v1/licensing/nominations/{id}/change-requests
```

#### Response `200`

```json
{
  "data": [ { "...change request object..." } ]
}
```

---

### 9. Get / cancel a change request

```http
GET  /api/v1/licensing/change-requests/{id}
POST /api/v1/licensing/change-requests/{id}/cancel
```

Cancel is only for **pending** requests (withdraw before WRRB decides).

---

## WRRB staff approval (Mafunzo UI)

Path: **Application Management → License change requests**

- Staff approve → changes are applied (position/org updated, or engagement released via cancel/revoke)  
- Staff reject → nomination unchanged; trainee is notified  
- Optional callback to licensing app with `event: license_change_request`

---

## Nomination object fields

| Field | Description |
|-------|-------------|
| `id` | Mafunzo nomination ID |
| `registration_number` | WRRB registration number |
| `final_position` | Position key |
| `final_position_label` | Human-readable position |
| `licensing_application_id` | Your application ID |
| `organization_name` | Organization shown to trainee |
| `status` | `pending`, `accepted`, `licensed`, `rejected`, `expired`, `cancelled`, `revoked` |
| `is_reserved` | `true` while accepted or under active license |
| `license_number` | Set after `issue` |
| `license_issued_at` | ISO 8601 timestamp |
| `license_valid_from` | `YYYY-MM-DD` |
| `license_valid_until` | `YYYY-MM-DD` |
| `expires_at` | Pending response deadline (ISO 8601) |
| `responded_at` | When trainee/system responded |
| `created_at` | Created timestamp |

---

## Callback (optional)

If `callback_url` was provided when creating the nomination, Mafunzo POSTs the **full nomination object** (same fields as above) to that URL when status changes (accept, reject, issue, cancel/revoke).

```http
POST https://licensing.example.com/hooks/mafunzo
Content-Type: application/json

{
  "id": 12,
  "registration_number": "WRRB/2026/1/0004",
  "final_position": "store_keeper",
  "status": "accepted",
  "is_reserved": true,
  "...": "..."
}
```

Licensing apps should still **poll** `GET /nominations/{id}` as a backup if the callback fails.

---

## Trainee actions in Mafunzo (not API)

Trainees respond inside Mafunzo (notification → nomination page):

- **Accept** — reserves them for that company until license issued / cancelled / revoked / validity ends  
- **Reject** — frees them for other nominations  

While reserved, Accept on any other pending nomination is blocked.

---

## Recommended integration sequence

1. `GET /trained-staff` — pick eligible people by position  
2. `POST /nominations` — nominate (trainee is notified)  
3. Poll `GET /nominations/{id}` (and/or handle callback) until `accepted` or `rejected` / `expired`  
4. If accepted and license is granted → `POST /nominations/{id}/issue` with validity dates  
5. If application withdrawn / license revoked → `POST /nominations/{id}/cancel`  
6. For position/org changes or early release → `POST /nominations/{id}/change-requests` → wait for WRRB approve/reject (poll change-request or callback)

---

## cURL examples

```bash
TOKEN="your-token"
BASE="https://mafunzo.example.com/api/v1/licensing"

# List staff
curl -s "$BASE/trained-staff?position=manager" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"

# Nominate
curl -s -X POST "$BASE/nominations" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "registration_number":"WRRB/2026/1/0004",
    "final_position":"store_keeper",
    "licensing_application_id":"LA-2026-100",
    "organization_name":"Demo Warehouse Ltd"
  }'

# Issue license
curl -s -X POST "$BASE/nominations/12/issue" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "license_number":"LIC-2026-001",
    "license_valid_from":"2026-08-01",
    "license_valid_until":"2027-07-31"
  }'
```
