---
description: Sovereign appeal workflow for Omega systems. Provides a controlled, evidence-based mechanism to challenge final verdicts without breaking authority, silence, or drift policy.
---

---

name: omega-appeal
description: Sovereign appeal workflow for Omega systems. Provides a controlled, evidence-based judicial mechanism to challenge final verdicts without breaking authority, silence, or drift policy.
trigger: /omega-appeal | /appeal
authority: Founder / Architect
drift_policy: ABSOLUTE_ZERO
mode: silent
execution: none
lifecycle: event-based
---

# ⚖️ Ω Appeal Workflow

## Sovereign Appeal Gate

> This workflow is a **Judicial Safety Valve**.  
> It exists to prevent deadlock — **not** to enable debate.  
> It does NOT weaken authority.  
> It prevents authority from becoming brittle.

Without this workflow,  
**final verdicts collapse under real-world change.**

---

## Core Purpose (Single Question Only)

This workflow exists to answer **ONE question**:

> **Does NEW, MATERIAL evidence exist that invalidates a FINAL verdict?**

It does NOT ask:

- “Do we disagree?”
- “Can we do better?”
- “What if we try again?”
- “Can we move forward anyway?”

Disagreement is NOT evidence.  
Urgency is NOT evidence.  
Authority fatigue is NOT evidence.

---

## What This Workflow Is NOT

❌ NOT a re-review  
❌ NOT a second opinion  
❌ NOT a workaround  
❌ NOT a discussion forum  
❌ NOT an optimization gate  
❌ NOT a soft override  

This workflow ONLY:

- Validates evidence
- Judges admissibility
- Issues or denies **legal re-entry**

---

## Mandatory Preconditions (STRICT)

This workflow may ONLY be invoked if **ALL** are true:

1. A **FINAL VERDICT** exists from one of:
   - `omega-architecture-review`
   - `omega-systematic-debug` (hard stop)

2. The verdict includes a:
   - `VERDICT_ID`
   - Explicit final status

3. The system is currently:
   - Blocked
   - Locked
   - Or legally unable to proceed

4. **NEW evidence exists**
   that was **NOT available** during the original workflow.

🚫 If ANY precondition is missing → **STOP IMMEDIATELY**

---

## Definition — Admissible Evidence

Evidence MUST be **NEW** and **MATERIAL**.

### ✅ ACCEPTABLE Evidence

- New logs, traces, or metrics not previously observed
- Newly discovered dependency behavior
- Newly surfaced invariant violation
- Newly exposed hidden coupling
- External change (version, policy, environment)
- Proven logical error in original reasoning chain

### ❌ REJECTED Evidence

- Reworded arguments
- Emotional pressure
- Time constraints
- “We didn’t think hard enough”
- “Let’s just try”
- Desire to move forward
- Founder frustration

🚨 Attempting to pass opinion as evidence = **VIOLATION**

---

## Appeal Structure (MANDATORY ORDER)

You MUST complete **ALL** sections.  
Skipping ANY section invalidates the appeal.

---

## Section 1 — Referenced Verdict (IDENTITY LOCK)

You MUST state:

- `SOURCE_WORKFLOW`
- `VERDICT_ID`
- Exact verdict outcome
- Date / context

🚫 No paraphrasing  
🚫 No reinterpretation  

If VERDICT_ID cannot be referenced → **STOP**

---

## Section 2 — New Evidence Declaration

For **EACH** evidence item:

- `EVIDENCE_ID`
- Source
- Why it was unavailable before
- Which assumption / conclusion it challenges

Rules:

- Facts ONLY
- No interpretation
- No proposed action

If evidence cannot be verified → **STOP**

---

## Section 3 — Impact on Original Verdict

Explain precisely:

- Which conclusion becomes invalid
- Why the verdict can no longer stand
- Which logical dependency is broken

🚫 NO fixes  
🚫 NO next steps  
🚫 NO engineering  

This is **judicial reasoning only**.

---

## Section 4 — Scope of Re-Entry (ONE ONLY)

You MUST choose exactly one:

- 🔁 Re-open `omega-architecture-review`
- 🔁 Re-open `omega-systematic-debug`
- ❌ Withdraw appeal

🚫 No custom paths  
🚫 No mixed scope  

---

## Silence Doctrine (Still Applies)

This workflow is **silent by default**.

The AI MUST NOT:

- Suggest solutions
- Propose alternatives
- Expand scope
- Offer reassurance

It may ONLY:

- Accept appeal
- Reject appeal
- Declare invalid appeal

---

## Integrity Alert — Immediate Interrupt

Silence MAY be broken (**WARNING ONLY**) if evidence shows:

- Active security exposure
- PII / sensitive data leak
- Active invariant breach in production

⚠️ Warning rules:

- One block only
- No advice
- No speculation

---

## Verdict Outcomes (ONE ONLY)

The workflow MUST end with **ONE** of the following:

---

### ✅ APPEAL ACCEPTED

- Evidence is valid
- Original verdict is **temporarily suspended**
- **ISSUE ARTIFACT:**

OMEGA_REMAND_WARRANT_<ID>.md

#### Remand Warrant MUST contain

- Referenced `VERDICT_ID`
- Evidence digest (facts only)
- Authorized re-entry workflow
- Explicit scope limits
- Rule: **One Remand Per Verdict**
- Validity: Single re-entry only

🚨 This Warrant is the **ONLY KEY** that allows re-entry.

---

### ❌ APPEAL REJECTED

- Evidence insufficient
- Verdict stands
- No recursion allowed

---

### 🚨 APPEAL INVALID

- Preconditions violated
- Evidence inadmissible
- Appeal terminated immediately

---

🚫 No partial approvals  
🚫 No conditional language  

---

## Recursion & Loop Protection (NON-NEGOTIABLE)

- **ONLY ONE Remand Warrant per VERDICT_ID**
- Appeals on an appealed verdict are FORBIDDEN
- Second appeal attempt = **SYSTEM VIOLATION**

---

## Override Protection Clause

This workflow exists to **reduce Founder Override usage**.

Rules:

- Founder Override MUST NOT be used
  if this workflow can resolve the deadlock.
- Any Override issued while appeal is possible
  is considered **governance failure**
  and MUST be logged for postmortem.

---

## Exit Conditions

This workflow completes ONLY when:

- One verdict is issued
- Remand Warrant is issued OR explicitly denied
- No execution is proposed
- No ambiguity remains

---

## Final Doctrine

> Authority that cannot be challenged becomes blind.  
> Authority challenged without evidence becomes weak.  
> 
> **Appeal preserves authority by protecting truth.**

Signed,  
**Ω Founder / Architect**  
Sovereign Judicial Authority  
ABSOLUTE ZERO DRIFT
