<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
namespace ConfigServerUI;
use WHMCS\Database\Capsule;
use ConfigServer\APIException;
use ConfigServer\PHPView;

class UI
{
    private $output = '';
    private $params;
    private $client;
    private $information;
    private $session;

    private function getLatestVersion(){
        $url = "https://raw.githubusercontent.com/configserverpro/WHMCS/master/modules/addons/ConfigServer/include/version?" . time();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_HEADER => 0,
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function __construct(array $params)
    {
        if(isset($_REQUEST['update'])){
            if ($_REQUEST['update'] == 1) {
                ConfigServer_update();
                header("Location: addonmodules.php?module=ConfigServer&update=2");
            } else if($_REQUEST['update'] == 2){
                header("Location: addonmodules.php?module=ConfigServer");
            }
            exit();
        }
        
        $version = $params['version'];
        $remoteVersion = $this->getLatestVersion();

        $v1 = (int)str_replace('.', null, $params['version']);
        $v2 = (int)str_replace('.', null, $remoteVersion);

        if(!empty($remoteVersion) && $version != $remoteVersion){
            $this->output .= '<div class="alert alert-info text-center">';
            $this->output .= "New update is available (Current version: $version, Latest version: $remoteVersion)&nbsp;";
            $this->output .= 'click <a href="addonmodules.php?module=ConfigServer&update=1"><strong>here</strong></a> to update.';
            $this->output .= '</div>';
            if($v2-$v1 >= 5){
                $this->output .= '<div class="alert alert-danger text-center">';
                $this->output .= "<strong>Your version is too old, you will need to update the module to continue using it.</strong>";
                $this->output .= '</div>';
                return;
            }
        }
        $this->session = new SessionHelper();
        $this->params = $params;
        $serverToken = isset($_REQUEST['serverId']) ? $this->getServerToken((int) $_REQUEST['serverId']) : null;
        if (!$serverToken) {
            $this->renderChooseServer();
            return;
        }
        try {
            $this->client = ConfigServer_getClient($serverToken);
            $this->information = $this->client->information();
        } catch(APIException $e){
            $this->output .= 'ConfigServer is currently not available. Please try again later.';
            $this->output .= '<br><br>';
            $this->output .= $this->renderTemplate('copyright', []);
            return;
        }
        if (isset($_REQUEST['licenseId'])) {
            $this->renderLicense((int)$_REQUEST['licenseId']);
            return;
        }
        $this->renderLicenses();
    }

    private function renderAddProducts(){
        $vars = [];
        $vars['serverId'] = $_REQUEST['serverId'];
        $vars['productGroups'] = Capsule::table('tblproductgroups')->get();
        $vars['currencies'] = Capsule::table('tblcurrencies')->get();
        $vars['products'] = $this->client->products()->all();
        $vars['exchangeRate'] = isset($_POST['exchangeRate']) ? (float)$_POST['exchangeRate'] : $this->information->exchangeRateRial;

        $vars['allowChangeIP'] = isset($_POST['allowChangeIP']) ? (bool)$_POST['allowChangeIP'] : false;
        $vars['currency'] = isset($_POST['currency']) ? (int)$_POST['currency'] : false;
        $vars['productGroup'] = isset($_POST['productGroup']) ? (int)$_POST['productGroup'] : false;
        $vars['roundBy'] = isset($_POST['roundBy']) ? (float)$_POST['roundBy'] : 100;
        $vars['product'] = array_unique(isset($_POST['product']) ? (array)$_POST['product'] : []);
        $vars['productType'] = isset($_POST['productType']) && $_POST['productType'] == 'addon' ? 'addon' : 'product';

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            foreach($vars['product'] as $pid){
                if(empty($pid)) continue;
                $this->addProduct($pid, $vars['productGroup'], $vars['allowChangeIP'], $vars['productType'] == 'addon', $vars['currency'], $vars['exchangeRate'], $vars['roundBy']);
            }
            $vars['success'] = 'Products added successfully.';
        }
        return $this->renderTemplate('addProducts', $vars);
    }


