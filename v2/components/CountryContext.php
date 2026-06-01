<?php

declare(strict_types=1);

namespace app\components;

use Yii;

final class CountryContext
{
    public const DEFAULT_COUNTRY = 'IT';

    /**
     * @return array<string,array{label:string,language:string}>
     */
    public static function catalog(): array
    {
        return [
            'IT' => ['label' => Yii::t('app', 'Italy'), 'language' => 'it-IT'],
            'GB' => ['label' => Yii::t('app', 'United Kingdom'), 'language' => 'en-US'],
            'FR' => ['label' => Yii::t('app', 'France'), 'language' => 'en-US'],
            'DE' => ['label' => Yii::t('app', 'Germany'), 'language' => 'en-US'],
            'ES' => ['label' => Yii::t('app', 'Spain'), 'language' => 'en-US'],
        ];
    }

    public static function normalize(?string $countryCode): string
    {
        $countryCode = strtoupper(trim((string) $countryCode));
        $catalog = self::catalog();

        if (isset($catalog[$countryCode])) {
            return $countryCode;
        }

        return self::DEFAULT_COUNTRY;
    }

    public static function languageForCountry(?string $countryCode): string
    {
        $normalized = self::normalize($countryCode);
        $catalog = self::catalog();
        return $catalog[$normalized]['language'] ?? 'it-IT';
    }

    /**
     * @return array<string,string>
     */
    public static function dropdownOptions(): array
    {
        $result = [];
        foreach (self::catalog() as $code => $meta) {
            $result[$code] = sprintf('%s (%s)', $meta['label'], $code);
        }
        return $result;
    }
}
