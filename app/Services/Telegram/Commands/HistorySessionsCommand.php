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
* Class HistorySessionsCommand
*/
class HistorySessionsCommand extends Command
{
    public static $btnMain = "seshist.btn.Main";

    private $msg_error_request = "seshist.msg.request.error";
    private $msg_no_items_left = "seshist.msg.no_items";
    private $msg_last10 = "seshist.text.last10";
    
    private $cache_key_start_date = "seshist_start";
    private $cache_key_stop_date = "seshist_stop";
    
    private $_last_start_date = "";
    private $_last_end_date = "";

    public function __construct(TeleBot $bot, Update $update)
    {
        parent::__construct($bot, $update);

        $this->_last_start_date = $this->getValue($this->cache_key_start_date, date("Y-m") . "-01");
        $this->_last_end_date = $this->getValue($this->cache_key_stop_date, date("Y-m-t"));
    }

    /**
     * Обработчик команд
     */
    public function handle()
    {

    }
    
    public function mainMenu() {
        $this->setLastAction(__FUNCTION__);
        
        $response = $this->ClientAPI->HistorySessions($this->_last_start_date, $this->_last_end_date);
        if( $this->validResponse($response) ) {
            if(!empty($response["data"])) {
                $text = trans($this->msg_last10);
                /**
                 * TODO: session list
                 */
                
                $keyboard = [
                    [["text" => trans("back")]],
                ];
            } else {
                $text = trans($this->msg_no_items_left);

                $keyboard = [
                    [["text" => trans("back")]],
                ];
            }
        } else {
            $text = trans($this->msg_error_request);
            $keyboard = [
                [["text" => trans("back")]],
            ];
        }

        $this->buttonKeyboard($text, $keyboard);
    }
}