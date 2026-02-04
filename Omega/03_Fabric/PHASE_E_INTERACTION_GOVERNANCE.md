# PHASE E: INTERACTION GOVERNANCE SPEC

**Objective:** Engineer cognitive friction into the user experience.
**Philosophy:** Interaction is a commitment, not a click.
**Status:** LOCKED

---

## 1. HOVER BEHAVIOR (Intentional Delay)
*   **Rule:** Hovers must never feel "snappy."
*   **Latency:** Acknowledge presence only after **150ms** of sustained cursor dwell.
    *   *Why?* To filter out accidental fly-overs.
*   **Visual:** Slow opacity shift (e.g., 60% → 80%).
*   **Feedback:** Silence. No sound. No scale change. No cursor transformation unless the element is explicitly actionable.

## 2. PRESS / HOLD LOGIC (The Commitment)
*   **Requirement:** ALL mutations (State Changes) require **Press-and-Hold**.
*   **Threshold:** Minimum **1200ms** (1.2 seconds) to confirm.
    *   *Why?* To enforce a "second thought" during the action.
*   **Visual Feedback:** A non-linear progress fill (starts slow, accelerates slightly at the end).
*   **Release:** Changing mind (releasing mouse) at 1199ms results in **Instant Reset**. No "almost" states.

## 3. TRANSITIONS (Visual Weight)
*   **Curve:** `ease-out` only. (Deceleration = Mass).
    *   *BANNED:* `ease-in` (accelerating feels uncontrolled) and `bounce` (playful).
*   **Duration:**
    *   *State Changes:* **600ms** (Heavy).
    *   *UI Toggles:* **300ms** (Deliberate).
*   **Amplitude:** Transitions must be subtle. Elements do not fly in from off-screen. They fade or slide marginally.

## 4. BLOCKED STATES (The Wall)
*   **Behavior:** Inert.
*   **Cursor:** Standard Pointer. Do not change to "Stop" sign (too instructive).
*   **Feedback:** None. Clicking a blocked element produces **Zero** reaction.
    *   *Why?* The user should already know it is blocked by the visual state. Clicking is a failure of reading.

## 5. FAILURE & CANCEL (The Review)
*   **Backing Out:** Moving cursor outside the hit area during a Hold = **Immediate Cancel**.
*   **System Acknowledgment:** The interface does not apologize. It fundamentally reverts to the previous "Stable" state.
*   **Error Messages:** Static text only. No shaking modals. No red flashes.

---

**Summary:**
This system resists you. It demands you prove your intent through time and precision.
If you are tired/distracted, the system will not let you act.
