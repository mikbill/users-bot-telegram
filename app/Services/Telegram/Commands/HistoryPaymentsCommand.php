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
    private $msg_table_title = [
        "payhist.text.msg.date",
        "payhist.text.msg.type",
        "payhist.text.msg.summa",
        "payhist.text.msg.comment",
    ];
    
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

                $title = [trans($this->msg_table_title[0]), trans($this->msg_table_title[1]), trans($this->msg_table_title[2]), trans($this->msg_table_title[3])];
                $body = [];
                foreach($response["data"] as $item) {
                    $body[] = [trim($item["date"]), trans(trim($item["bugh_type"])), trim($item["summa"]), trans(trim($item["comment"]))];
                }

                $text = trans($this->msg_last10);
                $text .= Helper::generateMessage($title, $body);
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