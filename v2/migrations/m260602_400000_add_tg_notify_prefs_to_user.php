<?php

declare(strict_types=1);

use yii\db\Migration;

class m260602_400000_add_tg_notify_prefs_to_user extends Migration
{
    private const CATS = ['transfer', 'match', 'staff', 'injury', 'discipline', 'finance', 'friendly', 'system'];

    public function safeUp(): void
    {
        foreach (self::CATS as $cat) {
            $this->addColumn(
                '{{%user}}',
                "tg_notify_{$cat}",
                $this->tinyInteger(1)->notNull()->defaultValue(1)->after('telegram_enabled')
            );
        }
    }

    public function safeDown(): void
    {
        foreach (self::CATS as $cat) {
            $this->dropColumn('{{%user}}', "tg_notify_{$cat}");
        }
    }
}
