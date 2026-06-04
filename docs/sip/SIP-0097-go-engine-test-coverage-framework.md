# SIP-0097 - Go Engine Test Coverage Framework

| Campo    | Valore              |
|----------|---------------------|
| ID       | SIP-0097            |
| Stato    | Proposed            |
| Priorita | High                |
| Tipo     | Quality / Engine    |
| Data     | 2026-06-04          |
| Dipende  | SIP-0021, SIP-0024, SIP-0066, SIP-0090 |

## Problema

Il worker Go e' una parte critica del gameplay realtime. La documentazione indica che `go test ./...` compila, ma non esistono ancora test Go specifici.

Questo crea rischio su:

- goal rate;
- piazzati;
- cartellini;
- infortuni;
- eventi SSE;
- finalizzazione partita;
- aggiornamento classifica;
- parita PHP/Go.

## Obiettivo

Introdurre una suite di test Go che protegga il match engine realtime da regressioni e renda misurabile la coerenza con le regole PHP.

## Soluzione

Creare test automatici per le aree principali:

1. Engine tick-by-tick.
2. Event stream e ordine eventi.
3. Set piece sequence.
4. Cards e injuries.
5. Goal rate calibration.
6. Match finalization.
7. Standing update.
8. Command channel live tactics.

## Implementazione

### Struttura proposta

```text
v2/worker-go/engine/*_test.go
v2/worker-go/stream/*_test.go
v2/worker-go/orchestrator/*_test.go
```

### Test minimi

- Simulare 1000 match con seed controllato e verificare goal rate medio.
- Verificare che rigore, punizione e corner generino evento assegnato + risoluzione successiva.
- Verificare che rosso diretto e doppio giallo riducano la forza outfield.
- Verificare che friendly non generi injury/card dove previsto.
- Verificare che `full_time` chiuda la partita una sola volta.
- Verificare che standings siano aggiornate idempotentemente.
- Verificare che i comandi tattici live cambino stato senza duplicati.

## Criteri di accettazione

- [ ] Esistono test Go reali oltre alla sola compilazione.
- [ ] `go test ./...` passa in locale e CI.
- [ ] Il goal rate medio resta entro una soglia documentata.
- [ ] Set piece, cards, injuries e finalizzazione hanno test dedicati.
- [ ] La documentazione testing viene aggiornata.

## Note operative

Questa SIP non modifica il gameplay. Serve a rendere sicuro ogni futuro intervento sul motore live.
