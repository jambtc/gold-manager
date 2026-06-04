# SIP-0095 - Documentation Governance and SIP Verification Audit

| Campo    | Valore            |
|----------|-------------------|
| ID       | SIP-0095          |
| Stato    | Proposed          |
| Priorita | High              |
| Tipo     | Process / Quality |
| Data     | 2026-06-04        |
| Dipende  | SIP-0001          |

## Problema

La documentazione di Gold Manager v2 cresce velocemente. README, roadmap, GAP e singoli SIP non sempre riportano lo stesso stato.

Rischi principali:

1. funzionalita gia implementate restano indicate come gap;
2. SIP parziali vengono considerati completati senza evidenza;
3. roadmap e codice reale divergono.

## Obiettivo

Creare una governance leggera per mantenere coerenti indice SIP, roadmap, GAP, codice e verifiche.

## Soluzione proposta

Ogni SIP deve avere uno stato unico:

- Draft
- Proposed
- Accepted
- Accepted (Partial)
- Accepted (Implemented)
- Deferred
- Rejected
- Superseded

Un SIP puo diventare `Accepted (Implemented)` solo con evidenza minima: codice, migration, test, route, comando di verifica o nota QA.

## Implementazione

Aggiungere uno script:

```bash
bash v2/scripts/check-docs-consistency.sh
```

Lo script controlla che:

- tutti i SIP citati nel README esistano;
- ogni SIP abbia una riga stato;
- lo stesso SIP non abbia stati incompatibili;
- il GAP non dichiari mancanti feature gia implementate;
- i SIP critici abbiano una verifica esplicita.

## Criteri di accettazione

- [ ] Esiste lo script di audit documentale.
- [ ] README, roadmap e GAP sono allineati.
- [ ] I gap obsoleti sono chiusi o spostati in una sezione risolta.
- [ ] Ogni SIP implementato ha evidenza minima.

## Note operative

La governance non deve rallentare lo sviluppo. Serve a evitare duplicazioni, falsi completamenti e decisioni basate su documenti superati.
