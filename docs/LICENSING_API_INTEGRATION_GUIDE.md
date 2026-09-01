# Mafunzo Licensing API — Integration Guide

**Document version:** 1.0  
**Date:** August 2026  
**Prepared for:** Warehouse Licensing Application Team  
**System:** Mafunzo Training Platform (WRRB)

---

## 1. Purpose

This API allows the **warehouse licensing application** to:

1. List WRRB-trained staff who are eligible for license nomination  
2. Nominate a person for a license seat (trainee is notified in Mafunzo)  
3. Record when a license is issued (with validity dates)  
4. Cancel nominations or revoke active licenses  
5. Submit change requests (position / organization / release) for WRRB approval  

Trainee **Accept** and **Reject** actions happen inside Mafunzo (not via this API).

---

## 2. Connection details

| Item | Value |
|------|--------|
| **Production base URL** | `http://41.59.85.9/api/v1/licensing` |
| **API prefix** | `/api/v1/licensing` |
| **Protocol** | HTTP (HTTPS when SSL is enabled) |
| **Format** | JSON |
| **Authentication** | Bearer token (provided separately by WRRB IT) |

### Example full URL

```
http://41.59.85.9/api/v1/licensing/trained-staff
```

---

## 3. Authentication

Every request must include the shared API token.

### Required headers

| Header | Value |
|--------|--------|
| `Authorization` | `Bearer <LICENSING_API_TOKEN>` |
| `Accept` | `application/json` |

For POST requests, also include:

| Header | Value |
|--------|--------|
| `Content-Type` | `application/json` |

### Alternative header

```
X-Licensing-Token: <LICENSING_API_TOKEN>
```

### Auth error responses

| HTTP status | Meaning |
|-------------|---------|
| `401` | Missing or invalid token |
| `503` | API token not configured on Mafunzo server |

```json
{ "message": "Unauthenticated." }
```

> **Security:** Do not commit the token to source control. Store it in your application secrets / environment variables.

---

## 4. Endpoint reference

All paths below are relative to the base URL  
`http://41.59.85.9/api/v1/licensing`

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 1 | `GET` | `/trained-staff` | List eligible trained staff |
| 2 | `POST` | `/nominations` | Create a nomination |
| 3 | `GET` | `/nominations?licensing_application_id={id}` | List nominations for your application |
| 4 | `GET` | `/nominations/{id}` | Get one nomination (poll status) |
| 5 | `POST` | `/nominations/{id}/issue` | Record license issued |
| 6 | `POST` | `/nominations/{id}/cancel` | Cancel nomination or revoke license |
| 7 | `POST` | `/nominations/{id}/change-requests` | Submit change request |
| 8 | `GET` | `/nominations/{id}/change-requests` | List change requests |
| 9 | `GET` | `/change-requests/{id}` | Get one change request |
| 10 | `POST` | `/change-requests/{id}/cancel` | Withdraw pending change request |

---

## 5. Eligible positions

When nominating staff, use one of these position keys:

| Key (send this) | Label | Aliases also accepted |
|-----------------|-------|------------------------|
| `manager` | Manager | — |
| `documentation` | Documentation | — |
| `quality_assurance` | Quality Assurance | `qa` |
| `weight_assistant` | Weight Assistant | `weight_clerk`, `weight` |
| `store_keeper` | Store Keeper | `storekeeper` |

---

## 6. Nomination lifecycle

```
pending  →  accepted  →  licensed
   │            │            │
   │            │            └─→ revoked (cancel after issue)
   │            └─→ cancelled
   ├─→ rejected (trainee rejects in Mafunzo)
   └─→ expired (no response within 7 days)
```

| Status | Who acts | Effect |
|--------|----------|--------|
| **pending** | Licensing app nominates; trainee Accept/Reject in Mafunzo | Multiple companies may nominate the same person |
| **accepted** | Trainee accepts | Person is **reserved**; hidden from trained-staff list |
| **licensed** | Licensing app calls `issue` | Reserved until `license_valid_until` (or revoke) |
| **cancelled / revoked / expired / rejected** | Various | Person becomes available again |

