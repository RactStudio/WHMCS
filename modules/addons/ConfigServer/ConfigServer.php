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
        "version" => '1.2.5',
        "author" => '<a href="mailto:matiniamirhossein@gmail.com">Amirhossein Matini</a>',
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
                    'regexpr' => '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/',
                    'adminonly' => '',
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

function ConfigServer_upgrade($vars)
{
    $version = str_replace('.', null, $vars['version']);
    if($version < 101){
        $products = Capsule::table('tblproducts')->where('servertype', 'ConfigServer')->get();
        foreach ($products as $product) {
            $customFields = Capsule::table('tblcustomfields')->where('type', 'product')->where('relid', $product->id)->get();
            foreach($customFields as $customField){
                $lowerFieldname = strtolower($customField->fieldname);
                if(strpos($lowerFieldname, 'ip') !== false){
                    Capsule::table('tblcustomfields')->where('id', $customField->id)->update([
                        'adminonly' => '',
                    ]);
                }
            }
        }
    }
    if($version < 107){
        $products = Capsule::table('tblproducts')->where('servertype', 'ConfigServer')->get();
        foreach($products as $product){
            $customFields = Capsule::table('tblcustomfields')->where('type', 'product')->where('relid', $product->id)->get();
            foreach($customFields as $customField){
                $lowerFieldname = strtolower($customField->fieldname);
                if(strpos($lowerFieldname, 'ip') !== false){
                    Capsule::table('tblcustomfields')->where('id', $customField->id)->update([
                        'regexpr' => '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/',
                    ]);
                }
            }
        }
    }
}

function ConfigServer_output(array $params)
{
    echo '<style>'.file_get_contents((__DIR__) . '/assets/styles_admin.css').'</style>';
    echo '<div class="ConfigServer">';
    echo (new UI($params))->output();
    echo '</div>';
}