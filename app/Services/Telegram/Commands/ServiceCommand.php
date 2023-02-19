<?php

namespace App\Services\Telegram\Commands;

use App;
use App\Helpers\Helper;
use App\Models\TelegramUsers;
use App\Notifications\BotNotification;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;
use Illuminate\Support\Facades\Log;

/**
* Class ServiceCommand
*/
class ServiceCommand extends Command
{
    public static $btnMain = "service.btn.Main";
    public static $btnChange = "service.btn.Change";

    private $msg_error_request = "service.msg.request.error";
    private $msg_no_items_left = "service.msg.no_items";
    
    private $msg_enter_code = "service.msg.enter_code";
    private $msg_service_menu = "service.msg.menu";
    
    public function __construct(TeleBot $bot, Update $update)
    {
        parent::__construct($bot, $update);
    }

    /**
     * Обработчик команд
     */
    public function handle()
    {

    }
    
    public function btnServiceMenu() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_service_menu);
        $keyboard = [
            [["text" => trans(TarifCommand::$btnInfo)]],
            [["text" => trans("back")]],
        ];
        
        $this->buttonKeyboard($text, $keyboard);
    }
}