# OnGrid API — Implementation vs Requirements Comparison

**Prepared:** June 2026  
**Primary code:** `app/Support/OnGrid.php`, `app/Http/Controllers/OnGridWeb/OnGridWebController.php`  
**Requirements sources:** Postman `OnGrid API Collection.postman_collection.json`, `docs/ONGRID_DEDUPLICATION_AND_DOCUMENT_KEYS.md`, HR onboarding flow

---

## Summary

| Area | Status |
|------|--------|
| Core initiate API (`POST /individuals/initiate`) | Implemented |
| ID / deduplication / documentUID rules (latest) | Implemented |
| Profile + EMPV + EDUV + PANV payload mapping | Implemented |
| CVV (post-initiate upload + `/cvv`) | Implemented |
| HR UI (start BGV, status panel) | Implemented |
| Several verification codes in UI | Listed but not fully wired |
| Phase-2 APIs (insufficiency, report PDF, invite) | Pending |
| Staging UAT for dedup/update behaviour | Pending |

---

## Already implemented | Pending implementation

### A. API endpoints & integration

| Already implemented | Pending implementation |
|---------------------|------------------------|
| `POST /community/{id}/individuals/initiate` — `OnGrid::initiateVerification()` | `POST /community/{id}/invite` — legacy SR invite flow (removed; `deleteInvite` returns 400) |
| `GET /individual/{id}` — individual detail | Insufficiency list API (Postman phase 2) |
| `GET /individual/{id}/verificationstatus` — live BGV status | Consolidated verification report PDF API |
| `GET /community/{id}/individuals` — list individuals | Webhook / push callbacks from OnGrid (not in scope today) |
| `POST /individual/{id}/doc/cv` — CV upload for CVV | Document update APIs for individual after initiate (re-upload per doc type via separate endpoints) |
| `POST /individual/{id}/cvv` — start CVV after CV upload | Automated re-initiate when HR fixes documents (manual retry only today) |

---

### B. Latest ID / deduplication / document rules

| Already implemented | Pending implementation |
|---------------------|------------------------|
| `employeeId` = raw joiner DB id (`"1"`) via `ongridEmployeeId()` | Align with Postman note *"deduplication key and employee id needs to be same"* — APPL **intentionally splits** them (`employeeId` = `1`, `deduplicationKeys` = `EMP_GID_1`) |
| `deduplicationKeys` = `["EMP_GID_{id}"]` via `deduplicationId()` | Confirm with OnGrid staging that split `employeeId` vs `EMP_GID_*` dedup works for update (not create duplicate) |
| All `verifications[].key` = `EMP_GID_{id}` | CVV `documentUID` pattern (`CVV_EMP_GID_1`) if OnGrid requires it on `/cvv` (today only `key` is sent) |
| `documentUID` = `{CODE}_EMP_GID_{id}` via `verificationDocumentUid()` | Staging proof: re-initiate updates documents when `documentUID` matches (UAT checklist) |
| EMPV `employmentRecord.employeeId` = raw joiner id (`"1"`) | — |
| PAN profile `documents[].documentUID` = `PANV_EMP_GID_{id}` (not PAN number) | Validate PANV still passes OnGrid with custom `documentUID` (differs from Postman sample which uses PAN as `documentUID`) |
| Generic checks (CCRV, LAV, PAV, GDC, etc.) send `data.documentUID` = `{CODE}_EMP_GID_{id}` | Confirm OnGrid accepts `documentUID` on checks that Postman shows as key-only |

---

### C. Profile block (`buildInitiatePayload` → `profileFields`)

