<?php

namespace App\Services\Telegram\Commands;

use App;
use App\Helpers\Helper;
use App\Models\TelegramUsers;
use App\Notifications\BotNotification;
use PHPUnit\TextUI\Help;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;
use Illuminate\Support\Facades\Log;

/**
* Class TarifCommand
*/
class TarifCommand extends Command
{
    public static $btnInfo = "tarif.btn.Info";
    public static $btnChange = "tarif.btn.Change";
    public static $btnChangeNow = "tarif.btn.ChangeNow";
    public static $btnChangeNMonth = "tarif.btn.ChangeNMonth";
    public static $btnSelect = "tarif.btn.Select";
    public static $btnList = "tarif.btn.List";
    public static $btnNext = "tarif.btn.Next";
    public static $btnPrev = "tarif.btn.Prev";

    private $msg_error_request = "tarif.msg.request.error";
    private $msg_no_items_left = "tarif.msg.no_items";
    private $msg_success = "tarif.msg.success_change";
    
    private $msg_tarif_change_now = "tarif.msg.changed_now";
    private $msg_tarif_change_next_month = "tarif.msg.changed_month";
    
    private $msg_tarif_current_info = "tarif.text.current_info";
    private $msg_tarif_available_info = "tarif.text.available_info";
    private $msg_tarif_selected_info = "tarif.text.selected_info";
    private $msg_tarif_name = "tarif.text.name";
    private $msg_tarif_price = "tarif.text.price";
    private $msg_tarif_speed = "tarif.text.speed";
    private $msg_tarif_speed_in = "tarif.text.speed_in";
    private $msg_tarif_speed_out = "tarif.text.speed_out";
    private $msg_tarif_residual_price = "tarif.text.residual_price";
    
    
    private $tarif_list = [];
    private $cache_key_tarif_list = "tarif_list";

    private $tarif_id = 0;
    private $cache_key_tarif_id = "tarif_id";

    private $position = -1;
    private $cache_key_position = "tarif_position";
    
    private $tarif_change_type = 1;
    private $cache_key_change_type = "tarif_change_type";
    
    public function __construct(TeleBot $bot, Update $update)
    {
        parent::__construct($bot, $update);
        
        $this->tarif_list = $this->getValue($this->cache_key_tarif_list, []);
        $this->tarif_id = $this->getValue($this->cache_key_tarif_id, 0);
        $this->position = $this->getValue($this->cache_key_position, -1);
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
    private function setTarifList($value) {
        $this->tarif_list = $value;
        $this->setValue($this->cache_key_tarif_list, $value);
    }

    /**
     * @param $value
     */
    private function setTarifID($value) {
        $this->tarif_id = $value;
        $this->setValue($this->cache_key_tarif_id, $value);
    }

    /**
     * @param $value
     */
    private function setPosition($value) {
        $this->position = $value;
        $this->setValue($this->cache_key_position, $value);
    }

    /**
     * @return mixed
     */
    private function getPosition() {
        return $this->position;
    }

    /**
     * @param $value
     */
    private function setChangeType($value) {
        $this->tarif_change_type = $value;
        $this->setValue($this->cache_key_change_type, $value);
    }
    
    public function btnTarifInfo() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_error_request);
        $keyboard = [
            [["text" => trans("back")]],
        ];
        
        $response = $this->ClientAPI->TarifsList();
        if( $this->validResponse($response) ) {
            $this->setTarifList($response["data"]);
            
            $response = $this->ClientAPI->getUser();
            if( $this->validResponse($response) ) {
                $userProper = $response["data"];
                $this->setTarifID($userProper["gid"]);
                
                $response = $this->ClientAPI->TarifsInfo($userProper["gid"]);
                if( $this->validResponse($response) ) {
                    $tarifProper = $response["data"];
                    
                    $text  = trans($this->msg_tarif_current_info) . "\n";
                    $text .= trans($this->msg_tarif_name) . " " . trans($tarifProper["packet"]) . "\n";
                    $text .= trans($this->msg_tarif_price) . " " . Helper::formatMoney($tarifProper["howmuch"]["tarif_abonka_new"], 2) . "\n";
                    $text .= trans($this->msg_tarif_speed_in) . " " . Helper::formatSpeed($tarifProper["speed_rate"]) . " " . trans($this->msg_tarif_speed) . "\n";
                    $text .= trans($this->msg_tarif_speed_out) . " " . Helper::formatSpeed($tarifProper["speed_burst"]) . " " . trans($this->msg_tarif_speed) . "\n";
                    
                    $keyboard = [
                        [["text" => trans(self::$btnList)]],
                        [["text" => trans("back")]],
                    ];
                }
            }
        }
        
