# Maintenance Output Block

## Purpose

This block SHALL be output by the AI upon issuance of ANY VERDICT_ID in 90_Judicial.

The User is the execution engine for memory decay.

## Mandatory Output Block

Upon VERDICT issuance, AI SHALL output:

```
═══════════════════════════════════════════════════════════════════
[ACTION REQUIRED]: EXECUTE MEMORY DECAY
═══════════════════════════════════════════════════════════════════

VERDICT ISSUED: <VERDICT_UUID>
VERDICT DATE: <YYYY-MM-DD HH:MM:SS UTC>

MEMORY DECAY PROTOCOL:

1. MOVE Active -> L1
   - traces/active/* -> traces/digest/ (compress to digest format)
   - snapshots/full/* remains (L1 keeps full snapshots)
   - overrides/active/* remains (L1 keeps full overrides)

2. MOVE L1 -> L2
   - traces/digest/* from previous session -> DELETE (L2 has no traces)
   - snapshots/full/* from previous session -> snapshots/minimal/ (compress)
   - overrides/active/* from previous session -> overrides/historical/ (marker only)

3. PRUNE Older to L3 (Delete)
   - DELETE all artifacts older than L2
   - DELETE traces from L2
   - DELETE full snapshots from L2

═══════════════════════════════════════════════════════════════════
ESTOPPEL REMINDER: Logs are NOT admissible evidence for appeals
if data existed during original review.
═══════════════════════════════════════════════════════════════════
```

## Enforcement

AI SHALL NOT proceed to new tasks until this block is displayed.

User acknowledgment is implicit. Cleanup is User responsibility.

Manual cleanup is the enforcement mechanism.
