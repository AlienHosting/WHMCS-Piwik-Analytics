<?php
/*
 * piwik_analytics.php
 * 
 * Copyright 2014 Alien-Hosting <admin@alien-hosting.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 * 
 */

function piwik_analytics_config() {
	$configarray = array( 
		'name' => 'Piwik Analytics', 
		'description' => 'This module provides a quick and easy way to integrate full Piwik Analytics tracking into your WHMCS installation', 
		'version' => '0.1', 
		'author' => '<a href="http://alien-hosting.com">Alien-Hosting</a>', 
		'fields' => array( 
			'tokenAuth' => array( 
				'FriendlyName' => 'Auth token', 
					'Type' => 'text', 
					'Size' => '25', 
					'Description' => 'User Auth token for Piwik connect'
			), 
			'piwikUrl' => array( 
				'FriendlyName' => 'Piwik URL', 
				'Type' => 'text', 
				'Size' => '25', 
				'Description' => 'REST API' 
			),
			'piwikID' => array( 
				'FriendlyName' => 'Piwik Domain ID', 
				'Type' => 'text', 
				'Size' => '25', 
				'Description' => 'Get it from tracking code'
			)
		) 
	);
	return $configarray;
}

function piwik_analytics_output($vars) {
	
	$errors = array();
	
	//auth token check
	if($vars['tokenAuth']==''){
		$errors[]='Please enter auth token';
	}
	
	//piwik url
	if($vars['piwikUrl']==''){
		$errors[]='Please enter piwik url';
	}
	
	//piwik site id
	if($vars['piwikID']==''){
		$errors[]='Please enter piwik url';
	}
	
	if(count($errors)>0){
		echo 'Please compleate the requirments on address <a href="configaddonmods.php"><b>Setup > Addon Modules</b></a><ul>';
		foreach($errors as $error){
			echo "<li>{$error}</li>";
		}
		echo '</ul>';
		return false;
	}
	
	$result = (json_decode(file_get_contents("{$vars['piwikUrl']}?module=API&method=SitesManager.getSitesIdFromSiteUrl&url=http%3A%2F%2F{$_SERVER['SERVER_NAME']}&format=JSON&tokenAuth={$vars['tokenAuth']}"),TRUE));

	if(count($result)>0){
		if ($result[0]['idsite'] == $vars['piwikID']){
			echo "<p>Configuration of the Piwik Analytics Addon connection is done and return {$result[0]['idsite']} for site ID which is the same as yours. Please also ensure your active client area footer.tpl template file includes the {\$footeroutput} template tag.</p>";
		}else{
			echo "<p>Piwik Analytics Addon connection is done but return {$result[0]['idsite']} for site ID which is not the same as yours. Please check your config</p>";
		}
			
	}else{
		echo 'Connection error please check all details and come back again, also be sure this domain is added to you piwik traking system';
		return false;
	}
}


if (!defined( 'WHMCS' )) {
	exit( 'This file cannot be accessed directly' );
}

?>