| Already implemented | Pending implementation |
|---------------------|------------------------|
| `name`, `gender`, `city`, `phone`, `email`, `dob` | `educationLevel` on profile root |
| `hasConsent` = `"true"`, `consentText` from `ONGRID_CONSENT_TEXT` | `profileImageUrl` / profile image document |
| `permanentAddress` (co, line1, locality, landmark, district, state, pincode, fullAddress) | `tags[]` |
| `currentAddress` | `customDocuments[]` |
| `fathersName`, `joiningDate` | `phoneCountryCode` / `alternatePhoneCountryCode` (stripped in `sanitizeProfilePayload` before send) |
| `alternatePhone` (10-digit only) | Full Aadhaar flow: `uid` + Aadhaar in `documents[]` for implicit UIDV |
| `uans[]` from profile UAN | `lnCode` / localized address fields |
| `otherIdentifiers.pan` when PAN in profile | Use `ONGRID_DEFAULT_PROFESSION_ID` from config — today `professionId` = joiner DB `id` |
| `professionId` + `otherProfession` from joiner id + role | Map real OnGrid profession catalogue id from HR profile field (if not joiner id) |
| Phone validation (10 digits) | — |
| Pincode validation (6 digits) | — |
| Omit `uid` unless Aadhaar PDF uploaded | Attach Aadhaar card in `documents[]` when uid is sent |

---

### D. Verification codes (HR modal → `verifications[]`)

| Code | Already implemented | Pending implementation |
|------|---------------------|------------------------|
| **CCRV** | Profile data + `key` + `documentUID` | — |
| **GDC** | Profile data + `key` + `documentUID` | — |
| **LAV** | `key` + `documentUID` (no extra data block in Postman) | — |
| **PAV** | Same as LAV | — |
| **PANV** | PAN in profile + PAN PDF in `documents[]` + `data.documentUID` | Staging UAT with `PANV_EMP_GID_*` (non-PAN documentUID) |
| **EMPV** | Single employment record, base64 PDFs (salary slip / appointment / experience letter), HR contact fields | Multiple EMPV rows (`count: 2` in Postman invite example) |
| **EMPV** | `annualCompensation`, joining/last working dates | `managerName`, `managerEmail`, `managerPhone`, `managerPhoneCountryCode` |
| **EDUV** | Multiple education rows, level mapping, base64 certificates | Per-row unique `key` when multiple EDUV in one call (today all use same `EMP_GID_{id}` key) |
| **CVV** | Post-initiate: upload CV → `POST /cvv` with `key` = `EMP_GID_{id}` | `documentUID` on CVV if required by OnGrid |
| **PCC** | Skip if no passport photo PDF | Photo not attached in initiate payload — only gate check; PCC will fail or need separate doc API |
| **DLV** | Auto-skipped: *"Driving licence / Voter ID not collected in portal yet"* | Portal fields + DL document upload + payload mapping |
| **VIDV** | Auto-skipped (same as DLV) | Voter ID portal fields + document mapping |
| **CC** | Auto-skipped: *"Not wired to portal data yet"* | Credit-check data mapping |
| **EREF** | Auto-skipped | eLockr reference check data mapping |
| **PVLF** | Shown in HR modal; sent as generic verification if selected | Confirm community supports; may need extra data |
| **Other Postman codes** (PADV, LADV, LAPV, PRC, BV, AV, PAPV, BAV, DCS) | Not in HR offering list | Add to UI + mapping if HR scope includes them |

---

### E. Documents & attachments

| Already implemented | Pending implementation |
|---------------------|------------------------|
| PDF → Base64 in payload (`fileDataType` = `Base64`) | URL-based document delivery option (signed public URL) |
| Max ~5 MB per file check (size guard in `fileAsBase64Attachment`) | Non-PDF formats where OnGrid allows image |
| PAN card → profile `documents[]` | Driving licence, voter ID, Aadhaar, passport photo in `documents[]` |
| EMPV / EDUV attachment types per Postman enums | PCC photo as `ProfileImage` or equivalent document type |

---

### F. HR application flow & storage

