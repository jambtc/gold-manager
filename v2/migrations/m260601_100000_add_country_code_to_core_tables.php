<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * SIP-0076 (phase 1): add country context for onboarding and leagues.
 */
class m260601_100000_add_country_code_to_core_tables extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%user}}', true)?->getColumn('country_code') === null) {
            $this->addColumn('{{%user}}', 'country_code', $this->char(2)->notNull()->defaultValue('IT')->after('language'));
            $this->createIndex('idx_user_country_code', '{{%user}}', 'country_code');
        }

        if ($this->db->getTableSchema('{{%team}}', true)?->getColumn('country_code') === null) {
            $this->addColumn('{{%team}}', 'country_code', $this->char(2)->notNull()->defaultValue('IT')->after('logo'));
            $this->createIndex('idx_team_country_code', '{{%team}}', 'country_code');
        }

        if ($this->db->getTableSchema('{{%competition}}', true)?->getColumn('country_code') === null) {
            $this->addColumn('{{%competition}}', 'country_code', $this->char(2)->notNull()->defaultValue('IT')->after('type'));
            $this->createIndex('idx_competition_country_tier_group', '{{%competition}}', ['country_code', 'tier', 'group_number']);
            $this->createIndex(
                'uq_competition_country_tier_group_season_type',
                '{{%competition}}',
                ['country_code', 'tier', 'group_number', 'season', 'type'],
                true
            );
        }
    }

    public function safeDown(): void
    {
        if ($this->db->getTableSchema('{{%competition}}', true)?->getColumn('country_code') !== null) {
            $this->dropIndex('uq_competition_country_tier_group_season_type', '{{%competition}}');
            $this->dropIndex('idx_competition_country_tier_group', '{{%competition}}');
            $this->dropColumn('{{%competition}}', 'country_code');
        }

        if ($this->db->getTableSchema('{{%team}}', true)?->getColumn('country_code') !== null) {
            $this->dropIndex('idx_team_country_code', '{{%team}}');
            $this->dropColumn('{{%team}}', 'country_code');
        }

        if ($this->db->getTableSchema('{{%user}}', true)?->getColumn('country_code') !== null) {
            $this->dropIndex('idx_user_country_code', '{{%user}}');
            $this->dropColumn('{{%user}}', 'country_code');
        }
    }
}