Pending nominations expire after **7 days** if the trainee does not respond.

---

## 7. Endpoint details

### 7.1 List trained staff

```http
GET /trained-staff
```

Returns staff who completed training, passed the exam, have a registration number, hold an **earned** eligible position (`assigned_position`), and are **not** currently reserved or under an active license.

**Gated positions:** `manager` and `quality_assurance` require score + education (Manager: degree + ≥ 70; Quality Assurance: agriculture diploma + ≥ 50).

**Query parameters (all optional):**

| Parameter | Type | Description |
|-----------|------|-------------|
| `position` | string | Filter by position(s), comma-separated. Example: `manager,store_keeper` |
| `registration_number` | string | Exact registration number |
| `q` | string | Search name or registration number |
| `course_id` | integer | Filter by course |
| `session_year` | integer | Filter by session year |
| `per_page` | integer | Page size (1–100) |
| `page` | integer | Page number |

**Example request:**

```http
GET /trained-staff?position=store_keeper&per_page=20
Authorization: Bearer <token>
Accept: application/json
```

**Example response (`200`):**

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

### 7.2 Create nomination

```http
POST /nominations
```

Creates a nomination and notifies the trainee in Mafunzo.

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `registration_number` | string | yes | WRRB registration number |
| `final_position` | string | yes | Eligible position key (or alias) |
| `licensing_application_id` | string | yes | Your license application ID |
| `organization_name` | string | no | Shown to the trainee |
| `callback_url` | url | no | Mafunzo POSTs status updates here |

**Example request:**

```http
POST /nominations
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json

{
  "registration_number": "WRRB/2026/1/0004",
  "final_position": "store_keeper",
  "licensing_application_id": "LA-2026-100",
  "organization_name": "Demo Warehouse Ltd",
  "callback_url": "https://your-licensing-app.example.com/hooks/mafunzo"
}
```

**Example response (`201`):**

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

**Errors:** `422` — invalid position, person not eligible, or already reserved/licensed.

---

### 7.3 List nominations for your application

```http
GET /nominations?licensing_application_id=LA-2026-100
```

| Parameter | Required | Description |
|-----------|----------|-------------|
| `licensing_application_id` | yes | Your license application ID |

---

### 7.4 Get one nomination

```http
GET /nominations/{id}
```

Use this to poll trainee response and license state.

---

### 7.5 Issue license

```http
POST /nominations/{id}/issue
```

Call when the licensing application **issues** the warehouse license. Nomination must be in `accepted` status.

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `license_number` | string | no | External license number |
| `license_valid_from` | date | yes | `YYYY-MM-DD` |
| `license_valid_until` | date | yes | `YYYY-MM-DD` (≥ `license_valid_from`) |

**Example:**

```json
{
  "license_number": "LIC-2026-001",
  "license_valid_from": "2026-08-01",
  "license_valid_until": "2027-07-31"
}
```

**Response (`200`):** nomination `status` becomes `licensed`, `is_reserved` remains `true`.

**Errors:** `422` — nomination is not `accepted`, or validation failed.

---

### 7.6 Cancel nomination or revoke license

```http
POST /nominations/{id}/cancel
```

| Current status | Result |
|----------------|--------|
| `pending` | → `cancelled` |
| `accepted` | → `cancelled` (reservation released) |
| `licensed` (still valid) | → `revoked` (staff available again) |
| other | `422` |

---

### 7.7 Submit change request

```http
POST /nominations/{id}/change-requests
```

Position / organization updates and early release require **WRRB staff approval** in Mafunzo.

