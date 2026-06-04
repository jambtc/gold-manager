# SIP-0099 - Daily Engagement Loop

| Campo    | Valore              |
|----------|---------------------|
| ID       | SIP-0099            |
| Stato    | Proposed            |
| Priorita | High                |
| Tipo     | Gameplay / Retention|
| Data     | 2026-06-04          |
| Dipende  | SIP-0080, SIP-0087  |

## Problema

Gold Manager ha molte funzionalita ma non garantisce che ogni login produca un evento interessante.

L'utente deve avere una ragione per tornare ogni giorno.

## Obiettivo

Creare un ciclo giornaliero di attivita che generi curiosita, progressione e decisioni.

## Soluzione

Ogni giorno il manager dovrebbe trovare almeno uno tra:

- report scout completato;
- miglioramento giocatore;
- proposta sponsor;
- offerta di mercato;
- rinnovo contratto;
- notizia societaria;
- evento spogliatoio;
- alert staff;
- obiettivo giornaliero.

## Implementazione

### Daily Digest

Creare una schermata o widget giornaliero che raccolga:

- eventi ultime 24 ore;
- azioni consigliate;
- trattative aperte;
- partite imminenti;
- staff e scouting.

### Trigger

Ogni login giornaliero deve valutare:

- presenza nuovi eventi;
- opportunita mercato;
- progressione giovani;
- obiettivi attivi.

### Notifiche

Gli eventi piu importanti devono essere visibili tramite news, SSE e canali notifiche.

## Criteri di accettazione

- [ ] Esiste un daily digest.
- [ ] Ogni giorno viene generato almeno un punto di interesse quando possibile.
- [ ] Gli eventi sono visibili nella news feed.
- [ ] Le opportunita mercato entrano nel digest.
- [ ] La dashboard mostra azioni consigliate.

## Note operative

L'obiettivo non e' premiare il login con bonus artificiali, ma creare un ecosistema che continui a produrre decisioni interessanti per il manager.
