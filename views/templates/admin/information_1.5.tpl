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
<fieldset>
    <div id="trustedshops_info">
        <div class="row">
            <div class="ts_description">
                <h4>{l s='Get better reviews with Trusted Shops' mod='trustedshops'}</h4>
                <p>
                    {l s='Easily collect, show and manage real customer reviews. Integrate this module within minutes and display trust at first glance.' mod='trustedshops'}
                </p>
            </div>
            <div class="ts_logo">
                <img src="{$_path|escape}img/ts_logo.jpg" alt=""/>
            </div>
        </div>
        <div class="row">
            <div class="ts_rating_img">
                <img src="{$ts_rating_image|escape}" alt=""/>
            </div>
            <div class="ts_rating_description">
                <h4>{l s='Top Features' mod='trustedshops'}</h4>
                <ul>
                    <li>
                        <strong>{l s='Stars in Google' mod='trustedshops'}</strong>
                        <p>{l s='Automatically transmit your seller ratings to Google and show your stars in your AdWords campaigns, Google Shopping and Product Listing Ads.' mod='trustedshops'}</p>
                    </li>
                    <li>
                        <strong>{l s='Optimised for mobile usage' mod='trustedshops'}</strong>
                        <p>{l s='We have made sure your review collection and display processes are fully optimised for mobile usage.' mod='trustedshops'}</p>
                    </li>
                    <li>
                        <strong>{l s='Comment on reviews' mod='trustedshops'}</strong>
                        <p>{l s='Received a negative review? Showcase how proactive your company is towards problems. Reply with a comment showing how you will deal with the issue in a professional way.' mod='trustedshops'}</p>
                    </li>
                </ul>
                <br>
                <h4>{l s='Your Benefits' mod='trustedshops'}</h4>
                <p>{l s='Genuine reviews from real people. Online shoppers can enter some personal data to give their reviews a social proof. Customers will trust you even more. Upload orders from previous customers and receive reviews within hours. Or collect reviews automatically with every purchase of your customers.' mod='trustedshops'}</p>
            </div>
        </div>
        <div class="row text-center">
            <a href="{$applynow_link|escape}" target="_blank" class="button"><span>{l s='Sign up free' mod='trustedshops'}</span></a>
        </div>
    </div>
</fieldset>