Allowed when nomination is `accepted` or actively `licensed`. Only **one pending** request per nomination.

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `change_type` | string | yes | `update` or `release` |
| `proposed_final_position` | string | for update* | New eligible position |
| `proposed_organization_name` | string | for update* | New organization name |
| `reason` | string | no | Reason for change |

\* For `update`, at least one of position or organization must differ from current values.

**Example — change position:**

```json
{
  "change_type": "update",
  "proposed_final_position": "manager",
  "reason": "Promoted to warehouse manager seat"
}
```

**Example — request release:**

```json
{
  "change_type": "release",
  "reason": "Staff left the warehouse"
}
```

---

### 7.8 List change requests

```http
GET /nominations/{id}/change-requests
```

---

### 7.9 Get / cancel change request

```http
GET  /change-requests/{id}
POST /change-requests/{id}/cancel
```

Cancel is only for **pending** requests (withdraw before WRRB decides).

---

## 8. Nomination object fields

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

## 9. Optional callback webhook

If you provide `callback_url` when creating a nomination, Mafunzo POSTs the **full nomination object** to that URL when status changes.

```http
POST https://your-licensing-app.example.com/hooks/mafunzo
Content-Type: application/json

{
  "id": 12,
  "registration_number": "WRRB/2026/1/0004",
  "final_position": "store_keeper",
  "status": "accepted",
  "is_reserved": true
}
```

**Recommendation:** Use callbacks **and** poll `GET /nominations/{id}` as a backup.

---

## 10. Recommended integration flow

```
1. GET  /trained-staff
        → Pick eligible people by position

2. POST /nominations
        → Nominate (trainee notified in Mafunzo)

3. GET  /nominations/{id}
        → Poll until accepted / rejected / expired
        (and/or handle callback_url)

4. POST /nominations/{id}/issue
        → When license is granted (with validity dates)

5. POST /nominations/{id}/cancel
        → If application withdrawn or license revoked

6. POST /nominations/{id}/change-requests
        → For position/org changes or early release
        → WRRB approves/rejects in Mafunzo UI
```

---

## 11. cURL quick test

Replace `<token>` with the token provided by WRRB.

```bash
TOKEN="<token>"
BASE="http://41.59.85.9/api/v1/licensing"

# 1. List trained staff
curl -s "$BASE/trained-staff?position=manager" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# 2. Create nomination
curl -s -X POST "$BASE/nominations" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "registration_number": "WRRB/2026/1/0004",
    "final_position": "store_keeper",
    "licensing_application_id": "LA-2026-100",
    "organization_name": "Demo Warehouse Ltd"
  }'

# 3. Poll nomination status
curl -s "$BASE/nominations/12" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# 4. Issue license
curl -s -X POST "$BASE/nominations/12/issue" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "license_number": "LIC-2026-001",
    "license_valid_from": "2026-08-01",
    "license_valid_until": "2027-07-31"
  }'
```

---

## 12. HTTP status codes summary

| Code | Meaning |
|------|---------|
| `200` | Success (GET, issue, cancel) |
| `201` | Created (nomination, change request) |
| `401` | Invalid or missing API token |
| `404` | Resource not found |
| `422` | Validation error or business rule violation |
| `503` | Licensing API not configured on server |

---

## 13. What is NOT part of this API

| Action | Where it happens |
|--------|------------------|
| Trainee Accept / Reject nomination | Mafunzo web app (trainee login) |
| WRRB approve / reject change requests | Mafunzo → Application Management → License change requests |
| Training, exams, certificates | Mafunzo training module (separate) |
| Warehouse operator interviews | Mafunzo interview module (separate, web only) |

---

## 14. Support

For API token, connectivity issues, or integration questions, contact **WRRB IT / Mafunzo administrator**.

When reporting issues, include:

- Endpoint and HTTP method  
- Request body (redact token)  
- Response status and body  
- Timestamp (UTC)  

---

*Warehouse Receipt Regulatory Board (WRRB) — Mafunzo Licensing API Integration Guide*
