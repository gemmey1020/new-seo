# Omega Judicial Archive

## Operational Mode

OFFICIAL: EXECUTION MODE (LEAN)

ARCHIVAL MODE: Available upon explicit Founder request only.

## Single Source of Truth

Artifact file = SSOT.

index.md = Navigational cache only.

Missing index entry does NOT invalidate artifact.

Loss of artifact = Permanent judicial death of that branch.

Loss of index = Recoverable by filesystem scan of UUID-named files.

## Artifact Rules

NO overwrite.

NO merge.

NO deletion.

NO amendment.

## Modes

### Execution Mode (Default)

- CORE verdict mandatory (100 lines max).
- EXTENDED analysis conditional (only if UNSAFE or CONDITIONALLY_SAFE + HIGH risks).
- Appeal = evidence admissibility gate only.
- Warrant = mechanical issuance upon APPEAL_ACCEPTED.

### Archival Mode (Explicit Request Only)

- Full EXTENDED analysis mandatory.
- Complete assumption inventory.
- Full dependency mapping.
- Founder sign-off required.

## Founder Override

Founder Override MUST be a separate artifact:

OMEGA_FOUNDER_OVERRIDE_<UUID>.md

Linked to the context being overridden.

Stored in same directory as affected artifact.

## Directory Organization

```
90_Judicial/
├── verdicts/
│   └── OMEGA_REVIEW_VERDICT_<UUID>.md
│   └── OMEGA_EXTENDED_ANALYSIS_<UUID>.md (conditional)
├── appeals/
│   └── OMEGA_APPEAL_<UUID>.md
├── warrants/
│   └── OMEGA_REMAND_WARRANT_<UUID>.md
├── index.md
└── README.md
```

NO YYYY/MM folders.

UUID is the ONLY locator.

Flat structure per artifact type.

## Integrity Rules

Artifact metadata MUST contain UUID in filename AND frontmatter.

UUID collision = system corruption requiring Founder intervention.

Cross-references MUST match file existence.

## Access

Read: Unrestricted within Omega Lab.

Write: Workflow execution only.

Delete: Forbidden.

Modify: Forbidden.
