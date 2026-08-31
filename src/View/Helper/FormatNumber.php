<?php declare(strict_types=1);

namespace Common\View\Helper;

use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\I18n\View\Helper\NumberFormat as LaminasNumberFormat;

/**
 * Format a number according to the locale of the site, user or installation.
 *
 * It extends the helper "numberFormat" of Laminas, that is registered by
 * default, but it is registered under another name, because its behavior is
 * different, so the existing callers of "numberFormat" are not impacted.
 *
 * It fixes its two limitations for Omeka:
 *
 *   - the locale is the one of the translator (site, user, then installation),
 *     and not the default locale of php: without the extension intl, Omeka does
 *     not call Locale::setDefault(), so the default locale is not the one of
 *     the site;
 *   - the extension intl is not required: without it, the class NumberFormatter
 *     does not exist and the helper of Laminas is a fatal error, so the number
 *     is formatted with number_format() and the separators of the locale.
 *
 * Furthermore, a value that is not a number is returned as a string instead of
 * throwing a TypeError, because a template should not break on a bad value.
 *
 * The fallback without intl is an approximation: only the decimal separator and
 * the grouping separator are managed. So it differs from icu for the locales
 * using their own digits (bengali, persian, burmese, nepali), that are output
 * with latin digits, and for the locales grouping by two digits (indian lakh:
 * 12,34,567 for hi, ta, te), that are grouped by three. The eighty other
 * locales of the list are identical to icu.
 *
 * @see \Laminas\I18n\View\Helper\NumberFormat
 */
class FormatNumber extends LaminasNumberFormat
{
    /**
     * Decimal and grouping separators by locale, extracted from icu (cldr).
     *
     * The format depends on the language and on the region: "de_DE" is
     * "1.234.567,89" but "de_CH" is "1’234’567.89". A region is listed only
     * when it differs from its language, so "br_FR" is absent and managed by
     * the fallback on the language "br". The fallback is never done on the
     * region: "ca_FR" follows catalan, not french.
     */
    const SEPARATORS = [
        'af' => [',', "\u{00A0}"],
        'am' => ['.', ','],
        'ar' => ['.', ','],
        'az' => [',', '.'],
        'be' => [',', "\u{00A0}"],
        'bg' => [',', "\u{00A0}"],
        'bn' => ['.', ','],
        'br' => [',', "\u{00A0}"],
        'bs' => [',', '.'],
        'ca' => [',', '.'],
        'co' => ['.', ''],
        'cs' => [',', "\u{00A0}"],
        'cy' => ['.', ','],
        'da' => [',', '.'],
        'de' => [',', '.'],
        'de_AT' => [',', "\u{00A0}"],
        'de_CH' => ['.', "\u{2019}"],
        'de_LI' => ['.', "\u{2019}"],
        'el' => [',', '.'],
        'en' => ['.', ','],
        'en_ZA' => [',', "\u{00A0}"],
        'eo' => [',', "\u{00A0}"],
        'es' => [',', '.'],
        'es_419' => ['.', ','],
        'es_MX' => ['.', ','],
        'es_US' => ['.', ','],
        'et' => [',', "\u{00A0}"],
        'eu' => [',', '.'],
        'fa' => ["\u{066B}", "\u{066C}"],
        'fi' => [',', "\u{00A0}"],
        'fr' => [',', "\u{202F}"],
        'fr_CA' => [',', "\u{00A0}"],
        'fr_LU' => [',', '.'],
        'ga' => ['.', ','],
        'gd' => ['.', ','],
        'gl' => [',', '.'],
        'ha' => ['.', ','],
        'he' => ['.', ','],
        'hi' => ['.', ','],
        'hr' => [',', '.'],
        'hu' => [',', "\u{00A0}"],
        'hy' => [',', "\u{00A0}"],
        'id' => [',', '.'],
        'ig' => ['.', ','],
        'is' => [',', '.'],
        'it' => [',', '.'],
        'it_CH' => ['.', "\u{2019}"],
        'ja' => ['.', ','],
        'ka' => [',', "\u{00A0}"],
        'kk' => [',', "\u{00A0}"],
        'km' => ['.', ','],
        'ko' => ['.', ','],
        'lo' => [',', '.'],
        'lt' => [',', "\u{00A0}"],
        'lv' => [',', "\u{00A0}"],
        'mk' => [',', '.'],
        'mn' => ['.', ','],
        'ms' => ['.', ','],
        'my' => ['.', ','],
        'nb' => [',', "\u{00A0}"],
        'ne' => ['.', ','],
        'nl' => [',', '.'],
        'nn' => [',', "\u{00A0}"],
        'oc' => [',', "\u{00A0}"],
        'pl' => [',', "\u{00A0}"],
        'pt' => [',', '.'],
        'pt_PT' => [',', "\u{00A0}"],
        'ro' => [',', '.'],
        'ru' => [',', "\u{00A0}"],
        'si' => ['.', ','],
        'sk' => [',', "\u{00A0}"],
        'sl' => [',', '.'],
        'sq' => [',', "\u{00A0}"],
        'sr' => [',', '.'],
        'sv' => [',', "\u{00A0}"],
        'sw' => ['.', ','],
        'ta' => ['.', ','],
        'te' => ['.', ','],
        'th' => ['.', ','],
        'tr' => [',', '.'],
        'uk' => [',', "\u{00A0}"],
        'ur' => ['.', ','],
        'uz' => [',', "\u{00A0}"],
        'vi' => [',', '.'],
        'yo' => ['.', ','],
        'zh' => ['.', ','],
        'zu' => ['.', ','],
    ];

