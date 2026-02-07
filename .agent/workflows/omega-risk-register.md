---
description: Sovereign risk registry and technical debt ledger. Prevents silent accumulation of structural fragility. The official memory of danger in Omega systems.
---

---

name: omega-risk-register
description: Sovereign risk registry and technical debt ledger. Prevents silent accumulation of structural fragility. The official memory of danger in Omega systems.
trigger: /risk | /risk-log
authority: Founder / Architect
drift_policy: ABSOLUTE_ZERO
mode: governance
lifecycle: persistent
---

# 📜 Ω Risk Register Workflow

> **The Sovereign Memory of Danger.**
> A risk unwritten is a permission to fail.
> This workflow ensures that no fragility is ever "forgotten" or "ignored."

---

## Core Mission

Bugs are for now.  
**Risks are for the future.**

This workflow exists to:

- Convert "worries" into **Managed Entities**.
- Prevent "Shadow Debt" (untracked complexity).
- Force explicit ownership of danger.
- Block phases that exceed the Risk Tolerance Threshold.

---

## The Iron Law (Non-Negotiable)

**IF YOU SEE IT, YOU MUST LOG IT.**

- ❌ NO "I'll remember this later."
- ❌ NO "It's probably fine."
- ❌ NO hiding risks to speed up release.

If a risk is identified in `omega-architecture-review` or `omega-systematic-debug`, it **MUST** be registered here immediately.

---

## Inputs (REQUIRED)

This workflow is usually triggered by:

- **Ω Architecture Review** (Output: Structural Risks)
- **Ω Systematic Debug** (Output: Root Cause implies deeper fragility)
- **Ω Authority Shadow** (Output: Silent failure paths)
- **Founder Direct Injection** (Strategic concern)

If triggered without context → **Ask for Source Artifact.**

---

## Phase 1 — Risk Identification (Naming the Demon)

For the issue at hand, you MUST capture:

1. **Risk Name:** Short, descriptive title.
2. **Source:** Which workflow/file revealed this?
3. **The Nightmare Scenario:**
    - *What is the worst possible outcome?*
    - Do not soften language. Write the disaster.
    - Example: "Database corruption during payment sync."

---

## Phase 2 — Quantification (The Score)

Assign values based on the **Omega Risk Matrix**:

### SEVERITY (Impact)

- **CRITICAL:** System collapse / Data loss / Authority breach.
- **HIGH:** Feature failure / Significant degradation.
- **MED:** Operational friction / Non-blocking errors.
- **LOW:** Maintenance annoyance.

### LIKELIHOOD (Probability)

- **CERTAIN:** Will happen without intervention.
- **LIKELY:** Dependent on common conditions (load, user error).
- **POSSIBLE:** Requires edge case sequence.
- **RARE:** Theoretical but unproven.

### TYPE

- **SECURITY:** Authority/Data breach.
- **STABILITY:** Uptime/Performance.
- **DRIFT:** Coupling/Complexity increase.
- **LOGIC:** Business rule violation.

---

## Phase 3 — Ownership Assignment

**Every Risk MUST have an Owner.**

Defined as:
> "The person/component responsible if this explodes."

- Usually: **Founder / Architect** (at this stage).
- Or specific **Sub-System** (e.g., Payment Gateway Wrapper).

🚫 "Shared Ownership" is FORBIDDEN.

---

## Phase 4 — Status Lifecycle (State Machine)

Risks exist in one of these states ONLY:

1. 🔴 **OPEN:** Recognized, dangerous, untouched. (Blocks Release).
2. 🟠 **MITIGATED:** Band-aid applied (Soft invariant), but root cause remains.
3. 🟢 **HARDENED:** Resolved via `omega-integrity-lock` (Code-enforced).
4. 🟣 **ACCEPTED:** Founder Override invoked. (Risk remains, but execution allowed).

---

## The Debt Ceiling (Circuit Breaker)

**SYSTEM HALT RULE:**

If the Registry contains:

- **> 3 CRITICAL Risks (OPEN)**
- OR **> 5 HIGH Risks (OPEN)**

Then:

- **ALL NEW FEATURES ARE BLOCKED.**
- The only allowed workflow is `omega-integrity-lock`.
- No new phases allowed until debt is reduced.

---

## Output Contract (REQUIRED)

You MUST generate/update the **Risk Register Artifact**.

### Risk Entry Structure

For each new risk, output:

```yaml
RISK_ID: <UUID v4>
TITLE: <String>
SEVERITY: <CRITICAL|HIGH|MED|LOW>
STATUS: <OPEN|MITIGATED|HARDENED|ACCEPTED>
TRIGGER: <Condition that activates risk>
BLAST_RADIUS: <What breaks>
LINKED_VERDICT: <UUID of Review/Debug Verdict>
CREATED_AT: <Timestamp>

Registry Summary

    Total Open Risks: <Count>

    Debt Ceiling Status: SAFE | BREACHED

Exit Conditions

This workflow completes ONLY when:

    All identified risks have UUIDs.

    Severity and Ownership are explicit.

    Debt Ceiling is checked.

    No immediate fixes are proposed (Fixes belong to Integrity Lock).

Mandatory Cleanup Protocol (Memory Decay)

IMMEDIATELY after generating the Risk Log, you MUST:

    Retrieve and display the content of 99_Logs/MAINTENANCE_OUTPUT_BLOCK.md verbatim.

    Do not proceed until user acknowledges the memory decay action.

Final Doctrine

    A system that forgets its weaknesses is a system waiting to die. The Registry remembers so you don't have to.

Signed, Ω Founder / Architect Keeper of the Ledger
