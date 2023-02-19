<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Lang;

/**
 * Class Helper
 * @package App\Helpers
 */
class Helper
{
    /**
     * Получить команду из текста текущей локали
     *
     * @param $text
     */
    public static function checkCommand($text)
    {
        // Ищем по локалям
        foreach (Lang::get('*') as $key => $value) {
            // Нашли
            if ($value === $text) {
                return $key;
            }
        }

        return ''; //default
    }

    /**
     * Форматирование денег до 6 знаков после разделителя
     *
     * @param float $summa "сумма"
     * @param int $sign "кол-во знаков, min 2, max 6"
     * @param boolean $throw_values "Откинуть значения без округления"
     * @return float
     */
    public static function formatMoney($summa, $sign = 6, $throw_values = false)
    {
        if ($throw_values == true) {
            if (function_exists("bcdiv")) {
                return bcdiv($summa, 1, $sign);
            } else {
                $pos = strpos((string)$summa, ".");
                if ($pos === false) {
                    return round($summa, $sign);
                }

                return substr((string)$summa, 0, $pos + 1 + $sign);
            }
        }

        switch ($sign) {
            case 1:
            {
                return floor(($summa) * 10 + .5) * .1;
            }
            case 2:
            {
                return floor(($summa) * 100 + .5) * .01;
            }
            case 3:
            {
                return floor(($summa) * 1000 + .5) * .001;
            }
            case 4:
            {
                return floor(($summa) * 10000 + .5) * .0001;
            }
            case 5:
            {
                return floor(($summa) * 100000 + .5) * .00001;
            }
            case 6:
            {
                return floor(($summa) * 1000000 + .5) * .000001;
            }

            default:
                return floor(($summa) * 1000000 + .5) * .000001;
        }
    }

    /**
     * Форматируем скорость из Кбит в Мбит
     *
     * @param int $inputValue
     * @param bool $round
     * @return float
     */
    public static function formatSpeed($inputValue, $round = true)
    {
        if ($round) {
            $outputValue = round(intval($inputValue) / 1024, 2);
        } else {
            $outputValue = intval($inputValue) / 1024;
        }

        return $outputValue;
    }
}
