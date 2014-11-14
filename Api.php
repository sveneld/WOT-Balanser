<?php

/**
 * Created by PhpStorm.
 * User: Sveneld
 * Date: 03.10.14
 * Time: 21:08
 */
class Api
{

    private $apiUrl = 'http://api.worldoftanks.ru/wot/';
    private $apiLanguage = 'ru';
    private $apiAplicationId = '59e9a836ada444dc468fc6d66bf24856';
    private $apiAccessToken = '';

    public function getData(DataContainer $params)
    {
        $key = $params->class . '/' . $params->method;
        $result = Cache::getInstance()->get($key, md5(json_encode($params->params)));
        if ($result) {
            return $result;
        }

        $url = $this->apiUrl . '' . $key . '/?application_id=' . $this->apiAplicationId;
        if (isset($params->params)) {
            foreach ($params->params as $field => $value) {
                $url .= '&' . $field . '=' . $value;
            }
        }
        if (!empty($this->apiAccessToken)) {
            $url .= '&access_token=' . $this->apiAccessToken;
        }
        $url .= '&language=' . $this->apiLanguage;
        if (!empty($params->redirect)) {
            header('Location: ' . $url);
            exit();
        } else {
            $result = $this->sendRequest($url);
            $resultDecoded = json_decode($result);
            if ($resultDecoded->status == 'error'){
                throw new Exception($resultDecoded->error->message, 0);
            }else{
                Cache::getInstance()->set($key, md5(json_encode($params->params)), $result);
            }
            return $resultDecoded;
        }
    }

    public function sendRequest($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function setAccessToken($accessToken)
    {
        $this->apiAccessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return $this->apiAccessToken;
    }

}