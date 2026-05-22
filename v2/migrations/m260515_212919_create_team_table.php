<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%team}}`.
 */
class m260515_212919_create_team_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%team}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
            'user_id' => $this->integer()->null(),
            'is_cpu' => $this->boolean()->defaultValue(false),
            'budget' => $this->bigInteger()->notNull()->defaultValue(0),
            'logo' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longblob')->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            '{{%idx-team-user_id}}',
            '{{%team}}',
            'user_id'
        );

        $this->addForeignKey(
            '{{%fk-team-user_id}}',
            '{{%team}}',
            'user_id',
            '{{%user}}',
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
        $this->dropForeignKey('{{%fk-team-user_id}}', '{{%team}}');
        $this->dropIndex('{{%idx-team-user_id}}', '{{%team}}');
        $this->dropTable('{{%team}}');
    }
}
