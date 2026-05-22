<?php

declare(strict_types=1);

use yii\db\Migration;

class m260521_090000_expand_goal_commentary_templates extends Migration
{
    public function safeUp(): void
    {
        // Keep old rows but reduce selection probability.
        $this->update('{{%commentary_template}}', ['weight' => 2], [
            'event_type' => 'goal',
            'enabled' => 1,
        ]);

        $now = time();
        $rows = [
            // home goal: build-up -> shot -> outcome
            ['goal', 'home', null, null, 20, "Azione insistita di {home_team} sulla corsia destra: palla dentro per {player_attacker}, controllo rapido e destro secco... GOL! {score_home}–{score_away}.", $now],
            ['goal', 'home', null, null, 20, "{home_team} manovra con pazienza, poi verticalizza: {player_attacker} attacca lo spazio, entra in area e conclude di prima... rete! Ora è {score_home}–{score_away}.", $now],
            ['goal', 'home', null, null, 18, "Recupero alto di {home_team}, transizione veloce e ultimo passaggio per {player_attacker}: tiro angolato e palla in fondo al sacco! {score_home}–{score_away}.", $now],
            ['goal', 'home', null, null, 18, "Palla lavorata al limite da {home_team}, scarico perfetto su {player_attacker}: conclusione potente sotto la traversa... GOL del {score_home}–{score_away}!", $now],
            ['goal', 'home', null, null, 16, "{minute}' — Spinta di {home_team} dalla fascia, cross teso sul primo palo: {player_attacker} anticipa tutti e insacca. Risultato aggiornato: {score_home}–{score_away}.", $now],
            ['goal', 'home', 75, null, 22, "Finale rovente: {home_team} alza il ritmo, sfonda centralmente e serve {player_attacker} in area. Tocco preciso e GOL pesantissimo! {score_home}–{score_away}.", $now],

            // away goal: build-up -> shot -> outcome
            ['goal', 'away', null, null, 20, "Ripartenza di {away_team} con campo aperto: palla filtrante per {player_attacker}, due passi e tiro sul secondo palo... GOL! Tabellone {score_home}–{score_away}.", $now],
            ['goal', 'away', null, null, 20, "{away_team} costruisce da sinistra, cambia lato e trova {player_attacker} libero: controllo orientato, conclusione immediata e rete! {score_home}–{score_away}.", $now],
            ['goal', 'away', null, null, 18, "Pressione offensiva di {away_team}, palla riconquistata al limite: {player_attacker} si coordina e calcia forte... gol ospite, siamo sul {score_home}–{score_away}.", $now],
            ['goal', 'away', null, null, 18, "{minute}' — Azione avvolgente di {away_team}, assist rasoterra per {player_attacker}: anticipo perfetto e palla in rete. Nuovo punteggio {score_home}–{score_away}.", $now],
            ['goal', 'away', null, null, 16, "Sovrapposizione esterna di {away_team}, cross arretrato in zona pericolosa: arriva {player_attacker}, tiro di prima e GOL! {score_home}–{score_away}.", $now],
            ['goal', 'away', 75, null, 22, "Nel finale {away_team} trova lo spiraglio: imbucata per {player_attacker}, conclusione fredda davanti alla porta e rete decisiva! {score_home}–{score_away}.", $now],
        ];

        foreach ($rows as $r) {
            $this->insert('{{%commentary_template}}', [
                'event_type' => $r[0],
                'subtype' => $r[1],
                'min_minute' => $r[2],
                'max_minute' => $r[3],
                'weight' => $r[4],
                'text' => $r[5],
                'enabled' => 1,
                'created_at' => $r[6],
            ]);
        }
    }

    public function safeDown(): void
    {
        echo "m260521_090000_expand_goal_commentary_templates cannot be reverted safely.\n";
    }
}

