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

        $response = $this->ClientAPI->getUserServices();
        //{"success":true,"code":0,"data":{"services":{"credit":1,"turbo":1,"freeze":1,"realip":1,"transfer":1,"tarif":1}},"message":"\u041e\u041a"}
        if( $this->validResponse($response) ) {
            $buttons = [];
            
            $services = $response["data"]["services"];
            if( isset($services["tarif"]) && $services["tarif"] == 1 ) {
                $buttons[] = ["text" => trans(TarifCommand::$btnInfo)];
            }
            
            if( isset($services["credit"]) && $services["credit"] == 1 ) {
                $buttons[] = ["text" => trans(CreditCommand::$btnCreditInfo)];
            }
            
            if( isset($services["freeze"]) && $services["freeze"] == 1 ) {
                $buttons[] = ["text" => trans(FreezeCommand::$btnFreezeInfo)];
            }
            
            if( isset($services["realip"]) && $services["realip"] == 1 ) {
                $buttons[] = ["text" => trans(RealIPCommand::$btnInfo)];
            }
            
            if( isset($services["turbo"]) && $services["turbo"] == 1 ) {
                $buttons[] = ["text" => trans(TurboCommand::$btnTurboInfo)];
            }

            $tmp = [];
            foreach($buttons as $button) {
                if(count($tmp) >= 2) {
                    $keyboard[] = $tmp;
                    $tmp = [];
                }

                $tmp[] = $button;
            }

            if( !empty($tmp) ) {
                $keyboard[] = $tmp;
            }

            $text = trans($this->msg_service_menu);
            if(empty($buttons)) {
                $text = trans($this->msg_no_items_left);
            }
            
            $keyboard[] = [["text" => trans("back")]];
        } else {
            $text = trans($this->msg_error_request);
            $keyboard = [
                [["text" => trans("back")]],
            ];
        }
        
        $this->buttonKeyboard($text, $keyboard);
    }
}