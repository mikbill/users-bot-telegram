<?php

namespace App\Services\Telegram\Commands;

use App;
use App\Helpers\Helper;
use App\Models\TelegramUsers;
use App\Notifications\BotNotification;
use App\Services\Telegram\Commands\HistoryPaymentsCommand;
use App\Services\Telegram\Commands\HistorySessionsCommand;
use App\Services\Telegram\Commands\NewsCommand;
use App\Services\Telegram\Commands\PaymentsCommand;
use App\Services\Telegram\Commands\UserCommand;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;
use App\Services\Telegram\Commands\VoucherCommand;

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
                
                case "help":
                    $this->helpMenu();
                    break;

                case "contacts":
                    $this->contactsMenu();
                    break;

                case "about":
                    $this->aboutMenu();
                    break;

                case "settings":
                    $this->settingsMenu();
                    break;
                    
                case "notifications":
                    $this->notificationsMenu();
                    break;

                case "lang":
                    $this->langMenu();
                    break;

                case "lang_ru":
                case "lang_uk":
                case "lang_en":
                    $this->changeLang($command);
                    break;

                default: {
                    // Кнопка платежной системы
                    if( strpos($command, PaymentsCommand::$btnPaysystem) !== false ) {
                        $class = new PaymentsCommand($bot, $update);
                        $class->choosePaysystem($command);
                    } else {
                        $this->parseInputText($text);
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
                case "langMenu": {
                    break;
                }
                
                case "choosePaysystem":
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
            [["text" => trans(UserCommand::$btnInfo)], ["text" => trans(NewsCommand::$btnMain)]],
            [["text" => trans(ServiceCommand::$btnMain)], ["text" => trans(PaymentsCommand::$btnMain)]],
            [["text" => trans(HistoryPaymentsCommand::$btnMain)], ["text" => trans(HistorySessionsCommand::$btnMain)]],
            [["text" => trans("help")], ["text" => trans("contacts")]],
            [["text" => trans("settings")]]
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
    
    private function aboutMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Здесь в будущем появится информация о вашем провайдере";

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
    
    private function langMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Выберите язык общения в боте";

        $keyboard = [
            [["text" => trans("lang_uk")], ["text" => trans("lang_ru")], ["text" => trans("lang_en")]],
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
    
    private function changeLang($command)
    {
        $this->setLastAction(__FUNCTION__);

        switch ($command) {
            case  'lang_uk':
                $locale = 'uk';
                break;

            case  'lang_ru':
                $locale = 'ru';
                break;
            case  'lang_en':
                $locale = 'en';
                break;

            default:
                $locale = 'ru';
        }

        // Установим язык
        App::setLocale($locale);

        // Обновим пользователя
        TelegramUsers::whereId($this->getUserID())->update(['language' => $locale]);

        $text = trans("lang_changed") . " " . trans($command);

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    private function settingsMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Настройки \n\n";
        $text .= "🔔 <b>Уведомления</b> - управление уведомлениями при пополнении счета, либо других финансовых операций; \n";
        $text .= "🇺🇸 <b>Выбор языка</b> - выберите язык, на котором бот будет вести диалог; \n";

        $keyboard = [
            //[["text" => trans("notifications")], ["text" => trans("lang")]],
            [["text" => trans("lang")]],
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    private function notificationsMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "<b>Текущий статус уведомлений:</b> \n\n";
        $text .= "🔕 Новости\n\n";
        $text .= "🔔 Финансовые уведомления \n\n";
        $text .= "🔔 За 3 дня до отключения \n\n";

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    private function contactsMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Наши контакты: \n";
        $text .= "Офис: г.Городской ул. Уличная 1. \n";
        $text .= "т. +38(000) 000-00-00 \n";
        $text .= "telegram: ";

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
    
    private function helpMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $tgUsers = $this->getUser();
        $text = trans("Hello");

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
    
    private function servicesMenu()
    {
        $this->setLastAction(__FUNCTION__);

        // Получаем подключенные услуги
        $response = $this->ClientAPI->getUser();
        $user = $response['data'];

        $text = trans("Hello");

        $keyboard = [
            [["text" => trans("back")]],
        ];

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
