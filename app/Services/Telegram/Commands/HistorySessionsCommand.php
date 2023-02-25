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
    private $msg_inmb = "seshist.text.inmb";
    private $msg_outmb = "seshist.text.outmb";
    private $msg_table_title = [
        "seshist.text.msg.start",
        "seshist.text.msg.time",
        "seshist.text.msg.in",
        "seshist.text.msg.out",
    ];
    
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
                /**
                 * "acctsessionid":"482572252",
                 * "before_billing":0,
                 * "billing_minus":0,
                 * "start_time":"2023-02-12 15:21:16",
                 * "stop_time":"2023-02-12 15:26:02",
                 * "last_change":"1644672601",
                 * "time_on":"0:04:46",
                 * "call_from":"00:23:55:33:44:55",
                 * "calledstationid":"eth21.1132:103",
                 * "acctterminatecause":"User-Request",
                 * "framedipaddress":"192.168.1.15",
                 * "nasipaddress":"192.168.1.1
                 * "in_bytes":0,
                 * "out_bytes":0,
                 * "IdRowPayView":2,
                 * "ipaddress":"192.168.1.15"
                 */
                $title = [trans($this->msg_table_title[0]), trans($this->msg_table_title[1]), trans($this->msg_table_title[2]), trans($this->msg_table_title[3])];
                $body = [];
                foreach($response["data"] as $item) {
                    $body[] = [trim($item["start_time"]), trim($item["time_on"]), trim($item["in_bytes"]) . " " . trans($this->msg_inmb), trim($item["out_bytes"]) . " " . trans($this->msg_outmb)];
                }

                $text = trans($this->msg_last10);
                $text .= Helper::generateMessage($title, $body);
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