    /**
     * Locale used when the locale of the site is unknown or not listed.
     */
    const DEFAULT_LOCALE = 'en';

    /**
     * @var \Laminas\I18n\Translator\TranslatorInterface
     */
    protected $translator;

    public function __construct(?TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    /**
     * Format a number with the separators of a locale.
     *
     * @param mixed $number Any numeric value, as int, float or string. Any
     * other value is returned as a string.
     * @param int|null $formatStyle A constant NumberFormatter::*, with intl.
     * @param int|null $formatType A constant NumberFormatter::TYPE_*, with intl.
     * @param string|null $locale Default is the locale of the site.
     * @param int|null $decimals Default is up to three decimals.
     * @param array|null $textAttributes Text attributes, with intl.
     */
    public function __invoke(
        $number,
        $formatStyle = null,
        $formatType = null,
        $locale = null,
        $decimals = null,
        ?array $textAttributes = null
    ) {
        // A stringable object holding a number is formatted like a string.
        if (is_object($number) && method_exists($number, '__toString')) {
            $number = (string) $number;
        }

        // Unlike the helper of Laminas, that throws a TypeError, a value that
        // is not a number is returned as a string: a template should not break.
        // A value that is not scalar (array, resource, object) has no string
        // representation, so it is skipped.
        if (!is_numeric($number)) {
            return is_scalar($number) ? (string) $number : '';
        }

        if (extension_loaded('intl')) {
            return parent::__invoke($number, $formatStyle, $formatType, $locale, $decimals, $textAttributes);
        }

        return $this->formatWithoutIntl($number, $locale ?? $this->getLocale(), $decimals);
    }

    /**
     * The locale of the translator is the one of the site, the user, then the
     * installation, unlike Locale::getDefault() used by the helper of Laminas,
     * that is set by Omeka only when the extension intl is loaded.
     *
     * {@inheritDoc}
     * @see \Laminas\I18n\View\Helper\NumberFormat::getLocale()
     */
    public function getLocale()
    {
        if ($this->locale === null && $this->translator) {
            $this->locale = $this->translator->getLocale();
        }
        if ($this->locale === null && extension_loaded('intl')) {
            $this->locale = \Locale::getDefault();
        }
        return $this->locale ?: self::DEFAULT_LOCALE;
    }

    /**
     * Format a number without the extension intl.
     */
    protected function formatWithoutIntl($number, ?string $locale, ?int $decimals): string
    {
        [$decimalSeparator, $groupingSeparator] = $this->separators($locale);
        $number = (float) $number;
        if ($decimals === null || $decimals < 0) {
            // Mimic the default of NumberFormatter: up to three decimals, with
            // no trailing zero.
            $rounded = (string) round($number, 3);
            $pos = strpos($rounded, '.');
            $decimals = $pos === false ? 0 : min(3, strlen($rounded) - $pos - 1);
        }
        return number_format($number, $decimals, $decimalSeparator, $groupingSeparator);
    }

    /**
     * Get the separators of a locale: full locale, then language, then default.
     */
    protected function separators(?string $locale): array
    {
        $locale = str_replace('-', '_', (string) $locale);
        if (isset(self::SEPARATORS[$locale])) {
            return self::SEPARATORS[$locale];
        }
        $language = strtok($locale, '_');
        return self::SEPARATORS[$language]
            ?? self::SEPARATORS[self::DEFAULT_LOCALE];
    }
}
