# 99_Logs Operational Memory Layer

## Purpose

Operational Support for Omega workflows.

NOT a source of truth.

NOT authoritative.

NOT a dependency.

## Truth Hierarchy

```
Level 1 (Sovereign): 90_Judicial Artifacts
Level 2 (Subordinate): 99_Logs
```

If 99_Logs contradicts 90_Judicial: Log is deemed CORRUPTED and ignored.

---

## Hybrid Memory Decay Protocol

### Layer 0: ACTIVE (Current Session)

Scope: Workflow currently executing.

Traces: FULL

Snapshots: FULL

Overrides: FULL

### Layer 1: PREVIOUS (Verdict -1)

Scope: Session immediately preceding last Verdict.

Traces: SEMANTIC DIGEST only.

Compress to Key State Transitions (Input -> Decision -> Output).

Remove loops. Remove retries.

Snapshots: FULL

Overrides: FULL (Linked to Snapshot)

### Layer 2: OLDER (Verdict -2)

Scope: History before Layer 1.

Traces: PURGED (Hard Delete)

Snapshots: MINIMAL (Key-Value state summary only)

Overrides: HISTORICAL MARKER only

### Layer 3: DEAD

Action: HARD DELETE

---

## Enforcement Mechanism

NO automation exists.

Decay Protocol is PROCEDURALLY ENFORCED.

Upon issuance of ANY VERDICT_ID in 90_Judicial, the acting Workflow SHALL output:

```
[ACTION REQUIRED]: EXECUTE MEMORY DECAY
- MOVE Active -> L1
- MOVE L1 -> L2
- PRUNE Older to L3 (Delete)
```

User is the execution engine.

---

## Estoppel Rule (Evidence Lock)

Logs are NOT Admissible Evidence in Appeals if data existed during original Review.

Rule: "If it was logged, it was knowable. Failure to read the log is an Architect error, not New Evidence."

Logs are ONLY evidence if they capture runtime anomaly AFTER the Verdict.

---

## Override Anchoring

Override record MUST link to specific SNAPSHOT_ID.

Override without snapshot is treated as noise.

---

## Conflict Resolution

If 99_Logs contradicts 90_Judicial:

Log is CORRUPTED.

Log is IGNORED.

90_Judicial is absolute.

---

## Non-Dependency Rule

All Omega workflows are fully valid if 99_Logs does not exist.

All Omega workflows are fully valid if 99_Logs is deleted.

All Omega workflows are fully valid if 99_Logs is empty.

---

## Directory Organization

```
99_Logs/
├── README.md
├── traces/
│   ├── active/
│   │   └── TRACE_<WORKFLOW>_<UUID>.md
│   └── digest/
│       └── DIGEST_<WORKFLOW>_<UUID>.md
├── snapshots/
│   ├── full/
│   │   └── SNAPSHOT_<CONTEXT>_<UUID>.md
│   └── minimal/
│       └── MINIMAL_<CONTEXT>_<UUID>.md
└── overrides/
    ├── active/
    │   └── OVERRIDE_<TYPE>_<UUID>.md
    └── historical/
        └── MARKER_<TYPE>_<UUID>.md
```

---

## Manual Cleanup is Protocol

Manual cleanup is NOT a weakness.

Manual cleanup is the ENFORCEMENT MECHANISM.

User action closes the enforcement gap.
