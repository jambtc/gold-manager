<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * SIP-0083: Add effort_level to formation and home/away_effort_level to match_state.
 */
class m260602_100000_add_effort_level extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%formation}}', 'effort_level',
            $this->tinyInteger()->unsigned()->notNull()->defaultValue(50)
                ->after('offside_trap')
        );

        $this->addColumn('{{%match_state}}', 'home_effort_level',
            $this->tinyInteger()->unsigned()->notNull()->defaultValue(50)
        );
        $this->addColumn('{{%match_state}}', 'away_effort_level',
            $this->tinyInteger()->unsigned()->notNull()->defaultValue(50)
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%match_state}}', 'away_effort_level');
        $this->dropColumn('{{%match_state}}', 'home_effort_level');
        $this->dropColumn('{{%formation}}', 'effort_level');
    }
}
