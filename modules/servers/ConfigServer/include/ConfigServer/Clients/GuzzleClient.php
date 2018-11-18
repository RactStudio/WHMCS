<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
namespace ConfigServer\Clients;

use ConfigServer\ConfigServerAPIClient;
use GuzzleHttp\Client;

class GuzzleClient extends Client
{
    /**
     *
     * @param ConfigServerAPIClient $client
     */
    public function __construct(ConfigServerAPIClient $client)
    {
        parent::__construct([
            'base_uri' => $client->getBaseUrl(),
            'exceptions' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $client->getApiToken(),
                'Content-Type' => 'application/json',
                'User-Agent' => 'configserver-php/' . ConfigServerAPIClient::VERSION . (strlen($client->getUserAgent() > 0) ? ' ' . $client->getUserAgent() : '')
            ],
            'verify' => false
        ]);
    }
}