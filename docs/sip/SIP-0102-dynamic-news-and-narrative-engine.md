# SIP-0102 - Dynamic News and Narrative Engine

| Campo    | Valore                 |
|----------|------------------------|
| ID       | SIP-0102               |
| Stato    | Proposed               |
| Priorita | High                   |
| Tipo     | Narrative / Retention  |
| Data     | 2026-06-04             |
| Dipende  | SIP-0099, SIP-0100, SIP-0101 |

## Problema

Molti eventi del gioco avvengono senza essere raccontati.

Il manager riceve dati e risultati ma non sempre percepisce la storia che emerge da stagioni, rivalita, mercato e crescita dei giocatori.

## Obiettivo

Trasformare gli eventi di gioco in una narrazione continua che renda ogni club unico.

## Soluzione

Generare articoli, titoli e notizie contestuali basate sugli eventi reali del campionato.

### Eventi supportati

- serie di vittorie o sconfitte;
- derby;
- promozioni e retrocessioni;
- record storici;
- rivalita manageriali;
- mercato e aste;
- giovani emergenti;
- infortuni importanti;
- crisi finanziarie;
- obiettivi completati.

### Livelli di notizia

- locale;
- campionato;
- nazionale;
- storica.

### Personalizzazione

Le news devono considerare:

- reputazione manager;
- rivalita;
- storia del club;
- andamento stagione;
- risultati recenti.

## Implementazione

Aggiungere un servizio dedicato capace di generare template narrativi a partire dagli eventi gia presenti nel sistema.

Le news possono essere pubblicate in:

- dashboard;
- feed societario;
- notifiche;
- digest giornaliero.

## Criteri di accettazione

- [ ] Esistono template narrativi per gli eventi principali.
- [ ] Rivalita e reputazione influenzano le notizie.
- [ ] Le news vengono mostrate nella dashboard.
- [ ] Gli eventi storici producono articoli dedicati.
- [ ] Il sistema evita articoli duplicati e ripetitivi.

## Note operative

Questo SIP aumenta il valore percepito di tutte le meccaniche esistenti senza modificare direttamente il bilanciamento del gameplay.
