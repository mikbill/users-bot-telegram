<?php

namespace App\Services\Telegram\Commands;

use App;
use App\Helpers\Helper;
use App\Models\TelegramUsers;
use App\Notifications\BotNotification;
use phpDocumentor\Reflection\Types\Array_;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;
use Illuminate\Support\Facades\Log;

/**
* Class FreezeCommand
*/
class FreezeCommand extends Command
{
    public static $btnFreezeInfo = "freeze.btn.Info";
    public static $btnFreezeActivate = "freeze.btn.Activate";
    public static $btnFreezeDeactivate = "freeze.btn.Deactivate";
    public static $btnFreezeDateStart = "freeze.btn.DateStart";
    public static $btnFreeze1M = "freeze.btn.Fixed1";
    public static $btnFreeze2M = "freeze.btn.Fixed2";
    public static $btnFreeze3M = "freeze.btn.Fixed3";
    
    private $msg_error_request = "freeze.msg.request.error";
    private $msg_success_activate = "freeze.msg.request.success_activate";
    private $msg_success_deactivate = "freeze.msg.request.success_deactivate";

    private $msg_unfreeze_date = "freeze.msg.unfreeze_allow_date";
    private $msg_unfreeze_cost = "freeze.msg.unfreeze_allow_cost";
    private $msg_unfreeze_disabled = "freeze.msg.unfreeze_disabled";
    
    private $msg_enter_date_start = "freeze.msg.enter_date_start";
    private $msg_date_start_error = "freeze.msg.enter_date_start_error";
    private $msg_enter_date_stop = "freeze.msg.enter_date_stop";
    private $msg_select_date_stop = "freeze.msg.select_date_stop";
    private $msg_min_days_conflict = "freeze.msg.min_days_conflict";
    private $msg_confirm_dates = "freeze.msg.confirm_dates";
    
    private $msg_not_available = "freeze.msg.not_available";
    private $msg_already_active = "freeze.msg.already_active";
    private $msg_freeze_info = "freeze.msg.info";
    
    private $msg_deactivation_cost = "freeze.text.deactivation_cost";
    private $msg_activation_date = "freeze.text.date_activation";
    private $msg_deactivation_date = "freeze.text.date_deactivation";
    private $msg_activation_cost = "freeze.text.activation_cost";
    private $msg_daily_cost = "freeze.text.daily_cost";
    private $msg_min_days = "freeze.text.min_days";
    
    private $cache_key_period = "freeze.cache.period";
    private $cache_key_cost = "freeze.cache.cost";
    private $cache_key_min_days = "freeze.cache.min_days";
    private $cache_key_cost_day = "freeze.cache.cost_day";
    private $cache_key_start_date = "freeze.cache.date.start";
    private $cache_key_stop_date = "freeze.cache.date.stop";
    private $cache_key_earlier_disallow = "freeze.cache.earlier_disallow";
    private $cache_key_earlier_cost = "freeze.cache.earlier_cost";
    private $cache_key_fixed = "freeze.cache.fixed";
    
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

    private function saveDateStart() {
        
    }

    /**
     * @param $key
     * @param int $default
     * @return mixed
     */
    private function getCache($key, $default = "") {
        return $this->getValue($key, $default);
    }

    /**
     * @param $key
     * @param $value
     */
    private function setCache($key, $value) {
        $this->setValue($key, $value);
    }
    
