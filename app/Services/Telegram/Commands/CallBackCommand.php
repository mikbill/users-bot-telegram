<?php


namespace App\Services\Telegram\Commands;


use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

class CallBackCommand extends Command
{

    /**
     * Обрабатываем callback_query
     *
     * @param Update $update
     * @param TeleBot $bot
     * @return bool
     */
    public static function trigger(Update $update, TeleBot $bot)
    {
        return isset($update->callback_query);
    }

    /*
     * Обработчки срабатывает на калбеки
     */
    public function handle()
    {
//        $this->answerCallbackQuery([
//            'callback_query_id' => $this->update->callback_query->id,
//            'text'              => 'Загружаем...',
//            "alert"             => false
//        ]);


//        $params = explode("_", $this->update->callback_query->data);
//
//        if (isset($params[0]) and method_exists(self::class, $params[0])) {
//            $method = $params[0];
//            $this->$method($params); // вызываем метод
//        } else {
//
//            $this->sendMessage([
//                'text'       => '⚠️ Мы еще работаем над этим меню... ',
//                'parse_mode' => 'HTML'
//            ]);
//        }
    }

}
