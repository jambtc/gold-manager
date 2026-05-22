<?php

declare(strict_types=1);

use yii\db\Migration;

class m260520_050000_create_commentary_template extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%commentary_template}}', [
            'id'             => $this->primaryKey(),
            'event_type'     => $this->string(30)->notNull(),
            'subtype'        => $this->string(20)->null(),
            'min_minute'     => $this->tinyInteger()->null(),
            'max_minute'     => $this->tinyInteger()->null(),
            'weight'         => $this->smallInteger()->notNull()->defaultValue(10),
            'text'           => $this->text()->notNull(),
            'enabled'        => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at'     => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-commentary-event', '{{%commentary_template}}',
            ['event_type', 'enabled']);

        // Seed initial phrases
        $now = time();
        $phrases = [
            // kickoff
            ['kickoff', null, null, null, 10, "Fischio d'inizio! {home_team} contro {away_team}, si parte.", $now],
            ['kickoff', null, null, null, 10, "Il calcio d'inizio è battuto da {home_team}. Il match ha inizio!", $now],
            ['kickoff', null, null, null, 10, "Si parte! {minute}° minuto — {home_team} affronta {away_team}.", $now],
            // pre_match
            ['pre_match', null, null, null, 10, "Benvenuti allo stadio! {home_team} e {away_team} pronti a sfidarsi. {spectators} spettatori presenti.", $now],
            ['pre_match', null, null, null, 10, "Atmosfera elettrica: {home_team} ospita {away_team}. Tutto pronto per il calcio d'inizio.", $now],
            ['pre_match', null, null, null, 10, "Le squadre scendono in campo: {home_team} in maglia di casa, {away_team} in trasferta.", $now],
            // goal home
            ['goal', 'home', null, null, 15, "GOOOOOL! {player_attacker} non sbaglia e porta in vantaggio {home_team}! {score_home}–{score_away}!", $now],
            ['goal', 'home', null, null, 15, "Rete di {player_attacker}! {home_team} esplode di gioia, è {score_home}–{score_away}!", $now],
            ['goal', 'home', null, null, 10, "{player_attacker} segna il gol del {score_home}–{score_away} per {home_team}! Grande giocata!", $now],
            ['goal', 'home', null, null, 10, "Che gol! {player_attacker} batte il portiere avversario. {score_home}–{score_away}!", $now],
            // goal away
            ['goal', 'away', null, null, 15, "GOL IN TRASFERTA! {player_attacker} porta {away_team} in vantaggio! {score_home}–{score_away}!", $now],
            ['goal', 'away', null, null, 15, "Segna {player_attacker} per {away_team}! Il tabellone segna {score_home}–{score_away}.", $now],
            ['goal', 'away', null, null, 10, "Rete di {player_attacker}! {away_team} trova il gol del {score_home}–{score_away}.", $now],
            // gk_save
            ['gk_save', null, null, null, 12, "{player_gk} si distende e para in modo straordinario! Che intervento!", $now],
            ['gk_save', null, null, null, 12, "Parata di {player_gk}! Il portiere nega il gol con i pugni.", $now],
            ['gk_save', null, null, null, 10, "Grande riflesso di {player_gk}: risponde presente su un tiro insidioso.", $now],
            ['gk_save', null, null, null, 10, "Miracolo del portiere! {player_gk} vola e devia il tiro in angolo.", $now],
            // near_miss
            ['near_miss', null, null, null, 12, "{player_attacker} calcia e sfiora il palo! A un soffio dal gol.", $now],
            ['near_miss', null, null, null, 12, "Traversa! {player_attacker} colpisce il legno, palla fuori.", $now],
            ['near_miss', null, null, null, 10, "Tiro di {player_attacker} — palla fuori di pochissimo. Che occasione!", $now],
            ['near_miss', null, null, null, 10, "{player_attacker} non trova la porta: conclusione alta sulla traversa.", $now],
            // half_time
            ['half_time', null, null, null, 10, "Duplice fischio dell'arbitro! Si va negli spogliatoi: {score_home}–{score_away}.", $now],
            ['half_time', null, null, null, 10, "Fine primo tempo: {home_team} {score_home} – {score_away} {away_team}.", $now],
            ['half_time', null, null, null, 10, "Intervallo! Le squadre rientrano negli spogliatoi sul risultato di {score_home}–{score_away}.", $now],
            // second_half_start
            ['second_half_start', null, null, null, 10, "Fischio d'inizio del secondo tempo! Si riprende.", $now],
            ['second_half_start', null, null, null, 10, "Le squadre tornano in campo. Secondo tempo: si riparte sul {score_home}–{score_away}.", $now],
            // full_time
            ['full_time', null, null, null, 10, "Triplice fischio! Finisce {home_team} {score_home} – {score_away} {away_team}.", $now],
            ['full_time', null, null, null, 10, "È finita! Risultato finale: {score_home}–{score_away}.", $now],
            ['full_time', null, null, null, 10, "L'arbitro fischia la fine. {home_team} {score_home}–{score_away} {away_team}.", $now],
            // substitution
            ['substitution', null, null, null, 10, "Cambio! Entra {player_in}, lascia il campo {player_out}.", $now],
            ['substitution', null, null, null, 10, "Sostituzione: {player_out} cede il posto a {player_in}.", $now],
            // tactic_change
            ['tactic_change', null, null, null, 10, "Cambio tattico in campo: l'allenatore ridisegna l'assetto.", $now],
            ['tactic_change', null, null, null, 10, "Nuova disposizione! La squadra cambia modulo e riparte.", $now],
            // yellow_card
            ['yellow_card', null, null, null, 10, "Cartellino giallo per {player_attacker}! L'arbitro lo ammonisce.", $now],
            ['yellow_card', null, null, null, 10, "{player_attacker} finisce sul taccuino dell'arbitro: giallo!", $now],
            // red_card
            ['red_card', null, null, null, 10, "ROSSO! {player_attacker} lascia il campo — la squadra in dieci!", $now],
            ['red_card', null, null, null, 10, "Espulsione diretta per {player_attacker}! Partita complicata.", $now],
            // injury
            ['injury', null, null, null, 10, "{player_attacker} si accascia a terra: infortunio in campo.", $now],
            ['injury', null, null, null, 10, "Stop per infortunio: {player_attacker} chiede il cambio.", $now],
            // midfield_duel / attack_attempt (quiet commentary)
            ['midfield_duel', null, null, null, 8, "Duello a centrocampo: le squadre si studiano.", $now],
            ['midfield_duel', null, null, null, 8, "Possesso palla prolungato senza azioni pericolose.", $now],
            ['attack_attempt', null, null, null, 8, "Azione offensiva! La palla viene smistata in area.", $now],
            ['attack_attempt', null, null, null, 8, "{player_attacker} tenta l'accelerazione sulla fascia.", $now],
            // late tension
            ['near_miss', null, 80, null, 15, "Minuto {minute}: {player_attacker} sfiora il gol del possibile {score_home}–{score_away}! Cuore in gola!", $now],
            ['goal', 'home', 80, null, 15, "GOL NEL FINALE! {player_attacker} segna al {minute}' per {home_team}! {score_home}–{score_away}!", $now],
            ['goal', 'away', 80, null, 15, "RIBALTONE FINALE! {player_attacker} buca la rete all'{minute}'! {score_home}–{score_away}!", $now],
        ];

        foreach ($phrases as $p) {
            $this->insert('{{%commentary_template}}', [
                'event_type' => $p[0],
                'subtype'    => $p[1],
                'min_minute' => $p[2],
                'max_minute' => $p[3],
                'weight'     => $p[4],
                'text'       => $p[5],
                'enabled'    => 1,
                'created_at' => $p[6],
            ]);
        }
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%commentary_template}}');
    }
}
