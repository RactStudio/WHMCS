<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
namespace ConfigServer\Models\Products;

use ConfigServer\ConfigServerAPIClient;
use ConfigServer\Models\Licenses\License;
use ConfigServer\Models\Model;

class Product extends Model
{
    public $id;
    public $type;
    public $fullName;
    public $osType;
    public $cost;
    public $discount;
    public $osOptions;
    public $installationHelp;


    public function __construct($id, ConfigServerAPIClient $APIClient)
    {
        $this->id = $id;
        parent::__construct($APIClient);
    }

    public static function parse($input, ConfigServerAPIClient $APIClient)
    {
        $obj = new self($input->id, $APIClient);
        $obj->type = $input->type;
        $obj->fullName = $input->fullname;
        $obj->osType = $input->ostype;
        $obj->cost = $input->cost;
        $obj->discount = $input->discount;
        $obj->osOptions = $input->osOptions;
        $obj->installationHelp = $input->installationHelp;
        return $obj;
    }

    public function priceWithDiscount($cycle)
    {
        $price = round($this->cost->{$cycle} * (1 - ($this->discount / 100)), 2);
        return number_format((float)$price, 2, '.', '');
    }

    /**
     * @param $ipAddress
     * @return bool|License
     * @throws \ConfigServer\APIException
     */
    public function order($ipAddress, $cycle, $os)
    {
        $data = [
            'ip' => $ipAddress,
            'cycle' => $cycle,
            'os' => $os,
        ];
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->post('products/'.$this->id.'/add', [
            'form_params' => $data,
        ]));
        if ($response->success) {
            return $this->apiClient->licenses()->get($response->licenseId);
        }
        return false;
    }
}
