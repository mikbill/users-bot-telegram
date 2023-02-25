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
* Class RealIPCommand
*/
class RealIPCommand extends Command
{
    public static $btnInfo = "realip.btn.Info";
    public static $btnActivate = "realip.btn.Activate";
    public static $btnDeactivate = "realip.btn.Deactivate";
    
    private $msg_error_request = "realip.msg.request.error";
    private $msg_success_activate = "realip.msg.request.success_activate";
    private $msg_success_deactivate = "realip.msg.request.success_deactivate";
    
    private $msg_not_available = "realip.msg.service_not_available";
    private $msg_already_active = "realip.msg.service_already_active";

    private $msg_activation_cost = "realip.text.activation_cost";
    private $msg_deactivation_cost = "realip.text.deactivation_cost";
    private $msg_realip_cost = "realip.text.cost";
    private $msg_realip_period_day = "realip.text.period_day";
    private $msg_realip_period_month = "realip.text.period_month";
    
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

    public function btnRealIPInfo()
    {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->RealIpInfo();
        if( $this->validResponse($response) ) {
            //{"success":true,"code":0,"data":{
            //  "available":1,"active":1,"info":{
            //      "cost_activate":"100",
            //      "cost_deactivate":"50",
            //      "cost":"70",
            //      "cost_period":"month"
            //}},"message":"\u041e\u041a"}
            if( $response["data"]["available"] == 1 ) {
                $realip = $response["data"]["info"];
                
                $period = ($realip["cost_period"] == "month") ? trans($this->msg_realip_period_month) : trans($this->msg_realip_period_day);
                if( $response["data"]["active"] == 1 ) {
                    $text = trans($this->msg_already_active) . "\n";
                    $text .= trans($this->msg_realip_cost) . ": " . $realip["cost"] . " " . $period . "\n";
                    if( $realip["cost_deactivate"] > 0 ) {
                        $text .= trans($this->msg_deactivation_cost) . ": " . $realip["cost_deactivate"] . "\n";
                    }
                    
                    $keyboard = [
                        [["text" => trans(self::$btnDeactivate)]],
                        [["text" => trans("back")]],
                    ];
                } else {
                    $text = trans($this->msg_realip_cost) . ": " . $realip["cost"] . " " . $period . "\n";
                    if( $realip["cost_activate"] > 0 ) {
                        $text .= trans($this->msg_activation_cost) . ": " . $realip["cost_activate"] . "\n";
                    }

                    $keyboard = [
                        [["text" => trans(self::$btnActivate)]],
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
    
    public function btnRealIPActivate() {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->RealIpActivate();
        if( $this->validResponse($response) ) {
            $text = trans($this->msg_success_activate);

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

    public function btnRealIPDeactivate() {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->RealIpDeactivate();
        if( $this->validResponse($response) ) {
            $text = trans($this->msg_success_deactivate);

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