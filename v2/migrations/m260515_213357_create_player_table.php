<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%player}}`.
 */
class m260515_213357_create_player_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%player}}', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->null(),
            'number' => $this->integer()->defaultValue(0),
            'name' => $this->string()->notNull(),
            'age' => $this->integer()->notNull()->defaultValue(18),
            'position' => $this->string(5)->notNull(),
            'foot' => $this->string(2)->notNull()->defaultValue('R'),
            'skill_po' => $this->integer()->notNull()->defaultValue(10),
            'skill_df' => $this->integer()->notNull()->defaultValue(10),
            'skill_cn' => $this->integer()->notNull()->defaultValue(10),
            'skill_pa' => $this->integer()->notNull()->defaultValue(10),
            'skill_rg' => $this->integer()->notNull()->defaultValue(10),
            'skill_cr' => $this->integer()->notNull()->defaultValue(10),
            'skill_tc' => $this->integer()->notNull()->defaultValue(10),
            'skill_tr' => $this->integer()->notNull()->defaultValue(10),
            'experience' => $this->integer()->notNull()->defaultValue(0),
            'general_skill' => $this->integer()->notNull()->defaultValue(10),
            'form' => $this->integer()->notNull()->defaultValue(50),
            'freshness' => $this->integer()->notNull()->defaultValue(100),
            'condition' => $this->integer()->notNull()->defaultValue(100),
            'character' => $this->string()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            '{{%idx-player-team_id}}',
            '{{%player}}',
            'team_id'
        );

        $this->addForeignKey(
            '{{%fk-player-team_id}}',
            '{{%player}}',
            'team_id',
            '{{%team}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%fk-player-team_id}}', '{{%player}}');
        $this->dropIndex('{{%idx-player-team_id}}', '{{%player}}');
        $this->dropTable('{{%player}}');
    }
}
