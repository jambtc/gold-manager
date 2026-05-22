<?php

use yii\db\Migration;

class m260516_161155_create_sponsor_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable("{{%sponsor}}", [
            "id" => $this->primaryKey(),
            "name" => $this->string()->notNull(),
            "base_payment" => $this->integer()->notNull(),
            "win_bonus" => $this->integer()->defaultValue(0),
            "duration_seasons" => $this->integer()->defaultValue(1),
            "prestige_required" => $this->integer()->defaultValue(0),
        ]);

        $this->createTable("{{%team_sponsor}}", [
            "id" => $this->primaryKey(),
            "team_id" => $this->integer()->notNull(),
            "sponsor_id" => $this->integer()->notNull(),
            "signed_at" => $this->integer()->notNull(),
            "ends_at" => $this->integer()->notNull(),
            "status" => $this->string(20)->defaultValue("active"),
        ]);

        $this->addForeignKey("fk-team-sponsor-team", "{{%team_sponsor}}", "team_id", "{{%team}}", "id", "CASCADE");
        $this->addForeignKey("fk-team-sponsor-sp", "{{%team_sponsor}}", "sponsor_id", "{{%sponsor}}", "id", "CASCADE");

        // Seed some base sponsors
        $this->batchInsert("{{%sponsor}}", ["name", "base_payment", "win_bonus", "duration_seasons"], [
            ["Ticino Airways", 50000, 5000, 1],
            ["Luganello Caffè", 30000, 2000, 1],
            ["Crypto Valley Swiss", 100000, 15000, 1],
            ["Orologi Mendrisio", 75000, 8000, 1],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable("{{%team_sponsor}}");
        $this->dropTable("{{%sponsor}}");
    }
}
