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
* Class VoucherCommand
*/
class VoucherCommand extends Command
{
    public static $btnMain = "voucher.btn.Main";
    public static $btnPay = "voucher.btn.Pay";

    private $msg_error_request = "voucher.msg.request.error";
    private $msg_success = "voucher.msg.success_pay";
    private $msg_enter_code = "voucher.msg.enter_code";
    
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
    
    public function enterVoucher() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_enter_code);
        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     * @param $value
     */
    public function useVoucher($value){
        $this->setLastAction(__FUNCTION__);

        $value = preg_replace('#\D#', '', $value);
        
        $seria = substr($value, 0, 4);
        $number = substr($value, 4);
        
        $response = $this->ClientAPI->useVoucher($seria, $number);
        if( $this->validResponse($response) ) {
            $response = $this->ClientAPI->getUser();
            $userProper = $response['data'];
            
            $text = trans($this->msg_success) . Helper::formatMoney($userProper["deposit"], 2) . " " . $userProper['UE'];
            
            $keyboard = [
                [["text" => trans("back")]],
            ];
        } else {
            if( isset($response["message"]) ) {
                $this->setLastAction("InvalidVoucher");
                $text = trans($response["message"]);
                $keyboard = [
                    [["text" => trans("back")]],
                ];
            } else {
                $text = trans($this->msg_error_request);
                $keyboard = [
                    [["text" => trans("back")]],
                ];
            }
        }

        $this->buttonKeyboard($text, $keyboard);
    }
}