    private function addProduct($pid, $gid, $allowChangeIP, $isAddon, $currency, $exchangeRate, $roundBy = 1){
        $productDetails = $this->client->products()->get($pid);
        $productType = $isAddon ? 'addon' : 'product';

        if($productType == 'product'){
            $product = Capsule::table('tblproducts')->where('servertype', 'ConfigServer')->where('configoption1', $pid)->first();

            if(!$product){
                $product = new \WHMCS\Product\Product();
                $product->type = "other";
                $product->productGroupId = $gid;
                $product->name = $productDetails->fullName;
                $product->paymentType = 'recurring';
                $product->showDomainOptions = false;
                $displayOrder = \Illuminate\Database\Capsule\Manager::table("tblproducts")->where("gid", "=", $gid)->max("order");
                $product->displayOrder = is_null($displayOrder) ? 0 : ++$displayOrder;
                $product->servertype = "ConfigServer";
                $product->autosetup = "payment";
                $product->configoption1 = $productDetails->id;
                $product->configoption2 = $allowChangeIP ? 'on' : '';
                $product->allowqty = 1;
                $product->save();
            } else {
                Capsule::table('tblproducts')->where('id', $product->id)->update([
                    'configoption2' => $allowChangeIP ? 'on' : '',
                ]);
            }
            $productId = $product->id;
        } else {
            $addon = Capsule::table('tbladdons as a')->where('module', 'ConfigServer')->whereRaw('(SELECT c.value FROM tblmodule_configuration c WHERE c.entity_type="addon" AND c.entity_id=a.id AND c.setting_name="configoption1")=' . $productDetails->id)->first();
            if(!$addon){
                $packages = Capsule::table('tblproducts')->whereNotIn('servertype', [
                    'ConfigServer',
                    'cpanel',
                    'directadmin',
                    'plesk',
                ])->pluck('id');
    
                $productId = Capsule::table('tbladdons')->insertGetId([
                    'packages' => implode(",", $packages),
                    'name' => $productDetails->fullName,
                    'description' => '',
                    'billingcycle' => 'recurring',
                    'tax' => 0,
                    'showorder' => 1,
                    'downloads' => '',
                    'autoactivate' => 'payment',
                    'suspendproduct' => 0,
                    'welcomeemail' => 0,
                    'type' => 'other',
                    'module' => 'ConfigServer',
                    'autolinkby' => '',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                Capsule::table('tblmodule_configuration')->insert([
                    'entity_type' => 'addon',
                    'entity_id' => $productId,
                    'setting_name' => 'configoption1',
                    'friendly_name' => 'Product',
                    'value' => $productDetails->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                Capsule::table('tblmodule_configuration')->insert([
                    'entity_type' => 'addon',
                    'entity_id' => $productId,
                    'setting_name' => 'configoption2',
                    'friendly_name' => 'Allow change IP?',
                    'value' => $allowChangeIP ? 'on' : '',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                Capsule::table('tbladdons')->where('id', $addon->id)->update([
                    'name' => $productDetails->fullName,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                Capsule::table('tblmodule_configuration')
                    ->where('entity_type', 'addon')
                    ->where('entity_id', $addon->id)
                    ->where('setting_name', 'configoption2')
                    ->update([
                        'friendly_name' => 'Allow change IP?',
                        'value' => $allowChangeIP ? 'on' : '',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                $productId = $addon->id;
            }
        }
        $pricing = Capsule::table('tblpricing')->where('type', $productType)->where('relid', $productId)->first();
        if(!$pricing){
            $pricing = new \stdClass();
            $pricing->monthly = ceil($productDetails->priceWithDiscount('monthly') * $exchangeRate / $roundBy) * $roundBy;
            $pricing->quarterly = ceil($productDetails->priceWithDiscount('quarterly') * $exchangeRate / $roundBy) * $roundBy;
            $pricing->semiannually = ceil($productDetails->priceWithDiscount('semiannually') * $exchangeRate / $roundBy) * $roundBy;
            $pricing->annually = ceil($productDetails->priceWithDiscount('annually') * $exchangeRate / $roundBy) * $roundBy;
            $pricing->msetupfee = $pricing->qsetupfee = $pricing->ssetupfee = $pricing->asetupfee = ceil($productDetails->priceWithDiscount('setupfee') * $exchangeRate / $roundBy) * $roundBy;
            $pricing->biennially = $pricing->bsetupfee = -1;
            $pricing->triennially = $pricing->tsetupfee = -1;
    
            $pricing->type = $productType;
            $pricing->relid = $productId;
            $pricing->currency = $currency;
    
            Capsule::table('tblpricing')->insert((array)$pricing);
        } else {
            Capsule::table('tblpricing')->where('id', $pricing->id)->update([
                'monthly' => ceil($productDetails->priceWithDiscount('monthly') * $exchangeRate / $roundBy) * $roundBy,
                'quarterly' => ceil($productDetails->priceWithDiscount('quarterly') * $exchangeRate / $roundBy) * $roundBy,
                'semiannually' => ceil($productDetails->priceWithDiscount('semiannually') * $exchangeRate / $roundBy) * $roundBy,
                'annually' => ceil($productDetails->priceWithDiscount('annually') * $exchangeRate / $roundBy) * $roundBy,
                'msetupfee' => $pricing->qsetupfee = $pricing->ssetupfee = $pricing->asetupfee = ceil($productDetails->priceWithDiscount('setupfee') * $exchangeRate / $roundBy) * $roundBy,
            ]);
        }
        $customfields = [
            'IP' => [
                'type' => 'text',
                'regexpr' => '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/',
                'adminonly' => false,
                'showinvoice' => true,
                'showorder' => true,
                'required' => true,
            ],
            'licenseId' => [
                'type' => 'text',
                'adminonly' => true,
            ],
        ];

        foreach ($customfields as $key => $val) {
            $field = Capsule::table('tblcustomfields')->where('type', $productType)->where('relid', $productId)->where('fieldname', $key)->first();
            $data = array(
                "type" => $productType, 
                "relid" => $productId, 
                "fieldname" => $key, 
                "fieldtype" => $val["type"],
                "regexpr" => isset($val["regexpr"]) ? $val["regexpr"] : '', 
                "adminonly" => isset($val["adminonly"]) && $val["adminonly"] ? 'on' : '', 
                "required" => isset($val["required"]) && $val["required"] ? 'on' : '', 
                "showorder" => isset($val["showorder"]) && $val["showorder"] ? 'on' : '', 
                "showinvoice" => isset($val["showinvoice"]) && $val["showinvoice"] ? 'on' : '',
            );
            if($val['type'] == 'dropdown'){
                $data['fieldoptions'] = $val['fieldoptions'];
            }
            if($field){
                Capsule::table('tblcustomfields')->where('id', $field->id)->update($data);
            } else {
                Capsule::table('tblcustomfields')->insert($data);
            }
        }
    }

    private function renderLicenses()
    {
        $vars = ['activeTab' => isset($_REQUEST['search']) ? 'search' : (isset($_REQUEST['addProducts']) ? 'addProducts' : '')];
        if (isset($_GET['c']) && $_GET['c'] == $this->session->getChecker()) {
            $this->session->changeChecker();
            if (isset($_REQUEST['extendLicense'])) {
                try {
                    $license = $this->client->licenses()->get((int) $_REQUEST['extendLicense']);
                    $result = $license->renew();
                    if ($result) {
                        $vars['success'] = sprintf('License has been renewed, $%s was deducted from your balance. Your new balance is $%s.', $result->cost, $result->balance);
                    }
                } catch (APIException $e) {
                    $vars['error'] = $e->getMessage();
                }
            }
        }
        $criteria = [];
        if (isset($_REQUEST['ip'])) {
            $criteria['ip'] = $_REQUEST['ip'];
        }
        if (isset($_REQUEST['status'])) {
            $criteria['status'] = $_REQUEST['status'];
        }
        $vars['addProducts'] = $vars['activeTab'] == 'addProducts' ? $this->renderAddProducts() : null;
        $vars['serverId'] = $_REQUEST['serverId'];
        $vars['criteria'] = $criteria;
        $vars['licenses'] = $this->client->licenses()->all($criteria);

        foreach($vars['licenses'] as &$license){
            $client = null;
            $license->client = '?';
            $result = Capsule::table('tblcustomfields as f')
            ->join('tblcustomfieldsvalues as v', 'v.fieldid', '=', 'f.id')
            ->where('f.type', 'product')->where('f.fieldname', 'licenseId')
            ->where('v.value', $license->id)
            ->whereRaw('(SELECT COUNT(tblhosting.id) FROM tblhosting WHERE tblhosting.id=v.relid)>0')
            ->first(['v.relid']);
            $addonId = 0;
            if($result){
                $service = Capsule::table('tblhosting')/*->where('server', (int)$_REQUEST['serverId'])*/->where('id', $result->relid)->whereIn('domainstatus', ['Active', 'Suspended'])->first(['id', 'userid']);
                if($service){
                    $client = Capsule::table('tblclients')->where('id', $service->userid)->first();
                }
            } else {
                $result = Capsule::table('tblcustomfields as f')
                    ->join('tblcustomfieldsvalues as v', 'v.fieldid', '=', 'f.id')
                    ->where('f.type', 'addon')->where('f.fieldname', 'licenseId')
                    ->where('v.value', $license->id)
                    ->whereRaw('(SELECT COUNT(tblhostingaddons.id) FROM tblhostingaddons WHERE tblhostingaddons.id=v.relid)>0')
                    ->first(['v.relid']);
                if($result){
                    $service = Capsule::table('tblhostingaddons')/*->where('server', (int)$_REQUEST['serverId'])*/->where('id', $result->relid)->whereIn('status', ['Active', 'Suspended'])->first(['id', 'userid']);
                    if($service){
                        $addonId = $result->relid;
                        $client = Capsule::table('tblclients')->where('id', $service->userid)->first();
                    }
                }
            }
            if(!is_null($client)){
                if($addonId>0){
                    $license->client = '<span title="'.sprintf('%s %s', $client->firstname, $client->lastname).'"><a target="_blank" href="clientsservices.php?userid='.$client->id.'&id='.$service->id.'&aid='.$addonId.'">🔍</a></span>';
                } else {
                    $license->client = '<span title="'.sprintf('%s %s', $client->firstname, $client->lastname).'"><a target="_blank" href="clientsservices.php?userid='.$client->id.'&id='.$service->id.'">🔍</a></span>';
                }
            }

        }

        $vars['sessionChecker'] = $this->session->getChecker();
        $vars['products'] = $this->client->products()->all();
        $vars['information'] = $this->information;

        $this->output .= $this->renderTemplate('licenses', $vars);
        $this->output .= $this->renderTemplate('footer', [
            'exchangeRateRial' => $this->information->exchangeRateRial,
            'exchangeRateToman' => $this->information->exchangeRateToman,
        ]);
    }
    private function renderChooseServer()
    {
        $servers = Capsule::table('tblservers')->where('type', 'ConfigServer')->get();
        if(!isset($_REQUEST['serverId']) && sizeof($servers) == 1){
            foreach($servers as $server){
                header("Location: addonmodules.php?module=ConfigServer&serverId={$server->id}");
                exit;
            }
        }
        $serversArr = [];
        foreach ($servers as $server) {
            if(empty($server->accesshash)) continue;
            try {
                $client = ConfigServer_getClient($server->accesshash);
                $information = $client->information();

                $row = &$serversArr[];
                $row = new \stdClass;

                $row->id = $server->id;
                $row->credit = $information->credit;
                $row->discount = $information->discount;
                $row->email = $information->email;
                $row->total_licenses = $information->total_licenses;
                $row->partnerLevel = $information->partnerLevel;
                $row->discount = $information->discount;
            } catch (\Exception $e) {
                if($e->getMessage() == "No data is provided."){
                    $this->output .= 'ConfigServer is currently not available. Please try again later.';
                    $this->output .= '<br><br>';
                    $this->output .= $this->renderTemplate('copyright', []);
                    return;
                }
                $this->output .= $e->getMessage();
            }
        }
        $this->output .= $this->renderTemplate('servers', ['servers' => $serversArr]);
    }

    private function renderLicense($id)
    {
        global $_LANG;
        $vars = [
            'activeTab' => 'details',
            'serverId' => (int) $_REQUEST['serverId'],
            'information' => $this->information,
        ];

        try {
            $license = $this->client->licenses()->get($id);
            
            $vars['license'] = $license;
        } catch (APIException $e) {
            $vars['error'] = $e->getMessage();
            goto render;
        }

        $vars['statusColor'] = $license->status == 'active' ? '#dff0d8' : ($license->status == 'suspended' ? '#f2dede' : 'initial');

        if(isset($_GET['c']) && $_GET['c'] == $this->session->getChecker()){
            $this->session->changeChecker();
            if (isset($_REQUEST['extend'])) {
                try {
                    $result = $license->renew();
                    if($result){
                        $vars['success'] = sprintf('License has been renewed, $%s was deducted from your balance. Your new balance is $%s.', $result->cost, $result->balance);
                    }
                } catch (APIException $e) {
                    $vars['error'] = $e->getMessage();
                }
            }
            if(isset($_POST['action'])){
                switch($_POST['action']){
                    case 'changeSettings':
                        try {
                            $license->changeAutoRenew($_POST['setAutoRenew'] == 'on');
                            $license->changeStatus((string) $_POST['setStatus']);
                            $license->changeCycle((string) $_POST['setBillingCycle']);
                            $vars['success'] = 'All settings were updated.';
                        } catch (APIException $e) {
                            $vars['error'] = $e->getMessage();
                        }
                        break;
                        case 'updateNote':
                            try {
                                $license->updateNotes(filter_var($_POST['notes'], FILTER_SANITIZE_STRING));
                                $vars['success'] = 'Notes were saved.';
                            } catch (APIException $e) {
                                $vars['error'] = $e->getMessage();
                            }
                            break;
                        case 'changeIP':
                            try {
                                $result = $license->changeIP($_POST['newIP'], true);
                                if($result){
                                    if(property_exists($result, 'cost')){
                                        $vars['success'] = sprintf('IP address was changed. $%s was deducted from your account. Your new balance is: $%s', $result->cost, $result->balance);
                                    } else {
                                        $vars['success'] = 'IP address was changed.';
                                    }
                                }
                            } catch (APIException $e) {
                                $vars['error'] = $e->getMessage();
                            }
                            break;
                }
            }
        }
        render:
        $vars['direction'] = ConfigServer_getLocale($_LANG['locale'], 'direction');
        $vars['textAlign'] = ConfigServer_getLocale($_LANG['locale'], 'textAlign');
        $vars['sessionChecker'] = $this->session->getChecker();
        $this->output .= $this->renderTemplate('license', $vars);
    }

    private function getServerToken($serverId)
    {
        $server = Capsule::table('tblservers')->where('type', 'ConfigServer')->where('id', $serverId)->first();
        if (!$server) {
            return false;
        }
        return $server->accesshash;
    }


    private function renderTemplate($template, $vars)
    {
        $vars['version'] = $this->params['version'];
        return PHPView::render(__DIR__ . '/templates/' . $template, $vars);
    }

    public function output()
    {
        return $this->output;
    }
}
