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
* Class CreditCommand
*/
class CreditCommand extends Command
{
    public static $btnCreditInfo = "credit.btn.Info";
    public static $btnCreditActivate = "credit.btn.Activate";

    private $msg_error_request = "credit.msg.request.error";
    private $msg_success = "credit.msg.request.success";
    
    private $msg_not_available = "credit.msg.not_available";
    private $msg_already_active = "credit.msg.already_active";
    
    private $msg_will_be_avilable_days = "credit.text.will_be_avilable_days";
    private $msg_days_lable = "credit.text.days_lable";
    
    private $msg_activate_cost = "credit.text.activate_cost";
    private $msg_credit_percent = "credit.text.percent";
    private $msg_credit_stop = "credit.text.stop_date";
    
    private $msg_credit_type1 = "credit.text.type1";
    private $msg_credit_type2 = "credit.text.type2";
    private $msg_credit_type3 = "credit.text.type3";
    
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

    public function btnCreditInfo()
    {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->CreditInfo();
        if( $this->validResponse($response) ) {
            //"data":{
            //    "available":1,"active":0,"info":{
            //        "type":1,"credit_procent":0,"active_cena":"0.00","do_credit_swing_date_days":"7","date_start":"2023-02-01","date_stop":"2023-02-28","will_be_avilable_days":0
            //    }
            //}
            if( $response["data"]["available"] == 1 ) {
                $credit = $response["data"]["info"];

                if( $response["data"]["active"]  == 1 ) {
                    $text = trans($this->msg_already_active) . "\n";
                    if ( $credit["type"] == 3 ) {
                        $text .= trans($this->msg_credit_stop) . ": " . date("Y-m-d", strtotime("+{$credit["do_credit_swing_date_days"]} DAYS")) . "\n";
                    } else {
                        $text .= trans($this->msg_credit_stop) . ": " . $credit["date_stop"] . "\n";
                    }
                    
                    $keyboard = [
                        [["text" => trans("back")]],
                    ];
                } elseif( $credit["type"] == 1 ) {
                    // simple credit
                    $text = trans($this->msg_credit_type1) . "\n";
                    $text .= trans($this->msg_activate_cost) . ": " . $credit["active_cena"] . "\n";
                    $text .= trans($this->msg_credit_stop) . ": " . $credit["date_stop"] . "\n";

                    $keyboard = [
                        [["text" => trans(self::$btnCreditActivate)]],
                        [["text" => trans("back")]],
                    ];
                } elseif ( $credit["type"] == 2 ) {
                    // % credit
                    $text = trans($this->msg_credit_type2) . "\n";
                    $text .= trans($this->msg_activate_cost) . ": " . $credit["active_cena"] . "\n";
                    $text .= trans($this->msg_credit_percent) . ": " . $credit["credit_procent"] . "% \n";
                    $text .= trans($this->msg_credit_stop) . ": " . $credit["date_stop"] . "\n";

                    $keyboard = [
                        [["text" => trans(self::$btnCreditActivate)]],
                        [["text" => trans("back")]],
                    ];
                } elseif ( $credit["type"] == 3 ) {
                    // swing credit
                    $text = trans($this->msg_credit_type3) . "\n";
                    $text .= trans($this->msg_activate_cost) . ": " . $credit["active_cena"] . "\n";
                    $text .= trans($this->msg_credit_stop) . ": " . date("Y-m-d", strtotime("+{$credit["do_credit_swing_date_days"]} DAYS")) . "\n";
                    
                    $keyboard = [
                        [["text" => trans(self::$btnCreditActivate)]],
                        [["text" => trans("back")]],
                    ];
                } elseif ( $credit["will_be_avilable_days"] > 0 ) {
                    $text = trans($this->msg_will_be_avilable_days) . " " . $credit["will_be_avilable_days"] . " " . trans($this->msg_days_lable);

                    $keyboard = [
                        [["text" => trans("back")]],
                    ];
                } else {
                    // -1 = not available
                    $text = trans($this->msg_not_available);

                    $keyboard = [
                        [["text" => trans("back")]],
                    ];
                }
            } else {
                $text = trans($this->msg_not_available);
                
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
    
    public function btnCreditActivate() {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->CreditActivate();
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