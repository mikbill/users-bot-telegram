<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Lang;

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
}
