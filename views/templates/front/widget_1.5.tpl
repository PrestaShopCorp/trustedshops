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
{if $display_widget && isset($filename)}
    <div>
        <a target="_blank" href="https://www.trustedshops.com/buyerrating/info_{$ts_id|escape}.html"
           title="See customer ratings of {$shop_name|escape}"><img alt="Customer ratings of {$shop_name|escape}"
                                                                    border="0" src="{$filename|escape}"/></a>
    </div>
    <br/>
{/if}
{if $display_rating_link}
    <div>
        <a target="_blank" href="{$rating_url|escape}" title="Rate this shop">
            <img alt="Rate this shop" border="0" src="{$module_dir|escape}img/apply_{$language|escape}.gif"/>
        </a>
    </div>
    <br/>
{/if}