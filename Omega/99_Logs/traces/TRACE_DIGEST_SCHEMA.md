# Trace Digest Schema

## Purpose

Compress FULL traces to SEMANTIC DIGEST when transitioning from Layer 0 (Active) to Layer 1 (Previous).

## Compression Strategy

### Input: Full Trace

Contains: All workflow steps, loops, retries, intermediate states.

### Output: Semantic Digest

Contains: Key State Transitions only.

Format: INPUT -> DECISION -> OUTPUT

## Compression Rules

1. REMOVE all retry attempts. Keep final result only.
2. REMOVE all loop iterations. Keep entry and exit state only.
3. REMOVE intermediate debugging steps.
4. REMOVE redundant state captures.
5. PRESERVE causality chain (what led to what).
6. PRESERVE decision points (where branching occurred).
7. PRESERVE final outcomes (what was decided).

## Digest Format

```
DIGEST_<WORKFLOW>_<UUID>.md

## Metadata

Original Trace: TRACE_<WORKFLOW>_<UUID>.md
Compression Date: <UTC>
Layer: L1 (Previous)

## State Transitions

1. INPUT: <initial state/trigger>
   DECISION: <what was evaluated>
   OUTPUT: <result>

2. INPUT: <next state>
   DECISION: <what was evaluated>
   OUTPUT: <result>

3. INPUT: <final state>
   DECISION: <what was evaluated>
   OUTPUT: <final result>

## Terminal State

Final Outcome: <COMPLETE | ABORTED | ERROR>
Linked Verdict: <UUID | NONE>
```

## Causality Preservation

Each transition SHALL link to the next.

If Transition N output is Transition N+1 input, the chain is valid.

Broken chains indicate digest corruption.

## Maxium Transitions

Digest SHALL contain no more than 10 State Transitions.

If original trace exceeds 10 decision points, merge related decisions.
