<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */

namespace ConfigServer\Models\Licenses;

use ConfigServer\ConfigServerAPIClient;
use ConfigServer\Models\Model;

class Licenses extends Model
{
    /**
     * @param null $ip
     * @param null $status
     * @return array
     * @throws \ConfigServer\APIException
     */
    public function all($criteria = [])
    {
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->post('licenses', [
            'form_params' => $criteria,
        ]));
        $result = [];
        foreach ($response->licenses as $license) {
            $result[] = License::parse($license, $this->apiClient);
        }
        return $result;
    }

    /**
     * @param $licenseID
     * @return bool|License
     * @throws \ConfigServer\APIException
     */
    public function get($licenseID)
    {
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->get('licenses/' . $licenseID));
        if (sizeof($response->licenses)) {
            return License::parse($response->licenses[0], $this->apiClient);
        }
        return false;
    }
}