    public function btnFreezeInfo()
    {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->FreezeInfo();
        if( $this->validResponse($response) ) {
            //{"success":true,"code":0,"data":{
            //  "available":1,"active":0,"info":{
            //      "cost_activate":"0",
            //      "cost_deactivate":"0.00",
            //      "cost_day":"0.00",
            //      "min_day":"7",
            //      "count_free":"0",
            //      "count_free_used":"10",
            //      "date_start":null,
            //      "date_stop":null,
            //      "fixed_month":"1",
            //      "unfreeze_earlier_pay":"1",
            //      "unfreeze_earlier_disallow":"1"
            //}},"message":"\u041e\u041a"}  

            if( $response["data"]["available"] == 1 ) {
                $freeze = $response["data"]["info"];

                $this->setCache($this->cache_key_cost, $freeze["cost_activate"]);
                $this->setCache($this->cache_key_cost_day, $freeze["cost_day"]);
                $this->setCache($this->cache_key_min_days, $freeze["min_day"]);
                $this->setCache($this->cache_key_start_date, $freeze["date_start"]);
                $this->setCache($this->cache_key_stop_date, $freeze["date_stop"]);
                $this->setCache($this->cache_key_earlier_cost, $freeze["cost_deactivate"]);
                $this->setCache($this->cache_key_earlier_disallow, $freeze["unfreeze_earlier_disallow"]);
                $this->setCache($this->cache_key_fixed, $freeze["fixed_month"]);
                
                if( $response["data"]["active"] == 1 ) {
                    $text = trans($this->msg_already_active) . "\n";
                    $text .= trans($this->msg_activation_date) . ": " . $freeze["date_start"] . "\n";
                    $text .= trans($this->msg_deactivation_date) . ": " . $freeze["date_stop"] . "\n";
                    
                    if( $freeze["unfreeze_earlier_disallow"] == 0 ) {
                        $text .= trans($this->msg_deactivation_cost) . ": " . $freeze["cost_deactivate"] . "\n";
                        
                        $unfreeze_allow_date = strtotime("+{$freeze["min_day"]} DAYS", strtotime($freeze["date_start"]));
                        if( $unfreeze_allow_date < strtotime(date("Y-m-d")) ) {
                            $keyboard = [
                                [["text" => trans(self::$btnFreezeDeactivate)]],
                                [["text" => trans("back")]],
                            ];
                        } else {
                            $text .= "\n";
                            $text .= trans($this->msg_unfreeze_date) . ": " . date("Y-m-d", $unfreeze_allow_date) . "\n";
                            $keyboard = [
                                [["text" => trans("back")]],
                            ];
                        }
                    } else {
                        $text .= "\n";
                        $text .= trans($this->msg_unfreeze_disabled) . "\n";
                        $keyboard = [
                            [["text" => trans("back")]],
                        ];
                    }
                } else {
                    $text = trans($this->msg_freeze_info) . "\n";
                    $text .= trans($this->msg_activation_cost) . ": " . $freeze["cost_activate"] . "\n";
                    if( $freeze["cost_day"] > 0 ) {
                        $text .= trans($this->msg_daily_cost) . ": " . $freeze["cost_day"] . "\n";
                    }
                    
                    if( $freeze["min_day"] > 0 ) {
                        $text .= trans($this->msg_min_days) . ": " . $freeze["min_day"] . "\n";
                    }
                    
                    $text .= trans($this->msg_activation_date) . ": " . $freeze["date_start"] . "\n";
                    $text .= trans($this->msg_deactivation_date) . ": " . $freeze["date_stop"] . "\n";

                    $keyboard = [
                        [["text" => trans(self::$btnFreezeDateStart)]],
                        [["text" => trans("back")]],
                    ];
                }
            } else {
                $text = trans($this->msg_not_available);
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

    /**
     * 
     */
    public function btnFreezeStepDateStart() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_activation_date) . ": " . $this->getCache($this->cache_key_start_date) . "\n";
        $text .= trans($this->msg_deactivation_date) . ": " . $this->getCache($this->cache_key_stop_date) . "\n\n";
        $text .= trans($this->msg_enter_date_start);

        $keyboard = [
            [["text" => trans("back")]],
        ];
        
        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     *
     */
    public function btnFreezeStepDateStop($days_to_start) {
        $this->setLastAction(__FUNCTION__);

        if( $days_to_start >= 0 ) {
            $this->setCache($this->cache_key_start_date, date("Y-m-d", strtotime("+{$days_to_start} DAYS")));

            $text = trans($this->msg_activation_date) . ": " . $this->getCache($this->cache_key_start_date) . "\n";
            $text .= trans($this->msg_deactivation_date) . ": " . $this->getCache($this->cache_key_stop_date) . "\n\n";

            if( $this->getCache($this->cache_key_fixed, 0) == 0 ) {
                // default
                $text .= trans($this->msg_enter_date_stop);
                $keyboard = [
                    [["text" => trans("back")]],
                ];
            } else {
                // fixed
                $text .= trans($this->msg_select_date_stop);
                $keyboard = [
                    [["text" => trans(self::$btnFreeze1M)], ["text" => trans(self::$btnFreeze2M)], ["text" => trans(self::$btnFreeze3M)]],
                    [["text" => trans("back")]],
                ];
            }
        } else {
            $this->setLastAction("btnFreezeStepDateStart");
            
            $text = trans($this->msg_date_start_error) . "\n";
            $text .= trans($this->msg_enter_date_start);
            
            $keyboard = [
                [["text" => trans("back")]],
            ];
        }
        
        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     *
     */
    public function btnFreezeStepConfirm($days_to_stop) {
        $this->setLastAction(__FUNCTION__);
        
        if( (int)$days_to_stop >= (int)$this->getCache($this->cache_key_min_days, 0) ) {
            $date_stop = date("Y-m-d", strtotime("+{$days_to_stop} DAYS", strtotime($this->getCache($this->cache_key_start_date))));
            $this->setCache($this->cache_key_stop_date, $date_stop);

            $text = trans($this->msg_activation_date) . ": " . $this->getCache($this->cache_key_start_date) . "\n";
            $text .= trans($this->msg_deactivation_date) . ": " . $this->getCache($this->cache_key_stop_date) . "\n\n";
            $text .= trans($this->msg_confirm_dates);

            $keyboard = [
                [["text" => trans(self::$btnFreezeDateStart)], ["text" => trans(self::$btnFreezeActivate)]],
                [["text" => trans("back")]],
            ];
        } else {
            $this->setLastAction("btnFreezeStepDateStop");
            $text = trans($this->msg_activation_date) . ": " . $this->getCache($this->cache_key_start_date) . "\n";
            $text .= trans($this->msg_deactivation_date) . ": " . $this->getCache($this->cache_key_stop_date) . "\n\n";

            $text .= trans($this->msg_min_days_conflict) . ":" . $this->getCache($this->cache_key_min_days, 0)  . "\n";
            $text .= trans($this->msg_enter_date_stop) . "\n";

            $keyboard = [
                [["text" => trans("back")]],
            ];
        }
        
        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     *
     */
    public function btnFreezeFixedConfirm($month) {
        $this->setLastAction(__FUNCTION__);

        $date_stop = date("Y-m-d", strtotime("+{$month} MONTH", strtotime($this->getCache($this->cache_key_start_date))));
        $this->setCache($this->cache_key_stop_date, $date_stop);
        $this->setCache($this->cache_key_fixed, $month);
        
        $text = trans($this->msg_activation_date) . ": " . $this->getCache($this->cache_key_start_date) . "\n";
        $text .= trans($this->msg_deactivation_date) . ": " . $this->getCache($this->cache_key_stop_date) . "\n\n";
        $text .= trans($this->msg_confirm_dates);

        $keyboard = [
            [["text" => trans(self::$btnFreezeDateStart)], ["text" => trans(self::$btnFreezeActivate)]],
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
    
    public function btnFreezeActivate() {
        $this->setLastAction(__FUNCTION__);

        $date_start = $this->getCache($this->cache_key_start_date);
        $date_stop = $this->getCache($this->cache_key_stop_date);
        
        $response = $this->ClientAPI->FreezeActivate($date_start, $date_stop, 0, $this->getCache($this->cache_key_fixed));
        if( $this->validResponse($response) ) {
            $text = trans($this->msg_success_activate);

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

    public function btnFreezeDeactivate() {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->FreezeDeactivate();
        if( $this->validResponse($response) ) {
            $text = trans($this->msg_success_deactivate);

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