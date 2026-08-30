# Interview module — testing plan

**Module:** Operator Interview Scoring  
**URL:** `/interviews`  
**Shared with training:** login `users` table only  
**Do not test against:** training apply / payment / exam / licensing flows (they must stay unchanged)

All demo accounts use password: **`Interview@2026`**

Seed locally (idempotent):

```bash
php artisan db:seed --class=InterviewDemoSeeder
```

---

## 1. Test accounts

| Role in interview module | Email | System `users.role` | What they should see |
|--------------------------|-------|---------------------|----------------------|
| Super admin (existing) | `edward.kafuka@wrrb.go.tz` | `super_admin` | Full access without interview-role rows |
| Interview admin | `interview.admin@wrrb.test` | `interview` | Companies, question sets, sessions, Users |
| Panelist 1 | `interview.panelist1@wrrb.test` | `interview` | Dashboard, assigned sessions, own scoring form |
| Panelist 2 | `interview.panelist2@wrrb.test` | `interview` | Same as Panelist 1 |
| Chair (also a panelist) | `interview.chair@wrrb.test` | `interview` | Scoring if assigned + chair review after consolidation |
| Approver | `interview.approver@wrrb.test` | `interview` | Review / approve after chair confirms |
| Viewer | `interview.viewer@wrrb.test` | `interview` | Read-only sessions and results |
| Negative control | `staff.nointerview@wrrb.test` | `staff` | **No** Interviews nav, **403** on `/interviews` |

Demo accounts with system role `interview` are **interview-only**: they do not see Application Management or Training menus.

Demo data also created:

- Company: **Demo Warehouse Company Ltd**
- Question set: **Mahojiano ya Kampuni ya mwendesha ghala — Seti Rasmi** (15 questions, max 300)

---

## 2. Access checks (do first)

1. Log in as `staff.nointerview@wrrb.test`.  
   Confirm **Interviews** is not in the nav. Open `/interviews` → expect 403.
2. Log in as `interview.panelist1@wrrb.test`.  
   Confirm **Interviews** appears. Confirm **Companies / Question sets / Module roles** are **not** in the interview sub-nav.
3. Log in as `interview.admin@wrrb.test`.  
   Confirm full interview sub-nav.
4. Open Application Management / training pages as interview admin.  
   Confirm training behaviour is unchanged (staff still sees app management; trainees still apply as before).

---

## 3. Happy path (end to end)

Use a **private / incognito window per user**, or log out between steps.

### A. Admin prepares the session

Log in: `interview.admin@wrrb.test`

1. **Module roles** — confirm seeded roles exist (do not delete them).
2. **Companies** — Demo Warehouse Company Ltd is listed; optionally add a second company.
3. **Question sets** — open the demo set; confirm 6 questions and max score 60.
4. **Sessions → Schedule session**
   - Company: Demo Warehouse Company Ltd  
   - Question set: Mahojiano ya Kampuni ya mwendesha ghala — Seti Rasmi  
   - Interviewee: `Hassan Juma` / title `CEO`  
   - Type: New operator  
   - Date/time/venue: any  
   - Pass mark: `50`  
   - Panelists: Panelist One, Panelist Two, Interview Chair  
   - Chairperson: Interview Chair  
5. Save. Note the session code (e.g. `INT-YYYYMMDD-XXXX`).
6. Open the session → **Open scoring**. Status becomes **Scoring**.

### B. Panelists score privately

Log in: `interview.panelist1@wrrb.test`

1. Open the session → **Enter my scores**.
2. Confirm you cannot see other panelists’ marks.
3. **Save draft** with some scores blank → should succeed.
4. Try **Submit scores** with a blank score → blocked (“score all questions”).
5. Enter all scores (suggest 6–8 per question) + a comment on one question.
6. Submit and confirm lock. Re-open form → fields disabled; no further submit.

Log in: `interview.panelist2@wrrb.test`

1. Repeat scoring with **different** marks (e.g. 4–9) so variance can flag.
2. Submit.

Log in: `interview.chair@wrrb.test`

1. Enter remaining scores (chair is a panelist) and submit.

After the **last** panelist submits, status should become **Under review** and a consolidated total should appear.

### C. Chair review

Stay as chair (or admin):

1. **View consolidated results**.
2. Confirm percentage = (sum of per-question averages / 60) × 100.
3. Confirm pass/fail vs 50%.
4. Confirm you can **Download consolidated PDF**.
5. Record recommendation (Recommend / Do not recommend / Conditional) + chair notes → **Confirm review**.

### D. Approver decision

Log in: `interview.approver@wrrb.test`

1. Open the same session review page.
2. **Approve decision** → status **Completed**.
3. PDF still downloads.

Optional second session: run to chair confirm, then **Reject decision** and confirm the session is not marked completed.

### E. Viewer

Log in: `interview.viewer@wrrb.test`

1. Can open dashboard / session / review / PDF.
2. Cannot open companies, question sets, module roles, or create sessions (403).

---

## 4. Negative and edge cases

| # | Action | Expected |
|---|--------|----------|
| 1 | Panelist opens a session they are not assigned to | 403 |
| 2 | Panelist opens scoring before **Open scoring** | 403 |
| 3 | Score above a question’s max mark | Validation / error |
| 4 | Admin tries to remove a panelist who already submitted | Error; assignment stays |
| 5 | Admin opens scoring with zero panelists | Error |
| 6 | Approver tries to approve before chair confirm | Error |
| 7 | Trainee user with no interview role visits `/interviews` | 403; training menus unchanged |
| 8 | Super admin visits `/interviews` without interview-role rows | Allowed |

---

## 5. Training isolation checks

After interview testing, as a **trainee** (existing training account) and as **staff**:

- Apply for training / my applications / exam results still work.
- Application Management still works for staff/admin.
- Licensing nomination screens unchanged.
- No new columns or required fields on training forms.

If any of those break, stop and report — interview work should not touch those controllers.

---

## 6. Suggested score sheet for the first demo session

Use these marks so the maths is easy to check (max 10 per question):

| Question | Panelist 1 | Panelist 2 | Chair | Average |
|----------|------------|------------|-------|---------|
| 1 Governance | 8 | 7 | 9 | 8.00 |
| 2 Operations | 7 | 6 | 7 | 6.67 |
| 3 Compliance | 8 | 8 | 7 | 7.67 |
| 4 Quality | 6 | 9 | 7 | 7.33 |
| 5 Records | 7 | 7 | 8 | 7.33 |
| 6 Integrity | 8 | 5 | 7 | 6.67 |
| **Total of averages** | | | | **43.67 / 60** |
| **Percentage** | | | | **72.78%** → **Pass** |

Question 4 spread is 3 (6 vs 9); question 6 spread is 3 (5 vs 8). Raise one mark by 1 if you want a variance flag (threshold is **> 3**).

---

## 7. Pass criteria for this round

- All seven accounts behave as in the table in section 1.
- Happy path completes: schedule → score (private + lock) → consolidate → chair → approve → PDF.
- Negative-control staff cannot enter the module.
- Training and licensing screens still work as before.