| Already implemented | Pending implementation |
|---------------------|------------------------|
| **Start OnGrid BGV** button + offerings modal (`offering-modal.blade.php`) | Email notification to HR on BGV start (mentioned in design doc, not wired) |
| Default offerings: CCRV, LAV, PAV, GDC, EMPV, EDUV | Config-driven default package per environment |
| Auto-skip offerings missing portal data (`filterOfferingCodes`) | Block initiate entirely if any selected check is skipped (today partial start is allowed) |
| Save `ongrid_id` = `individual.id` | Migration note for old rows that stored `inviteId` |
| Save full `ongrid_response` + `initiated_at` + `verification_codes` + `skipped_offerings` | Store per-verification `documentUID` in DB for support/debug |
| Set onboarding step `bgv_started` on success | Auto-set `bgv_completed` when all live statuses pass (today HR clicks **BGV Done** manually) |
| `OnboardingDocumentReedit::canStartBgv()` gate (post-offer, documents completed) | Retry rules when `ongrid_id` already exists (re-initiate vs new individual) — documented behaviour only |
| BGV status panel with live refresh (`bvg-status-hr.blade.php`) | Report download link in HR UI |
| EMPV last working date fix form (`setEmpvLastWorkingDate`) | — |
| Friendly error hints in `failIfError()` (consent, PAN, fileDataType, CV, EDUV) | — |

---

### G. Configuration & environment

| Already implemented | Pending implementation |
|---------------------|------------------------|
| `ONGRID_BASE_URL`, `ONGRID_USERNAME`, `ONGRID_PASSWORD`, `ONGRID_COMMUNITY_ID` | Production community ID + credentials UAT sign-off |
| `ONGRID_CONSENT_TEXT` (required, exact match) | Per-community consent text management in admin UI |
| `ONGRID_HTTP_TIMEOUT` (default 120s) | — |
| `OnGrid::isConfigured()` / `missingConfigKeys()` | Health-check route or admin “test connection” button |

---

## Example: current payload shape (joiner id = 1)

```json
{
  "employeeId": "1",
  "deduplicationKeys": ["EMP_GID_1"],
  "documents": [
    { "documentType": "PANCard", "documentUID": "PANV_EMP_GID_1", "documents": [{ "fileDataType": "Base64", "...": "..." }] }
  ],
  "verifications": [
    { "code": "CCRV", "key": "EMP_GID_1", "data": { "documentUID": "CCRV_EMP_GID_1" } },
    { "code": "EMPV", "key": "EMP_GID_1", "data": { "documentUID": "EMPV_EMP_GID_1", "employmentRecord": { "employeeId": "1", "...": "..." } } }
  ]
}
```

---

## Recommended next steps (priority)

1. **Staging UAT** — initiate → fix document → re-initiate; confirm no duplicate individual and documents update via `EMP_GID_*` + `documentUID`.
2. **PANV validation** — confirm `PANV_EMP_GID_*` documentUID works (Postman uses PAN number).
3. **PCC** — either attach photo in payload or remove from default/modal until wired.
4. **DLV / VIDV / CC / EREF** — portal data + payload, or hide from HR modal.
5. **Auto `bgv_completed`** — optional when all `verificationstatus` rows succeed.
6. **Phase-2 APIs** — insufficiency list + consolidated PDF for HR download.

---

## Reference files

| File | Purpose |
|------|---------|
| `app/Support/OnGrid.php` | All API + payload logic |
| `app/Http/Controllers/OnGridWeb/OnGridWebController.php` | HR actions |
| `docs/ONGRID_DEDUPLICATION_AND_DOCUMENT_KEYS.md` | Latest ID rules |
| `docs/ONGRID_INITIATE_VERIFICATION.md` | Original integration design |
| `OnGrid API Collection.postman_collection.json` | OnGrid API reference |
| `resources/views/pages/new-join-employee/partials/offering-modal.blade.php` | Start BGV UI |
| `resources/views/pages/new-join-employee/partials/bvg-status-hr.blade.php` | Status panel |


<!-- 
set auto matic basic sallery 12% PF amount

like this:
CTC: 4,80,000
BASIC: ADD 465000 AUTO MATIC SHOW PF 12%.
REMNING AMOUNT ADD HRA SECTION WHEN ADD HRS OR REMING AMOUNT ADD SPECIAL FILDE


MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=37a99b0212ba1f
MAIL_PASSWORD=4b45e98c5e4dbc
 -->