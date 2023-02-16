<?php


namespace App\Services\Telegram\Commands;


class StartCommand extends Command
{
    protected static $aliases = ['/start', '/s'];
    protected static $description = 'Send "/start" or "/s" to get "Hello, World!"';

    /**
     * Обработчик подключения к боту или команды /start
     */
    public function handle()
    {
        $chat_id = $this->update->message->from->id;

        if (isset($this->update->message->chat->last_name, $this->update->message->chat->first_name)) {
            $text = "<b>" . trans("hello") . ",  " . $this->update->message->chat->last_name . " " . $this->update->message->chat->first_name . " ! </b> 👋 \n\n";
        } else {
            $text = "<b>" . trans("hello") . "! </b> 👋 \n\n";
        }

        $text .= trans("desc");

        $this->sendMessage([
            'text'       => $text,
            'parse_mode' => 'HTML'
        ]);
    }
}
