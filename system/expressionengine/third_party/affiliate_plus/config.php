<?php

if ( ! defined('AFFILIATE_PLUS_ADDON_NAME'))
{
	define('AFFILIATE_PLUS_ADDON_NAME',         'Affiliate Plus');
	define('AFFILIATE_PLUS_ADDON_VERSION',      '0.2.0');
}

$config['name']=AFFILIATE_PLUS_ADDON_NAME;
$config['version']=AFFILIATE_PLUS_ADDON_VERSION;

$config['nsm_addon_updater']['versions_xml']='http://www.intoeetive.com/index.php/update.rss/195';