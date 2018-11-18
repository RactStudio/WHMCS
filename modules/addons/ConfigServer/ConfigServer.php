<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
use ConfigServerUI\UI;
use WHMCS\Database\Capsule;

require __DIR__ . '/include/bootstrap.php';
function ConfigServer_config()
{
    $configArray = array(
        "name" => "ConfigServer",
        "description" => "ConfigServer License Management module",
        "version" => '1.0',
        "author" => "Amirhossein Matini",
        "language" => "english",
        "fields" => []
    );
    return $configArray;
}

function ConfigServer_activate()
{
    Capsule::table('tblservers')->where('type', 'configserverlicense')->update([
        'type' => 'ConfigServer',
    ]);
    $mapToMap = [
        'cPanelVPS' => 12,
        'cPanelDedicated' => 13,
        'CloudLinux' => 14,
        'LiteSpeed' => 15,
        'cPanelVPSORG' => '',
        'cPanelDEDORG' => '',
        'CloudLinuxORG' => '',
        'CloudLinuxCP' => 14,
        'CloudLinuxKare' => '',
        'Directadmin' => 27,
        'CXS' => 26,
        'JetBackup' => 28,
        'PleskVPSWebAdmin' => 29,
        'PleskVPSWebPro' => 30,
        'PleskVPSWebHost' => 31,
        'PleskDedWebAdmin' => 32,
        'PleskDedWebPro' => 33,
        'PleskDedWebHost' => 34,
        'WHMAMP' => 35,
        'WHMSonic' => 36,
        'DirectADM' => 27,
        'MSFE' => 37,
    ];
    $products = Capsule::table('tblproducts')->where('servertype', 'configserverlicense')->get();
    foreach($products as $product){
        $product->configoption1 = isset($mapToMap[$product->configoption1]) ? $mapToMap[$product->configoption1] : $product->configoption1;
        Capsule::table('tblproducts')->where('id', $product->id)->update([
            'servertype' => 'ConfigServer',
            'configoption1' => $product->configoption1,
            'configoption2' => $product->configoption2 == 'Yes' ? 'on' : '',
        ]);

        $customFields = Capsule::table('tblcustomfields')->where('type', 'product')->where('relid', $product->id)->get();
        foreach($customFields as $customField){
            $lowerFieldname = strtolower($customField->fieldname);
            if(strpos($lowerFieldname, 'ip') !== false){
                Capsule::table('tblcustomfields')->where('id', $customField->id)->update([
                    'fieldname' => 'IP',
                    'regexpr' => '/^((25[0-5]|2[0-4][0-9]|[01]?[1-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[1-9][0-9]?)$/',
                    'adminonly' => 'on',
                    'showinvoice' => 'on',
                    'showorder' => 'on',
                    'required' => 'on',
                ]);
            } else if(strpos($lowerFieldname, 'id') !== false){
                Capsule::table('tblcustomfields')->where('id', $customField->id)->update([
                    'fieldname' => 'licenseId',
                    'adminonly' => 'on',
                    'showinvoice' => '',
                    'showorder' => '',
                    'required' => '',
                ]);
            }
        }
    }
}

function ConfigServer_deactivate()
{

}

function ConfigServer_output(array $params)
{
    echo '<style>'.file_get_contents((__DIR__) . '/assets/styles_admin.css').'</style>';
    echo (new UI($params))->output();
}