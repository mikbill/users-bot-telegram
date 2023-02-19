<?php

namespace App\Models;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Class ClientAPI
 * @package App\Models
 */
class ClientAPI
{
    private $secret_key = null;
    private $jwt_token = null;
    private $client = null;

    /**
     * ClientAPI constructor.
     * @param string $host
     * @param null $secret_key
     */
    public function __construct(string $host, $secret_key = null)
    {
        $this->secret_key = $secret_key;

        $this->client = new Client([
            'base_uri' => $host,
            'verify'   => false
        ]);
    }

    /**
     * @param string $token
     */
    public function setJWT(string $token)
    {
        $this->jwt_token = $token;
    }


    /**
     * Поиск в биллинге абонента по user_id telegram`a
     *
     * p.s. Запрос необходимо подписывать. Не пользовательское API
     *
     * @param string $value
     * @param string $key
     * @return bool|mixed
     */
    public function searchUser(string $value, string $key = 'user_id')
    {
        $params = [
            'field' => $key,
            'value' => $value
        ];

        return $this->sendRequest('/api/v1/billing/users/search', 'POST', $params, true);
    }


    /**
     * Привязка абонента к user_id telegram`a
     *
     * p.s. Запрос необходимо подписывать. Не пользовательское API
     *
     * @param int $user_id
     * @param int $uid
     * @return bool|mixed
     */
    public function bindUser(int $user_id, int $uid)
    {
        $params = [
            'user_id' => $user_id,
            'uid'     => $uid,
        ];

        return $this->sendRequest('/api/v1/billing/users/bind', 'POST', $params, true);
    }


    /**
     * Получим JWT токен для работы пользовательским API
     *
     * p.s. Запрос необходимо подписывать. Не пользовательское API
     *
     * @param int $uid
     * @return bool|mixed
     */
    public function getUserToken(int $uid)
    {
        $params = [
            "uid" => $uid
        ];
        $response = $this->sendRequest('/api/v1/billing/users/token', 'POST', $params, true);

        // Если пришел токен пропишем его
        if (isset($response['data']['token'])) {
            $this->setJWT($response['data']['token']);
        }

        return $response;
    }


    /**
     * Получить информацию об абоненте
     *
     * @return bool|mixed
     */
    public function getUser()
    {
        return $this->sendRequest('/api/v1/cabinet/user', 'GET');
    }

    /**
     * Авторизация пользователя
     *
     * @param string $login
     * @param string $pass
     * @return mixed
     */
    public function auth(string $login, string $pass)
    {
        $params = [
            'login'    => $login,
            'password' => $pass,
        ];

        return $this->sendRequest('/api/v1/cabinet/auth/login', 'POST', $params);
    }

    /**
     * Получить пользователя по номеру телефона
     *
     * @param string $phone
     * @return bool|mixed
     */
    public function preAuth(string $phone)
    {
        $params = [
            'phone' => $phone,
        ];

        return $this->sendRequest('/api/v1/cabinet/preauth/phone', 'POST', $params);
    }

    /**
     * Авторизация по телефону
     *
     * @param string $phone
     * @return bool|mixed
     */
    public function authPhone(string $phone)
    {
        $params = [
            'phone' => $phone,
        ];

        return $this->sendRequest('/api/v1/cabinet/auth/phone', 'POST', $params);
    }

    /**
     * Ввод кода отп для авторизации по телефону
     *
     * @param string $otp
     * @return bool|mixed
     */
    public function authPhoneOtpApply(string $otp)
    {
        $params = [
            'otp' => $otp,
        ];

        return $this->sendRequest('/api/v1/cabinet/auth/phone/otp', 'POST', $params);
    }


    /**
     * Получить новости
     * @return false|mixed
     */
    public function getNews() {
        return $this->sendRequest('/api/v1/cabinet/news', 'GET');
    }

