<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;

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

    /**
     * @param array $title
     * @param array $body
     * @return string
     */
    public static function generateMessage($title, $body) {
        $text = "";
        foreach($body as $index => $item) {
            if( is_array($item) ) {
                $text .= self::generateMessage($title, $item);
            } else {
                $text .= "{$title[$index]}: {$item}";
            }

            $text .= "\n";
        }
        
        return $text;
    }
    
    /**
     * Format message table
     * @param array $title
     * @param array $body
     * @return string
     */
    public static function generateTable($title, $body) {
        $length = self::getLength($title, $body);
        
        $text = "<pre>\n";
        $text .= self::makeRow($title, $length);
        $text .= self::makeSeparator($title, $length, "-");
        foreach($body as $index => $item) {
            $text .= self::makeRow($item, $length);
        }
        $text .= "</pre>";

        return $text;
    }

    /**
     * @param $rowArray
     * @param $length
     * @param string $fill_symbol
     * @return string
     */
    private static function makeRow($rowArray, $length, $fill_symbol = " ") {
        $row = "|";
        
        foreach($rowArray as $key => $value) {
            $section_length = (int)$length[$key] - (int)mb_strlen($value);
            $row .= " " . $value . str_pad("", $section_length, $fill_symbol) . " |";
        }

        $row .= "\n";
        
        return $row;
    }

    /**
     * @param $rowArray
     * @param $length
     * @param string $fill_symbol
     * @return string
     */
    private static function makeSeparator($rowArray, $length, $fill_symbol = " ") {
        $row = "|";

        foreach($rowArray as $key => $value) {
            $row .= " " . str_pad("", (int)$length[$key], $fill_symbol) . " |";
        }

        $row .= "\n";

        return $row;
    }
    
    /**
     * 
     * @param array $title
     * @param array $body
     * @return array
     */
    private static function getLength($title, $body) {
        $length = [];

        foreach($title as $k => $v) {
            $str_length = (int)mb_strlen((string)$v);

            if( !isset($length[$k]) ) {
                $length[$k] = $str_length;
            }

            if( $length[$k] < $str_length ) {
                $length[$k] = $str_length;
            }
        }
        
        foreach($body as $key => $item) {
            foreach($item as $k => $v) {
                $str_length = (int)mb_strlen((string)$v);

                if( !isset($length[$k]) ) {
                    $length[$k] = $str_length;
                }

                if( $length[$k] < $str_length ) {
                    $length[$k] = $str_length;
                }
            }
        }

        return $length;
    } 
}
