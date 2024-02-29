<?php

namespace App\Services\Telegram\Commands;

use App;
use App\Helpers\Helper;
use App\Models\TelegramUsers;
use App\Notifications\BotNotification;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

/**
 * Class InputCommand
 * @package App\Services\Telegram\Commands
 */
class InputCommand extends Command
{
    /**
     * This function should return `true` if this handler should handle given update, or `false` if should not.
     *
     * @return bool
     */
    public static function trigger(Update $update, TeleBot $bot)
    {
        return isset($update->message->text) || isset($update->message->contact);
    }

    /*
     * Обработчик срабатывает когда был введен текст
     */
    public function handle()
    {
        $update = $this->update;
        $bot = $this->bot;

        $text = isset($update->message->text) ? $update->message->text : '';
        $command = Helper::checkCommand($text);
        if(empty($command)) {
            $command = $text;
        }
        
        dump(__CLASS__ . "->text:" . $text);
        dump(__CLASS__ . "->command:" . $command);
        
        //Log::debug(__CLASS__ . "->text:" . $text);
        //Log::debug(__CLASS__ . "->command:" . $command);
        
        // Если не авторизованы
        if (!$this->isAuth()) {
            // Если поделились контактом
            if (isset($update->message->contact)) {
                $command = "send_contact"; // переопределяем меню
            }

            switch ($command) {
                case "no_auth":
                    $this->noAuthMenu();
                    break;

                case "send_contact":
                    $this->sendContactMenu();
                    break;

                default:
                    $this->parseInputText($text);
            }
        } else {
            switch ($command) {
                case "main_menu":
                    $this->mainMenu();
                    break;

                case UserCommand::$btnInfo: {
                    $class = new UserCommand($bot, $update);
                    $class->userInfoMenu();
                    break;
                }

                case NewsCommand::$btnMain: {
                    $class = new NewsCommand($bot, $update);
                    $class->mainMenu();
                    break;
                }

                case NewsCommand::$btnNext: {
                    $class = new NewsCommand($bot, $update);
                    $class->nextNews();
                    break;
                }
                
                case NewsCommand::$btnPrev: {
                    $class = new NewsCommand($bot, $update);
                    $class->prevNews();
                    break;
                }

                case HistoryPaymentsCommand::$btnMain: {
                    $class = new HistoryPaymentsCommand($bot, $update);
                    $class->mainMenu();
                    break;
                }

                case HistorySessionsCommand::$btnMain: {
                    $class = new HistorySessionsCommand($bot, $update);
                    $class->mainMenu();
                    break;
                }

                case PaymentsCommand::$btnMain: {
                    $class = new PaymentsCommand($bot, $update);
                    $class->mainMenu();
                    break;
                }

                case PaymentsCommand::$btnChaneSumma: {
                    $class = new PaymentsCommand($bot, $update);
                    $class->changePaymentSummaBtn();
                    break;
                }
                
                case PaymentsCommand::$btnGenerateURL: {
                    $class = new PaymentsCommand($bot, $update);
                    $class->generateURLBtn();
                    break;
                }

                case VoucherCommand::$btnMain: {
                    $class = new VoucherCommand($bot, $update);
                    $class->enterVoucher();
                    break;
                }

                case ServiceCommand::$btnMain: {
                    $class = new ServiceCommand($bot, $update);
                    $class->btnServiceMenu();
                    break;
                }

                case TarifCommand::$btnInfo: {
                    $class = new TarifCommand($bot, $update);
                    $class->btnTarifInfo();
                    break;
                }

                case TarifCommand::$btnList: {
                    $class = new TarifCommand($bot, $update);
                    $class->btnTarifList();
                    break;
                }

                case TarifCommand::$btnNext: {
                    $class = new TarifCommand($bot, $update);
                    $class->btnTarifNext();
                    break;
                }

                case TarifCommand::$btnPrev: {
                    $class = new TarifCommand($bot, $update);
                    $class->btnTarifPrev();
                    break;
                }
                
                case TarifCommand::$btnSelect: {
                    $class = new TarifCommand($bot, $update);
                    $class->btnTarifConfirm();
                    break;
                }

                case TarifCommand::$btnChangeNow:
                case TarifCommand::$btnChangeNMonth:
                case TarifCommand::$btnChange: {
                    $class = new TarifCommand($bot, $update);
                    $class->btnTarifChange();
                    break;
                }
                
                case TurboCommand::$btnTurboInfo:{
                    $class = new TurboCommand($bot, $update);
                    $class->btnTurboInfo();
                    break;
                }

                case TurboCommand::$btnTurboActivate:{
                    $class = new TurboCommand($bot, $update);
                    $class->btnTurboActivate();
                    break;
                }

                case ChangemacCommand::$btnChangeMACInfo:{
                    $class = new ChangemacCommand($bot, $update);
                    $class->btnChangeMACInfo();
                    break;
                }
                
                case CreditCommand::$btnCreditInfo:{
                    $class = new CreditCommand($bot, $update);
                    $class->btnCreditInfo();
                    break;
                }

                case CreditCommand::$btnCreditActivate:{
                    $class = new CreditCommand($bot, $update);
                    $class->btnCreditActivate();
                    break;
                }

                case FreezeCommand::$btnFreezeInfo:{
                    $class = new FreezeCommand($bot, $update);
                    $class->btnFreezeInfo();
                    break;
                }

                case FreezeCommand::$btnFreezeDateStart:{
                    $class = new FreezeCommand($bot, $update);
                    $class->btnFreezeStepDateStart();
                    break;
                }

                case FreezeCommand::$btnFreeze1M:{
                    $class = new FreezeCommand($bot, $update);
                    $class->btnFreezeFixedConfirm(1);
                    break;
                }

                case FreezeCommand::$btnFreeze2M:{
                    $class = new FreezeCommand($bot, $update);
                    $class->btnFreezeFixedConfirm(2);
                    break;
                }

                case FreezeCommand::$btnFreeze3M:{
                    $class = new FreezeCommand($bot, $update);
                    $class->btnFreezeFixedConfirm(3);
                    break;
                }

                case FreezeCommand::$btnFreezeActivate:{
                    $class = new FreezeCommand($bot, $update);
                    $class->btnFreezeActivate();
                    break;
                }

                case FreezeCommand::$btnFreezeDeactivate:{
                    $class = new FreezeCommand($bot, $update);
                    $class->btnFreezeDeactivate();
                    break;
                }

                case RealIPCommand::$btnInfo:{
                    $class = new RealIPCommand($bot, $update);
                    $class->btnRealIPInfo();
                    break;
                }

                case RealIPCommand::$btnActivate:{
                    $class = new RealIPCommand($bot, $update);
                    $class->btnRealIPActivate();
                    break;
                }

                case RealIPCommand::$btnDeactivate:{
                    $class = new RealIPCommand($bot, $update);
                    $class->btnRealIPDeactivate();
                    break;
                }

                case AboutCommand::$btnMain:{
                    $class = new AboutCommand($bot, $update);
                    $class->btnAbountInfo();
                    break;
                }

                case SettingsCommand::$btnMain:{
                    $class = new SettingsCommand($bot, $update);
                    $class->btnSettingsInfo();
                    break;
                }

                case SettingsCommand::$btnLangChange:{
                    $class = new SettingsCommand($bot, $update);
                    $class->btnLangMenu();
                    break;
                }

                case SettingsCommand::$btnExit:{
                    $class = new SettingsCommand($bot, $update);
                    $class->btnExitInfo();
                    break;
                }

                case SettingsCommand::$btnExitConfirm:{
                    $class = new SettingsCommand($bot, $update);
                    $class->btnExitConfirm();
                    break;
                }
                
                case SettingsCommand::$btnLangUA:
                case SettingsCommand::$btnLangEN:
                case SettingsCommand::$btnLangRU:{
                    $class = new SettingsCommand($bot, $update);
                    $class->btnLangChange($command);
                    break;
                }

                case TicketsCommand::$btnList:
                case TicketsCommand::$btnMain:{
                    $class = new TicketsCommand($bot, $update);
                    $class->btnTicketsList();
                    break;
                }

                case TicketsCommand::$btnOpen:{
                    $class = new TicketsCommand($bot, $update);
                    $class->btnEnterTicketID();
                    break;
                }

                case TicketsCommand::$btnCreate:{
                    $class = new TicketsCommand($bot, $update);
                    $class->btnEnterTicketCreateMessage();
                    break;
                }

                case TicketsCommand::$btnAddMessage:{
                    $class = new TicketsCommand($bot, $update);
                    $class->btnEnterTicketAddMessage();
                    break;
                }
                
                default: {
                    // Кнопка платежной системы
                    if( strpos($command, PaymentsCommand::$btnPaysystem) !== false ) {
                        $class = new PaymentsCommand($bot, $update);
                        $class->choosePaysystem($command);
                    } else {
                        if( $command == "back" ) {
                            // по умолчанию
                            $this->mainMenu();
                        } else {
                            $this->parseInputText($text);
                        }
                    }
                }
            }
        }
    }