    /**
     * Получить список всех тикетов
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function TicketsList() {
        return $this->sendRequest('/api/v1/cabinet/tickets', 'GET');
    }

    /**
     * Создать новый тикет.
     * @param string $message Текст сообщения
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function TicketsCreate(string $message = "") {
        $params = [
            'message' => $message,
        ];
        
        return $this->sendRequest('/api/v1/cabinet/tickets', 'POST', $params);
    }

    /**
     * Получить сообщения тикета по ID
     * @param int $ticketID
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function TicketsGetMessages(int $ticketID) {
        return $this->sendRequest('/api/v1/cabinet/tickets/' . (int)$ticketID, 'GET');
    }

    /**
     * Добавить сообщение в тикет
     * @param int $ticketID
     * @param string $message
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function TicketsSendMessage(int $ticketID, string $message) {
        $params = [
            'message' => $message,
        ];
        
        return $this->sendRequest('/api/v1/cabinet/tickets/' . (int)$ticketID, 'POST', $params);
    }

    /**
     * Список доступных тарифов для смены
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function TarifsList() {
        return $this->sendRequest('/api/v1/cabinet/packets', 'GET');
    }

    /**
     * Получить информацию по ID тарифа
     * @param int $id
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function TarifsInfo(int $id) {
        return $this->sendRequest('/api/v1/cabinet/packets/' . (int)$id, 'GET');
    }

    /**
     * Карточка пользователя
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function UserInfo() {
        return $this->sendRequest('/api/v1/cabinet/user', 'GET');
    }

    /**
     * Изменить данные абонента пользователя
     * Дата рожденья
     * @param string $value
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function UserEditDateBirth(string $value) {
        $params = [
            'date_birth' => $value,
        ];
        
        return $this->sendRequest('/api/v1/cabinet/user', 'POST', $params);
    }

    /**
     * Изменить данные абонента пользователя
     * Домашний телефон
     * @param string $value
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function UserEditPhone(string $value) {
        $params = [
            'phone' => $value,
        ];

        return $this->sendRequest('/api/v1/cabinet/user', 'POST', $params);
    }

    /**
     * Изменить данные абонента пользователя
     * Мобильный телефон
     * @param string $value
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function UserEditMobTel(string $value) {
        $params = [
            'mob_tel' => $value,
        ];

        return $this->sendRequest('/api/v1/cabinet/user', 'POST', $params);
    }

    /**
     * Изменить данные абонента пользователя
     * СМС телефон
     * @param string $value
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function UserEditSMSTel(string $value) {
        $params = [
            'sms_tel' => $value,
        ];

        return $this->sendRequest('/api/v1/cabinet/user', 'POST', $params);
    }

    /**
     * Изменить данные абонента пользователя
     * Email
     * @param string $value
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function UserEditEmail(string $value) {
        $params = [
            'email' => $value,
        ];

        return $this->sendRequest('/api/v1/cabinet/user', 'POST', $params);
    }

    /**
     * Напомнить пароль
     * @param string $value
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function UserPasswordRestore(string $phone) {
        $params = [
            'phone' => $phone,
        ];
        
        return $this->sendRequest('/api/v1/cabinet/user/password/restore', 'POST', $params);
    }

    /**
     * Изменить пароль
     * @param string $old_password
     * @param string $new_password
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function UserPasswordChange(string $old_password, string $new_password) {
        $params = [
            'password' => $old_password,
            'password_new' => $new_password,
        ];

        return $this->sendRequest('/api/v1/cabinet/user/password/change', 'POST', $params);
    }

    /**
     * Сменить тарифный план абоненту
     * @param int $id ID тарифа на который переходим
     * @param int $from_next_month 0 перейти моментально, 1 перейти в начале нового месяца
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function UserTarifChange(int $id, int $from_next_month = 0) {
        $params = [
            'gid' => $id,
            'from_next_month' => $from_next_month,
        ];

        return $this->sendRequest('/api/v1/cabinet/user/packet', 'POST', $params);
    }

    /**
     * Получить информацию по услуге ТУРБО
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function TurboInfo() {
        return $this->sendRequest('/api/v1/cabinet/user/services/turbo', 'GET');
    }

    /**
     * Активировать услугу ТУРБО
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function TurboActivate() {
        return $this->sendRequest('/api/v1/cabinet/user/services/turbo', 'POST');
    }

    /**
     * Получить информацию по услуге ЗАМОРОЗКА
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function FreezeInfo() {
        return $this->sendRequest('/api/v1/cabinet/user/services/freeze', 'GET');
    }

    /**
     * Активировать услугу ЗАМОРОЗКА
     * @param string $date_start
     * @param string $date_stop
     * @param int $freeze_do_ever
     * @param int $fixed_month_num
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function FreezeActivate(string $date_start, string $date_stop, int $freeze_do_ever, int $fixed_month_num) {
        $params = [
            'activate' => 1,
            'date_start' => $date_start,
            'date_stop' => $date_stop,
            'freeze_do_ever' => $freeze_do_ever,
            'fixed_month_num' => $fixed_month_num,
        ];
        
        return $this->sendRequest('/api/v1/cabinet/user/services/freeze', 'POST', $params);
    }

    /**
     * Деактивировать услугу ЗАМОРОЗКА
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function FreezeDeactivate() {
        $params = [
            'activate' => 0,
        ];

        return $this->sendRequest('/api/v1/cabinet/user/services/freeze', 'POST', $params);
    }

    /**
     * Получить информацию по услуге RealIP
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function RealIpInfo() {
        return $this->sendRequest('/api/v1/cabinet/user/services/realip', 'GET');
    }

    /**
     * Активировать услугу RealIP
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function RealIpActivate() {
        $params = [
            'activate' => 1,
        ];

        return $this->sendRequest('/api/v1/cabinet/user/services/realip', 'POST', $params);
    }

    /**
     * Деактивировать услугу RealIP
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function RealIpDeactivate() {
        $params = [
            'activate' => 0,
        ];

        return $this->sendRequest('/api/v1/cabinet/user/services/realip', 'POST', $params);
    }

    /**
     * Получить информацию по услуге кредит
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function CreditInfo() {
        return $this->sendRequest('/api/v1/cabinet/user/services/credit', 'GET');
    }

    /**
     * Активировать услугу кредит
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function CreditActivate() {
        return $this->sendRequest('/api/v1/cabinet/user/services/credit', 'POST');
    }

    /**
     * История платежей за указанный период
     * @param string $from_date
     * @param string $to_date
     * @param int $limit Кол-во строк которое необходимо отобразить
     * @param int $offset Кол-во которое необходимо пропустить
     * @param string $sort DESC|DESC
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function HistoryPayments(string $from_date, string $to_date, int $limit = 10, int $offset = 0, string $sort = "DESC") {
        $params = [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'limit' => $limit,
            'offset' => $offset,
            'sort' => $sort,
        ];
        
        return $this->sendRequest('/api/v1/cabinet/report/payments', 'POST', $params);
    }

    /**
     * История сессий за указанный период
     * @param string $from_date
     * @param string $to_date
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function HistorySessions(string $from_date, string $to_date) {
        $params = [
            'from_date' => $from_date,
            'to_date' => $to_date
        ];

        return $this->sendRequest('/api/v1/cabinet/report/sessions', 'POST', $params);
    }

    /**
     * Пополнение счета с помощью ваучера
     * @param string $series
     * @param string $number
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function useVoucher(string $series, string $number) {
        $params = [
            'series' => $series,
            'number' => $number
        ];

        return $this->sendRequest('/api/v1/cabinet/payments/voucher', 'POST', $params);
    }

    /**
     * Настройки кабинета
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getConfig() {
        return $this->sendRequest('/api/v1/cabinet/config', 'GET');
    }
    
    /**
     * @param $uri
     * @param string $method
     * @param array $params
     * @param false $sign
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function sendRequest($uri, $method = 'POST', $params = [], $sign = false)
    {
        $headers = [];

        if ($sign) {
            $salt = uniqid();
            $params['salt'] = $salt;
            $params['sign'] = hash_hmac('sha512', $salt, $this->secret_key);
        } else {
            $headers['Authorization'] = $this->jwt_token;
        }

        $res = $this->client->request($method, $uri, [
            'form_params' => $params,
            'headers'     => $headers
        ]);

        Log::debug("url: {$method} {$uri}");
        Log::debug("form_params:" . json_encode($params));
        Log::debug("headers:" . json_encode($headers));

        if ($res->getStatusCode() == 200) { // 200 OK
            $response = json_decode($res->getBody()->getContents(), true);
            Log::debug("response:" . json_encode($response));
            return $response;
        } else {
            Log::debug("response error: status code=" . $res->getStatusCode());
        }

        return false;
    }

}
