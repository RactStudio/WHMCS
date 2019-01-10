<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
namespace ConfigServer\Models\Licenses;

use ConfigServer\ConfigServerAPIClient;
use ConfigServer\Models\Model;
use function property_exists;
use function strtolower;

class License extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_EXPIRED = 'expired';

    public $id;
    public $productId;
    public $status;
    public $renewDate;
    public $hostname;
    public $licenseKey;
    public $type;
    public $ostype;
    public $cycle;
    public $ip;
    public $os;
    public $kernel;
    public $changeIP;
    public $autoRenew;
    public $notes;
    public $suspendedReason;

    private $product;

    public function __construct($id, ConfigServerAPIClient $APIClient)
    {
        $this->id = $id;
        parent::__construct($APIClient);
    }

    /**
     * @return bool|\ConfigServer\Models\Products\Product
     * @throws \ConfigServer\APIException
     */
    public function product()
    {
        if (!$this->product) {
            $this->product = $this->apiClient->products()->get($this->productId);
        }
        return $this->product;
    }

    public function remainingDays($full = false){
        $timeLeft = strtotime($this->renewDate) - time();
        if($full){
            if($timeLeft < 0){
                return '-';
            }
            $days = floor($timeLeft / 86400);
            $hours = floor(($timeLeft % 86400) / 3600);
            $minutes = floor(($timeLeft % 3600) / 60);
            $seconds = $timeLeft % 60;

            return sprintf(
                '%sd + %s:%s:%sh', $days, 
                $hours < 10 ? '0'.$hours : $hours, 
                $minutes < 10 ? '0'.$minutes : $minutes, 
                $seconds < 10 ? '0'.$seconds : $seconds
            );
        }
        return ceil($timeLeft / 86400);
    }

    /**
     * @param $status
     * @return bool
     * @throws \ConfigServer\APIException
     */
    public function changeStatus($status)
    {
        $data = [
            'status' => $status,
        ];
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->post('licenses/' . $this->id . '/changeStatus', [
            'form_params' => $data,
        ]));
        if (property_exists($response, 'success') && $response->success) {
            $this->status = strtolower($response->status);
            return true;
        }
        return false;
    }

    /**
     * @param $newIP
     * @return bool
     * @throws \ConfigServer\APIException
     */
    public function changeIP($newIP, $force = false)
    {
        $data = [
            'newIP' => $newIP,
            'force' => $force,
        ];
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->post('licenses/' . $this->id . '/changeIP', [
            'form_params' => $data,
        ]));

        if (property_exists($response, 'success') && $response->success) {
            $this->ip = $newIP;
            $this->changeIP++;
            return $response;
        }
        return $response->approveRequired ? 'approvedRequired' : false;
    }

    /**
     * @param $cycle
     * @return bool
     * @throws \ConfigServer\APIException
     */
    public function changeCycle($cycle)
    {
        $data = [
            'cycle' => $cycle,
        ];
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->post('licenses/' . $this->id . '/changeCycle', [
            'form_params' => $data,
        ]));
        if (property_exists($response, 'success') && $response->success) {
            $this->cycle = $response->cycle;
            return true;
        }
        return false;
    }

    /**
     * @param $cycle
     * @return bool
     * @throws \ConfigServer\APIException
     */
    public function updateNotes($notes)
    {
        $data = [
            'notes' => $notes,
        ];
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->post('licenses/' . $this->id . '/updateNotes', [
            'form_params' => $data,
        ]));
        if (property_exists($response, 'success') && $response->success) {
            $this->notes = $response->notes;
            return true;
        }
        return false;
    }

    /**
     * @param $cycle
     * @return bool
     * @throws \ConfigServer\APIException
     */
    public function changeOS($os)
    {
        $data = [
            'os' => $os,
        ];
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->post('licenses/' . $this->id . '/changeOS', [
            'form_params' => $data,
        ]));
        if (property_exists($response, 'success') && $response->success) {
            $this->os = $response->os;
            return true;
        }
        return false;
    }

    /**
     * @param $status
     * @return bool
     * @throws \ConfigServer\APIException
     */
    public function changeAutoRenew($status)
    {
        $data = [
            'status' => $status ? 'on' : 'off',
        ];
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->post('licenses/' . $this->id . '/autoRenew', [
            'form_params' => $data,
        ]));
        if (property_exists($response, 'success') && $response->success) {
            $this->autoRenew = (bool)$response->autoRenew;
            return true;
        }
        return false;
    }

    /**
     * @throws \ConfigServer\APIException
     */
    public function renew()
    {
        $response = ConfigServerAPIClient::checkResponse($this->httpClient->get('licenses/' . $this->id . '/renew'));
        if (property_exists($response, 'success') && $response->success) {
            $this->renewDate = $response->renewDate;
            $this->status = strtolower($response->status);
            return $response;
        }
        return false;
    }

    public function renewDate($includeHour = false){
        return date('Y-m-d' . ($includeHour ? ' H:i' : null), strtotime($this->renewDate));
    }

    public static function parse($input, ConfigServerAPIClient $APIClient)
    {
        $obj = new self($input->id, $APIClient);
        $obj->productId = $input->productId;
        $obj->status = strtolower($input->status);
        $obj->renewDate = $input->renewDate;
        $obj->cycle = $input->cycle;
        $obj->type = $input->type;
        $obj->ostype = $input->ostype;
        $obj->hostname = $input->hostname;
        $obj->licenseKey = $input->licenseKey;
        $obj->changeIP = $input->changeip;
        $obj->kernel = $input->kernel;
        $obj->autoRenew = $input->autoRenew;
        $obj->ip = $input->ip;
        $obj->os = $input->os;
        $obj->notes = $input->notes;
        $obj->suspendedReason = $input->suspendedReason;
        return $obj;
    }
}