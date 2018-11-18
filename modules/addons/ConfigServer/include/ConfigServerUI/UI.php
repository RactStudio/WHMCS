<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
namespace ConfigServerUI;
use WHMCS\Database\Capsule;
use ConfigServer\Models\Licenses\License;
use ConfigServer\APIException;
use ConfigServer\PHPView;
class UI
{
    private $output = null;
    private $params;
    private $client;
    private $information;
    private $session;

    public function __construct(array $params)
    {
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
            $this->output .= 'Config server is currently not avaiable. Please try again later.';
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

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            foreach($vars['product'] as $pid){
                if(empty($pid)) continue;
                $productDetails = $this->client->products()->get($pid);

                $product = new \WHMCS\Product\Product();
                $product->type = "other";
                $product->productGroupId = $vars['productGroup'];
                $product->name = $productDetails->fullName;
                $product->paymentType = 'recurring';
                $product->showDomainOptions = false;
                $displayOrder = \Illuminate\Database\Capsule\Manager::table("tblproducts")->where("gid", "=", $vars['productGroup'])->max("order");
                $product->displayOrder = is_null($displayOrder) ? 0 : ++$displayOrder;
                $product->servertype = "ConfigServer";
                $product->autosetup = "payment";
                $product->configoption1 = $productDetails->id;
                $product->configoption2 = $vars['allowChangeIP'] ? 'on' : '';
                $product->allowqty = 1;
                $product->save();

                $pricing = new \stdClass();
                $pricing->monthly = ceil($productDetails->priceWithDiscount('monthly') * $vars['exchangeRate'] / $vars['roundBy']) * $vars['roundBy'];
                $pricing->quarterly = ceil($productDetails->priceWithDiscount('quarterly') * $vars['exchangeRate'] / $vars['roundBy']) * $vars['roundBy'];
                $pricing->semiannually = ceil($productDetails->priceWithDiscount('semiannually') * $vars['exchangeRate'] / $vars['roundBy']) * $vars['roundBy'];
                $pricing->annually = ceil($productDetails->priceWithDiscount('annually') * $vars['exchangeRate'] / $vars['roundBy']) * $vars['roundBy'];
                $pricing->msetupfee = $pricing->qsetupfee = $pricing->ssetupfee = $pricing->asetupfee = ceil($productDetails->priceWithDiscount('setupfee') * $vars['exchangeRate'] / $vars['roundBy']) * $vars['roundBy'];
                $pricing->biennially = $pricing->bsetupfee = -1;
                $pricing->triennially = $pricing->tsetupfee = -1;
        
                $pricing->type = 'product';
                $pricing->relid = $product->id;
                $pricing->currency = $vars['currency'];
                Capsule::table('tblpricing')->insert((array)$pricing);

                $customfields = [
                    'IP' => [
                        'type' => 'text',
                        'regexpr' => '/^((25[0-5]|2[0-4][0-9]|[01]?[1-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[1-9][0-9]?)$/',
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
                    $data = array(
                        "type" => "product", 
                        "relid" => $product->id, 
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
                    Capsule::table('tblcustomfields')->insert($data);
                }
            }
            $vars['success'] = 'Products added successfully.';
        }
        return $this->renderTemplate('addProducts', $vars);
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
        if(sizeof($servers) == 1){
            header("Location: addonmodules.php?module=ConfigServer&serverId={$servers[0]->id}");
            exit;
        }
        $serversArr = [];
        foreach ($servers as $server) {
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
        return PHPView::render(__DIR__ . '/templates/' . $template, $vars);
    }

    public function output()
    {
        return $this->output;
    }
}
