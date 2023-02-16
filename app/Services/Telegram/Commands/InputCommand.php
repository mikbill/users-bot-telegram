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
 * Class InputCommand
 * @package App\Services\Telegram\Commands
 */
class InputCommand extends Command
{
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


        dump($text);
        dump($command);
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

                case "user_info":
                    $this->userInfoMenu();
                    break;

                case "news":
                    $this->newsMenu();
                    break;

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

                case "services":
                    $this->servicesMenu();
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

                default:
                    $this->parseInputText($text);
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
                case "langMenu":

                    break;

                default:
                    // по умолчанию
                    $this->mainMenu();
            }
        }
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
            $response = $this->ClientAPI->authLoginPassword($response['data']['login'], $text);
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

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
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


        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
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

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

    private function userInfoMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->getUser();
        $user = $response['data'];

        $text = "<b>" . trans("fio") . ":</b> " . $user['fio'] . "\n";
        $text .= "<b>" . trans("deposit") . ":</b> " . $user['deposit'] . " " . $user['UE'] . "\n";
        $text .= "<b>" . trans("credit") . ":</b> " . $user['credit'] . " " . $user['UE'] . "\n";
        $text .= "<b>" . trans("tariff") . ":</b> " . $user['tarif'] . "\n";
        $text .= "<b>" . trans("login") . ":</b> " . $user['user'] . "\n";
        $text .= "<b>UID:</b>" . $user['useruid'] . " \n";
        $text .= "<b>" . trans("dogovor") . ":</b>" . $user['numdogovor'] . " \n";
        if ($user['blocked']) {
            $text .= "<b>" . trans("internet") . ":</b> 🚫 \n";
        } else {
            $text .= "<b>" . trans("internet") . ":</b> ✅ \n";

            if (!empty($user['date_itog'])) {
                $text .= "<b>" . trans("date_off") . ":</b> " . $user['date_itog'] . " \n";
                $text .= "<b>" . trans("days_left") . ":</b> " . $user['days_left'] . " \n";
            }
        }


        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }


    private function aboutMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Здесь в будущем появится информация о вашем провайдере";

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }


    private function langMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Выберите язык общения в боте";

        $keyboard = [
            [["text" => trans("lang_uk")], ["text" => trans("lang_ru")], ["text" => trans("lang_en")]],
            [["text" => trans("back")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);

    }


    private function changeLang($command)
    {
        $this->setLastAction(__FUNCTION__);

        dump($command);
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

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);

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

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
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

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
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

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }


    private function helpMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $tgUsers = $this->getUser();

        $text = "🤯 Мы сейчас сильно заняты. Если что то срочное позвоните в техподдержку...";

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

    private function newsMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "🤐 Тсс... Здесь будут новости, но чуть позже...";

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
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

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

    private function mainMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = trans("main_menu_text");

//        $keyboard = [
//            [["text" => trans("user_info")], ["text" => trans("services")]],
//            [["text" => trans("news")], ["text" => trans("contacts")]],
//            [["text" => trans("help")], ["text" => trans("settings")]]
//        ];
//

        $keyboard = [
            [["text" => trans("user_info")], ["text" => trans("news")]],
            [["text" => trans("help")], ["text" => trans("contacts")]],
            [["text" => trans("settings")]]
        ];

        $this->sendMessage([
            'text'         => $text,
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

    private function noAuthMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = trans("auth_notice");

        $keyboard = [
            [["text" => trans("send_contact"), "request_contact" => true]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

}
