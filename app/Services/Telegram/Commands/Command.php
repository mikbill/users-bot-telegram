<?php


namespace App\Services\Telegram\Commands;


use App;
use App\Models\TelegramUsers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Kagatan\MikBillClientAPI\ClientAPI;
use WeStacks\TeleBot\Handlers\CommandHandler;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

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
        if (isset($this->update->message->from->language_code) and !in_array($this->update->message->from->language_code, ['uk', 'ru', 'en'])) {
            $language_code = 'ru';
        } else {
            $language_code = $this->update->message->from->language_code;
        }

        // Логируем все что пришло на вход update
//        Log::emergency($this->update);

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
        $this->ClientAPI = new ClientAPI(config('services.mb_api.host'), config('services.mb_api.secret_key'));

        if (!empty($tgUser->token)) {
            $this->ClientAPI->setJWT($tgUser->token);
        }

        //  Переключим язык пользователя
        App::setLocale($tgUser->language);

        // Проверяем авторизацию пользователя
        $this->checkAuth();
    }

    private function setUserID($user_id)
    {
        $this->user_id = $user_id;
    }

    public function setUser(TelegramUsers $user)
    {
        $this->user = $user;
    }


    public function getUser()
    {
        return $this->user;
    }

    public function getUserID()
    {
        return $this->user_id;
    }

    public function setLastAction($action)
    {
        Cache::put($this->user_id . '_last_action', $action);
    }

    public function getLastAction()
    {
        return Cache::get($this->user_id . '_last_action');
    }

    public function isAuth()
    {
        return $this->isAuth;
    }

    public function checkAuth()
    {
        return $this->isAuth = TelegramUsers::where('id', '=', $this->getUserID())->whereNotNull('token')->exists();
    }
}
