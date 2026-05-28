<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Normalizza market_bid.market_type da legacy "player_pool" a "transfer_market".
 */
class m260528_184500_market_bid_transfer_market_type extends Migration
{
    public function safeUp()
    {
        $this->update(
            '{{%market_bid}}',
            ['market_type' => 'transfer_market'],
            ['market_type' => 'player_pool']
        );
    }

    public function safeDown()
    {
        $this->update(
            '{{%market_bid}}',
            ['market_type' => 'player_pool'],
            ['market_type' => 'transfer_market']
        );
    }
}

