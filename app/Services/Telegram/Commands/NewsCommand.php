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
* Class NewsCommand
*/
class NewsCommand extends Command
{
    public static $btnMain = "news.btn.Main";
    public static $btnPrev = "news.btn.Prev";
    public static $btnNext = "news.btn.Next";
    
    private $cache_key_count = "news_count";
    private $cache_key_array = "news_array";
    private $cache_key_position = "news_position";
    
    private $msg_error_request = "news.msg.request.error";
    private $msg_no_items_left = "news.msg.no_items";
    
    private $_last_position = 0;
    private $_news_array = [];
    private $_news_cout = 0;
    
    public function __construct(TeleBot $bot, Update $update)
    {
        parent::__construct($bot, $update);

        $this->_last_position = $this->getValue($this->cache_key_position);
        $this->_news_array = $this->getValue($this->cache_key_array, []);
        $this->_news_cout = $this->getValue($this->cache_key_count);
    }

    /**
     * Обработчик команд
     */
    public function handle()
    {

    }
    
    /**
     * @param $value
     */
    private function setPosition($value) {
        $this->_last_position = $value;
        $this->setValue($this->cache_key_position, $value);
    }

    /**
     * @param $value
     */
    private function getPosition() {
        return $this->_last_position;
    }

    /**
     * @return mixed
     */
    private function getNewsCount() {
        return $this->_news_cout;
    }
    
    /**
     * @param $value
     */
    private function setNewsArray($value) {
        $this->_news_array = $value;
        $this->_news_cout = count($value);
        $this->_last_position = 0;
        
        $this->setValue($this->cache_key_array, $value);
        $this->setValue($this->cache_key_count, count($value));
        $this->setValue($this->cache_key_position, 0);
    }
    
    public function mainMenu() {
        $this->setLastAction(__FUNCTION__);
        
        $response = $this->ClientAPI->getNews();
        if( $this->validResponse($response) ) {
            if(!empty($response["data"])) {
                $this->setNewsArray($response["data"]);

                $text = trans($this->msg_no_items_left);
                foreach($response["data"] as $item) {
                    $text = strip_tags($item["text"]);
                    break;
                }
                
                $keyboard = [
                    [["text" => trans(self::$btnNext)]],
                    [["text" => trans("back")]],
                ];
            } else {
                $text = trans($this->msg_no_items_left);

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

    public function nextNews() {
        $this->setLastAction(__FUNCTION__);

        $position = $this->getPosition() +1;
        if( isset($this->_news_array[$position]) ) {
            $news = $this->_news_array[$position];
            $text = strip_tags($news["text"]);
            $this->setPosition($position);
        } else {
            $text = trans($this->msg_no_items_left);
        }

        $nextNews = $this->getPosition() +1;
        if( !isset($this->_news_array[$nextNews]) ) {
            $keyboard = [
                [["text" => trans(self::$btnPrev)]],
                [["text" => trans("back")]],
            ];
        } else {
            $keyboard = [
                [["text" => trans(self::$btnPrev)], ["text" => trans(self::$btnNext)]],
                [["text" => trans("back")]],
            ];
        }
        
        $this->buttonKeyboard($text, $keyboard);
    }

    public function prevNews() {
        $this->setLastAction(__FUNCTION__);

        $position = $this->getPosition() -1;
        if( isset($this->_news_array[$position]) ) {
            $news = $this->_news_array[$position];
            $text = strip_tags($news["text"]);
            $this->setPosition($position);
        } else {
            $text = trans($this->msg_no_items_left);
        }

        $nextNews = $this->getPosition() -1;
        if( !isset($this->_news_array[$nextNews]) ) {
            $keyboard = [
                [["text" => trans(self::$btnNext)]],
                [["text" => trans("back")]],
            ];
        } else {
            $keyboard = [
                [["text" => trans(self::$btnPrev)], ["text" => trans(self::$btnNext)]],
                [["text" => trans("back")]],
            ];
        }

        $this->buttonKeyboard($text, $keyboard);
    }
}