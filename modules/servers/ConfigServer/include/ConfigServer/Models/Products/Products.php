<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
namespace ConfigServer\Models\Products;

use ConfigServer\ConfigServerAPIClient;
use ConfigServer\Models\Model;

class Products extends Model
{
    /**
     * @throws \ConfigServer\APIException
     */
    public function all()
    {
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->post('products'));
        $result = [];
        foreach ($response->products as $product) {
            $result[$product->id] = Product::parse($product, $this->apiClient);
        }
        return $result;
    }

    /**
     * @param $id
     * @return bool|Product
     * @throws \ConfigServer\APIException
     */
    public function get($id)
    {
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->get('products/' . (int)$id));
        if (sizeof($response->products)) {
            return Product::parse($response->products[0], $this->apiClient);
        }
        return false;
    }
}