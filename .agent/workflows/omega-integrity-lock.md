---
description: Sovereign integrity-lock workflow that seals weak architectural assumptions into code-enforced guarantees before authority activation. Driven strictly by the Omega Risk Register.
---

---

name: omega-integrity-lock
description: Sovereign integrity-lock workflow that seals weak architectural assumptions into code-enforced guarantees before authority activation. Driven strictly by the Omega Risk Register.
trigger: /lock-integrity
authority: Founder / Architect
drift_policy: ABSOLUTE_ZERO
---

# 🔐 Ω Integrity Lock Workflow

> This workflow transforms **WEAK invariants** into **HARD guarantees**.
> Nothing is hardened unless it is already registered as a risk.

---

## Core Mission

Architecture Review tells us:

- What is fragile

Risk Register tells us:

- What is dangerous

Invariant Hardening decides:
> **What must never be trusted to humans again**

---

## Absolute Rules (Non-Negotiable)

- ❌ NO new features
- ❌ NO scope expansion
- ❌ NO “nice to have” refactors
- ❌ NO fixing unregistered risks

This workflow ONLY hardens:

- Registered risks
- Explicit weak invariants

---

## Inputs (REQUIRED)

This workflow may ONLY start if:

- Ω Architecture Review exists
- Ω Risk Register exists
- Target phase is BLOCKED by specific risks

Missing any input → **STOP**

---

## Step 1 — Map Risks → Invariants

For EACH risk in the Risk Register:

You MUST answer:

Risk ID:
Which invariant does this violate?
Where is that invariant currently enforced?

    Code?

    Test?

    Docs?

    Human behavior?

If the answer is:

- Docs / Humans → **WEAK**
- Tests only → **SOFT**
- Compiler / Runtime → **HARD**

---

## Step 2 — Define the Desired Invariant State

For each WEAK or SOFT invariant, define:

Invariant Name:
Current Enforcement: WEAK / SOFT
Target Enforcement: HARD
Failure if Violated:
Who detects the violation?

Important:
> If you cannot define *how it should fail*, you don’t understand the invariant yet.

---

## Step 3 — Choose Hardening Strategy (Design Only)

⚠️ **No code yet. Design only.**

Choose ONE per invariant:

### Hardening Strategies (Allowed)

- Static typing / strict types
- Runtime assertions
- Schema validation
- Compile-time guards
- Contract tests
- Single source of truth
- Capability restriction (remove access)

🚫 Forbidden:

- “Developer discipline”
- “Code review”
- “Documentation”
- “We’ll be careful”

---

## Step 4 — Hardening Design Artifact (MANDATORY)

For EACH invariant, you MUST produce:

Invariant ID:
Risk IDs Covered:
Old Assumption:
New Guarantee:
Enforcement Layer:
Breakage Behavior:
Backward Compatibility Impact

This becomes a **Design Contract**, not implementation.

---

## Step 5 — Re-evaluate Risk Status

After design (still no code):

For each Risk:

- Does this design eliminate the risk?
- Or reduce it?
- Or only make it detectable?

Update Risk Status to:

- MITIGATABLE
- REDUCED
- STILL OPEN

⚠️ No risk is CLOSED here.

---

## Output Contract (REQUIRED)

You MUST produce:

### 1️⃣ Invariant Hardening Plan

- One entry per invariant
- Linked to Risk IDs

### 2️⃣ Residual Risk List

- Risks not eliminated
- Why they persist

### 3️⃣ Authority Readiness Delta

- Which blocks to J.3 are removed
- Which remain

---

## Exit Conditions

This workflow completes ONLY when:

- Every WEAK invariant has:
  - A target HARD design
  - A clear enforcement strategy
- No mitigation is “human-based”
- No code has been written yet

---

## Lock Certificate (Forensic Anchor)

Upon successful completion, output:

**LOCK_CERTIFICATE:**

- **Event_ID:** <UUID v4>
- **Related Verdict_ID:** <UUID of the Review>
- **Invariants Locked:** <count>
- **Timestamp:** <UTC>
- **Log Decay:** ACKNOWLEDGED (Ready for Purge)

---

## Mandatory Cleanup Protocol (Memory Decay)

IMMEDIATELY after generating the Invariant Hardening Plan, you MUST:

1. Retrieve and display the content of `99_Logs/MAINTENANCE_OUTPUT_BLOCK.md` verbatim
2. Do not proceed until user acknowledges the memory decay action

---

## Final Doctrine

> Architecture fails when humans are trusted.  
> **Systems survive when invariants enforce themselves.**

Signed,  
**Ω Founder / Architect**  
Invariant Sovereign Authority
