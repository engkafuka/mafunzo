# Mafunzo architecture & flowcharts

WRRB training system (Laravel 12 + PostgreSQL).  
Live: `http://41.59.85.9`

This document covers:

1. Current live deployment  
2. Docker Phase 1 (planned cutover)  
3. Application / module architecture  
4. Roles & access  
5. Trainee lifecycle flowchart  
6. Licensing integration flowchart  
7. License change-request flowchart  

---

## 1. Current live deployment

Host PHP + host nginx + host PostgreSQL on one Ubuntu VPS.

```mermaid
flowchart TB
    subgraph users [Users]
        B[Browser / Trainee / WRRB staff]
        P[Postman / Licensing app]
        A[pgAdmin on PC via SSH tunnel]
    end

    subgraph vps ["Live VPS  41.59.85.9"]
        N[Host nginx :80]
        PHP[PHP-FPM + Laravel Mafunzo]
        PG[(PostgreSQL :5432<br/>DB: mafunzo)]
        FS[storage/ uploads, logs, sessions]
        N --> PHP
        PHP --> PG
        PHP --> FS
    end

    B -->|HTTPS/HTTP| N
    P -->|Bearer token<br/>/api/v1/licensing/*| N
    A -.->|SSH tunnel 5433→5432| PG
```

| Layer | Technology |
|--------|------------|
| Web | nginx |
| App | Laravel 12, PHP 8.2+ |
| DB | PostgreSQL (`mafunzo` / `mafunzo_user`) |
| Cache / session / queue | `file` / `file` / `sync` (no Redis yet) |
| Integrations | Licensing API (Bearer), optional WRMS / TMX (super admin) |

---

## 2. Docker Phase 1 (safe target)

**In Docker:** nginx + PHP-FPM app.  
**On host (unchanged):** PostgreSQL.  
**Test port:** `:8080` while current site stays on `:80`.

```mermaid
flowchart TB
    subgraph users [Users]
        B[Browser]
        L[Licensing app / Postman]
    end

    subgraph docker [Docker Compose]
        NGX[mafunzo-nginx<br/>:8080 → :80]
        APP[mafunzo-app<br/>PHP-FPM 8.3]
        NGX -->|FastCGI :9000| APP
    end

    subgraph host [Host OS]
        PG[(PostgreSQL :5432<br/>existing live data)]
        ST[./storage bind mount]
        PUB[./public bind mount]
    end

    B -->|http://IP:8080| NGX
    L -->|/api/v1/licensing| NGX
    APP -->|host.docker.internal| PG
    APP --> ST
    NGX --> PUB
```

Rollback: `docker compose down` → start host nginx again.

---

## 3. Application architecture

```mermaid
flowchart LR
    subgraph clients [Clients]
        WEB[Web UI<br/>Breeze / Blade]
        API[Licensing API<br/>Bearer token]
        PUB[Public pages<br/>QR attendance / ID verify]
    end

    subgraph laravel [Laravel]
        MW[Middleware<br/>auth, roles, licensing.api]
        CTR[Controllers]
        SVC[Support services]
        MDL[Eloquent models]
        NTF[Database notifications]
        MW --> CTR --> SVC --> MDL
        CTR --> NTF
    end

    subgraph data [Data]
        PG[(PostgreSQL)]
        FILES[storage/]
    end

    WEB --> MW
    API --> MW
    PUB --> CTR
    MDL --> PG
    SVC --> FILES
```

### Main modules

| Module | Purpose |
|--------|---------|
| Auth & registration | Sign up, WRRB approve/reject, resubmit |
| Courses | Create / publish training sessions |
| Training applications | Apply, control number, verify payment |
| Attendance | QR sessions + public scan |
| Exams | Upload scores, publish results, assign final position |
| Certificates | Generate / issue PDF certificates |
| Identity cards | Generate, publish, verify, revoke |
| Reports | Trained users export |
| Licensing API | Trained staff list, nominations, issue, change requests |
| Audit trail | Admin/super-admin activity log |
| WRMS / TMX | Super-admin data views |

---

## 4. Roles & access

```mermaid
flowchart TB
    U[Authenticated user]
    U --> R{role}

    R -->|trainee| T[Registration gate]
    T -->|pending/rejected| P[Pending / resubmit only]
    T -->|approved| TR[Training, exams, ID cards,<br/>license Accept/Reject]

    R -->|staff / admin / super_admin| AM[Application Management]
    AM --> REG[Registrations]
    AM --> APP[Applications & payments]
    AM --> ATT[Attendance]
    AM --> EX[Exam results]
    AM --> CERT[Certificates & ID cards]
    AM --> LCR[License change requests]
    AM --> RPT[Reports]

    R -->|trainer| TRN[Trainer exam results]

    R -->|admin / super_admin| ADM[Users, courses, audit logs]
    R -->|super_admin only| SA[WRMS API + TMX auction]
```

| Role | Typical access |
|------|----------------|
| `trainee` | Own registration, apply for courses, view results/IDs, respond to license nominations |
| `staff` | Application Management (ops) |
| `trainer` | Exam results portal |
| `admin` | Ops + users + courses + audit |
| `super_admin` | Everything including WRMS/TMX |

