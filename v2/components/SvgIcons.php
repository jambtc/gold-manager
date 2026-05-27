<?php

declare(strict_types=1);

namespace app\components;

/**
 * Backward-compatible facade.
 * SIP-0033 canonical icon entrypoint is UiIconHelper.
 */
class SvgIcons
{
    public static function position(string $pos, int $size = 20): string
    {
        return UiIconHelper::renderPositionIcon($pos, $size);
    }

    public static function skill(string $code, int $size = 18): string
    {
        return UiIconHelper::renderTalentTypeIcon($code, $size);
    }

    public static function foot(string $foot, int $size = 16): string
    {
        return UiIconHelper::renderFootIcon($foot, $size);
    }
}

