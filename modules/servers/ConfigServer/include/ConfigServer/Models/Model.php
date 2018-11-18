<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */

namespace ConfigServer\Models;

use ConfigServer\Clients\GuzzleClient;
use ConfigServer\ConfigServerAPIClient;
use const JSON_PRETTY_PRINT;

abstract class Model
{
    /** @var ConfigServerAPIClient */
    protected $apiClient;
    /** @var GuzzleClient */
    protected $httpClient;

    /**
     * Model constructor.
     * @param ConfigServerAPIClient $APIClient
     */
    public function __construct(ConfigServerAPIClient $APIClient)
    {
        $this->apiClient = $APIClient;
        $this->httpClient = $APIClient->getHttpClient();
    }

    /**
     * @param $input
     * @param GuzzleClient $httpClient
     */
    public static function parse($input, ConfigServerAPIClient $APIClient)
    {
    }

    public function __toString()
    {
        return \GuzzleHttp\json_encode($this, JSON_PRETTY_PRINT);
    }
}