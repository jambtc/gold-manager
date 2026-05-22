<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%staff}}`.
 */
class m260516_105906_create_staff_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%staff}}', [
            'id'          => $this->primaryKey(),
            'team_id'     => $this->integer()->notNull(),
            'name'        => $this->string()->notNull(),
            'role'        => $this->string(30)->notNull(),
            // roles: head_coach | assistant_coach | goalkeeping_coach | fitness_coach | scout
            'ability'     => $this->integer()->notNull()->defaultValue(50),  // 1-100
            'experience'  => $this->integer()->notNull()->defaultValue(0),
            'motivation'  => $this->integer()->notNull()->defaultValue(50),
            'philosophy'  => $this->string(20)->null(),
            // philosophy: offensivo | difensivo | bilanciato | contropiede | possesso
            'salary'      => $this->bigInteger()->notNull()->defaultValue(0),
            'created_at'  => $this->integer()->notNull(),
            'updated_at'  => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-staff-team_id', '{{%staff}}', 'team_id');
        $this->addForeignKey('fk-staff-team_id', '{{%staff}}', 'team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-staff-team_id', '{{%staff}}');
        $this->dropTable('{{%staff}}');
    }
}
