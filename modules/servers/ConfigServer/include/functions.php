<?php
/**
 * ConfigServer module by Amirhossein Matini (matiniamirhossein@gmail.com) 
 * © All right reserved for ConfigServer Team (ConfigServer.Pro)
 */
use ConfigServer\ConfigServerAPIClient;

/**
 * @return ConfigServerAPIClient
 */
function ConfigServer_getClient($accessToken)
{
    static $connections = [];
    if (!isset($connections[$accessToken]))
        $connections[$accessToken] = new ConfigServerAPIClient($accessToken);
    return $connections[$accessToken];
}

function ConfigServer_getLocale($locale, $key = null, $parameters = [])
{
    static $languages = [];
    if (!isset($languages[$locale])) {
        $localeFile = __DIR__ . '/locale/' . $locale . '.php';
        if (is_file($localeFile)) {
            $languages[$locale] = require $localeFile;
        } else {
            $localeFile = __DIR__ . '/locale/en_GB.php';
            $languages[$locale] = require $localeFile;
        }
    }
    $localeData = $languages[$locale];
    if ($key)
        if (isset($localeData[$key]))
            return str_replace(array_keys($parameters), array_values($parameters), $localeData[$key]);
        else
            return $key;
    return $localeData;
}