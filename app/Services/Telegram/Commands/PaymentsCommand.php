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
* Class PaymentsCommand
*/
class PaymentsCommand extends Command
{
    public static $btnMain = "payment.btn.Main";
    public static $btnChaneSumma = "payment.btn.ChangeSumma";
    public static $btnGenerateURL = "payment.btn.GenerateURL";

    // префикс кнопки платежной системы
    public static $btnPaysystem = "payment.btn_";
    
    private $msg_error_request = "payment.msg.request.error";
    private $msg_no_items_left = "payment.msg.no_items";
    private $msg_error_user_deleted = "payment.msg.userstate_deleted";
    private $msg_error_user_disabled = "payment.msg.userstate_disabled";
    private $msg_choose_method = "payment.msg.choose_method";
    private $msg_recommend_summa = "payment.msg.rec_summa";
    private $msg_enter_custom_summa = "payment.msg.enter_summa";
    private $msg_final_summa = "payment.msg.final_custom_summa";
    
    private $msg_url_payment = "payment.text.url";
    private $msg_url_description = "payment.text.url_description";
    
    private $cache_key_choosen_paysystem = "payment_choosen";
    private $cache_key_summa = "payment_summa";

    private $paysystem = "";
    private $payment_summa = 0;

    private $paysystems = [
        'use_wqiwiru', 'use_robokassa', 'use_liqpay', 'use_onpay', 'use_privat24', 'use_pscb', 'use_paymaster', 'use_stripe', 'use_paypal', 'use_paykeeper',
        'use_ukrpays', 'use_yandex', 'use_portmone', 'use_uniteller', 'use_ipay', 'use_fondy', 'use_sberbankrumrch', 'use_simplepay', 'use_yandexmoney', 'use_cloudpayments',
        'use_alfabankru', 'use_isbank', 'use_paysoft', 'use_ckassa', 'use_tinkoff', 'use_easypay', 'use_paycell', 'use_masterpass', 'use_paymo', 'use_payme',
        'use_click', 'barcode_on', 'qrcode_on', 'paysera_on', 'freedompay_on', 'qiwi_on', 'reeves_on', 'use_privat_v2'
    ];
    
    private $replace = ["use_", "_on"];
    
    public function __construct(TeleBot $bot, Update $update)
    {
        parent::__construct($bot, $update);

        $this->paysystem = $this->getValue($this->cache_key_choosen_paysystem, "");
        $this->payment_summa = $this->getValue($this->cache_key_summa, 0);
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
    private function savePaysystem($value) {
        $this->paysystem = $value;
        $this->setValue($this->cache_key_choosen_paysystem, $value);
    }

    /**
     * @param $value
     */
    private function saveReccomendSumma($value) {
        $this->payment_summa = $value;
        $this->setValue($this->cache_key_summa, $value);
    }

    /**
     * @param $value
     */
    private function saveCustomSumma($value) {
        $this->payment_summa = $value;
        $this->setValue($this->cache_key_summa, $value);
    }
    
    public function mainMenu() {
        $this->setLastAction(__FUNCTION__);
        
        $response = $this->ClientAPI->getUser();
        if( $this->validResponse($response) ) {
            if(!empty($response["data"])) {
                if( $response["data"]["otkluchentable"] == 1 ) {
                    $text = trans($this->msg_error_user_deleted);

                    $keyboard = [
                        [["text" => trans("back")]],
                    ];
                } elseif ( $response["data"]["deletedtable"] == 1 ) {
                    $text = trans($this->msg_error_user_disabled);

                    $keyboard = [
                        [["text" => trans("back")]],
                    ];
                } else {
                    // сохраним рек. сумму
                    $this->saveReccomendSumma((float)$response["data"]["erec_payment"]);
                    
                    $text = trans($this->msg_choose_method);
                    
                    // карточки пополнения
                    if(isset($response["data"]["use_cards"]) && $response["data"]["use_cards"] == 1) {
                        $keyboard[] = [["text" => trans(VoucherCommand::$btnMain)]];
                    }
                    
                    // платежные системы
                    $tmp = [];
                    foreach($this->paysystems as $paysystem) {
                        if(isset($response["data"][$paysystem]) && (int)$response["data"][$paysystem] == 1) {
                            if(count($tmp) >= 2) {
                                $keyboard[] = $tmp;
                                $tmp = [];
                            }
                            
                            $tmp[] = ["text" => trans(self::$btnPaysystem . str_ireplace($this->replace, "", $paysystem))];
                        }
                    }

                    if( count($tmp) == 1 ) {
                        $keyboard[] = [$tmp];
                    } elseif ( count($tmp) == 2 ) {
                        $keyboard[] = $tmp;
                    }
                    
                    $keyboard[] = [["text" => trans("back")]];
                }
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

    /**
     * @param $paysystem
     */
    public function choosePaysystem($paysystem) {
        $this->savePaysystem(str_ireplace(self::$btnPaysystem, "", $paysystem));

        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_recommend_summa) . " " . $this->payment_summa;

        $keyboard = [
            [["text" => trans(self::$btnChaneSumma)], ["text" => trans(self::$btnGenerateURL)]],
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
    
    public function changePaymentSummaBtn() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_enter_custom_summa);

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     * @param $summa
     */
    public function confirmSummaBtn($summa) {
        $this->setLastAction(__FUNCTION__);

        $this->saveCustomSumma((float)str_ireplace(",", ".", $summa));
        $text = trans($this->msg_final_summa) . " " . $this->payment_summa;

        $keyboard = [
            [["text" => trans(self::$btnChaneSumma)], ["text" => trans(self::$btnGenerateURL)]],
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     * 
     */
    public function generateURLBtn() {
        $this->setLastAction(__FUNCTION__);

        $lk_url = config("services.mb_api.cabinet_host");
        $request = $lk_url . "/json/index/pay/url/". $this->paysystem ."?uid=". $this->getUser()->mb_uid ."&summa=" . $this->payment_summa;
        
        $text = trans($this->msg_url_description);
        $text .= "<a href='{$request}'>". trans($this->msg_url_payment) . " " . $this->paysystem."</a>";

        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }
}