<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
require_once __DIR__ . "/include/bootstrap.php";
use WHMCS\Database\Capsule;
use ConfigServer\APIException;
use ConfigServer\Models\Licenses\License;
use ConfigServer\PHPView;

function ConfigServer_MetaData()
{
    return array(
        'DisplayName' => 'ConfigServer',
        'APIVersion' => '1.0',
        'RequiresServer' => true,
    );
}

function ConfigServer_TestConnection(array $params)
{
    global $_LANG;
    if (empty($params['serveraccesshash'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'pleaseEnterAPIToken');
    }
    try {
        $client = ConfigServer_getClientByParams($params);
        if ($client->ping()) {
            return ['success' => true, 'error' => null];
        }
    } catch (APIException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
    return [
        'success' => false,
        'error' => ConfigServer_getLocale($_LANG['locale'], 'unknownError')
    ];
}

function ConfigServer_ConfigOptions()
{
    global $_LANG;
    $t = function ($x) use ($_LANG) {
        return ConfigServer_getLocale($_LANG['locale'], $x);
    };
    return array(
        'Product' => array(
            "FriendlyName" => $t('Product'),
            'Type' => 'dropdown',
            'Loader' => 'ConfigServer_loadProducts',
            'SimpleMode' => true,
        ),
        'allowChnageIP' => array(
            "FriendlyName" => $t('allowChnageIP'),
            "Type" => "yesno",
            "Options" => [
                0 => "No",
                1 => "Yes",
            ],
            "Description" => $t('tickToEnable'),
            'SimpleMode' => true,
        ),
    );
}

function ConfigServer_loadProducts(array $params)
{
    $client = ConfigServer_getClientByParams($params);
    try {
        $products = $client->products()->all();
        $result = [];
        foreach ($products as $product) {
            /** @var $product ConfigServer\Models\Products\Product */
            $result[$product->id] = $product->fullName;
        }
        return $result;
    } catch (APIException $e) {
        return ['Failed to load products: ' . $e->getMessage()];
    }
}

function ConfigServer_getServerIdByParams(array $params){
    if(isset($params['addonId']) && $params['addonId'] > 0){
        $version = (int)str_replace('.', null, explode("-", $params['whmcsVersion'])[0]);
        if($version < 745){
            $server = Capsule::table('tblservers')->where('type', 'ConfigServer')->first();
            return $server->id;
        }
    }
    if(isset($params['serveraccesshash'])){
        return $params['serverid'];
    }
}

function ConfigServer_getClientByParams(array $params){
    if(isset($params['addonId']) && $params['addonId'] > 0){
        $version = (int)str_replace('.', null, explode("-", $params['whmcsVersion'])[0]);
        if($version < 745){
            $server = Capsule::table('tblservers')->where('type', 'ConfigServer')->first();
            return ConfigServer_getClient($server->accesshash);
        }
    }
    if(isset($params['serveraccesshash'])){
        return ConfigServer_getClient($params['serveraccesshash']);
    }
}

function ConfigServer_CreateAccount(array $params)
{
    global $_LANG;
    $client = ConfigServer_getClientByParams($params);
    if (!array_key_exists('IP', $params['customfields'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'ipFieldNotFound');
    }
    if (array_key_exists('licenseId', $params['customfields']) && !empty($params['customfields']['licenseId'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'licenseAlreadyAssigned');
    }
    if (empty($params['customfields']['IP'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'ipAddressEmpty');
    }
    if($params['addonId'] > 0){
        $addon = Capsule::table('tblhostingaddons')->where('id', $params['addonId'])->first();
    } else {
        try {
            $service = \WHMCS\Service\Service::findOrFail($params['serviceid']);
        } catch (Exception $e) {
            return 'failure';
        }
    }
    $type = $params['addonId'] > 0 ? 'addon' : 'product';
    $serviceId = $type == 'addon' ? $params['addonId'] : $params['serviceid'];
    try {
        $product = $client->products()->get($params['configoption1']);
        if($params['addonId']>0){
            $cycle = lcfirst($addon->billingcycle);
        } else {
            $cycle = lcfirst($service->billingcycle);
        }
        $response = $product->order($params['customfields']['IP'], $cycle);
            if ($response){
                $customField = Capsule::table('tblcustomfields')
                    ->where('type', $type)
                    ->where('relid', $type == 'addon' ? $addon->addonid : $params['pid'])
                    ->where('fieldname', 'licenseId')
                    ->first(['id']);
            if ($customField) {
                $customFieldValueExists = Capsule::table('tblcustomfieldsvalues')
                    ->where('relid', $serviceId)
                    ->where('fieldid', $customField->id)->count() > 0;
                if ($customFieldValueExists) {
                    Capsule::table('tblcustomfieldsvalues')
                        ->where('relid', $serviceId)
                        ->where('fieldid', $customField->id)
                        ->update(['value' => $response->id]);
                } else {
                    Capsule::table('tblcustomfieldsvalues')->insert([
                        'relid' => $serviceId,
                        'fieldid' => $customField->id,
                        'value' => $response->id,
                    ]);
                }
            }
            if($type == 'product'){
                /** @var $server Server */
                $params['model']->serviceProperties->save([
                    'domain' => $params['customfields']['IP'],
                ]);
            }
            return 'success';
        }
    } catch (APIException $e) {
        return $e->getMessage();
    }
    return ConfigServer_getLocale($_LANG['locale'], 'failedUnknownError');
}

function ConfigServer_Renew(array $params)
{
    global $_LANG;
    if (empty($params['customfields']['licenseId'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'noLicenseAssigned');
    }
    $client = ConfigServer_getClientByParams($params);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotFound');
        }
        if($params['addonId']>0){
            $addon = Capsule::table('tblhostingaddons')->where('id', $params['addonId'])->first();
        } else {
            try {
                $service = \WHMCS\Service\Service::findOrFail($params['serviceid']);
            } catch (Exception $e) {
                return 'failure';
            }
        }
        if($params['addonId']>0){
            $cycle = lcfirst($addon->billingcycle);
        } else {
            $cycle = lcfirst($service->billingcycle);
        }
        $license->changeCycle($cycle);
        if ($license->renew()) {
            return 'success';
        }
    } catch (APIException $e) {
        return $e->getMessage();
    }
    return ConfigServer_getLocale($_LANG['locale'], 'failedUnknownError');
}

function ConfigServer_SuspendAccount(array $params)
{
    global $_LANG;
    if (empty($params['customfields']['licenseId'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'noLicenseAssigned');
    }
    $client = ConfigServer_getClientByParams($params);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotFound');
        }
        if ($license->status == License::STATUS_ACTIVE) {
            if ($license->changeStatus(License::STATUS_SUSPENDED)) {
                return 'success';
            }
        } else if($license->status == License::STATUS_SUSPENDED){
            return 'success';
        } else {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotActive');
        }
    } catch (APIException $e) {
        return $e->getMessage();
    }
    return ConfigServer_getLocale($_LANG['locale'], 'failedUnknownError');
}

function ConfigServer_UnsuspendAccount(array $params)
{
    global $_LANG;
    if (empty($params['customfields']['licenseId'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'noLicenseAssigned');
    }
    $client = ConfigServer_getClientByParams($params);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotFound');
        }
        if ($license->status == License::STATUS_ACTIVE) {
            return 'success';
        }
        if ($license->status == License::STATUS_SUSPENDED) {
            if(strtotime($license->renewDate) < time()){
                if($params['addonId']>0){
                    $addon = Capsule::table('tblhostingaddons')->where('id', $params['addonId'])->first();
                    $cycle = lcfirst($addon->billingcycle);
                } else {
                    try {
                        $service = \WHMCS\Service\Service::findOrFail($params['serviceid']);
                    } catch (Exception $e) {
                        return 'failure';
                    }
                    $cycle = lcfirst($service->billingcycle);
                }
                $license->changeCycle($cycle);
                $license->renew();
                return 'success';
            }
            if ($license->changeStatus(License::STATUS_ACTIVE)) {
                return 'success';
            }
        } else {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotSuspended');
        }
    } catch (APIException $e) {
        return $e->getMessage();
    }
    return ConfigServer_getLocale($_LANG['locale'], 'failedUnknownError');
}

function ConfigServer_ChangeIPAdmin(array $params)
{
    global $_LANG;
    if (empty($params['customfields']['licenseId'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'noLicenseAssigned');
    }
    $client = ConfigServer_getClientByParams($params);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotFound');
        }
        if ($license->status != License::STATUS_ACTIVE) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseMustBeActiveToChangeIP');
        }
        if ($params['customfields']['IP'] == $license->ip) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNewIPIsSameAsBefore');
        }
        $result = $license->changeIP($params['customfields']['IP'], true);
        if ($result)
            return 'success';
    } catch (APIException $e) {
        return $e->getMessage();
    }
    return ConfigServer_getLocale($_LANG['locale'], 'failedUnknownError');
}