        $this->buttonKeyboard($text, $keyboard);
    }

    public function btnTarifList() {
        $this->setLastAction(__FUNCTION__);
        
        $tarifProper = $this->tarif_list[0];
        
        $text  = "(1/".count($this->tarif_list).") " . trans($this->msg_tarif_available_info) . "\n";
        $text .= trans($this->msg_tarif_name) . " " . trans($tarifProper["packet"]) . "\n";
        $text .= trans($this->msg_tarif_price) . " " . Helper::formatMoney($tarifProper["full_price_discounted"], 2) . "\n";
        $text .= trans($this->msg_tarif_speed_in) . " " . Helper::formatSpeed($tarifProper["speed_rate"]) . " " . trans($this->msg_tarif_speed) . "\n";
        $text .= trans($this->msg_tarif_speed_out) . " " . Helper::formatSpeed($tarifProper["speed_burst"]) . " " . trans($this->msg_tarif_speed) . "\n";
        $text .= trans($this->msg_tarif_residual_price) . Helper::formatMoney($tarifProper["residual_price_discounted"], 2);

        $this->setPosition(0);

        $nextPosition = $this->getPosition() +1;
        if( !isset($this->tarif_list[$nextPosition]) ) {
            $keyboard = [
                [["text" => trans(self::$btnPrev)]],
                [["text" => trans(self::$btnSelect)]],
                [["text" => trans("back")]],
            ];
        } else {
            $keyboard = [
                [["text" => trans(self::$btnPrev)], ["text" => trans(self::$btnNext)]],
                [["text" => trans(self::$btnSelect)]],
                [["text" => trans("back")]],
            ];
        }

        $this->buttonKeyboard($text, $keyboard);
    }
    
    public function btnTarifNext() {
        $this->setLastAction(__FUNCTION__);

        $position = $this->getPosition() +1;
        if( isset($this->tarif_list[$position]) ) {
            $tarifProper = $this->tarif_list[$position];

            $text  = "(". ($position+1) ."/".count($this->tarif_list).") " . trans($this->msg_tarif_available_info) . "\n";
            $text .= trans($this->msg_tarif_name) . " " . trans($tarifProper["packet"]) . "\n";
            $text .= trans($this->msg_tarif_price) . " " . Helper::formatMoney($tarifProper["full_price_discounted"], 2) . "\n";
            $text .= trans($this->msg_tarif_speed_in) . " " . Helper::formatSpeed($tarifProper["speed_rate"]) . " " . trans($this->msg_tarif_speed) . "\n";
            $text .= trans($this->msg_tarif_speed_out) . " " . Helper::formatSpeed($tarifProper["speed_burst"]) . " " . trans($this->msg_tarif_speed) . "\n";
            $text .= trans($this->msg_tarif_residual_price) . Helper::formatMoney($tarifProper["residual_price_discounted"], 2);
            
            $this->setPosition($position);
        } else {
            $text = trans($this->msg_no_items_left);
        }

        $nextPosition = $this->getPosition() +1;
        if( !isset($this->tarif_list[$nextPosition]) ) {
            $keyboard = [
                [["text" => trans(self::$btnPrev)]],
                [["text" => trans(self::$btnSelect)]],
                [["text" => trans("back")]],
            ];
        } else {
            $keyboard = [
                [["text" => trans(self::$btnPrev)], ["text" => trans(self::$btnNext)]],
                [["text" => trans(self::$btnSelect)]],
                [["text" => trans("back")]],
            ];
        }

        $this->buttonKeyboard($text, $keyboard);
    }

    public function btnTarifPrev() {
        $this->setLastAction(__FUNCTION__);

        $position = $this->getPosition() -1;
        if( isset($this->tarif_list[$position]) ) {
            $tarifProper = $this->tarif_list[$position];

            $text  = "(". ($position+1) ."/".count($this->tarif_list).") " . trans($this->msg_tarif_available_info) . "\n";
            $text .= trans($this->msg_tarif_name) . " " . trans($tarifProper["packet"]) . "\n";
            $text .= trans($this->msg_tarif_price) . " " . Helper::formatMoney($tarifProper["full_price_discounted"], 2) . "\n";
            $text .= trans($this->msg_tarif_speed_in) . " " . Helper::formatSpeed($tarifProper["speed_rate"]) . " " . trans($this->msg_tarif_speed) . "\n";
            $text .= trans($this->msg_tarif_speed_out) . " " . Helper::formatSpeed($tarifProper["speed_burst"]) . " " . trans($this->msg_tarif_speed) . "\n";
            $text .= trans($this->msg_tarif_residual_price) . Helper::formatMoney($tarifProper["residual_price_discounted"], 2);
            
            $this->setPosition($position);
        } else {
            $text = trans($this->msg_no_items_left);
        }

        $nextPosition = $this->getPosition() -1;
        if( !isset($this->tarif_list[$nextPosition]) ) {
            $keyboard = [
                [["text" => trans(self::$btnNext)]],
                [["text" => trans(self::$btnSelect)]],
                [["text" => trans("back")]],
            ];
        } else {
            $keyboard = [
                [["text" => trans(self::$btnPrev)], ["text" => trans(self::$btnNext)]],
                [["text" => trans(self::$btnSelect)]],
                [["text" => trans("back")]],
            ];
        }

        $this->buttonKeyboard($text, $keyboard);
    }

    public function btnTarifConfirm() {
        $this->setLastAction(__FUNCTION__);

        $position = $this->getPosition();
        $text = trans($this->msg_no_items_left);
        
        if( isset($this->tarif_list[$position]) ) {
            $tarifProper = $this->tarif_list[$position];

            $text  = trans($this->msg_tarif_selected_info) . "\n";
            $text .= trans($this->msg_tarif_name) . " " . trans($tarifProper["packet"]) . "\n";
            $text .= trans($this->msg_tarif_price) . " " . Helper::formatMoney($tarifProper["full_price_discounted"], 2) . "\n";
            $text .= trans($this->msg_tarif_speed_in) . " " . Helper::formatSpeed($tarifProper["speed_rate"]) . " " . trans($this->msg_tarif_speed) . "\n";
            $text .= trans($this->msg_tarif_speed_out) . " " . Helper::formatSpeed($tarifProper["speed_burst"]) . " " . trans($this->msg_tarif_speed) . "\n";
            $text .= trans($this->msg_tarif_residual_price) . Helper::formatMoney($tarifProper["residual_price_discounted"], 2);
        }

        $response = $this->ClientAPI->getConfig();
        if( $this->validResponse($response) ) {
            $config = $response["data"]["gui"];
            if( !empty($config["changetariffoptions_visible"]) ) {
                // есть выбор
                $keyboard = [
                    [["text" => trans(self::$btnChangeNow)], ["text" => trans(self::$btnChangeNMonth)]],
                    [["text" => trans("back")]],
                ];
            } else {
                $this->setChangeType($config["changetariffoptions_default"]);
                $keyboard = [
                    [["text" => trans(self::$btnChange)]],
                    [["text" => trans("back")]],
                ];
            }
        } else {
            $text = trans($this->msg_error_request);
            $keyboard = [
                [["text" => trans(self::$btnChange)]],
                [["text" => trans("back")]],
            ];
        }
        
        $this->buttonKeyboard($text, $keyboard);
    }

    public function btnTarifChange() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_no_items_left);
        $position = $this->getPosition();
        if( isset($this->tarif_list[$position]) ) {
            $tarifProper = $this->tarif_list[$position];

            $type = $this->tarif_change_type - 1;
            $response = $this->ClientAPI->UserTarifChange($tarifProper["gid"], $type);
            if( $this->validResponse($response) ) {
                if( $type == 0 ) {
                    $text  = trans($this->msg_tarif_change_now);
                } else {
                    $text  = trans($this->msg_tarif_change_next_month);
                }
            }
        }

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    public function btnTarifChangeNow() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_no_items_left);
        $position = $this->getPosition();
        if( isset($this->tarif_list[$position]) ) {
            $tarifProper = $this->tarif_list[$position];

            $response = $this->ClientAPI->UserTarifChange($tarifProper["gid"], 0);
            if( $this->validResponse($response) ) {
                $text  = trans($this->msg_tarif_change_now);
            }
        }

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    public function btnTarifChangeMonth() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_no_items_left);
        $position = $this->getPosition();
        if( isset($this->tarif_list[$position]) ) {
            $tarifProper = $this->tarif_list[$position];

            $response = $this->ClientAPI->UserTarifChange($tarifProper["gid"], 1);
            if( $this->validResponse($response) ) {
                $text  = trans($this->msg_tarif_change_next_month);
            }
        }

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
}
