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
* Class UserCommand
*/
class UserCommand extends Command
{
    public static $btnInfo = "user.btn.Info";

    private $msg_error_request = "user.msg.request.error";
    
    private $msg_user_fio = "user.text.fio";
    private $msg_user_deposit = "user.text.deposit";
    private $msg_user_credit = "user.text.credit";
    private $msg_user_tarif = "user.text.tarif";
    private $msg_user_login = "user.text.login";
    private $msg_user_uid = "user.text.uid";
    private $msg_user_dogovor = "user.text.dogovor";
    private $msg_user_internet = "user.text.internet";
    private $msg_user_date_off = "user.text.date_off";
    private $msg_user_days_left = "user.text.days_left";
    
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

    public function userInfoMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->getUser();
        if( $this->validResponse($response) ) {
            $user = $response['data'];

            $text = "<b>" . trans($this->msg_user_fio) . ":</b> " . $user['fio'] . "\n";
            $text .= "<b>" . trans($this->msg_user_deposit) . ":</b> " . Helper::formatMoney($user['deposit'], 2) . " " . $user['UE'] . "\n";
            $text .= "<b>" . trans($this->msg_user_credit) . ":</b> " . Helper::formatMoney($user['credit'], 2) . " " . $user['UE'] . "\n";
            $text .= "<b>" . trans($this->msg_user_tarif) . ":</b> " . $user['tarif'] . "\n";
            $text .= "<b>" . trans($this->msg_user_login) . ":</b> " . $user['user'] . "\n";
            $text .= "<b>" . trans($this->msg_user_uid) . ":</b>" . $user['useruid'] . " \n";
            $text .= "<b>" . trans($this->msg_user_dogovor) . ":</b>" . $user['numdogovor'] . " \n";
            if ($user['blocked']) {
                $text .= "<b>" . trans($this->msg_user_internet) . ":</b> 🚫 \n";
            } else {
                $text .= "<b>" . trans($this->msg_user_internet) . ":</b> ✅ \n";

                if (!empty($user['date_itog'])) {
                    $text .= "<b>" . trans($this->msg_user_date_off) . ":</b> " . $user['date_itog'] . " \n";
                    $text .= "<b>" . trans($this->msg_user_days_left) . ":</b> " . $user['days_left'] . " \n";
                }
            }

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