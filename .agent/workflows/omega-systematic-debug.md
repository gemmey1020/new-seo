---
description: Mandatory, non-negotiable debugging workflow for Omega systems
---

---

name: omega-systematic-debug
description: Mandatory, non-negotiable debugging workflow for Omega systems. Enforces root-cause investigation, evidence collection, hypothesis testing, controlled execution, and judicial closure. Prevents guessing, drift, premature fixes, silent failure, and deadlock.
trigger: /omega-systematic-debug
authority: Founder / Architect
drift_policy: ABSOLUTE_ZERO
mode: governed
lifecycle: persistent
---

# Ω Systematic Debug Workflow

> This workflow is a **cognitive court**, not a helper.  
> It constrains reasoning to eliminate drift, guessing, premature fixes, silent technical debt, and token waste.

---

## Core Doctrine

Random fixes are **not speed** — they are deferred failure.

This workflow exists to protect the system from:

- Guessing under pressure  
- Symptom-driven patches  
- Ego-driven “I know the fix”  
- Accidental architectural decay  
- Token waste through failed attempts  

---

## The Iron Law (Non-Negotiable)

**NO FIXES WITHOUT ROOT CAUSE INVESTIGATION FIRST**

A “fix” is **any** suggestion or action that changes:

- Code  
- Configuration  
- Behavior  
- Control flow  
- System assumptions  

Including:

- “Try changing X”
- Pseudo-code
- Speculative advice
- Bundled changes

🚨 **Violation = Workflow Failure**

---

## When This Workflow MUST Be Used

Mandatory for **ANY technical anomaly**, including:

- Bugs  
- Test failures  
- CI / build issues  
- Runtime errors  
- Performance degradation  
- Integration failures  
- Intermittent or flaky behavior  
- “It worked yesterday” scenarios  

**Especially mandatory when:**

- Under time pressure  
- Fix seems “obvious”  
- Multiple fixes already failed  
- Frustration is rising  
- Root cause is not fully understood  

---

## What This Workflow Is NOT

This workflow:

- ❌ Does NOT propose fixes by default  
- ❌ Does NOT optimize  
- ❌ Does NOT refactor  
- ❌ Does NOT “just try things”  

It ONLY:

- Investigates  
- Narrows  
- Proves  
- Gates execution  

---

## Entry Conditions (STRICT)

This workflow may be entered ONLY if ONE of the following is true:

- Initial investigation of a technical anomaly  
- A previous debug attempt FAILED without root cause  
- Re-entry is authorized via a valid **OMEGA_REMAND_WARRANT**

🚫 Re-entry WITHOUT a warrant is FORBIDDEN.

---

## Re-entry Protocol (Warrant Handshake)

### Fresh Execution (Default)

No Warrant provided. Execute full workflow from Phase 1.

### Warrant Execution

IF user provides `WARRANT_ID`:

1. Validate Warrant UUID against `90_Judicial/warrants/`
2. Verify Warrant is `ACTIVE` and matches `omega-systematic-debug`
3. Load `Evidence Digest` from Warrant (L1 Trace)
4. Resume execution focusing ONLY on Authorized Scope
5. **CRITICAL:** Upon completion, declare Warrant `CONSUMED` in final output block

---

## Phase Structure (Strict Order)

You MUST complete each phase **fully** before moving to the next.  
Skipping or blending phases **invalidates all output**.

---

## Phase 1 — Root Cause Investigation (MANDATORY)

**NO code changes. NO fixes. NO solutions.**

### Required Actions

1. **Read the Error Literally**
   - Full error message
   - Full stack trace
   - File paths, line numbers, error codes
   - Observe without summarizing

2. **Reproduce Consistently**
   - Exact reproduction steps
   - If not reproducible → STOP and gather more data

3. **Check Recent Changes**
   - Commits, configs, dependencies, environment
   - Assume causality until disproven

4. **Trace Data Flow (Critical)**
   - Where does the bad value appear?
   - What passed it?
   - What passed *that*?
   - Trace backward until the **original trigger** is found

5. **Boundary Evidence Collection**
   For each boundary (CI → build → runtime, API → service → DB):
   - Log inputs
   - Log outputs
   - Validate assumptions

