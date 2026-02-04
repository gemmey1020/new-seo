# PHASE D: HUD MANIFEST (PROPOSAL)

**Objective:** Transform the Cognitive Interface (Wireframe) into a Usable HUD.
**Philosophy:** Usability via Clarity, not Speed.
**Constraint:** The HUD must never feel "light."

---

## 1. THE VIEWPORT (The Canvas)
The HUD is **NOT** a scrolling dashboard. It is a **Persistent Cockpit**.
*   **Behavior:** Fixed Position. 100% Height.
*   **Rule:** The System State and Human Decision zones never leave the screen. You cannot scroll away from the consequence.
*   **Responsiveness:** Elements scale in density, but never stack to hide complexity.

## 2. THE GRID (The Skeleton)
Mirroring the Mental Model, the layout uses a **Rigid Vertical Stack**.

| ZONE | HEIGHT | ROLE |
| :--- | :--- | :--- |
| **01 STATE** | Fixed (Top) | The Anchor. Always defined. Never collapsed. |
| **02 CONTEXT** | Flex (Mid) | Contains WHY, RISK, TIME. The "Thinking" space. |
| **03 SIMULATION**| Flex (Mid) | The "Ghost" space. Expands only during potential mutation. |
| **04 DECISION** | Fixed (Btm) | The Trigger. Separated by "The Void" (Whitespace). |

## 3. THE PHYSICS (Resistance)
"Ease of use" is a bug in this system. We implement **Resistance**.
*   **No Quick Clicks:** Key actions (Approve/Reject) require **Purposeful Interaction**.
    *   *Prop:* "Press and Hold" to confirm (simulating weight), OR
    *   *Prop:* A physical "Arming" switch before the button becomes active.
*   **Hover States:** Do not "pop" or "bouncify". They **Slow Glow**. The interface acknowledges presence, not urgency.

## 4. THE QUIET (Silence)
*   **Tooltips:** BANNED. If it needs a tooltip, the design is too clever or the metric is too obscure.
*   **Micro-copy:** BANNED. No "Click here to...". The affordance must be self-evident.
*   **Empty States:** PRESERVED. If there is no Risk, the Risk block remains visible but Empty/Dark. We do not hide the zone; we show the absence of threat.

## 5. THE PALETTE (The Void)
*   **Background:** `#050505` (Not absolute black only to give depth to the Void).
*   **Foreground:** `#E0E0E0` (Not pure white, reduces eye strain, feels analog).
*   **Signal Colors:**
    *   **Stable:** Monochromatic Glow.
    *   **Risk:** Deep Amber/Red (Low brightness, High saturation). "Burning coals," not "Traffic lights."

## 6. USABILITY COMPLIANCE
*   **Readability:** Monospace for Data (Truth). Sans-serif for Context (Humanity).
*   **Access:** High contrast borders. Explicit boundaries.
*   **Focus:** One simulation at a time. The HUD does not allow multitasking.

---

**Status:** Ready for Implementation.
**Verdict:** Safe. Heavy. Usable.