function ConfigServer_renderTemplate($template, $vars){
    return PHPView::render(__DIR__ . '/include/templates/' . $template, $vars);
}

function ConfigServer_ClientArea(array $params)
{
    global $_LANG;
    $client = ConfigServer_getClientByParams($params);
    try {
        $t = function ($x) use ($_LANG) {
            return ConfigServer_getLocale($_LANG['locale'], $x);
        };

        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license)
            return ConfigServer_getLocale($_LANG['locale'], 'noLicenseAssigned');

    } catch (APIException $e) {
        return $e->getMessage();
    }
    $vars = [];
    $vars['license'] = $license;
    $vars['allowChangeIP'] = $params['configoption2'] == 'on';
    $vars['serviceId'] = $params['serviceid'];
    $vars['installationHelp'] = [];

    if ($license->status == 'active' && isset($_REQUEST['modop'], $_REQUEST['a']) && $_REQUEST['modop'] == 'custom') {
        try {
            switch ($_REQUEST['a']) {
                case 'changeIP':
                    if($license->changeIP < 3 && isset($_POST['newIP']) && ($response = $license->changeIP($_POST['newIP']))){
                        $vars['success'] = 'IP address was changed successfully.';
                    }
                    break;
            }
        } catch (APIException $e){
            $vars['error'] = $e->getMessage();
        }
    }

    $information = $client->information();
    foreach($license->product()->installationHelp as $os => $commands){
        $commands = trim($commands);
        if($information->dedicatedLink){
            $commands = preg_replace('/([A-Za-z0-9]+)\.configserver\.pro/', $information->dedicatedLink, $commands);
        }
        $vars['installationHelp'][] = (object)['os' => $os, 'commands' => $commands];
    }
    if(is_file(__DIR__ . '/include/templates/clientarea_custom.php')){
        return ConfigServer_renderTemplate('clientarea_custom', $vars);
    }
    return ConfigServer_renderTemplate('clientarea', $vars);
}

