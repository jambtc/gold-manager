<?php

use yii\db\Migration;

class m260528_210000_add_height_weight_to_player extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%player}}', 'height_cm', $this->tinyInteger()->unsigned()->notNull()->defaultValue(180)->after('nationality'));
        $this->addColumn('{{%player}}', 'weight_kg', $this->tinyInteger()->unsigned()->notNull()->defaultValue(75)->after('height_cm'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%player}}', 'height_cm');
        $this->dropColumn('{{%player}}', 'weight_kg');
    }
}