    /**
     * Ищем для какого меню был прислан текст
     */
    private function parseInputText($text)
    {
        $lastAction = $this->getLastAction();

        // Если не авторизованы
        if (!$this->isAuth()) {
            switch ($lastAction) {
                case "otp_sended":
                    $this->applyOtp($text);
                    break;

                case "enter_password":
                    $this->applyUserPassword($text);
                    break;

                default:
                    // по умолчанию
                    $this->noAuthMenu();
            }
        } else {
            switch ($lastAction) {
                case "choosePaysystem": {
                    $class = new PaymentsCommand($this->bot, $this->update);
                    $class->mainMenu();
                    break;
                }
                
                case "changePaymentSummaBtn": {
                    $class = new PaymentsCommand($this->bot, $this->update);
                    $class->confirmSummaBtn($text);
                    break;
                }

                case "enterVoucher": {
                    $class = new VoucherCommand($this->bot, $this->update);
                    $class->useVoucher($text);
                    break;
                }

                case "btnChangeMACInfo": {
                    $class = new ChangemacCommand($this->bot, $this->update);
                    $class->btnChangeMACActivate($text);
                    break;
                }
                
                case "btnFreezeStepDateStart": {
                    $class = new FreezeCommand($this->bot, $this->update);
                    $class->btnFreezeStepDateStop($text);
                    break;
                }
                
                case "btnFreezeStepDateStop": {
                    $class = new FreezeCommand($this->bot, $this->update);
                    $class->btnFreezeStepConfirm($text);
                    break;
                }
                
                case "btnEnterTicketID": {
                    $class = new TicketsCommand($this->bot, $this->update);
                    $class->btnOpenTicket($text);
                    break;
                }
                
                case "btnEnterTicketCreateMessage": {
                    $class = new TicketsCommand($this->bot, $this->update);
                    $class->btnCreateTicket($text);
                    break;
                }

                case "btnEnterTicketAddMessage": {
                    $class = new TicketsCommand($this->bot, $this->update);
                    $class->btnTicketAddMessage($text);
                    break;
                }
                
                case "InvalidVoucher": {
                    $class = new PaymentsCommand($this->bot, $this->update);
                    $class->mainMenu();
                    break;
                }
                
                case "btnExitConfirm": {
                    $this->noAuthMenu();
                    break;
                }
                
                default:
                    // по умолчанию
                    $this->mainMenu();
            }
        }
    }

