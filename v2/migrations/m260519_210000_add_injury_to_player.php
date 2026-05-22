<?php

declare(strict_types=1);

use yii\db\Migration;

class m260519_210000_add_injury_to_player extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%player}}', 'injury_weeks', $this->tinyInteger()->notNull()->defaultValue(0)->after('condition'));
        $this->addColumn('{{%player}}', 'injury_type',  $this->string(10)->null()->defaultValue(null)->after('injury_weeks'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%player}}', 'injury_weeks');
        $this->dropColumn('{{%player}}', 'injury_type');
    }
}
