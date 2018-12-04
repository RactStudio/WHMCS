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

/**
 * Created by PhpStorm.
 * User: Amirhossein Matini
 * Date: 10/9/2018
 * Time: 17:56
 */

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
        $client = ConfigServer_getClient($params['serveraccesshash']);
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
    $client = ConfigServer_getClient($params['serveraccesshash']);
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

function ConfigServer_CreateAccount(array $params)
{
    global $_LANG;
    $client = ConfigServer_getClient($params['serveraccesshash']);
    if (!isset($params['customfields']['licenseId'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'licenseIdFieldNotFound');
    }
    if (!isset($params['customfields']['IP'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'ipFieldNotFound');
    }
    if (!empty($params['customfields']['licenseId'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'licenseAlreadyAssigned');
    }
    if (!isset($params['customfields']['IP']) && empty($params['customfields']['IP'])) {
        return ConfigServer_getLocale($_LANG['locale'], 'ipAddressEmpty');
    }
    try {
        $service = \WHMCS\Service\Service::findOrFail($params['serviceid']);
    } catch (Exception $e) {
        return 'failure';
    }
    try {
        $product = $client->products()->get($params['configoption1']);
        if($product->osRequired && !isset($params['customfields']['OS'])){
            return ConfigServer_getLocale($_LANG['locale'], 'ostypeRequired');
        }
        $os = isset($params['customfields']['OS']) ? $params['customfields']['OS'] : null;
        $cycle = lcfirst($service->billingcycle);
        $response = $product->order($params['customfields']['IP'], $cycle, $os);
            if ($response){
                $customField = Capsule::table('tblcustomfields')->where('relid', $params['pid'])->where('fieldname', 'licenseId')->first(['id']);
            if ($customField) {
                $customFieldValueExists = Capsule::table('tblcustomfieldsvalues')->where('relid', $params['serviceid'])->where('fieldid', $customField->id)->count() > 0;
                if ($customFieldValueExists) {
                    Capsule::table('tblcustomfieldsvalues')
                        ->where('relid', $params['serviceid'])
                        ->where('fieldid', $customField->id)
                        ->update(['value' => $response->id,]);
                } else {
                    Capsule::table('tblcustomfieldsvalues')->insert([
                        'relid' => $params['serviceid'],
                        'fieldid' => $customField->id,
                        'value' => $response->id,
                    ]);
                }
            }
            /** @var $server Server */
            $params['model']->serviceProperties->save([
                'domain' => $params['customfields']['IP'],
            ]);
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
    $client = ConfigServer_getClient($params['serveraccesshash']);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotFound');
        }
        try {
            $product = \WHMCS\Service\Service::findOrFail($params['serviceid']);
        } catch (Exception $e) {
            return 'failure';
        }
        $license->changeCycle(lcfirst($product->billingcycle));
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
    $client = ConfigServer_getClient($params['serveraccesshash']);
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
    $client = ConfigServer_getClient($params['serveraccesshash']);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotFound');
        }
        if ($license->status == License::STATUS_ACTIVE) {
            return 'success';
        }
        if ($license->status == License::STATUS_SUSPENDED) {
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
    $client = ConfigServer_getClient($params['serveraccesshash']);
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
    $client = ConfigServer_getClient($params['serveraccesshash']);
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
                case 'changeOS':
                    if(isset($_POST['newOS']) && ($response = $license->changeOS($_POST['newOS']))){
                        $vars['success'] = 'OS was changed successfully.';
                    }
                    break;
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
    $client = ConfigServer_getClient($params['serveraccesshash']);
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
            $t('licenseDetails') => '<a href="addonmodules.php?module=ConfigServer&serverId='.$params['serverid'].'&licenseId='.$license->id.'" target="_blank">» '.$t('licenseDetails').'</a>',
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
    $client = ConfigServer_getClient($params['serveraccesshash']);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotFound');
        }
        $customField = Capsule::table('tblcustomfields')->where('relid', $params['pid'])->where('fieldname', 'licenseId')->first(['id']);
        if ($customField) {
            $customFieldValueExists = Capsule::table('tblcustomfieldsvalues')->where('relid', $params['serviceid'])->where('fieldid', $customField->id)->count() > 0;
            if ($customFieldValueExists) {
                Capsule::table('tblcustomfieldsvalues')->where('relid', $params['serviceid'])->where('fieldid', $customField->id)->delete();
            }
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
    $client = ConfigServer_getClient($params['serveraccesshash']);
    try {
        $license = $client->licenses()->get($params['customfields']['licenseId']);
        if (!$license) {
            return ConfigServer_getLocale($_LANG['locale'], 'licenseNotFound');
        }
         /** @var $server Server */
         $params['model']->serviceProperties->save([
            'domain' => $license->ip,
        ]);
        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update([
            'nextduedate' => $license->renewDate
        ]);
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
    $client = ConfigServer_getClient($params['serveraccesshash']);
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