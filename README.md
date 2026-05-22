# Gold Manager v2
Il simulatore di calcio moderno basato sullo storico Gold Manager.

## Stack Tecnologico
- **Backend**: Yii2 (PHP 8.3)
- **Engine Live**: Go 1.21 (Worker per simulazione tick-based con supporto a SIP-0023/24)
- **Streaming**: SSE (Server-Sent Events) sulla porta 8080
- **Real-time**: Canale di comando per interventi tattici live
- **Frontend**: Bootstrap 5 + Vanilla JS (Modern Dark UI)
- **Database**: MariaDB 10.11

## Funzionalità Principali
- **Live Match**: Streaming istantaneo con possibilità di cambi tattici in tempo reale.
- **Mondo Infinito**: Generazione dinamica di nuove leghe (Serie A/B/C) all'infinito.
- **Management 360°**: Gestione Staff, Allenamento, Scouting e Sponsor.
- **Financial Dashboard**: Proiezioni economiche settimanali e analisi di sostenibilità.

## Installazione e Avvio

```bash
docker compose up --build
```

L'applicazione sarà disponibile su:
- **Web UI**: http://localhost:30203
- **SSE Stream**: http://localhost:8080/stream/{fixture_id}
