<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%stadium}}`.
 */
class m260516_105906_create_stadium_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%stadium}}', [
            'id'              => $this->primaryKey(),
            'team_id'         => $this->integer()->notNull()->unique(),
            'name'            => $this->string()->notNull()->defaultValue('Stadio Municipale'),
            'capacity'        => $this->integer()->notNull()->defaultValue(5000),
            'level'           => $this->integer()->notNull()->defaultValue(1),
            'upgrade_cost'    => $this->bigInteger()->notNull()->defaultValue(500000),
            'ticket_price'    => $this->integer()->notNull()->defaultValue(10), // € per ticket
            'season_revenue'  => $this->bigInteger()->notNull()->defaultValue(0), // running total this season
            'created_at'      => $this->integer()->notNull(),
            'updated_at'      => $this->integer()->notNull(),
        ]);

        $this->addForeignKey('fk-stadium-team_id', '{{%stadium}}', 'team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-stadium-team_id', '{{%stadium}}');
        $this->dropTable('{{%stadium}}');
    }
}
