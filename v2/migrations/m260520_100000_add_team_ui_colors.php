<?php

declare(strict_types=1);

use yii\db\Migration;

class m260520_100000_add_team_ui_colors extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%team}}', 'color_left', $this->string(7)->null()->after('logo'));
        $this->addColumn('{{%team}}', 'color_right', $this->string(7)->null()->after('color_left'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%team}}', 'color_right');
        $this->dropColumn('{{%team}}', 'color_left');
    }
}

