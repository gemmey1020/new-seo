SEO OS — HUMAN VERIFICATION CHECKLIST

Purpose: تأكيد الإنسان لمجهود الآلة
Drift Policy: ZERO
Audience: Jemy (Architect) × Grafity (Agent)
Rule: No fixes during verification. Observation only.

🧱 SECTION 0 — SYSTEM ENTRY POINT
0.1 Add Website
```
| Check        | Expected                         | Result | Status |
| ------------ | -------------------------------- | ------ | ------ |
| Form renders | بدون Errors                      |        | ⬜      |
| Validation   | يمنع URL غلط                     |        | ⬜      |
| Submit       | Creates Site record              |        | ⬜      |
| Redirect     | Goes to Site Dashboard           |        | ⬜      |
| Bootstrap    | Pages / Meta / Audit initialized |        | ⬜      |

```
🔴 FAIL IF: الموقع يتضاف بس من غير أي Data أولية
🟢 PASS IF: الموقع يدخل فورًا في دورة النظام

__________________________
SECTION 1 — SIDEBAR INTEGRITY (GLOBAL)

Rule: كل عنصر Sidebar لازم يفتح صفحة حقيقية، مش Placeholder

Sidebar Item Checklist (كرر الجدول لكل عنصر)

```
| Sidebar Item | Page Opens | Data Loads | No JS Errors | Meaningful Content | Status |
| ------------ | ---------- | ---------- | ------------ | ------------------ | ------ |
| Dashboard    |            |            |              |                    | ⬜      |
| Pages        |            |            |              |                    | ⬜      |
| Page Details |            |            |              |                    | ⬜      |
| Meta         |            |            |              |                    | ⬜      |
| Audits       |            |            |              |                    | ⬜      |
| Sitemap      |            |            |              |                    | ⬜      |
| Redirects    |            |            |              |                    | ⬜      |
| Settings     |            |            |              |                    | ⬜      |

```
🔴 FAIL IF:

Page تفتح فاضية

Loading لا نهائي

Console error

Static text
____________________


📄 SECTION 2 — PAGE-LEVEL VERIFICATION
لكل صفحة داخل النظام
2.1 Truth Check
| Question                      | Yes / No |
| ----------------------------- | -------- |
| Data from DB (not hardcoded)? | ⬜        |
| Matches crawler/audit output? | ⬜        |
| Reflects real site state?     | ⬜        |


| Question                              | Yes / No |
| ------------------------------------- | -------- |
| SEO Specialist يفهمها لوحده؟          | ⬜        |
| Clear purpose (Why this page exists)? | ⬜        |
| No need for Architect explanation?    | ⬜        |

| Type                       | Mark |
| -------------------------- | ---- |
| Read-only (Analyzer)       | ⬜    |
| Control (User can change)  | ⬜    |
| Execution (System mutates) | ⬜    |

⚠️ NOTE:
صفحة Read-only مش عيب
صفحة بلا Purpose = عيب قاتل
🔁 SECTION 3 — FLOW CONSISTENCY
Test This Exact Flow
Add Website
→ Crawl
→ Audit
→ Page List
→ Page Details
→ Meta
→ Decision / Action
| Step              | Smooth | Blocked | Confusing |
| ----------------- | ------ | ------- | --------- |
| Crawl starts      | ⬜      | ⬜       | ⬜         |
| Audit appears     | ⬜      | ⬜       | ⬜         |
| Pages clickable   | ⬜      | ⬜       | ⬜         |
| Meta editable     | ⬜      | ⬜       | ⬜         |
| Decision possible | ⬜      | ⬜       | ⬜         |

🔴 FAIL IF:
User يوصل لنهاية مسدودة بدون رسالة واضحة

🧨 SECTION 4 — SILENT FAILURE DETECTION

أخطر مرحلة
| Check                               | Exists? |
| ----------------------------------- | ------- |
| Button works but no effect          | ⬜       |
| API returns 200 but no DB change    | ⬜       |
| UI says success but state unchanged | ⬜       |
| Data masked by fallback             | ⬜       |

	

🔴 أي ⬜ هنا = CRITICAL

🧠 SECTION 5 — ROLE ALIGNMENT (SEO Specialist)
اسأل السؤال ده بوضوح:

“إيه اللي الشخص ده يقدر يعمله لوحده؟”
| Capability                     | Available |
| ------------------------------ | --------- |
| See SEO truth                  | ⬜         |
| Understand issues              | ⬜         |
| Fix Meta safely                | ⬜         |
| Control indexing               | ⬜         |
| Trigger execution (with guard) | ⬜         |

❌ لو محتاجك في كل خطوة → السيستم ناقص



📜 SECTION 6 — FINAL VERDICT

| Area             | Verdict         |
| ---------------- | --------------- |
| System Truth     | ⬜ PASS / ⬜ FAIL |
| UX Clarity       | ⬜ PASS / ⬜ FAIL |
| Execution Safety | ⬜ PASS / ⬜ FAIL |
| Drift Risk       | ⬜ LOW / ⬜ HIGH  |

Overall Status:

⬜ READY FOR NEXT PHASE

⬜ NEEDS REFACTOR

⬜ BLOCKED


🔐 GOVERNANCE RULES

❌ ممنوع الإصلاح أثناء الـ checklist

❌ ممنوع “ما هي شغالة”

✅ كل اختلاف بينك وبين جرافيتي يرجع للبند ده

✅ أي Feature جديدة لازم تضيف بند هنا