<?php

namespace App\Enum;

use Symfony\Component\Intl\Languages;

enum Language: string
{
    case AR = 'ar';
    case DE = 'de';
    case EN = 'en';
    case ES = 'es';
    case FR = 'fr';
    case HI = 'hi';
    case IT = 'it';
    case JA = 'ja';
    case KO = 'ko';
    case NL = 'nl';
    case PL = 'pl';
    case PT = 'pt';
    case RU = 'ru';
    case SV = 'sv';
    case TR = 'tr';
    case UK = 'uk';
    case ZH = 'zh';

    /**
     * Get a localized display name for this language
     */
    public function label(?string $locale = 'en'): string
    {
        return Languages::getName($this->value, $locale);
    }

    /**
     * @return non-empty-string[]
     */
    public static function getCodesList(): array
    {
        return array_map(fn(self $lang) => $lang->value, self::cases());
    }
}
