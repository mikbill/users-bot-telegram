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
* Class AboutCommand
*/
class AboutCommand extends Command
{
    public static $btnMain = "about.btn.Main";

    private $msg_error_request = "about.msg.request.error";
    private $msg_no_items_left = "about.msg.no_items";

    private $msg_company_name = "about.text.company_name";
    private $msg_company_address = "about.text.company_address";
    private $msg_company_site = "about.text.company_site";
    private $msg_company_email = "about.text.company_email";
    
    private $msg_table_title = [
        "payhist.text.msg.date",
        "payhist.text.msg.type",
        "payhist.text.msg.summa",
        "payhist.text.msg.comment",
    ];

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
    
    public function btnAbountInfo() {
        $this->setLastAction(__FUNCTION__);
        
        $response = $this->ClientAPI->getConfig();
        if( $this->validResponse($response) ) {
            if(!empty($response["data"])) {
                if( !empty($response["data"]["gui"]["contact_menu_show"]) ) {
                    //"company_info":{
                    //  "company_name":"ISP DEMO2",
                    //  "company_adress":"",
                    //  "company_email":"isp_company@email.com",
                    //  "company_site":"",
                    //  "name_mobile_1":"",
                    //  "name_mobile_2":"",
                    //  "name_mobile_3":"",
                    //  "phone_mobile_1":"",
                    //  "phone_mobile_2":"",
                    //  "phone_mobile_3":"",
                    //"show_map":"1"}
                    $company_info = $response["data"]["company_info"];
                    $text = trans($this->msg_company_name) . ": " . trans(@$company_info["company_name"]) . "\n";
                    $text .= trans($this->msg_company_address) . ": " . @$company_info["company_adress"] . "\n";
                    $text .= trans($this->msg_company_site) . ": " . @$company_info["company_site"] . "\n";
                    $text .= trans($this->msg_company_email) . ": " . @$company_info["company_email"] . "\n";

                    $contacts = [];
                    foreach($company_info as $key => $value) {
                        if( strpos($key, "name_mobile_") !== false ) {
                            $index = str_replace("name_mobile_", "", $key);
                            if( isset($company_info["name_mobile_{$index}"], $company_info["phone_mobile_{$index}"]) ) {
                                $text .= $company_info["name_mobile_{$index}"] . ": " . $company_info["phone_mobile_{$index}"] . "\n";
                            }
                        }
                    }
                } else {
                    $text = trans($this->msg_no_items_left);
                }
                
                $keyboard = [
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
}
