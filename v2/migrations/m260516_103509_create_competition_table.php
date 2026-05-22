<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%competition}}`.
 */
class m260516_103509_create_competition_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%competition}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'season' => $this->integer()->notNull()->defaultValue(1),
            'type' => $this->string(50)->notNull()->defaultValue('league'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%competition}}');
    }
}
