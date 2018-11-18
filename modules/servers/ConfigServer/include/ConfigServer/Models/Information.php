<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */

namespace ConfigServer\Models;
use ConfigServer\ConfigServerAPIClient;

class Information extends Model
{
    public $total_licenses;
    public $credit;
    public $exchangeRateRial;
    public $exchangeRateToman;
    public $discount;
    public $partnerLevel;
    public $email;
    public $dedicatedLink;
    public static function parse($input, ConfigServerAPIClient $APIClient)
    {
        $obj = new self($APIClient);
        $obj->total_licenses = $input->TotalLicenses;
        $obj->credit = $input->Credit;
        $obj->exchangeRateRial = $input->ExchangeRateRial;
        $obj->exchangeRateToman = $input->ExchangeRateToman;
        $obj->discount = $input->MonthlyDiscount;
        $obj->partnerLevel = $input->PartnerLevel;
        $obj->email = $input->Email;
        $obj->dedicatedLink = $input->LinkDedicated ? $input->LicenseDomain : false;
        return $obj;
    }
}