---

## 5. Trainee lifecycle flowchart

End-to-end path from sign-up to licensed warehouse staff.

```mermaid
flowchart TD
    A[Create account] --> B[Submit registration]
    B --> C{WRRB staff review}
    C -->|reject| D[Trainee resubmits]
    D --> C
    C -->|approve| E[Select course & apply]
    E --> F[Staff enter 12-digit control number]
    F --> G[Staff Verify payment]
    G --> H[Attendance via QR]
    H --> I[Staff/trainer enter exam scores]
    I --> J[Publish exam results]
    J --> K{Passed?}
    K -->|no| L[Shown as failed]
    K -->|yes| M[Registration number issued<br/>final position assigned]
    M --> N[Certificate & warehouse ID card]
    N --> O[Eligible for licensing API]
```

### Exam final-position rules (summary)

| Applied / rule | Pass condition | Else |
|----------------|----------------|------|
| Score ≥ 50 | Exam passed | Fail |
| Manager | Degree + score ≥ 70 | Fallback group* |
| Quality Assurance | Agriculture diploma + score ≥ 60 | Fallback group* |
| Field | `assigned_position` if set | — |

\*Fallback group: Documentation / Weight Assistant / Store Keeper.

---

## 6. Licensing integration flowchart

Licensing application ↔ Mafunzo.

```mermaid
flowchart TD
    L[Licensing app] -->|GET /trained-staff<br/>Bearer token| S[List eligible staff]
    S --> N[POST /nominations]
    N --> T[Notify trainee in Mafunzo]
    T --> R{Trainee within 7 days}
    R -->|Reject / expire| X[Available again]
    R -->|Accept| RES[Reserved<br/>cannot accept another]
    RES --> I{License issued?}
    I -->|POST .../issue<br/>valid_from / valid_until| LIC[status = licensed<br/>reserved until validity ends]
    I -->|POST .../cancel| X
    LIC -->|validity ends or revoke| X
```

### Nomination statuses

```text
pending → accepted → licensed
   │          │          │
   │          │          └─ revoked
   │          └─ cancelled
   ├─ rejected
   └─ expired (7-day window)
```

### API surface

| Method | Path |
|--------|------|
| GET | `/api/v1/licensing/trained-staff` |
| POST | `/api/v1/licensing/nominations` |
| GET | `/api/v1/licensing/nominations/{id}` |
| POST | `/api/v1/licensing/nominations/{id}/issue` |
| POST | `/api/v1/licensing/nominations/{id}/cancel` |
| POST | `/api/v1/licensing/nominations/{id}/change-requests` |

Auth: `Authorization: Bearer LICENSING_API_TOKEN`

---

## 7. License change-request flowchart

Staff or company may request a position/org change or early release. **WRRB approval required.**

```mermaid
flowchart TD
    A[Accepted or actively licensed nomination]
    A --> B{Who requests?}
    B -->|Trainee UI| C[Submit change request]
    B -->|Licensing API| C
    C --> D{Already a pending request?}
    D -->|yes| E[Block — wait for WRRB]
    D -->|no| F[status = pending]
    F --> G[WRRB Application Management]
    G --> H{Decision}
    H -->|Approve update| I[Apply new position / organization<br/>callback to licensing]
    H -->|Approve release| J[Cancel reservation or revoke license]
    H -->|Reject| K[Nomination unchanged]
    H -->|Requester cancel| L[Withdraw pending request]
```

Change types: `update` (position and/or organization) · `release` (leave company).

---

## 8. Request path inside Laravel (web vs API)

```mermaid
sequenceDiagram
    participant C as Client
    participant N as nginx
    participant L as Laravel
    participant M as Middleware
    participant Ctrl as Controller
    participant DB as PostgreSQL

    C->>N: HTTP request
    N->>L: public/index.php
    alt Web UI
        L->>M: auth + role middleware
        M->>Ctrl: Blade controller
    else Licensing API
        L->>M: licensing.api Bearer token
        M->>Ctrl: Api\\Licensing\\*
    end
    Ctrl->>DB: Eloquent / query
    DB-->>Ctrl: rows
    Ctrl-->>C: HTML or JSON
```

---

## 9. Key data entities

```mermaid
erDiagram
    users ||--o{ training_applications : applies
    users ||--o{ license_nominations : nominated
    courses ||--o{ training_applications : has
    training_applications ||--o{ license_nominations : may_have
    license_nominations ||--o{ license_change_requests : requests
    users ||--o{ license_change_requests : requested_by

    users {
        string role
        string registration_status
    }
    courses {
        int session_year
        string name
    }
    training_applications {
        string registration_number
        string status
        string assigned_position
        boolean exam_passed
    }
    license_nominations {
        string status
        string final_position
        date license_valid_until
    }
    license_change_requests {
        string change_type
        string status
    }
```

---

## Related docs

- [Licensing API](LICENSING_API.md) — endpoint contract for the licensing team  
- [Docker Phase 1 deploy](DOCKER_DEPLOY.md) — safe cutover on the live VPS  
