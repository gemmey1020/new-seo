---
description:  Sovereign architecture review workflow for Omega systems. Detects structural risks, false confidence, hidden coupling, sleeping assumptions, and silent failure paths. No fixes, no refactors, no execution.
---

---

name: omega-architecture-review
description: Sovereign architecture review workflow for Omega systems. Detects structural risks, false confidence, hidden coupling, sleeping assumptions, and silent failure paths. No fixes, no refactors, no execution.
trigger: /omega-review | /review
authority: Founder / Architect
drift_policy: ABSOLUTE_ZERO
mode: silent
execution: none
lifecycle: persistent
---

# 🔐 Ω Architecture Review Workflow

> This workflow is a **Sovereign Inspection Layer**.  
> It does not fix systems.  
> It judges whether they *deserve to continue*.

If this workflow is wrong,  
**everything built on top of it is unsafe.**

---

## Core Mission

Debugging asks:  
> “Why did this fail?”

Architecture Review asks:  
> **“Why does this *appear* safe?”**

This workflow exists to expose:

- Sleeping assumptions  
- False confidence  
- Hidden coupling  
- Fragile invariants  
- Silent failure paths  
- “Works for now” illusions  

---

## Iron Constraints (Non-Negotiable)

During this workflow:

- ❌ NO fixes  
- ❌ NO refactors  
- ❌ NO optimizations  
- ❌ NO code changes  
- ❌ NO execution plans  
- ❌ NO architectural proposals  

This workflow ONLY:

- Inspects structure  
- Questions trust  
- Names risk  
- Issues verdicts  

Any attempt to “improve” is a **violation**.

---

## Mandatory Entry Condition

This workflow MUST be preceded by **one** of the following:

- `/debug` completed with **no root-cause bug found**
- A system that “works” but feels *too confident*
- Pre-freeze safety certification
- Pre-scale or pre-authority review

🚫 Do NOT use this workflow for:

- Active bugs  
- Test failures  
- CI / build errors  

→ Use `/debug` instead.

---

## Pre-Review Integrity Check (MANDATORY)

Before Section 1 begins, the AI MUST:

### 🔎 Artifact Digest Injection

For **every referenced artifact**, produce a short digest:

- Artifact name
- Last known status
- 3–5 **verifiable facts only**
- No interpretation
- No opinion

🚫 If a digest cannot be produced → **STOP**  
(Prevents Pointer Rot & hallucination)

---

## Review Structure (STRICT ORDER)

You MUST complete **all sections**.  
Skipping ANY section invalidates the verdict.

---

## Section 1 — System Boundary Definition

Define the system **before judging it**.

Required:

- What is IN scope  
- What is OUT of scope  
- Trust boundaries  
- External dependencies  
- Human touchpoints  

If boundaries cannot be drawn → **STOP**  
(Unbounded systems cannot be reviewed.)

---

## Section 2 — Assumption Inventory (CRITICAL)

List **ALL assumptions**, explicit or implicit.

Examples:

- “This config never changes”
- “This dependency is stable”
- “This service always responds”
- “This environment matches prod”
- “This flow is impossible”
- “A human will always notice”

Rules:

- If it’s not written → it’s assumed  
- If it’s assumed → it’s a risk  
- If it’s critical and unenforced → it’s dangerous  

---

## Section 3 — Invariants & Guarantees

For EACH invariant, answer:

- What MUST always be true?
- Where is it enforced?
- What breaks if it fails?
- How would we **know** it failed?

⚠️ If an invariant has:

- No enforcement  
- No detection  

→ It is **imaginary**.

---

## Section 4 — Coupling & Dependency Analysis

Identify:

- Tight coupling  
- Hidden dependencies  
- Order sensitivity  
- Temporal assumptions  
- Shared mutable state  

Ask relentlessly:

- What breaks if this moves?
- What breaks if this runs twice?
- What breaks if this is delayed?
- What breaks if this is skipped?
- What breaks if this fails silently?

---

## Section 5 — Failure Modes & Silence Zones

For EACH component:

- How can it fail?
- How would failure surface?
- Can it fail silently?
- Would we notice immediately?

🚨 **Silent failure = HIGH RISK**

If a failure has:

- No alarm
- No log
- No signal  

→ The system is **unsafe by design**.

---

## Section 6 — Change Impact Simulation (Thought-Only)

Simulate **WITHOUT DOING**:

- Dependency version bump
- Config drift
- Load spike
- Partial outage
- Unexpected input
- Human mistake

Ask:

- What breaks first?
- What degrades quietly?
- What cascades?
- What lies to us?

---

## Section 7 — Confidence Audit (Brutal Honesty)

Answer plainly:

- Why do we believe this is safe?
- Is that belief evidence-based?
- Or pattern-based?
- Or habit-based?
- Or “it hasn’t failed yet”?

⚠️ Confidence without proof  
= **latent catastrophe**.

---

## Mandatory Output Contract

You MUST produce ALL of the following:

### 1️⃣ Structural Risks

- Real architectural risks
- Ranked by severity

### 2️⃣ Sleeping Assumptions

- Unenforced
- Unmonitored

### 3️⃣ Fragility Points

- Small change → large damage

### 4️⃣ Silence Zones

- Failures that would go unnoticed

### 5️⃣ Verdict (One Only)

- ✅ STRUCTURALLY SOUND  
- ⚠️ CONDITIONALLY SAFE  
- 🚨 ARCHITECTURALLY UNSAFE  

🚫 No hedging  
🚫 No mixed language

### 6️⃣ Verdict ID (MANDATORY)

- **Verdict ID:** UUID v4 (immutable)
- **Format:** `OMEGA_REVIEW_VERDICT_<UUID>`
- Without this ID, the review is VOID and cannot be appealed

### 7️⃣ Re-entry Status

- CLOSED
- OR REQUIRES_APPEAL

**Warrant Consumption (If Applicable):**

- IF entered via Warrant: Declare `WARRANT_ID: <UUID> — STATUS: CONSUMED`

---

## Exit Conditions

This workflow completes ONLY when:

- All 7 sections are reviewed
- Single verdict issued with VERDICT_ID
- Re-entry status declared
- No execution proposed

---

## Mandatory Cleanup Protocol (Memory Decay)

IMMEDIATELY after generating the Verdict, you MUST:

1. Retrieve and display the content of `99_Logs/MAINTENANCE_OUTPUT_BLOCK.md` verbatim
2. Do not proceed until user acknowledges the memory decay action

---

## Appeal Clause (Deadlock Protection)

A verdict is **final**  
UNLESS challenged via:
