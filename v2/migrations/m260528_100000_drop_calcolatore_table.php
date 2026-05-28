<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Drop legacy calcolatore table.
 *
 * Formula 2 coefficients are now shipped in app code
 * (PitchZoneHelper::FORMULA_2_COEFFS), so runtime DB table is obsolete.
 */
class m260528_100000_drop_calcolatore_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%calcolatore}}', true) !== null) {
            $this->dropTable('{{%calcolatore}}');
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('{{%calcolatore}}', true) !== null) {
            return true;
        }

        $this->createTable('{{%calcolatore}}', [
            'formula' => $this->string(10)->notNull()->defaultValue(''),
            'qu' => $this->integer()->notNull()->defaultValue(0),
            'po' => $this->decimal(8, 4)->notNull()->defaultValue(0),
            'df' => $this->decimal(8, 4)->notNull()->defaultValue(0),
            'cn' => $this->decimal(8, 4)->notNull()->defaultValue(0),
            'pa' => $this->decimal(8, 4)->notNull()->defaultValue(0),
            'rg' => $this->decimal(8, 4)->notNull()->defaultValue(0),
            'cr' => $this->decimal(8, 4)->notNull()->defaultValue(0),
            'tc' => $this->decimal(8, 4)->notNull()->defaultValue(0),
            'tr' => $this->decimal(8, 4)->notNull()->defaultValue(0),
            'ord' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->createIndex('idx-calcolatore-qu', '{{%calcolatore}}', 'qu');

        // Note: safeDown restores schema only (not historical rows).
        return true;
    }
}

