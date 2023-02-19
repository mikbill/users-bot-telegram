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
* Class HistoryPaymentsCommand
*/
class HistoryPaymentsCommand extends Command
{
    public static $btnMain = "payhist.btn.Main";

    private $msg_error_request = "payhist.msg.request.error";
    private $msg_no_items_left = "payhist.msg.no_items";
    private $msg_last10 = "payhist.text.last10";
    private $msg_summa = "payhist.text.summa";
    private $msg_comment  = "payhist.text.comment";
    
    private $cache_key_start_date = "payhist_start";
    private $cache_key_stop_date = "payhist_stop";

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
        
        $response = $this->ClientAPI->HistoryPayments($this->_last_start_date, $this->_last_end_date);
        if( $this->validResponse($response) ) {
            if(!empty($response["data"])) {
                $text = trans($this->msg_last10);
                foreach($response["data"] as $item) {
                    $text .= "[{$item["date"]}] " . trans($item["bugh_type"]) . "; " . trans($this->msg_summa) . " " . $item["summa"] . "\n";
                    if( !empty($item["comment"]) ) {
                        $text .= "; " . trans($this->msg_comment) . " " . $item["comment"];
                    }
                    $text .= "\n\n";
                }
                
                $keyboard = [
                    [["text" => trans("back")]],
                ];
            } else {
                $text = trans($this->msg_no_items_left);

                $keyboard = [
                    ["text" => trans("back")],
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