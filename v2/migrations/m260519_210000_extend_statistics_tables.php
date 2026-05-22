<?php

use yii\db\Migration;

class m260519_210000_extend_statistics_tables extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%player_stat}}', 'minutes_played', $this->smallInteger()->notNull()->defaultValue(0)->after('red_cards'));
        $this->addColumn('{{%player_stat}}', 'penalties_scored', $this->tinyInteger()->notNull()->defaultValue(0)->after('minutes_played'));
        $this->addColumn('{{%player_stat}}', 'penalties_missed', $this->tinyInteger()->notNull()->defaultValue(0)->after('penalties_scored'));
        $this->addColumn('{{%player_stat}}', 'saves', $this->tinyInteger()->notNull()->defaultValue(0)->after('penalties_missed'));
        $this->addColumn('{{%player_stat}}', 'clean_sheet', $this->tinyInteger()->notNull()->defaultValue(0)->after('saves'));

        $this->addColumn('{{%standing}}', 'penalties_for', $this->smallInteger()->notNull()->defaultValue(0)->after('goals_against'));
        $this->addColumn('{{%standing}}', 'penalties_against', $this->smallInteger()->notNull()->defaultValue(0)->after('penalties_for'));
        $this->addColumn('{{%standing}}', 'clean_sheets', $this->smallInteger()->notNull()->defaultValue(0)->after('penalties_against'));
        $this->addColumn('{{%standing}}', 'biggest_win_home', $this->tinyInteger()->notNull()->defaultValue(0)->after('clean_sheets'));
        $this->addColumn('{{%standing}}', 'biggest_win_away', $this->tinyInteger()->notNull()->defaultValue(0)->after('biggest_win_home'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%standing}}', 'biggest_win_away');
        $this->dropColumn('{{%standing}}', 'biggest_win_home');
        $this->dropColumn('{{%standing}}', 'clean_sheets');
        $this->dropColumn('{{%standing}}', 'penalties_against');
        $this->dropColumn('{{%standing}}', 'penalties_for');

        $this->dropColumn('{{%player_stat}}', 'clean_sheet');
        $this->dropColumn('{{%player_stat}}', 'saves');
        $this->dropColumn('{{%player_stat}}', 'penalties_missed');
        $this->dropColumn('{{%player_stat}}', 'penalties_scored');
        $this->dropColumn('{{%player_stat}}', 'minutes_played');
    }
}
