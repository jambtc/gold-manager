# SIP-0096 - i18n Compliance Enforcement

| Campo    | Valore            |
|----------|-------------------|
| ID       | SIP-0096          |
| Stato    | Proposed          |
| Priorita | High              |
| Tipo     | Quality / UX      |
| Data     | 2026-06-04        |
| Dipende  | SIP-0069          |

## Problema

La regola i18n richiede testo sorgente inglese dentro `Yii::t('app', ...)` e traduzioni nei cataloghi locali.
Nel codice sono ancora presenti testi italiani hardcoded in controller, service e notifiche.

Questo rende difficile mantenere UI multilingua, PWA, mobile API e notifiche coerenti.

## Obiettivo

Rendere verificabile la conformita i18n di tutto il testo visibile all'utente.

## Soluzione

1. Vietare stringhe user-facing hardcoded fuori da `Yii::t`.
2. Usare sempre sorgente inglese in `Yii::t('app', 'English text')`.
3. Spostare l'italiano in `messages/it-IT/app.php`.
4. Coprire anche news, Telegram, email, SSE, flash messages e template JS.

## Implementazione

- Audit di controller, componenti, view e asset JS.
- Script `v2/scripts/check-i18n-source-strings.sh`.
- Regole base: cercare testo italiano in `setFlash`, `NewsService`, `NotificationService`, template HTML e JS.
- Aggiungere traduzioni mancanti nei cataloghi.

## Criteri di accettazione

- [ ] Nessun nuovo testo italiano hardcoded in controller/view/componenti.
- [ ] Tutti i messaggi utente passano da `Yii::t`.
- [ ] Catalogo `it-IT` aggiornato.
- [ ] Script di controllo eseguibile localmente.
- [ ] Le notifiche generate dal backend sono localizzabili.

## Note operative

La migrazione puo essere incrementale: prima controller e notifiche, poi view, poi JS e template dinamici.
