<?php
/*
 * hook.php
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

function piwik_analytics_hook_checkout_tracker($vars) {
	global $CONFIG;

	$modulevars = array();
	$result = select_query( 'tbladdonmodules', '', array( 'module' => 'piwik_analytics' ) );

	while ($data = mysql_fetch_array( $result )) {
		$value = $data['value'];
		$value = explode( '|', $value );
		$value = trim( $value[0] );
		$modulevars[$data['setting']] = $value;
	}


	if (!$modulevars['tokenAuth'] || !$modulevars['piwikUrl']) {
		return false;
	}

	$orderid = $vars['orderid'];
	$ordernumber = $vars['ordernumber'];
	$invoiceid = $vars['invoiceid'];
	$ispaid = $vars['ispaid'];
	$amount = $subtotal = $vars['amount'];
	$paymentmethod = $vars['paymentmethod'];
	$clientdetails = $vars['clientdetails'];
	$result = select_query( 'tblorders', 'renewals', array( 'id' => $orderid ) );
	$data = mysql_fetch_array( $result );
	$renewals = $data['renewals'];

	if ($invoiceid) {
		$result = select_query( 'tblinvoices', 'subtotal,tax,tax2,total', array( 'id' => $invoiceid ) );
		$data = mysql_fetch_array( $result );
		$subtotal = $data['subtotal'];
		$tax = $data['tax'] + $data['tax2'];
		$total = $data['total'];
	}

	$code = "_paq.push(['trackEcommerceOrder',
			'{$orderid}', // (required) Unique Order ID
			{$amount }, // (required) Order Revenue grand total (includes tax, shipping, and subtracted discount)
			{$subtotal}, // (optional) Order sub total (excludes shipping)
			{$tax} // (optional) Tax amount
			]);";
	
	$result = select_query( 'tblhosting", "tblhosting.id,tblproducts.id AS pid,tblproducts.name,tblproductgroups.name AS groupname,tblhosting.firstpaymentamount', array( 'orderid' => $orderid ), '', '', '', 'tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblproductgroups ON tblproductgroups.id=tblproducts.gid' );

	while ($data = mysql_fetch_array( $result )) {
		$serviceid = $data['id'];
		$itempid = $data['pid'];
		$name = $data['name'];
		$groupname = $data['groupname'];
		$itemamount = $data['firstpaymentamount'];
		$code .= "_paq.push(['addEcommerceItem',
			'PID{$itempid}', // (required) SKU: Product unique identifier
			'{$name}', // (optional) Product name
			'{$groupname}', // (optional) Product category. You can also specify an array of up to 5 categories eg.
			{$itemamount}, // (recommended) Product price
			1 // (optional, default to 1) Product quantity
			]);";
	}

	$result = select_query( 'tblhostingaddons', 'tblhostingaddons.id,tblhostingaddons.addonid,tbladdons.name,tblhostingaddons.setupfee,tblhostingaddons.recurring', array( 'orderid' => $orderid ), '', '', '', 'tbladdons ON tbladdons.id=tblhostingaddons.addonid' );

	while ($data = mysql_fetch_array( $result )) {
		$aid = $data['id'];
		$addonid = $data['addonid'];
		$name = $data['name'];
		$groupname = $data['groupname'];
		$itemamount = $data['setupfee'] + $data['recurring'];
		$code .= "_paq.push(['addEcommerceItem',
			'AID{$addonid}', // (required) SKU: Product unique identifier
			'{$name}', // (optional) Product name
			'Addons', // (optional) Product category. You can also specify an array of up to 5 categories eg.
			{$itemamount}, // (recommended) Product price
			1 // (optional, default to 1) Product quantity
			]);";
	}

	$result = select_query( 'tbldomains', 'tbldomains.id,tbldomains.type,tbldomains.domain,tbldomains.firstpaymentamount', array( 'orderid' => $orderid ) );

	while ($data = mysql_fetch_array( $result )) {
		$did = $data['id'];
		$regtype = $data['type'];
		$domain = $data['domain'];
		$itemamount = $data['firstpaymentamount'];
		$domainparts = explode( '.', $domain, 2 );
		$code .= "
			_paq.push(['addEcommerceItem',
			'TLD". strtoupper( $domainparts[1] ) ."', // (required) SKU: Product unique identifier
			'{$regtype}', // (optional) Product name
			'Domain', // (optional) Product category. You can also specify an array of up to 5 categories eg.
			{$itemamount}, // (recommended) Product price
			1 // (optional, default to 1) Product quantity
			]);";
	}


	if ($renewals) {
		$renewals = explode( ',', $renewals );
		foreach ($renewals as $renewal) {
			$renewal = explode( '=', $renewal );
			$domainid = $renewal[0];
			$registrationperiod = $renewal[1];
			$result = select_query( 'tbldomains', 'id,domain,recurringamount', array( 'id' => $domainid ) );
			$data = mysql_fetch_array( $result );
			$did = $data['id'];
			$domain = $data['domain'];
			$itemamount = $data['recurringamount'];
			$domainparts = explode( '.', $domain, 2 );
			$code .= "_paq.push(['addEcommerceItem',
			'TLD". strtoupper( $domainparts[1] ) ."', // (required) SKU: Product unique identifier
			'Renewal', // (optional) Product name
			'Domain', // (optional) Product category. You can also specify an array of up to 5 categories eg.
			{$itemamount}, // (recommended) Product price
			1 // (optional, default to 1) Product quantity
			]);";
		}
	}
	
	//ugly way to do it !!!!!
	$systemurl = str_replace(array('https','http'),'',$vars['systemurl']);

	$code .= '<!-- Piwik -->
<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(["trackPageView"]);
  _paq.push(["enableLinkTracking"]);

  (function() {
    var u=(("https:" == document.location.protocol) ? "https" : "http") + "'.$systemurl.'modules/addons/piwik_analytics/";
    _paq.push(["setTrackerUrl", u+"piwik.php"]);
    _paq.push(["setSiteId", "'.$modulevars['piwikID'].'"]);
    var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
    g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Piwik Code -->';
	return $code;
}


function piwik_analytics_hook_page_tracking($vars) {
	global $smarty;
	
	$output = '<!-- Admin Logn Do not track -->';
	
	//do not track the admin users
	if(!isset($_SESSION['adminid'])){
		$modulevars = array();
		$result = select_query( 'tbladdonmodules', '', array( 'module' => 'piwik_analytics' ) );
		while ($data = mysql_fetch_array( $result )) {
			$value = $data['value'];
			$value = explode( '|', $value );
			$value = trim( $value[0] );
			$modulevars[$data['setting']] = $value;
		}
	
		if (!$modulevars['tokenAuth'] || !$modulevars['piwikUrl'] || !$modulevars['piwikID']) {
			return false;
		}
		
		//ugly way to do it !!!!!
		$systemurl = str_replace(array('https','http'),'',$vars['systemurl']);
		
		$output=("<!-- Piwik -->
<script type='text/javascript'>
  var _paq = _paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u=((\"https:\" == document.location.protocol) ? \"https\" : \"http\") + \"{$systemurl}modules/addons/piwik_analytics/\";
    _paq.push(['setTrackerUrl', u+'piwik.php']);
    _paq.push(['setSiteId', {$modulevars['piwikID']}]);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript';
    g.defer=true; g.async=true; g.src=u+'piwik.php'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Piwik Code -->");
	}

	return $output;
}


if (!defined( 'WHMCS' )) {
	exit( 'This file cannot be accessed directly' );
}

add_hook( 'ShoppingCartCheckoutCompletePage', 1, 'piwik_analytics_hook_checkout_tracker' );
add_hook( 'ClientAreaFooterOutput', 1, 'piwik_analytics_hook_page_tracking' );

?>
