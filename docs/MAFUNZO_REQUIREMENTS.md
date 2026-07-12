# Mafunzo — Updated System Requirements

**Warehouse Receipt Regulatory Board (WRRB)**  
Document version: 2026-07-12  
Status: Phase 1 improvements **implemented in the system** (payment remains staff-approved)

This document updates the Mafunzo requirements with usability and process improvements. It does **not** replace the existing training lifecycle; it extends it.

---

## 1. Core flow (unchanged)

```text
Register (+ profile photo)
  → Staff approve registration (issue WRRB registration number)
    → Complete trainee profile
      → Apply for published course
        → Trainee confirms payment (control number / offline channel)
          → Staff verify account + payment   ← remains manual in Phase 1
            → Attendance (QR)
              → Exam results → Publish results
                → Certificate (optional)
                → Warehouse ID card (generate → publish → download / QR verify)
```

### Payment rule (Phase 1 — confirmed)

| Item | Requirement |
|------|-------------|
| Trainee action | Confirm payment after paying via official WRRB channel / control number |
| Staff action | **Must** verify payment (and account) before the application proceeds |
| Payment gateway / auto-verify | **Out of scope for Phase 1** — planned for **next system phase** |
| Rationale | Integration with the payment system will come later; current trust model stays staff-controlled |

---

## 2. Updated functional requirements (Phase 1 improvements)

These requirements improve the current system without changing payment approval.

### 2.1 Trainee progress / status tracker

| ID | Requirement |
|----|-------------|
| REQ-UX-01 | The trainee dashboard (or application detail) shall show a clear step tracker for: Registered → Approved → Applied → Payment confirmed → Staff verified → Attended → Exam published → Certificate / ID card |
| REQ-UX-02 | Each step shall show status: not started, in progress, waiting for staff, or complete |
| REQ-UX-03 | The tracker shall indicate the **next action** for the trainee (or “waiting for staff”) |

### 2.2 Notifications / reminders

| ID | Requirement |
|----|-------------|
| REQ-NTF-01 | The system shall notify the trainee when registration is approved or rejected |
| REQ-NTF-02 | The system shall notify the trainee when staff verifies payment / application is ready for training |
| REQ-NTF-03 | The system shall notify the trainee when exam results are published |
| REQ-NTF-04 | The system shall notify the trainee when an identity card is published and ready to download |
| REQ-NTF-05 | Channel may be in-app and/or email (SMS optional later); delivery must not block staff workflows |

### 2.3 Identity card eligibility & publishing

| ID | Requirement |
|----|-------------|
| REQ-ID-01 | Eligibility remains: official registration number + full application approval path + exam passed + results published + profile photo |
| REQ-ID-02 | Staff and trainee views shall show an **eligibility checklist** listing each missing item before generate/download |
| REQ-ID-03 | When exam results are published and the trainee is otherwise eligible, the system **may auto-create an ID card draft**; staff still **publish** manually |
| REQ-ID-04 | Published cards remain downloadable by the trainee; public QR verification remains available |
| REQ-ID-05 | Card validity remains **3 years** from issue date |
| REQ-ID-06 | Staff may revoke a published card; revoked cards must fail public verification |

### 2.4 ID card expiry awareness (Phase 1 light)

| ID | Requirement |
|----|-------------|
| REQ-ID-07 | The system shall display issue and expiry dates on the card and in staff/trainee views |
| REQ-ID-08 | Full renewal workflow (re-issue after expiry without full re-registration) is **deferred** unless scheduled in a later phase |

### 2.5 Staff application checks

| ID | Requirement |
|----|-------------|
| REQ-STAFF-01 | Staff verification of **account** and **payment** remains required (Phase 1) |
| REQ-STAFF-02 | UI may combine both checks into one “Approve payment package” action **only if** both are confirmed together; underlying records must still store both verifications |
| REQ-STAFF-03 | Each application and ID card shall show a simple **activity timeline** (who approved, verified, published, revoked, and when) |

### 2.6 Attendance and exam (optional gate — confirm with WRRB)

| ID | Requirement |
|----|-------------|
| REQ-ATT-01 | Attendance via QR remains as today |
| REQ-ATT-02 | **Optional business rule (to confirm):** block exam mark entry or “pass” publishing until a configured minimum attendance is met. If not confirmed by WRRB, do not enforce in Phase 1 |

### 2.7 Legacy vs new applicants

| ID | Requirement |
|----|-------------|
| REQ-REG-01 | Legacy trained persons and new applicants share the same official WRRB registration number series on approval |
| REQ-REG-02 | Legacy **certificate number** (if any) is stored separately and must not replace the official WRRB registration number |
| REQ-REG-03 | Staff UI shall clearly label legacy vs new so certificate number is not confused with WRRB registration number |

### 2.8 Role dashboards

| ID | Requirement |
|----|-------------|
| REQ-UX-04 | Dashboard shall highlight role-specific “work for today” (e.g. pending registrations, pending payment verifications, eligible ID cards, unpublished results) |

---

## 3. Explicit non-goals for Phase 1

| Item | Status |
|------|--------|
| Automatic payment verification via payment gateway | **Next phase** |
| Removing staff payment approval | **Not allowed** in Phase 1 |
| Full ID renewal / re-issue process after expiry | Deferred (unless prioritized later) |
| Changing CR80 2-page Kiswahili ID card layout rules already delivered | Keep current PDF design unless a separate change request is raised |

---

## 4. Phase 2 (next system phase) — payment integration

When payment integration is delivered:

| ID | Requirement (future) |
|----|----------------------|
| REQ-PAY-F01 | Integrate with WRRB payment system / control-number confirmation |
| REQ-PAY-F02 | Optionally auto-mark payment verified on successful gateway confirmation |
| REQ-PAY-F03 | Retain staff override / exception handling for disputed or offline payments |
| REQ-PAY-F04 | Phase 1 staff-approval path must remain available as fallback until gateway is trusted in production |

---

## 5. Roles (no change)

| Role | Responsibilities |
|------|------------------|
| Trainee | Register, profile, apply, confirm payment, attend, view results, download published ID |
| Staff / App management | Registration approval, application review, account & payment verify, attendance, exams, certificates, ID generate/publish/revoke |
| Trainer | Enter exam marks |
| Admin | Staff capabilities + users, courses, publish/export results, audit logs |
| Super Admin | Admin + WRMS / TMX modules |

---

## 6. Acceptance summary

Phase 1 is accepted when:

1. Core flow still requires **staff payment verification**.
2. Trainee can see progress and next step.
3. Notifications cover approval, verification, results, and ID publish (as implemented channels allow).
4. ID eligibility checklist is visible; draft may auto-create; publish remains staff-controlled.
5. Timeline / audit visibility exists for key application and ID actions.
6. Payment gateway work is documented as Phase 2 only.

---

## 7. Related documents

- `docs/MAFUNZO_USER_TRAINING.md` — user training guide (operational how-to)
- This file — requirements for agreed improvements and phase boundaries