function ConfigServer_AdminServicesTabFields(array $params)
{
    global $_LANG;
    try {
        $t = function ($x) use ($_LANG) {
            return ConfigServer_getLocale($_LANG['locale'], $x);
        };
    } catch (APIException $e) {
        return [
            'License info' => 'Failed to process: ' . $e->getMessage(),
        ];
    }
    if (empty($params['customfields']['licenseId'])) {
        return [
            $t('LicenseInfo') => ConfigServer_getLocale($_LANG['locale'], 'noLicenseAssigned'),
        ];
    }
    $client = ConfigServer_getClientByParams($params);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
    } catch (APIException $e) {
        return [
            $t('LicenseInfo') => 'Failed to process: ' . $e->getMessage(),
        ];
    }
    if(!$license){
        return [
            $t('LicenseInfo') => 'No info was found for this license.',
        ];
    }
    $serverId = ConfigServer_getServerIdByParams($params);
    try {
        $product = $license->product();
        return [
            $t('product') => $product->fullName,
            $t('ipAddress') => sprintf('%s (%s)', $license->ip, $license->hostname),
            $t('status') => $t($license->status),
            $t('licenseKey') => $license->licenseKey,
            $t('renewDate') => $license->renewDate . ' (' . $license->remainingDays() . ' ' . $t('days') . ')',
            $t('cost') => sprintf('%s$ (%s)', $product->priceWithDiscount($license->cycle), $t($license->cycle)),
            $t('numberOfIPChanges') => $license->changeIP.'/3',
            $t('licenseDetails') => '<a href="addonmodules.php?module=ConfigServer&serverId='.$serverId.'&licenseId='.$license->id.'" target="_blank">» '.$t('licenseDetails').'</a>',
        ];
    } catch (APIException $e) {
        return [
            $t('LicenseInfo') => 'Failed to process: ' . $e->getMessage(),
        ];
    }
}

