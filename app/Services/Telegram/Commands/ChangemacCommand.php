<?php

namespace App\Services\Telegram\Commands;

use App;
use App\Helpers\Helper;
use App\Models\TelegramUsers;
use App\Notifications\BotNotification;
use phpDocumentor\Reflection\Types\Array_;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;
use Illuminate\Support\Facades\Log;

/**
* Class ChangemacCommand
*/
class ChangemacCommand extends Command
{
    public static $btnChangeMACInfo = "changemac.btn.Info";
    public static $btnChangeMACActivate = "changemac.btn.Activate";
    
    private $msg_error_request = "changemac.msg.request.error";
    private $msg_success = "changemac.msg.request.success";
    private $msg_text = "changemac.msg.enter_new_mac";
    
    private $msg_not_available = "changemac.msg.service_not_available";
    
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

    public function btnChangeMACInfo()
    {
        $this->setLastAction(__FUNCTION__);
        
        $text = trans($this->msg_text);
        $keyboard = [
            [["text" => trans("back")]],
        ];
        
        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     * @param string $value
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function btnChangeMACActivate($value) {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->UserLocalMac($value);
        if( $this->validResponse($response) ) {
            $text = trans($this->msg_success);

            $keyboard = [
                [["text" => trans("back")]],
            ];
        } else {
            $text = trans($this->msg_error_request);
            $keyboard = [
                [["text" => trans("back")]],
            ];
        }
        
        $this->buttonKeyboard($text, $keyboard);
    }
}