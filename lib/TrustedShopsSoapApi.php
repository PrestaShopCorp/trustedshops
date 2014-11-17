<?php
/**
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
 */

class TrustedShopsSoapApi
{
	const TS_SERVER = 'www.trustedshops.de';
	const WS_USER = 'silbersaiten';
	const WS_PASSWORD = 'Yx1F5uXR';

	const ACTIVATE = 1;
	const DESACTIVATE = 0;

	const RT_OK = 'OK';
	const RT_SOAP_ERROR = -1;
	const RT_INVALID_TSID = 'INVALID_TSID';
	const RT_NOT_REGISTERED = 'NOT_REGISTERED_FOR_TRUSTEDRATING';
	const RT_WRONG_LOGIN = 'WRONG_WSUSERNAME_WSPASSWORD';


	public static function validate($partener_package, $trusted_shops_id, $action = self::ACTIVATE)
	{
		ini_set('soap.wsdl_cache_enabled', 1);
		$result = self::RT_SOAP_ERROR;

		try {
			$wsdlUrl = 'https://' . self::TS_SERVER . '/ts/services/TsRating?wsdl';
			$client = new SoapClient($wsdlUrl);

			$result = $client->updateRatingWidgetState($trusted_shops_id, $action, self::WS_USER, self::WS_PASSWORD, $partener_package);
		} catch (SoapFault $fault) {
			/** Enable these lines if you are experiencing issues with your Trusted Shops ID activation.
			 *
			 * $errorText = 'SOAP Fault: (faultcode:{$fault->faultcode}, faultstring:{$fault->faultstring})';
			 * die($errorText);
			 *
			 */
		}

		if ($result == self::RT_WRONG_LOGIN)
			die('Wrong login/password');

		return $result;
	}
}

