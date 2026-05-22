<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%calculator_config}}`.
 */
class m260515_213622_create_calculator_config_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
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
        
        // Import data from old SQL dump
        $sqlPath = Yii::getAlias('@app/../mysql/m29621d1.sql');
        if (file_exists($sqlPath)) {
            $sqlContent = file_get_contents($sqlPath);
            // Extract the INSERT statement for calcolatore
            if (preg_match('/INSERT INTO `calcolatore` \([^)]+\) VALUES.*?(?=;\n--)/s', $sqlContent, $matches)) {
                $this->execute($matches[0] . ';');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%calcolatore}}');
    }
}
