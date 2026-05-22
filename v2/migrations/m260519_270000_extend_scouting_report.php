<?php

declare(strict_types=1);

use yii\db\Migration;

class m260519_270000_extend_scouting_report extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%scouting_report}}', 'status',    $this->string(10)->notNull()->defaultValue('pending')->after('team_id'));
        $this->addColumn('{{%scouting_report}}', 'ready_at',  $this->integer()->null()->after('status'));
        $this->addColumn('{{%scouting_report}}', 'scout_eff', $this->tinyInteger()->notNull()->defaultValue(50)->after('ready_at'));

        $this->createIndex('idx-scouting_report-status', '{{%scouting_report}}', ['status', 'ready_at']);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx-scouting_report-status', '{{%scouting_report}}');
        $this->dropColumn('{{%scouting_report}}', 'status');
        $this->dropColumn('{{%scouting_report}}', 'ready_at');
        $this->dropColumn('{{%scouting_report}}', 'scout_eff');
    }
}
