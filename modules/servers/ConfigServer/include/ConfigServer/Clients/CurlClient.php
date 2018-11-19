<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
namespace ConfigServer\Clients;

use ConfigServer\ConfigServerAPIClient;

class CurlClient
{
    private $client, $curl;
    public function __construct(ConfigServerAPIClient $client)
    {
        $this->client = $client;
        $this->curl = curl_init();
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $client->getApiToken(),
        );
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
    }

    public function get($url)
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->client->getBaseUrl() . $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "GET");
        return new CurlResponse($this->curl);
    }

    public function post($url, $params = [])
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->client->getBaseUrl() . $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        return new CurlResponse($this->curl);
    }
}