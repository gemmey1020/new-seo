# PHASE F: COMPONENT CONTRACTS

**Objective:** Prevent philosophical drift via strict architectural contracts.
**Philosophy:** Components are dumb. Logic is external. Speed is failure.
**Status:** LOCKED

---

## 1. STATE COMPONENTS (The Anchor)
*   **Purpose:** To render the current Truth of the system (e.g., Risk Level, Health).
*   **Allowed Behaviors:**
    *   Accept `StatusObject` prop.
    *   Render visual weight based on severity.
*   **Forbidden Behaviors:**
    *   Click handlers.
    *   Hover tooltips.
    *   Collapse/Expand.
    *   Internal polling or data fetching.
*   **Interaction Constraints:** **NONE**. Must be totally inert.
*   **Failure Mode:** If data is missing, render "UNKNOWN" (Grey Void). Never hide.

## 2. CONTEXT COMPONENTS (The Why)
*   **Purpose:** To explain causality without triggering action.
*   **Allowed Behaviors:**
    *   List causal factors (text).
    *   Show trend direction (arrow).
*   **Forbidden Behaviors:**
    *   Linking to "fix" actions.
    *   Drill-down navigation.
    *   Sorting/Filtering.
*   **Interaction Constraints:** **PASSIVE**. Selection/Copying text is allowed. clicking is banned.
*   **Failure Mode:** Empty state. No "No data" icons. Just silence.

## 3. SIMULATION COMPONENTS (The Ghost)
*   **Purpose:** To render the "Before vs After" comparison.
*   **Allowed Behaviors:**
    *   Accept `CurrentState` and `ProjectedState`.
    *   Render diff visualization (Ghosting).
*   **Forbidden Behaviors:**
    *   "Apply" buttons inside the component.
    *   Editable inputs (Simulation is calculated by the engine, not the user).
    *   Optimistic UI updates.
*   **Interaction Constraints:** **READ-ONLY**.
*   **Failure Mode:** If simulation fails, render nothing. Do not show "Calculation Error".

## 4. DECISION COMPONENTS (The Trigger)
*   **Purpose:** To capture the Human Commitment to a change.
*   **Allowed Behaviors:**
    *   Emit `onRequestCommit` event.
    *   Visualize "Resistance" (Hold Progress).
*   **Forbidden Behaviors:**
    *   Direct state mutation.
    *   Instant click events.
    *   Exisiting outside the dedicated Decision Zone.
*   **Interaction Constraints:** **GOVERNED**. Must adhere strictly to Phase E (1.2s Hold).
*   **Failure Mode:** Lock to "Disabled" state.

## 5. FEEDBACK COMPONENTS (The Silence)
*   **Purpose:** To acknowledge rejection or completion.
*   **Allowed Behaviors:**
    *   Static text display.
    *   Return to Null state.
*   **Forbidden Behaviors:**
    *   Animated "Toasts".
    *   Sticky "Dismiss" bars.
    *   Sound.
*   **Interaction Constraints:** **EPHEMERAL**. Disappears on input change.
*   **Failure Mode:** Invisibility.

---

**Summary:**
You are not building a UI kit. You are building a set of read-only lenses and one heavy trigger.
Any component that tries to be "smart" violates the contract.
