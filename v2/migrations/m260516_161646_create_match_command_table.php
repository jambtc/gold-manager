<?php

use yii\db\Migration;

class m260516_161646_create_match_command_table extends Migration
{
    public function safeUp()
    {
        $this->createTable("{{%match_command}}", [
            "id" => $this->primaryKey(),
            "fixture_id" => $this->integer()->notNull(),
            "team_id" => $this->integer()->notNull(),
            "command_type" => $this->string(50)->notNull(),
            "payload" => $this->text(),
            "minute_submitted" => $this->integer()->notNull(),
            "executed_at_minute" => $this->integer(),
            "created_at" => $this->integer()->notNull(),
        ]);

        $this->addForeignKey("fk-mc-fixture", "{{%match_command}}", "fixture_id", "{{%fixture}}", "id", "CASCADE");
        $this->addForeignKey("fk-mc-team", "{{%match_command}}", "team_id", "{{%team}}", "id", "CASCADE");
    }

    public function safeDown()
    {
        $this->dropTable("{{%match_command}}");
    }
}
