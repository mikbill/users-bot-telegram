<?php

namespace App\Services\Telegram\Commands;

use App;
use App\Models\TelegramUsers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\ClientAPI;
use WeStacks\TeleBot\Handlers\CommandHandler;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

/**
 * Class Command
 * @package App\Services\Telegram\Commands
 */
abstract class Command extends CommandHandler
{
    private $user_id = -1;
    private $user;
    private $isAuth = false;


    /**
     * @var ClientAPI
     */
    protected $ClientAPI;

    public function __construct(TeleBot $bot, Update $update)
    {
        parent::__construct($bot, $update);

        // Переопределяем язык и проверим что он поддерживается в боте
        $language_code = 'en';
        if (isset($this->update->message->from->language_code) and !in_array($this->update->message->from->language_code, ['uk', 'ru', 'en'])) {
            $language_code = 'ru';
        } else {
            if( isset($this->update->message->from->language_code) ) {
                $language_code = $this->update->message->from->language_code;
            } elseif (isset($this->update->callback_query->from->language_code)) {
                $language_code = $this->update->callback_query->from->language_code;
            }
        }

        // Инициализируем ID пользователя
        if (isset($this->update->message->from->id)) {
            $this->setUserID($this->update->message->from->id);
        } elseif (isset($this->update->callback_query->from->id)) {
            $this->setUserID($this->update->callback_query->from->id);
        }

        $tgUser = TelegramUsers::find($this->getUserID());
        if ($tgUser) {
            // Обновим пользователя
            TelegramUsers::whereId($this->getUserID())
                ->update([
                    'username'   => isset($this->update->message->from->username) ? $this->update->message->from->username : null,
                    'first_name' => isset($this->update->message->from->first_name) ? $this->update->message->from->first_name : null,
                    'last_name'  => isset($this->update->message->from->last_name) ? $this->update->message->from->last_name : null,
                ]);
        } else {
            // Создадим пользователя
            $tgUser = TelegramUsers::create([
                'id'         => $this->getUserID(),
                'username'   => isset($this->update->message->from->username) ? $this->update->message->from->username : null,
                'first_name' => isset($this->update->message->from->first_name) ? $this->update->message->from->first_name : null,
                'last_name'  => isset($this->update->message->from->last_name) ? $this->update->message->from->last_name : null,
                'language'   => $language_code,
            ]);
        }

        //Заполним пользователя
        $this->setUser($tgUser);

        // Пришел номер пытаемся авторизоваться по ОТП
        $this->ClientAPI = new ClientAPI(config('services.mb_api.host'), config('services.mb_api.secret_key'), config('app.debug', false));

        if (!empty($tgUser->token)) {
            $this->ClientAPI->setJWT($tgUser->token);
        }

        //  Переключим язык пользователя
        App::setLocale($tgUser->language);

        // Проверяем авторизацию пользователя
        $this->checkAuth();
    }

    /**
     * @param $user_id
     */
    private function setUserID($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * @param TelegramUsers $user
     */
    public function setUser(TelegramUsers $user)
    {
        $this->user = $user;
    }

    /**
     * @return TelegramUsers
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getUserID()
    {
        return $this->user_id;
    }

    /**
     * @param $action
     */
    public function setLastAction($action)
    {
        Cache::put($this->user_id . '_last_action', $action);
    }

    /**
     * @return mixed
     */
    public function getLastAction()
    {
        return Cache::get($this->user_id . '_last_action');
    }

    /**
     * Сохранить в кеше
     * @param string $value
     * @param mixed $value
     */
    public function setValue($key, $value) {
        Cache::put($this->user_id . '_memory_' . $key, $value);
    }

    /**
     * Достать из кеша
     * @param mixed $value
     * @param mixed $default
     * @return mixed
     */
    public function getValue($key, $default = 0) {
        return Cache::get($this->user_id . '_memory_' . $key, $default);
    }

    /**
     * Успешный ответ
     * @param array $response
     * @return bool
     */
    public function validResponse(array $response) {
        if (isset($response['code']) and $response['code'] == 0) {
            return true;
        }

        return false;
    }
    
    /**
     * @return bool
     */
    public function isAuth()
    {
        return $this->isAuth;
    }
    
    /**
     * @return bool
     */
    public function checkAuth()
    {
        return $this->isAuth = TelegramUsers::where('id', '=', $this->getUserID())->whereNotNull('token')->exists();
    }

    /**
     * @param $text
     * @param $keyboard
     */
    public function buttonKeyboard($text, $keyboard) {
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
     * @param $text
     * @param $keyboard
     * @return false|\WeStacks\TeleBot\Interfaces\Message|\WeStacks\TeleBot\Interfaces\PromiseInterface
     */
    public function InlineKeyboard($text, $keyboard) {
        return $this->sendMessage([
            'text' => $text,
            'chat_id' => $this->update->chat()->id,
            'reply_markup'   =>  [
                'inline_keyboard' => $keyboard
            ],
        ]);
    }
}
