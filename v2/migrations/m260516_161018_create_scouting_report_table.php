<?php

use yii\db\Migration;

class m260516_161018_create_scouting_report_table extends Migration
{
    public function safeUp()
    {
        $this->createTable("{{%scouting_report}}", [
            "id" => $this->primaryKey(),
            "team_id" => $this->integer()->notNull(),
            "player_id" => $this->integer()->notNull(),
            "revealed_talent" => $this->string(50),
            "potential_score" => $this->integer(),
            "report_text" => $this->text(),
            "created_at" => $this->integer()->notNull(),
        ]);

        $this->addForeignKey("fk-scout-team", "{{%scouting_report}}", "team_id", "{{%team}}", "id", "CASCADE");
        $this->addForeignKey("fk-scout-player", "{{%scouting_report}}", "player_id", "{{%player}}", "id", "CASCADE");
    }

    public function safeDown()
    {
        $this->dropTable("{{%scouting_report}}");
    }
}
