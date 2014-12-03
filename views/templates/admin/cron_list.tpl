{**
* 2014 silbersaiten The module is based on the trustedshops module originally developed by PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@silbersaiten.de so we can send you a copy immediately.
*
* @author    silbersaiten www.silbersaiten.de <info@silbersaiten.de>
* @copyright 2014 silbersaiten
* @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}
<div class="panel">
    <div class="panel-heading"><img src="../img/admin/warning.gif" alt="" /> {l s='Cronjob configuration' mod='trustedshops'}</div>
    <p>
	{l s='If you are using a Trusted Shops EXCELLENCE cetificate in your shop, set up a cron job on your web server.' mod='trustedshops'}
    </p>
    <p>
	{l s='Run the script file (with an interval of 10 minutes):' mod='trustedshops'}
	<div class="well">{$cron_path|escape:'html':'UTF-8'}</div>
    </p>
    <p>
	{l s='The corresponding line in your cron file may look like this:' mod='trustedshops'}
	<div class="well">*/10 * * * * {$cron_path|escape:'html':'UTF-8'}>/dev/null 2>&1</div>
    </p>
</div>