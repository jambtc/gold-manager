<?php

declare(strict_types=1);

use yii\db\Migration;

class m260519_200000_add_cards_system extends Migration
{
    public function safeUp(): void
    {
        // Player: season card tallies + suspension counter
        $this->addColumn('{{%player}}', 'yellow_cards',      $this->tinyInteger()->notNull()->defaultValue(0)->after('character'));
        $this->addColumn('{{%player}}', 'red_cards',         $this->tinyInteger()->notNull()->defaultValue(0)->after('yellow_cards'));
        $this->addColumn('{{%player}}', 'suspended_matches', $this->tinyInteger()->notNull()->defaultValue(0)->after('red_cards'));

        // Match state: ejection flag + per-match yellow tracker (JSON)
        $this->addColumn('{{%match_state}}', 'home_ejected', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('away_subs_used'));
        $this->addColumn('{{%match_state}}', 'away_ejected', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('home_ejected'));
        $this->addColumn('{{%match_state}}', 'home_yellows', $this->text()->null()->after('away_ejected'));
        $this->addColumn('{{%match_state}}', 'away_yellows', $this->text()->null()->after('home_yellows'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%player}}', 'yellow_cards');
        $this->dropColumn('{{%player}}', 'red_cards');
        $this->dropColumn('{{%player}}', 'suspended_matches');
        $this->dropColumn('{{%match_state}}', 'home_ejected');
        $this->dropColumn('{{%match_state}}', 'away_ejected');
        $this->dropColumn('{{%match_state}}', 'home_yellows');
        $this->dropColumn('{{%match_state}}', 'away_yellows');
    }
}
