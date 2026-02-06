# OMEGA_APPEAL_<UUID>

## Metadata

Appeal ID: <UUID v4>

Referenced Verdict ID: <UUID v4>

Timestamp: <YYYY-MM-DD HH:MM:SS UTC>

## Evidence Admissibility (Mechanical)

### Evidence 1

Evidence ID: E-<UUID>

Source: <Origin>

NEW: <YES | NO>

MATERIAL: <YES | NO>

Admissibility: <ADMITTED | REJECTED>

### Evidence 2

Evidence ID: E-<UUID>

Source: <Origin>

NEW: <YES | NO>

MATERIAL: <YES | NO>

Admissibility: <ADMITTED | REJECTED>

## Mechanical Outcome

IF any evidence ADMITTED: APPEAL_ACCEPTED

IF all evidence REJECTED: APPEAL_REJECTED

IF preconditions violated: APPEAL_INVALID

## Result

Outcome: <APPEAL_ACCEPTED | APPEAL_REJECTED | APPEAL_INVALID>

## Warrant Issuance

IF APPEAL_ACCEPTED:

Warrant ID: <UUID v4>

Warrant File: warrants/OMEGA_REMAND_WARRANT_<UUID>.md

## Signature

Processed By: Omega Sovereign Judicial Authority

Date: <YYYY-MM-DD HH:MM:SS UTC>
