<?php

declare(strict_types=1);

use yii\db\Migration;

class m260521_120000_add_game_state_commentary extends Migration
{
    public function safeUp(): void
    {
        $table = $this->db->schema->getTableSchema('{{%commentary_template}}', true);
        if ($table !== null && !isset($table->columns['game_state'])) {
            $this->addColumn('{{%commentary_template}}', 'game_state', $this->string(24)->null()->after('subtype'));
        }

        $indexes = $this->db->schema->getTableIndexes('{{%commentary_template}}', true);
        if (!isset($indexes['idx-commentary-event-state'])) {
            $this->createIndex(
                'idx-commentary-event-state',
                '{{%commentary_template}}',
                ['event_type', 'game_state', 'enabled']
            );
        }

        // Keep legacy generic rows as fallback, but lower probability.
        $this->update('{{%commentary_template}}', ['weight' => 2], [
            'enabled' => 1,
            'event_type' => ['midfield_duel', 'attack_attempt', 'near_miss', 'gk_save', 'half_time', 'full_time'],
            'game_state' => null,
        ]);

        $now = time();
        $stateMinuteMin = [
            'late_push' => 75,
            'time_wasting' => 75,
            'high_press' => 55,
        ];

        $rows = [];
        $push = static function (array &$rows, string $eventType, string $state, int $weight, string $text) use ($stateMinuteMin): void {
            $rows[] = [
                'event_type' => $eventType,
                'subtype' => null,
                'game_state' => $state,
                'min_minute' => $stateMinuteMin[$state] ?? null,
                'max_minute' => null,
                'weight' => $weight,
                'text' => $text,
            ];
        };

        $midfield = [
            'winning' => [
                "{home_team} gestisce il possesso con pazienza: ritmo basso e linee corte, {player_attacker} detta i tempi in mezzo al campo.",
                "Fase di controllo totale: {player_attacker} gioca semplice e {home_team} fa girare palla senza concedere ripartenze.",
                "La squadra in vantaggio addormenta il match: scambi rapidi e pochi rischi nella zona centrale.",
                "{player_attacker} protegge il pallone e rallenta: manovra ordinata, avversari costretti a inseguire.",
                "Partita in gestione: baricentro compatto e circolazione prudente, la mediana tiene il comando.",
                "Possesso ragionato e tempi spezzati: chi è avanti sceglie quando accelerare e quando congelare la giocata.",
            ],
            'drawing' => [
                "Equilibrio pieno a centrocampo: {player_attacker} riceve tra le linee ma trova subito pressione.",
                "Ritmi medi e nessuno scoperto: le due squadre si studiano, palla che viaggia spesso in orizzontale.",
                "Fase tattica pulita: pochi strappi, tanti duelli nella zona mediana e linee ancora molto compatte.",
                "{player_attacker} prova a cambiare lato, ma la densità centrale non lascia varchi evidenti.",
                "Parità anche nel palleggio: possesso alternato, ogni palla persa viene subito riaggredita.",
                "Si gioca molto sul dettaglio: un passaggio forzato può aprire campo, ma per ora prevale prudenza.",
            ],
            'losing' => [
                "{home_team} deve alzare i giri: {player_attacker} cerca verticalità immediata per accorciare i tempi d'azione.",
                "La squadra sotto nel punteggio spinge da centrocampo: recupero aggressivo e ricerca rapida della profondità.",
                "{player_attacker} riceve e orienta in avanti: serve più coraggio per ribaltare l'inerzia.",
                "Pressione crescente nella metà campo avversaria, ma serve maggiore pulizia nell'ultimo passaggio.",
                "Chi insegue prova ad alzare il baricentro: mediana più offensiva e meno tocchi conservativi.",
                "{player_attacker} tenta di alzare il ritmo con una giocata verticale, la partita resta aperta.",
            ],
            'high_press' => [
                "Pressione alta organizzata: {player_attacker} va in anticipo sul primo possesso e forza l'errore in uscita.",
                "Ritmo in aumento: linee aggressive e recupero immediato dopo ogni palla persa.",
                "Fase di forcing: la squadra che insegue stringe gli spazi e prova a schiacciare l'avversario.",
                "{player_attacker} guida la riaggressione, duelli continui e pochi secondi per pensare in costruzione.",
                "Intensità alta nella mediana: pressing coordinato e seconde palle quasi tutte contese.",
                "La pressione resta costante: ogni passaggio orizzontale viene attaccato con grande decisione.",
            ],
            'late_push' => [
                "Finale tirato: {player_attacker} accelera il giro palla e chiama la squadra in avanti alla ricerca dell'episodio.",
                "Ultimi minuti di spinta: la mediana alza metri e prova a schiacciare gli avversari nell'ultimo terzo.",
                "Si sente l'urgenza del finale: scelte più dirette, meno gestione e più attacco della profondità.",
                "{player_attacker} prova a rompere le linee con un filtrante, partita ormai giocata sui nervi.",
                "Fase calda: possesso offensivo insistito, tutti cercano il varco utile prima del fischio finale.",
                "La squadra in rimonta forza i tempi: pressing e transizioni rapide per costruire l'ultima occasione.",
            ],
            'time_wasting' => [
                "Gestione del cronometro evidente: passaggi sicuri e ritmo abbassato per portare a casa il risultato.",
                "{player_attacker} difende palla vicino alla linea laterale e fa respirare i compagni.",
                "Fase di controllo puro: tocchi brevi, pochi rischi e ricerca sistematica del possesso lungo.",
                "La squadra avanti spezza il ritmo con esperienza: giocate semplici e tanta protezione della palla.",
                "Meno verticalità, più gestione: obiettivo chiaro, far scorrere secondi preziosi.",
                "{player_attacker} rallenta l'azione e aspetta supporto: amministrazione lucida del vantaggio.",
            ],
            'balanced' => [
                "Partita ancora in bilico: centrocampo ordinato e attenzione massima sulle seconde palle.",
                "Ritmo controllato ma vivo: ogni transizione viene letta in anticipo da entrambe le linee mediane.",
                "{player_attacker} tocca molti palloni e prova a dare direzione a una fase molto tattica.",
                "Squadre compatte: si gioca sul dettaglio, senza concedere corridoi centrali.",
                "Fase disciplinata: la palla gira, ma le distanze restano corte e la gara resta apertissima.",
                "Nessuna delle due vuole sbilanciarsi troppo: possesso ragionato e coperture sempre puntuali.",
            ],
        ];

        $attack = [
            'winning' => [
                "{player_attacker} porta palla verso il lato forte, ma {home_team} preferisce consolidare il possesso prima dell'affondo finale.",
                "Azione costruita con calma: sovrapposizione esterna, cross schermato e palla di nuovo in gestione.",
                "La squadra avanti attacca con criterio: pochi uomini oltre la linea, priorità all'equilibrio.",
                "{player_attacker} accelera, poi scarica all'indietro: scelta prudente per evitare la transizione avversaria.",
                "Tentativo offensivo ragionato: ingresso in area rinviato, si ricomincia da dietro per mantenere controllo.",
                "Spinta controllata sulla fascia, ma la rifinitura non forza il rischio e l'azione si resetta.",
            ],
            'drawing' => [
                "{player_attacker} riceve fronte porta, punta l'uomo e cerca il corridoio: difesa chiusa all'ultimo.",
                "Buona trama offensiva: scarico e inserimento, ma il cross viene letto con anticipo.",
                "Azione manovrata con ampiezza, palla in area e deviazione che spegne il tentativo.",
                "{player_attacker} prova il cambio passo nel mezzo spazio, raddoppio difensivo tempestivo.",
                "La squadra cerca superiorità sul lato: palla dentro interessante, manca il tocco decisivo.",
                "Transizione veloce, conduzione palla al piede e conclusione preparata ma schermata dalla difesa.",
            ],
            'losing' => [
                "{player_attacker} va diretto verso l'area: la squadra che rincorre prova ad aumentare volume offensivo.",
                "Ricerca immediata della profondità: palla filtrante e difesa che chiude in extremis.",
                "Tentativo rapido e verticale per rientrare in partita, ma la copertura difensiva regge.",
                "Azione insistita sul lato debole, {player_attacker} prova la giocata decisiva senza trovare spazio pulito.",
                "Chi è sotto forza tempi e metri: conclusione preparata in fretta, opposizione efficace.",
                "{player_attacker} trascina l'attacco e cerca il varco centrale, ma la linea difensiva resta compatta.",
            ],
            'high_press' => [
                "Recupero alto e attacco immediato: {player_attacker} cerca il tiro rapido dopo la riconquista.",
                "Pressione offensiva continua: palla strappata in zona pericolosa e rifinitura tentata di prima.",
                "Azione da riaggressione: pochi tocchi e verticalità, difesa che si salva in chiusura.",
                "{player_attacker} riceve dopo forcing collettivo e prova a sfondare centralmente.",
                "Spinta feroce nel terzo offensivo: recupero e immediata ricerca della porta.",
                "L'intensità resta altissima: attacco diretto, ma l'ultimo controllo allunga la giocata.",
            ],
            'late_push' => [
                "Minuti finali e pressione totale: {player_attacker} cerca il fondo, cross teso respinto all'ultimo.",
                "Forcing nel finale: palla in area, rimpallo favorevole sfumato per un soffio.",
                "La squadra ci crede fino in fondo: attacco rapido, difesa in affanno ma ancora in piedi.",
                "{player_attacker} tenta la giocata individuale nel traffico, pallone allontanato di misura.",
                "Ultimo assalto organizzato: ampiezza, palla interna e tiro preparato ma murato.",
                "Spinta disperata nel recupero: transizione offensiva veloce, manca solo il dettaglio conclusivo.",
            ],
            'time_wasting' => [
                "Azione offensiva più per respirare che per colpire: {player_attacker} porta palla e guadagna secondi preziosi.",
                "Gestione lucida in avanti: possesso vicino alla bandierina e ritmo volutamente spezzato.",
                "La squadra avanti non forza il tiro: tiene palla alta e fa salire il blocco.",
                "{player_attacker} protegge il pallone spalle alla porta, compagni che consolidano il possesso.",
                "Attacco a basso rischio: pochi uomini in area, obiettivo principale far scorrere il tempo.",
                "Transizione controllata e poi pausa: si sceglie di congelare la giocata in zona offensiva.",
            ],
            'balanced' => [
                "{player_attacker} prova a dare ritmo all'azione, ma la difesa legge bene tempi e linee di passaggio.",
                "Manovra offensiva ordinata: buona occupazione degli spazi, manca solo l'ultimo tocco.",
                "Tentativo in progressione: palla portata al limite e conclusione non liberata.",
                "Azione elaborata con pazienza, poi imbucata intercettata sul più bello.",
                "{player_attacker} cambia passo e cerca il triangolo: difensori attenti e azione rallentata.",
                "Attacco costruito bene fino alla trequarti, dove arriva la chiusura decisiva.",
            ],
        ];

        $nearMiss = [
            'winning' => [
                "{player_attacker} si crea lo spazio e conclude: palla a lato di un niente, occasione per il possibile allungo.",
                "Buon attacco posizionale e tiro di {player_attacker}: traiettoria fuori di poco, difesa graziata.",
                "{player_attacker} arriva al tiro dal limite, pallone che sfiora il palo e termina fuori.",
                "Azione pulita e conclusione immediata: solo centimetri negano la rete del raddoppio.",
            ],
            'drawing' => [
                "{player_attacker} calcia con intenzione, palla che lambisce il palo e lascia il risultato in equilibrio.",
                "Occasione netta: conclusione di {player_attacker} fuori di un soffio.",
                "Che brivido! {player_attacker} trova il tempo del tiro, ma manca la porta per questione di centimetri.",
                "Traiettoria insidiosa di {player_attacker}: palla alta di poco sopra la traversa.",
            ],
            'losing' => [
                "Grandissima occasione per {player_attacker}: tiro a botta sicura, ma la palla esce di pochissimo.",
                "{player_attacker} sfiora il pari con una conclusione improvvisa che accarezza il palo.",
                "La squadra in rimonta va vicina al gol: {player_attacker} non trova la porta per un nulla.",
                "Occasione pesante nel momento chiave: {player_attacker} conclude e manca il bersaglio di poco.",
            ],
            'late_push' => [
                "Finale incandescente: {player_attacker} calcia dal cuore dell'area, palla fuori per centimetri.",
                "Ultimi minuti e brivido enorme: il tiro di {player_attacker} sfiora il palo esterno.",
                "Forcing totale, conclusione improvvisa di {player_attacker}: stadio col fiato sospeso, niente rete.",
                "Occasione gigantesca nel finale: {player_attacker} non trova il gol per una deviazione minima.",
            ],
            'high_press' => [
                "Recupero alto e tiro immediato di {player_attacker}: palla fuori di poco.",
                "Pressione efficace, conclusione rapida: {player_attacker} manca il bersaglio per centimetri.",
                "Azione nata dal pressing: {player_attacker} va al tiro, ma il pallone esce sul fondo.",
                "Riconquista e tentativo diretto: {player_attacker} sfiora la rete con un tiro teso.",
            ],
        ];

        $gkSave = [
            'winning' => [
                "{player_gk} resta concentrato e blocca la conclusione: parata pulita che protegge il vantaggio.",
                "Intervento sicuro di {player_gk} sul tiro di {player_attacker}: leadership totale nell'area piccola.",
                "{player_gk} legge in anticipo la giocata e respinge con autorità.",
                "Parata tecnica di {player_gk}: nessun rischio sulla conclusione avversaria.",
            ],
            'drawing' => [
                "Riflesso notevole di {player_gk}! Nega il gol a {player_attacker} e mantiene la parità.",
                "{player_gk} si distende e devia: intervento decisivo in un momento equilibrato.",
                "Gran parata di {player_gk} sul tiro di {player_attacker}, punteggio ancora bloccato.",
                "{player_gk} salva il risultato con un intervento rapido sul primo palo.",
            ],
            'losing' => [
                "{player_gk} tiene viva la partita con una parata importante su {player_attacker}.",
                "Intervento d'orgoglio di {player_gk}: evita un passivo ancora più pesante.",
                "Parata di reazione di {player_gk}, la squadra resta in corsa nonostante il punteggio.",
                "{player_gk} risponde presente e dà nuova energia ai compagni in difficoltà.",
            ],
            'late_push' => [
                "Nel finale {player_gk} compie un intervento enorme su {player_attacker}, partita ancora apertissima.",
                "Parata pesantissima di {player_gk} a tempo quasi scaduto!",
                "{player_gk} vola sul tiro ravvicinato e tiene tutto in bilico.",
                "Intervento decisivo negli ultimi minuti: {player_gk} respinge e salva il risultato.",
            ],
            'high_press' => [
                "Con squadra lunga e pressione alta, {player_gk} deve intervenire subito: parata efficace.",
                "Azione veloce avversaria, ma {player_gk} chiude lo specchio con grande tempismo.",
                "Parata in situazione delicata: {player_gk} gestisce bene il tiro dopo transizione.",
                "{player_gk} neutralizza il tentativo di {player_attacker} e rilancia immediatamente l'azione.",
            ],
        ];

        $halfTime = [
            'winning' => [
                "Intervallo: {home_team} conduce {score_home}–{score_away} su {away_team}. Primo tempo gestito con ordine e tempi giusti.",
                "Fine primo tempo sul {score_home}–{score_away}: squadra avanti meritatamente, avversari chiamati a cambiare ritmo.",
                "Duplice fischio: {score_home}–{score_away}. Vantaggio costruito con equilibrio e qualità nella gestione palla.",
            ],
            'drawing' => [
                "Intervallo sul {score_home}–{score_away}: gara molto tattica, equilibrio pieno e pochi varchi concessi.",
                "Fine primo tempo in parità, {score_home}–{score_away}: partita ancora tutta da scrivere.",
                "Duplice fischio: risultato bloccato sul {score_home}–{score_away}, ritmi alterni e massima attenzione difensiva.",
            ],
            'losing' => [
                "Intervallo: {home_team} sotto {score_home}–{score_away}. Servirà più intensità nella ripresa per rimetterla in piedi.",
                "Fine primo tempo complicata: {score_home}–{score_away}. La squadra in svantaggio deve alzare ritmo e precisione.",
                "Duplice fischio sul {score_home}–{score_away}: c'è margine di reazione, ma il secondo tempo richiede un cambio netto.",
            ],
        ];

        $fullTime = [
            'winning' => [
                "Triplice fischio: {home_team} chiude sul {score_home}–{score_away} con una prova solida e matura.",
                "Finisce {score_home}–{score_away}: vittoria costruita con gestione intelligente dei momenti chiave.",
                "Finale allo stadio: {score_home}–{score_away}. Prestazione concreta e risultato difeso con ordine.",
            ],
            'drawing' => [
                "È finita: {score_home}–{score_away}. Pareggio coerente con l'equilibrio visto nei novanta minuti.",
                "Triplice fischio sul {score_home}–{score_away}: partita combattuta e sostanzialmente bilanciata.",
                "Finale: {score_home}–{score_away}. Nessuna squadra è riuscita a trovare l'episodio decisivo.",
            ],
            'losing' => [
                "Triplice fischio: termina {score_home}–{score_away}. Risultato amaro, ma gara rimasta aperta a lungo.",
                "Finisce qui: {score_home}–{score_away}. La squadra sconfitta ha provato a rientrare senza completare la rimonta.",
                "Finale allo stadio: {score_home}–{score_away}. Ospiti più concreti nei momenti decisivi.",
            ],
        ];

        foreach ($midfield as $state => $texts) {
            foreach ($texts as $text) {
                $push($rows, 'midfield_duel', $state, 14, $text);
            }
        }
        foreach ($attack as $state => $texts) {
            foreach ($texts as $text) {
                $push($rows, 'attack_attempt', $state, 14, $text);
            }
        }
        foreach ($nearMiss as $state => $texts) {
            foreach ($texts as $text) {
                $push($rows, 'near_miss', $state, 13, $text);
            }
        }
        foreach ($gkSave as $state => $texts) {
            foreach ($texts as $text) {
                $push($rows, 'gk_save', $state, 13, $text);
            }
        }
        foreach ($halfTime as $state => $texts) {
            foreach ($texts as $text) {
                $push($rows, 'half_time', $state, 12, $text);
            }
        }
        foreach ($fullTime as $state => $texts) {
            foreach ($texts as $text) {
                $push($rows, 'full_time', $state, 12, $text);
            }
        }

        foreach ($rows as $row) {
            $this->insert('{{%commentary_template}}', [
                'event_type' => $row['event_type'],
                'subtype' => $row['subtype'],
                'game_state' => $row['game_state'],
                'min_minute' => $row['min_minute'],
                'max_minute' => $row['max_minute'],
                'weight' => $row['weight'],
                'text' => $row['text'],
                'enabled' => 1,
                'created_at' => $now,
            ]);
        }
    }

    public function safeDown(): void
    {
        echo "m260521_120000_add_game_state_commentary cannot be reverted safely.\n";
    }
}