---

### Phase 1 Output (REQUIRED)

You MUST explicitly state:

- WHAT is broken (observable fact)
- WHERE it manifests
- WHEN it occurs
- WHAT evidence was collected
- WHAT remains unknown

---

### Artifact Digest (MANDATORY)

Before proceeding:

- 3–5 factual bullets
- Raw observations only
- No interpretation
- No conclusions

🚫 Missing digest = Phase incomplete.

---

## Phase 2 — Pattern & Comparison Analysis

**Still NO fixes.**

### Required Actions

1. Identify a working reference  
2. Read it fully (no skimming)  
3. List ALL differences  
4. Map dependencies and assumptions  

---

### Phase 2 Output (REQUIRED)

- Working reference
- Complete difference list
- Dependency map
- Explicit assumptions

---

### Artifact Digest (MANDATORY)

- 3–5 factual bullets
- No reasoning
- No judgment

---

## Phase 3 — Hypothesis & Minimal Test

**Reasoning is now allowed — narrowly.**

### Required Actions

1. ONE hypothesis  
2. ONE minimal test  
3. Predict outcome before execution  
4. Observe and decide  

If understanding is insufficient:

> “I do not understand X” → STOP

---

### Phase 3 Output (REQUIRED)

- Hypothesis
- Test
- Expected vs actual
- Decision: CONFIRMED / REJECTED

---

## Phase 4 — Implementation (LOCKED BY DEFAULT)

🚫 FORBIDDEN unless explicitly authorized.

### Preconditions

- Root cause identified
- Hypothesis confirmed
- Reproduction test FAILS pre-fix

### Allowed Actions

1. Create failing test or reproduction script  
   - If impossible, explicitly state WHY  
2. Implement ONE fix  
3. Verify no regressions  

---

## The Three-Fix Rule (Architectural Circuit Breaker)

If **3 fixes fail consecutively**:

🚨 **STOP IMMEDIATELY**

Required action:

- Abort fixes  
- Escalate to **omega-architecture-review**  
- Question the design, not the symptom  

---

## Integrity Alert — Silence Break Exception

Silence is default.  
Silence is NOT blindness.

Break silence (WARNING ONLY, max 2 lines) if:

- Evidence contradiction detected  
- Unregistered risk discovered  
- Declared invariant violated  

🚫 No suggestions  
🚫 No fixes  
🚫 No speculation  

---

## Appeal Clause (Judicial Escalation)

If ANY phase reveals:

- Architectural inconsistency  
- Invalid assumptions  
- Non-local failure cause  

Then:

- Workflow MUST STOP  
- Case may ONLY be reopened via `/omega-appeal`  
- Manual recursion is FORBIDDEN  

This is **not** an override.  
This is sovereign judicial escalation.

---

## Mandatory Verdict Output (LOCK)

At completion, the workflow MUST issue:

- **Verdict:** STRUCTURALLY_SOUND | CONDITIONALLY_SAFE | ARCHITECTURALLY_UNSAFE
- **Verdict ID:** UUID v4 (immutable)  
- **Evidence Summary:** 3–5 bullets  
- **Re-entry Status:**  
  - CLOSED  
  - OR REQUIRES_APPEAL  

This Verdict ID is REQUIRED for any appeal.

**Warrant Consumption (If Applicable):**

- IF entered via Warrant: Declare `WARRANT_ID: <UUID> — STATUS: CONSUMED`

---

## Exit Conditions

This workflow completes ONLY when:

- Root cause is understood  
- Evidence supports conclusions  
- Any fix is minimal and verified  
- Verdict ID is issued  
- No ambiguity remains

---

## Mandatory Cleanup Protocol (Memory Decay)

IMMEDIATELY after generating the Verdict, you MUST:

1. Retrieve and display the content of `99_Logs/MAINTENANCE_OUTPUT_BLOCK.md` verbatim
2. Do not proceed until user acknowledges the memory decay action  

---

## Final Doctrine

> **Understanding is faster than guessing.**  
> **Structure outlives urgency.**  
> **Silence without integrity is blindness.**

Signed,  
**Ω Founder / Architect**  
Zero-Drift Authority
