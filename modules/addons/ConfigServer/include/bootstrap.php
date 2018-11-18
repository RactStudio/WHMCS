<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
require dirname(dirname(dirname(__DIR__))) . '/servers/ConfigServer/include/bootstrap.php';
function ConfigServer_getAssetPath($file = null){
    return '/modules/addons/ConfigServer/assets/' . $file;
}