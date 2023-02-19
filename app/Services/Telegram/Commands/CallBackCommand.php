<?php

namespace App\Services\Telegram\Commands;

use App\Helpers\Helper;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;
use Illuminate\Support\Facades\Log;

/**
 * Class CallBackCommand
 * @package App\Services\Telegram\Commands
 */
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

    }
}
