# PHASE G: IMPLEMENTATION PLAN

**Objective:** Guarantee the philosophical integrity of the system through code.
**Strategy:** Constraints as Code. Rules are enforced by the compiler, not the developer.
**Status:** LOCKED

---

## 1. TECH STACK (The Tools of Resistance)

### Frontend Framework: **React** (Strict Mode)
*   *Why?* Component isolation allows us to enforce the "Component Contracts" (Phase F).
*   *Constraint:* Functional components only. No class components.

### State Management: **XState** (Finite State Machines)
*   *Why?* We surrender control to a deterministic machine.
    *   It prevents "illegal states" (e.g., clicking "Approve" while "Simulating" is active).
    *   It models "Time" (delays, timeouts) as explicit first-class citizens.
*   *Constraint:* UI components **NEVER** hold logic state (no `useState` for business logic). They only receive `state.context` and send `events`.

### Rendering: **Client-Side Rendering (CSR)** in a Persistent Shell
*   *Why?* The HUD is a "Cockpit". It must maintain physics continuity. Page reloads destroy the sense of weight/persistence.

---

## 2. LAYERED ARCHITECTURE (Separation of Concerns)

### Layer 1: The Cognitive Core (Logic)
*   **Role:** The Brain.
*   **Content:** XState Machines (`HudMachine`, `InteractionMachine`).
*   **Responsibility:** Handling timers, enforcing delays, managing Simulation/Real transitions.
*   **Output:** A readonly `State` object.

### Layer 2: The Visualization Layer (Render)
*   **Role:** The Lens.
*   **Content:** React Components (contracts from Phase F).
*   **Responsibility:** Pure rendering of `State`.
*   **Constraint:** Zero side effects.

### Layer 3: The Interaction Layer (Physics)
*   **Role:** The Hands.
*   **Content:** Event handlers (`onPointerDown`, `onPointerUp`).
*   **Responsibility:** Measuring time (Duration). Dispatching raw events to the Core.
*   **Constraint:** Cannot mutate state directly. Can only signal intent.

### Layer 4: The Decision Gateway (API)
*   **Role:** The Boundary.
*   **Content:** Authenticated API Client.
*   **Responsibility:** Final transmission of the "Commit" token.

---

## 3. FILE / MODULE STRUCTURE

```text
src/
├── core/
│   ├── machines/           # XState Logic (The Law)
│   ├── types/              # TypeScript Contracts (Immutable)
│   └── api/                # Gateway Client
├── features/
│   ├── hud/
│   │   ├── StateZone.tsx   # Inert
│   │   ├── ContextZone.tsx # Passive
│   │   ├── SimZone.tsx     # Read-Only
│   │   └── DecisionZone.tsx # Governed
├── shared/
│   ├── physics/            # Animation curves (ease-out only)
│   └── primitives/         # Dumb abstract blocks
└── enforcement/
    └── lints/              # Custom rules (No logic in UI)
```

---

## 4. DATA FLOW (Unidirectional & Resistant)

1.  **Input:** User holds mouse down.
2.  **Physics:** Interaction Layer measures duration (0ms... 1200ms).
3.  **Event:** If threshold met, `COMMIT_INTENT` sent to Machine.
4.  **Guard:** Machine checks `canCommit` (Is Sim finished? Is Risk acknowledged?).
5.  **Transition:** State moves to `COMMITTING`.
6.  **Render:** UI updates to show "Locked/Processing".
7.  **Gateway:** API call executed.

**Simulation Data:**
*   Enters via the `Machine` (Context).
*   Propagated to `SimZone`.
*   **Never** touched/modified by the Client. It is treated as "Truth from Server".

---

## 5. ENFORCEMENT MECHANISMS

### TypeScript (Static Guard)
*   **Strict Types:** No `any`. State objects are Readonly.
*   **Exhaustive Matching:** UI must handle ALL machine states (Idle, Simulating, Error, etc.).

### Linter Rules (Code Guard)
*   **Ban:** `useEffect` in Feature Components (Logic leak).
*   **Ban:** `onClick` on non-Decision components.
*   **Ban:** `setTimeout` (Time must be managed by XState).

### Runtime Guards (Physics Guard)
*   **The 1.2s Watchdog:** The `DecisionComponent` physically prevents emission of the `commit` event if the internal timer < 1200ms, regardless of visual state.

---

**Summary:**
This plan removes the developer's ability to "optimize" for speed.
The architecture forces every interaction to travel through a layer of time and logic before it becomes an action.
