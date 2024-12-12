<?php

namespace Soap\LaravelOmise\Omise\Helpers;

use Exception;

class OmiseMoney
{
    /**
     * @var array
     */
    private static $subunitMultiplier = [
        'AUD' => 100,
        'CAD' => 100,
        'CHF' => 100,
        'CNY' => 100,
        'DKK' => 100,
        'EUR' => 100,
        'GBP' => 100,
        'HKD' => 100,
        'JPY' => 1,
        'MYR' => 100,
        'SGD' => 100,
        'THB' => 100,
        'USD' => 100,
    ];

    /**
     * @param  int|float|string  $amount
     * @param  string  $currency
     * @return int|float Note that the expected output value's type of this method is to be `int` as Omise Charge API requires.
     *                   However, there is a case that this method will return a `float` regarding to
     *                   the improper currency setting, which considered as an invalid type of amount.
     *
     *                    And we would like to let the API raises an error out loud instead of silently remove
     *                    or casting a `float` value to `int` subunit.
     *                    This is to prevent any miscalculation for those fractional subunits
     *                    between the amount that is charged, and the actual amount from the store.
     */
    public static function toSubunit($amount, $currency)
    {
        $amount = self::purifyAmount($amount);
        $currency = strtoupper($currency);

        if (! isset(self::$subunitMultiplier[$currency])) {
            throw new Exception(trans('treasurer::translations.unsupport_currency'));
        }

        return $amount * self::$subunitMultiplier[$currency];
    }

    /**
     * convert omise amount to human readable amount.
     */
    public static function toCurrencyUnit($amount, $currency)
    {
        $amount = self::purifyAmount($amount);
        $currency = strtoupper($currency);

        if (! isset(self::$subunitMultiplier[$currency])) {
            throw new Exception(trans('treasurer::translations.unsupport_currency'));
        }

        return $amount / self::$subunitMultiplier[$currency];
    }

    /**
     * @param  int|float|mixed  $amount
     * @return float
     */
    private static function purifyAmount($amount)
    {
        if (! is_numeric($amount)) {
            throw new Exception(trans('treasurer::translations.inavlid_type_of_amount'));
        }

        return (float) $amount;
    }
}
