<?php

use yii\db\Migration;

class m260517_141549_add_half_time_ticks_to_match_state extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%match_state}}', 'half_time_ticks', $this->integer()->notNull()->defaultValue(0)->after('away_subs_used'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%match_state}}', 'half_time_ticks');
    }
}

