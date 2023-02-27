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
* Class TicketsCommand
*/
class TicketsCommand extends Command
{
    public static $btnMain = "tickets.btn.Main";
    public static $btnList = "tickets.btn.List";
    public static $btnCreate = "tickets.btn.Create";
    public static $btnOpen = "tickets.btn.Open";
    public static $btnAddMessage = "tickets.btn.AddMessage";
    
    private $msg_error_request = "tickets.msg.request.error";
    private $msg_success = "tickets.msg.request.success";
    private $msg_no_items_left = "tickets.msg.no_items";
    private $msg_system_disabled = "tickets.msg.disabled";
    
    private $msg_list = "tickets.msg.list";
    private $msg_enter_id = "tickets.msg.enter_ticketid";
    private $msg_enter_message = "tickets.msg.enter_message";
    private $msg_add_message = "tickets.msg.add_message";
    private $msg_add_success = "tickets.msg.add_message_success";
    
    private $msg_number = "tickets.text.list.number";
    private $msg_date = "tickets.text.list.date";
    private $msg_status = "tickets.text.list.status";
    private $msg_status_open = "tickets.text.list.status_open";
    private $msg_status_closed = "tickets.text.list.status_closed";
    
    private $msg_ticket_date = "tickets.text.message.date";
    private $msg_ticket_who = "tickets.text.message.who";
    private $msg_ticket_message = "tickets.text.message.text";
    private $msg_ticket_from_support = "tickets.text.message.from_support";
    private $msg_ticket_from_you = "tickets.text.message.from_you";
    
    private $cache_key_ticketID = "tickets.cache.ticketid";
    
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

    public function btnTicketsList() {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->getConfig();
        if( $this->validResponse($response) ) {
            $config = $response["data"]["gui"];
            if( $config["menu_show_tickets"] == 1 ) {
                $tickets_data = $this->ClientAPI->TicketsList();
                if( $this->validResponse($tickets_data) ) {
                    if( !empty($tickets_data["data"]) ) {
                        //{"success":true,"code":0,"data":[{
                        //  "ticketid":"258",
                        //  "statustypeid":"1",
                        //  "creationdate":"2022-11-14 15:17:36",
                        //  "prioritytypeid":"2",
                        //  "prioritytypename":"normal",
                        //  "statustypename":"opened"},
                        //],"message":"\u041e\u041a"}  
                        $text = trans($this->msg_list) . ":\n\n";
                        $title = [trans($this->msg_number), trans($this->msg_date), trans($this->msg_status)];
                        $body = [];

                        $limit = 10; $i = 0;
                        rsort($tickets_data["data"]);
                        foreach($tickets_data["data"] as $index => $item) {
                            $status = ($item["statustypename"] == "opened") ? trans($this->msg_status_open) : trans($this->msg_status_closed);
                            $body[] = [$item["ticketid"], $item["creationdate"], $status];
                            $i++;

                            if( $i >= $limit) {
                                break;
                            }
                        }
                        
                        $text .= Helper::generateMessage($title, $body);
                        $keyboard = [
                            [["text" => trans(self::$btnCreate)], ["text" => trans(self::$btnOpen)]],
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
            } else {
                $text = trans($this->msg_system_disabled);
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
    
    public function btnEnterTicketID() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_enter_id);
        $keyboard = [
            [["text" => trans("back")]],
        ];
        
        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     *
     */
    public function btnEnterTicketCreateMessage() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_enter_message);
        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     *
     */
    public function btnCreateTicket($message) {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->TicketsCreate($message);
        if( $this->validResponse($response) ) {
            $text = trans($this->msg_success) . "\n";
            $text .= trans($this->msg_number) . ": " . (int)$response["data"]["ticketid"];

            $keyboard = [
                [["text" => trans(self::$btnList)]],
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
    
    /**
     * @param $ticketID
     */
    public function btnOpenTicket($ticketID) {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->TicketsGetMessages((int)$ticketID);
        if( $this->validResponse($response) ) {
            if( !empty($response["data"]) ) {
                $this->setValue($this->cache_key_ticketID, (int)$ticketID);
                // "data":[{
                //  "unread":"0","messageid":"175","date":"2022-11-14 15:17:37","ticketid":"258","stuffid":"0","useruid":"1",
                //  "message":"", "fio":"", "user":"test", "login":""},
                //
                // {"unread":"1","messageid":"176","date":"2022-11-14 15:18:02","ticketid":"258","stuffid":"1","useruid":"0",
                // "message":"","fio":"", "user":"","login":"admin"},

                $title = [
                    trans($this->msg_ticket_date), trans($this->msg_ticket_who), trans($this->msg_ticket_message)
                ];
                $body = [];
                
                $limit = 10; $i = 0;
                rsort($response["data"]);
                foreach($response["data"] as $item) {
                    $from = ((int)$item["stuffid"] == 0) ? trans($this->msg_ticket_from_you) : trans($this->msg_ticket_from_support);
                    $body[] = [$item["date"], $from, $item["message"]];
                    $i++;
                    
                    if( $i >= $limit) {
                        break;
                    }
                }

                $text = Helper::generateMessage($title, $body);
                $keyboard = [
                    [["text" => trans(self::$btnAddMessage)]],
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
    
    /**
     *
     */
    public function btnEnterTicketAddMessage() {
        $this->setLastAction(__FUNCTION__);

        $text = trans($this->msg_add_message);
        $keyboard = [
            [["text" => trans("back")]],
        ];

        $this->buttonKeyboard($text, $keyboard);
    }

    /**
     *
     */
    public function btnTicketAddMessage($message) {
        $this->setLastAction(__FUNCTION__);

        $ticketID = $this->getValue($this->cache_key_ticketID);
        $response = $this->ClientAPI->TicketsSendMessage((int)$ticketID, (string)$message);
        if( $this->validResponse($response) ) {
            $text = trans($this->msg_add_success);
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