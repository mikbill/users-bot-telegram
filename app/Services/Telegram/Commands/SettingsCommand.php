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
* Class SettingsCommand
*/
class SettingsCommand extends Command
{
    public static $btnMain = "settings.btn.Main";
    public static $btnLangChange = "settings.btn.Lang";
    public static $btnExit = "settings.btn.Exit";
    public static $btnExitConfirm = "settings.btn.Exit.Confirm";
    
    public static $btnLangUA = "settings.btn.Lang.UA";
    public static $btnLangEN = "settings.btn.Lang.EN";
    public static $btnLangRU = "settings.btn.Lang.RU";
    
    private $msg_settings = "settings.text.msg";
    private $msg_select_lang = "settings.text.lang.select";
    private $msg_lang_cahnged = "settings.text.lang.changed";
    private $msg_exit = "settings.text.exit";
    private $msg_exit_msg = "settings.text.exit.msg";
    
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
    
    public function btnSettingsInfo() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_settings);

        $keyboard = [
            [["text" => trans(self::$btnLangChange)]],
            [["text" => trans(self::$btnExit)]],
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    public function btnLangMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_select_lang);

        $keyboard = [
            [["text" => trans(self::$btnLangUA)], ["text" => trans(self::$btnLangEN)], ["text" => trans(self::$btnLangRU)]],
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     * @param $command
     */
    public function btnLangChange($command)
    {
        $this->setLastAction(__FUNCTION__);

        switch ($command) {
            case self::$btnLangUA:
                $locale = 'uk';
                break;

            case self::$btnLangRU:
                $locale = 'ru';
                break;
                
            case self::$btnLangEN:
                $locale = 'en';
                break;

            default:
                $locale = 'ru';
        }

        // Установим язык
        App::setLocale($locale);

        // Обновим пользователя
        TelegramUsers::whereId($this->getUserID())->update(['language' => $locale]);

        $text = trans($this->msg_lang_cahnged) . " " . trans($command);

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
    
    public function btnExitInfo() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_exit);
        
        $keyboard = [
            [["text" => trans(self::$btnExitConfirm)]],
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
    
    public function btnExitConfirm() {
        $this->setLastAction(__FUNCTION__);
        
        $this->getUser()->forceDelete();
        $this->unAuth();
        
        $text = trans($this->msg_exit_msg);
        
        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
}