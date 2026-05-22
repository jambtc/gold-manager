<?php

use yii\db\Migration;

class m260519_270000_create_friendly_challenge_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%friendly_challenge}}', [
            'id' => $this->primaryKey(),
            'challenger_id' => $this->integer()->notNull(),
            'challenged_id' => $this->integer()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'proposed_at' => $this->integer()->notNull(),
            'decline_reason' => $this->string(120)->null(),
            'fixture_id' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'responded_at' => $this->integer()->null(),
        ]);

        $this->createIndex('idx-friendly-challenge-status', '{{%friendly_challenge}}', ['status']);
        $this->createIndex('idx-friendly-challenge-proposed', '{{%friendly_challenge}}', ['proposed_at']);
        $this->createIndex('idx-friendly-challenge-challenger', '{{%friendly_challenge}}', ['challenger_id']);
        $this->createIndex('idx-friendly-challenge-challenged', '{{%friendly_challenge}}', ['challenged_id']);
        $this->createIndex('idx-friendly-challenge-fixture', '{{%friendly_challenge}}', ['fixture_id']);

        $this->addForeignKey(
            'fk-friendly-challenge-challenger',
            '{{%friendly_challenge}}',
            'challenger_id',
            '{{%team}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-friendly-challenge-challenged',
            '{{%friendly_challenge}}',
            'challenged_id',
            '{{%team}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-friendly-challenge-fixture',
            '{{%friendly_challenge}}',
            'fixture_id',
            '{{%fixture}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-friendly-challenge-fixture', '{{%friendly_challenge}}');
        $this->dropForeignKey('fk-friendly-challenge-challenged', '{{%friendly_challenge}}');
        $this->dropForeignKey('fk-friendly-challenge-challenger', '{{%friendly_challenge}}');
        $this->dropTable('{{%friendly_challenge}}');
    }
}

