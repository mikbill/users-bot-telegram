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
* Class TurboCommand
*/
class TurboCommand extends Command
{
    public static $btnTurboInfo = "turbo.btn.Info";
    public static $btnTurboActivate = "turbo.btn.Activate";
    
    private $msg_error_request = "turbo.msg.request.error";
    private $msg_success = "turbo.msg.request.success";
    
    private $msg_not_available = "turbo.msg.service_not_available";
    private $msg_already_active = "turbo.msg.service_already_active";
    
    private $msg_price = "turbo.text.cost";
    private $msg_speed_in = "turbo.text.speed_in";
    private $msg_speed_out = "turbo.text.speed_out";
    private $msg_speed_label = "turbo.text.speed_label";
    private $msg_duration = "turbo.text.duration";
    private $msg_duration_label = "turbo.text.duration_label";
    
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

    public function btnTurboInfo()
    {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->TurboInfo();
        if( $this->validResponse($response) ) {
            //"data":{"available":1,"active":0,"info":{"cost_activate":"15.00","speed_in":"20480","speed_out":"10240","time":"24"}}
            if( $response["data"]["available"] == 1 ) {
                $turbo = $response["data"]["info"];
                
                if( $response["data"]["active"] == 0 ) {
                    $title = [
                        trans($this->msg_price),
                        trans($this->msg_duration),
                        trans($this->msg_speed_in),
                        trans($this->msg_speed_out),
                    ];

                    $body = [
                        $turbo["cost_activate"],
                        $turbo["time"] . " " . trans($this->msg_duration_label),
                        Helper::formatSpeed($turbo["speed_in"]) . " " . trans($this->msg_speed_label),
                        Helper::formatSpeed($turbo["speed_out"]) . " " . trans($this->msg_speed_label),
                    ];

                    $text = Helper::generateMessage($title, $body);
                    $keyboard = [
                        [["text" => trans(self::$btnTurboActivate)]],
                        [["text" => trans("back")]],
                    ];
                } else {
                    $text = trans($this->msg_already_active) . "\n";
                    $title = [
                        trans($this->msg_speed_in),
                        trans($this->msg_speed_out),
                    ];

                    $body = [
                        Helper::formatSpeed($turbo["speed_in"]) . " " . trans($this->msg_speed_label),
                        Helper::formatSpeed($turbo["speed_out"]) . " " . trans($this->msg_speed_label),
                    ];

                    $text .= Helper::generateMessage($title, $body);
                    
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
    
    public function btnTurboActivate() {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->TurboActivate();
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