<?php

use yii\db\Migration;

class m260516_160406_add_training_focus_to_team_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%team}}", "training_focus", $this->string(10)->defaultValue("balanced"));
        $this->addColumn("{{%team}}", "training_intensity", $this->integer()->defaultValue(50));
    }

    public function safeDown()
    {
        $this->dropColumn("{{%team}}", "training_focus");
        $this->dropColumn("{{%team}}", "training_intensity");
    }
}
