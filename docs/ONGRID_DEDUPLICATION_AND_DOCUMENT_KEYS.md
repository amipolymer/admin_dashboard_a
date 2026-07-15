# OnGrid initiate — deduplicationKeys & document/verification keys

**Status:** Implemented  
**Code:** `OnGrid::ongridEmployeeId()`, `OnGrid::deduplicationId()`, `verificationDocumentUid()`

---

## Rule (joiner id = 1)

| Field | Format | Example |
|-------|--------|---------|
| `employeeId` | `EMP_{id}` | `"EMP_1"` |
| `professionId` | `EMP_{id}` | `"EMP_1"` |
| `deduplicationKeys` | `EMP_GID_{id}` | `["EMP_GID_1"]` |
| `verifications[].key` | `EMP_GID_{id}` | `"EMP_GID_1"` |
| Profile `documents[].key` | `EMP_GID_{id}` | `"EMP_GID_1"` |
| Nested file `documents[].key` | `EMP_GID_{id}` | `"EMP_GID_1"` |
| **Identity `documentUID`** | **actual document number** | PAN → `"ABCDE1234F"`, Aadhaar → `"767688980900"` |
| **Check `documentUID`** (no doc number) | `{CODE}_EMP_{id}` | `"CCRV_EMP_1"`, `"EMPV_EMP_1"` |
| Nested employment / education file `documentUID` | `{CODE}_EMP_{id}` or `{CODE}_EMP_{id}_{n}` | `"EMPV_EMP_1_1"` (salary slip) |
| EMPV `employmentRecord.employeeId` | `EMP_{id}` | `"EMP_1"` |
| EMPV `lastWorkingDate` | HR **Employment verification start date** (BGV modal) or profile | not required from candidate portal |

---

## Example payload

```json
{
  "employeeId": "EMP_1",
  "professionId": "EMP_1",
  "deduplicationKeys": ["EMP_GID_1"],
  "uid": "767688980900",
  "documents": [
    {
      "documentType": "Aadhaar",
      "documentUID": "767688980900",
      "key": "EMP_GID_1",
      "documents": [{ "documentType": "Aadhaar", "key": "EMP_GID_1", "documentUID": "767688980900", "fileDataType": "Base64", "fileName": "aadhaar.pdf", "fileContent": "..." }]
    },
    {
      "documentType": "PANCard",
      "documentUID": "ABCDE1234F",
      "key": "EMP_GID_1",
      "documents": [{ "documentType": "PANCard", "key": "EMP_GID_1", "documentUID": "ABCDE1234F", "fileDataType": "Base64", "fileName": "pan.pdf", "fileContent": "..." }]
    }
  ],
  "verifications": [
    { "code": "CCRV", "key": "EMP_GID_1", "data": { "documentUID": "CCRV_EMP_1" } },
    { "code": "PANV", "key": "EMP_GID_1", "data": { "documentUID": "ABCDE1234F" } },
    { "code": "EMPV", "key": "EMP_GID_1", "data": { "documentUID": "EMPV_EMP_1", "employmentRecord": { "employeeId": "EMP_1", "employerName": "...", "documents": [] } } }
  ]
}
```

---

## Helpers

- `ongridEmployeeId($employee)` → `EMP_{id}` — profile `employeeId`, `professionId`, EMPV record
- `deduplicationId($employee)` → `EMP_GID_{id}` — `deduplicationKeys`, verification `key`
- `verificationDocumentUid($code, $employee)` → e.g. `CCRV_EMP_1` (checks without a document number)
- PAN / Aadhaar profile `documentUID` → PAN number / 12-digit Aadhaar from candidate Info tab
