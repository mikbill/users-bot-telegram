<?php

namespace App\Services\Telegram;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Class ClientAPI
 * @package App\Services\Telegram
 */
class ClientAPI
{
    private $secret_key = null;
    private $jwt_token = null;
    private $client = null;

    /**
     * ClientAPI constructor.
     * @param $host
     * @param null $secret_key
     */
    public function __construct($host, $secret_key = null)
    {
        $this->secret_key = $secret_key;

        $this->client = new Client([
            'base_uri' => $host,
            'verify'   => false
        ]);
    }

    /**
     * @param $token
     */
    public function setJWT($token)
    {
        $this->jwt_token = $token;
    }


    /**
     * Поиск в биллинге абонента по user_id telegram`a
     *
     * p.s. Запрос необходимо подписывать. Не пользовательское API
     *
     * @param $value
     * @param string $key
     * @return bool|mixed
     */
    public function searchUser($value, $key = 'user_id')
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
     * @param $user_id
     * @param $uid
     * @return bool|mixed
     */
    public function bindUser($user_id, $uid)
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
     * @param $uid
     * @return bool|mixed
     */
    public function getUserToken($uid)
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
     * @param $login
     * @param $pass
     * @return mixed
     */
    public function auth($login, $pass)
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
     * @param $phone
     * @return bool|mixed
     */
    public function preAuth($phone)
    {
        $params = [
            'phone' => $phone,
        ];

        return $this->sendRequest('/api/v1/cabinet/preauth/phone', 'POST', $params);
    }

    /**
     * Авторизация по логину/паролю
     *
     * @param $phone
     * @return bool|mixed
     */
    public function authLoginPassword($login, $password)
    {
        $params = [
            'login' => $login,
            'password' => $password
        ];

        return $this->sendRequest('/api/v1/cabinet/auth/login', 'POST', $params);
    }

    /**
     * Авторизация по телефону
     *
     * @param $phone
     * @return bool|mixed
     */
    public function authPhone($phone)
    {
        $params = [
            'phone' => $phone,
        ];

        return $this->sendRequest('/api/v1/cabinet/auth/phone', 'POST', $params);
    }

    /**
     * Ввод кода отп для авторизации по телефону
     *
     * @param $otp
     * @return bool|mixed
     */
    public function authPhoneOtpApply($otp)
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

        Log::debug("url:" . $uri);
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