    private function mainMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = trans("main_menu_text");

        $keyboard = [
            [["text" => trans(NewsCommand::$btnMain)], ["text" => trans(UserCommand::$btnInfo)], ["text" => trans(ServiceCommand::$btnMain)]],
            [["text" => trans(PaymentsCommand::$btnMain)], ["text" => trans(HistoryPaymentsCommand::$btnMain)], ["text" => trans(HistorySessionsCommand::$btnMain)]],
            [["text" => trans(TicketsCommand::$btnMain)], ["text" => trans(AboutCommand::$btnMain)], ["text" => trans(SettingsCommand::$btnMain)]]
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
    
    /**
     * Применить введенный пароль
     */
    private function applyUserPassword($text)
    {
        $this->setLastAction(__FUNCTION__);

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $item = TelegramUsers::whereId($this->getUserID())->get("phone");
        $response = $this->ClientAPI->preAuth($item[0]["phone"]);
        if (isset($response['code']) and $response['code'] == 0) {
            $response = $this->ClientAPI->auth($response['data']['login'], $text);
            if (isset($response['data']['token'])) {
                // Привяжем номер user_id телеграма к uid запишем токен
                TelegramUsers::updateOrCreate(['id' => $this->getUserID()], ['token'  => $response['data']['token']]);

                $text = trans("success_loginpassword_enter");
                $keyboard = [
                    [["text" => trans("main_menu")]],
                ];
            } else {
                $text = trans("unknown_error_text");
            }
        } else {
            $text = trans("unknown_error_text");
        }

        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     * Проверяем введенный ОТР код
     */
    private function applyOtp($text)
    {
        $this->setLastAction(__FUNCTION__);

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $response = $this->ClientAPI->authPhoneOtpApply($text);

        if (isset($response['data']['uid'])) {

            // Привяжем номер user_id телеграма к uid запишем токен
            TelegramUsers::updateOrCreate(
                ['id' => $this->getUserID()],
                [
                    'mb_uid' => $response['data']['uid'],
                    'token'  => $response['data']['token'],
                ]
            );

            $text = trans("apply_otp_text");

            $keyboard = [
                [["text" => trans("main_menu")]],
            ];
        } else {
            $text = trans("unknown_error_text");
        }

        $this->buttonKeyboard($text, $keyboard);
    }

    private function sendContactMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $phone_number = $this->update->message->contact->phone_number;

        // Запишем присланный телефон
        TelegramUsers::where('id', $this->getUserID())->update(['phone' => $phone_number]);

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $auth_method = config("services.mb_api.auth_method");
        if( $auth_method == "login" ) {
            $response = $this->ClientAPI->preAuth($phone_number);
            if (isset($response['code']) and $response['code'] == 0) {
                TelegramUsers::where('id', $this->getUserID())->update(['mb_uid' => $response['data']['uid']]);

                $text = trans("enter_password");
                $this->setLastAction("enter_password");
            } else {
                if (isset($response['code']) and $response['code'] == -12) {
                    // АПИ не ответило
                    $text = trans("auth_not_found_user", ["support_phone" => "0 800 00-00-00"]);
                } else {
                    // АПИ не ответило
                    $text = trans("unknown_error_text");
                }
            }
        } else {
            // Пришел номер пытаемся авторизоваться по ОТП
            $response = $this->ClientAPI->authPhone($phone_number);
            if (isset($response['code']) and $response['code'] == 0) {
                $text = trans("otp_sended");
                $this->setLastAction("otp_sended");
            } else {
                if (isset($response['code']) and $response['code'] == -12) {
                    // АПИ не ответило
                    $text = trans("auth_not_found_user", ["support_phone" => "0 800 00-00-00"]);
                } else {
                    // АПИ не ответило
                    $text = trans("unknown_error_text");
                }
            }
        }

        $this->buttonKeyboard($text, $keyboard);
    }
    
    private function noAuthMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = trans("auth_notice");

        $keyboard = [
            [["text" => trans("send_contact"), "request_contact" => true]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
}