function ConfigServer_TerminateAccount(array $params){
    global $_LANG;
    if (empty($params['customfields']['licenseId'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'noLicenseAssigned');
    }
    $client = ConfigServer_getClientByParams($params);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotFound');
        }
        $type = $params['addonId'] > 0 ? 'addon' : 'product';
        $serviceId = $type == 'addon' ? $params['addonId'] : $params['serviceid'];
        if($params['addonId']>0){
            $relid = Capsule::table('tblhostingaddons')->where('id', $params['addonId'])->first()->addonid;
        } else {
            $relid = $params['pid'];
        }
        $customField = Capsule::table('tblcustomfields')
            ->where('type', $type)
            ->where('relid', $relid)
            ->where('fieldname', 'licenseId')
            ->first(['id']);
        if ($customField) {
            Capsule::table('tblcustomfieldsvalues')->where('relid', $serviceId)->where('fieldid', $customField->id)->delete();
        }
        return 'success';
    } catch (APIException $e) {
        return $e->getMessage();
    }
    return ConfigServer_getLocale($_LANG['locale'], 'failedUnknownError');
}

function ConfigServer_SyncWithCSP(array $params){
    global $_LANG;
    if (empty($params['customfields']['licenseId'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'noLicenseAssigned');
    }
    $client = ConfigServer_getClientByParams($params);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotFound');
        }
        if($params['addonId']==0){
            /** @var $server Server */
            $params['model']->serviceProperties->save([
                'domain' => $license->ip,
            ]);
        }
        if($params['addonId']>0){
            Capsule::table('tblhostingaddons')->where('id', $params['addonId'])->update([
                'nextduedate' => $license->renewDate
            ]);
        } else {
            Capsule::table('tblhosting')->where('id', $params['serviceid'])->update([
                'nextduedate' => $license->renewDate
            ]);
        }
        return 'success';
    } catch (APIException $e) {
        return $e->getMessage();
    }
    return ConfigServer_getLocale($_LANG['locale'], 'failedUnknownError');
}

function ConfigServer_AdminCustomButtonArray(array $params)
{
    global $_LANG;
    if (empty($params['customfields']['licenseId'])) {
        return [];
    }
    $client = ConfigServer_getClientByParams($params);
    try {
        $t = function ($x) use ($_LANG) {
            return ConfigServer_getLocale($_LANG['locale'], $x);
        };
        $license = $client->licenses()->get($params['customfields']['licenseId']);
    } catch (APIException $e) {
        return [];
    }
    return [
        $t('SyncWithCSP') => 'SyncWithCSP',
        ($license->changeIP < 3 ?  $t('ChangeIP') :  $t('ChangeIPWith2$')) => 'ChangeIPAdmin',
    ];
}