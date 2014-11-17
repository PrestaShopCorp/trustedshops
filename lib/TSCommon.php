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

require_once(_PS_MODULE_DIR_ . 'trustedshops/lib/TSBPException.php');
require_once(_PS_MODULE_DIR_ . 'trustedshops/lib/TrustedShopsSoapApi.php');
require_once(_PS_MODULE_DIR_ . 'trustedshops/lib/WidgetCache.php');
require_once(_PS_MODULE_DIR_ . 'trustedshops/lib/RatingAlert.php');

/**
 * @see the technical doc for entire description.
 *        too long to set it here.
 * @author Prestashop - Nans Pellicari
 * @since prestashop 1.4
 * @version 0.1
 */
class TSCommon extends AbsTrustedShops
{

	const APPLY_URL = 'https://www.trustedshops.com/buyerrating/signup.html';
	const PARTNER_PACKAGE = 'presta';
	const SHOP_SW = 'PrestaShop';

	private $rating_url_base = array(
		'EN' => 'https://www.trustedshops.com/buyerrating/rate_',
		'DE' => 'https://www.trustedshops.com/bewertung/bewerten_',
		'FR' => 'https://www.trustedshops.com/evaluation/evaluer_',
		'ES' => 'https://www.trustedshops.es/evaluacion/evaluar_',
		'IT' => 'https://www.trustedshops.it/valutazione-del-negozio/rate_'
	);

	private $apply_url_base = array(
		'EN' => 'https://www.trustedshops.com/buyerrating/signup.html',
		'DE' => 'https://www.trustedshops.com/bewertung/anmeldung.html',
		'FR' => 'https://www.trustedshops.com/evaluation/inscription.html',
		'ES' => 'https://www.trustedshops.es/comerciante/'
	);

	private $apply_url_tracker = array(
		'EN' => '&et_cid=53&et_lid=3361',
		'DE' => '',
		'FR' => '&et_cid=53&et_lid=3362',
		'ES' => ''
	);

	private $error_soap_call;

	const PREFIX_TABLE = 'TS';
	const ENV_MOD = 'production'; // 'test' or 'production'
	const DB_ITEMS = 'ts_buyerprotection_items';
	const DB_APPLI = 'ts_application_id';
	const WEBSERVICE_BO = 'administration';
	const WEBSERVICE_FO = 'front-end';

	/**
	 * List of registration link, need to add parameters
	 * @see TSCommon::_getRegistrationLink()
	 * @var array
	 */
	private $registration_link = array(
		'DE' => 'http://www.trustedshops.de/shopbetreiber/mitgliedschaft.html',
		'EN' => 'http://www.trustedshops.com/merchants/membership.html',
		'FR' => 'http://www.trustedshops.com/marchands/affiliation.html',
		'PL' => 'http://www.trustedshops.pl/handlowcy/cennik.html',
		'ES' => '',
		'IT' => ''
	);

	/**
	 * Link to obtain the certificate about the shop.
	 * Use by seal of approval.
	 * @see TSCommon::hookRightColumn()
	 * @var array
	 */
	private static $certificate_link = array(
		'DE' => 'http://www.trustedshops.de/profil/#shop_name#_#shop_id#.html',
		'EN' => 'http://www.trustedshops.com/profile/#shop_name#_#shop_id#.html',
		'FR' => 'http://www.trustedshops.fr/boutique_en_ligne/profil/#shop_name#_#shop_id#.html',
		'PL' => 'http://www.trustedshops.de/profil/#shop_name#_#shop_id#.html',
		'ES' => 'http://www.trustedshops.es/perfil/#shop_name#_#shop_id#.html',
		'IT' => 'http://www.trustedshops.it/profilo/#shop_name#_#shop_id#.html'
	);

	/**
	 * Available language for used TrustedShops Buyer Protection
	 * @see TSCommon::__construct()
	 * @var array
	 */
	public static $available_languages = array(/*'XX'=>'xx', */'EN' => 'en', 'FR' => 'fr', 'DE' => 'de', 'PL' => 'pl', 'ES' => 'es', 'IT' => 'it');

	public static $available_languages_for_adding = array();

	/**
	 * @todo : be sure : see TrustedShopsRating::__construct()
	 * @var array
	 */
	public $limited_countries = array('PL', 'GB', 'US', 'FR', 'DE', 'ES', 'IT');

	/**
	 * Differents urls to call for Trusted Shops API
	 * @var array
	 */
	private static $webservice_urls = array(
		'administration' => array(
			'test' => 'https://qa.trustedshops.de/ts/services/TsProtection?wsdl',
			'production' => 'https://www.trustedshops.de/ts/services/TsProtection?wsdl',
		),
		'front-end' => array(
			'test' => 'https://protection-qa.trustedshops.com/ts/protectionservices/ApplicationRequestService?wsdl',
			'production' => 'https://protection.trustedshops.com/ts/protectionservices/ApplicationRequestService?wsdl',
		),
	);

	// Configuration vars
	private static $SHOPSW;
	private static $ET_CID;
	private static $ET_LID;

	/**
	 * Its must look like :
	 * array(
	 *        'lang_iso(ex: FR)' => array('stateEnum'=>'', 'typeEnum'=>'', 'url'=>'', 'tsID'=>'', 'user'=>'', 'password'=>'', 'variant'=>''),
	 *        ...
	 * )
	 * @var array
	 */
	public static $CERTIFICATES;

	private $available_seal_variants = array('default' => 'Default', 'small' => 'Small', 'text' => 'Text', 'reviews' => 'Reviews');

	private static $DEFAULT_LANG;
	private static $CAT_ID;
	private static $ENV_API;

	/**
	 * save shop url
	 * @var string
	 */
	private $site_url;

	/**
	 * Payment type used by Trusted Shops.
	 * @var array
	 */
	private static $payments_type;

	public function __construct()
	{
		// need to set this in constructor to allow translation
		TSCommon::$payments_type = array(
			'DIRECT_DEBIT' => $this->l('Direct debit'),
			'CREDIT_CARD' => $this->l('Credit Card'),
			'INVOICE' => $this->l('Invoice'),
			'CASH_ON_DELIVERY' => $this->l('Cash on delivery'),
			'PREPAYMENT' => $this->l('Prepayment'),
			'CHEQUE' => $this->l('Cheque'),
			'PAYBOX' => $this->l('Paybox'),
			'PAYPAL' => $this->l('PayPal'),
			'CASH_ON_PICKUP' => $this->l('Cash on pickup'),
			'FINANCING' => $this->l('Financing'),
			'LEASING' => $this->l('Leasing'),
			'T_PAY' => $this->l('T-Pay'),
			'CLICKANDBUY' => $this->l('Click&Buy'),
			'GIROPAY' => $this->l('Giropay'),
			'GOOGLE_CHECKOUT' => $this->l('Google Checkout'),
			'SHOP_CARD' => $this->l('Online shop payment card'),
			'DIRECT_E_BANKING' => $this->l('DIRECTebanking.com'),
			'MONEYBOOKERS' => $this->l('moneybookers.com'),
			'OTHER' => $this->l('Other method of payment'),
		);

		$this->tab_name = $this->l('Trusted Shops quality seal and buyer protection');
		$this->site_url = Tools::htmlentitiesutf8('http://' . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__);

		TSBPException::setTranslationObject($this);

		if (!method_exists('Tools', 'jsonDecode') || !method_exists('Tools', 'jsonEncode'))
			$this->warnings[] = $this->l('Json functions must be implemented in your php version');
		else {
			foreach (self::$available_languages as $iso => $lang) {
				$certificate = Configuration::get(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.strtoupper($iso));
				TSCommon::$CERTIFICATES[strtoupper($iso)] = (array)Tools::jsonDecode(Tools::htmlentitiesDecodeUTF8($certificate));

				if (!isset(TSCommon::$CERTIFICATES[strtoupper($iso)]['tsID']) || (isset(TSCommon::$CERTIFICATES[strtoupper($iso)]['tsID']) && TSCommon::$CERTIFICATES[strtoupper($iso)]['tsID'] == ''))
					TSCommon::$available_languages_for_adding[strtoupper($iso)] = strtoupper($iso);
			}

			if (TSCommon::$SHOPSW === NULL) {
				TSCommon::$SHOPSW = Configuration::get(TSCommon::PREFIX_TABLE . 'SHOPSW');
				TSCommon::$ET_CID = Configuration::get(TSCommon::PREFIX_TABLE . 'ET_CID');
				TSCommon::$ET_LID = Configuration::get(TSCommon::PREFIX_TABLE . 'ET_LID');
				TSCommon::$DEFAULT_LANG = (int)Configuration::get('PS_LANG_DEFAULT');
				TSCommon::$CAT_ID = (int)Configuration::get(TSCommon::PREFIX_TABLE . 'CAT_ID');
				TSCommon::$ENV_API = Configuration::get(TSCommon::PREFIX_TABLE . 'ENV_API');
			}
		}
	}

	public function install()
	{
		if (!method_exists('Tools', 'jsonDecode') || !method_exists('Tools', 'jsonEncode'))
			return false;

		foreach (self::$available_languages as $iso => $lang)
			Configuration::updateValue(TSCommon::PREFIX_TABLE . 'CERTIFICATE_' . strtoupper($iso),
				Tools::htmlentitiesUTF8(Tools::jsonEncode(array('stateEnum' => '', 'typeEnum' => '', 'url' => '', 'tsID' => '', 'user' => '', 'password' => ''))));

		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'SHOPSW', '');
		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'ET_CID', '');
		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'ET_LID', '');
		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'ENV_API', TSCommon::ENV_MOD);

		$query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . TSCommon::DB_ITEMS . '` (' .
			'`id_item` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,' .
			'`id_product` INT NOT NULL,' .
			'`ts_id` VARCHAR( 33 ) NOT NULL,' .
			'`id` INT NOT NULL,' .
			'`currency` VARCHAR( 3 ) NOT NULL,' .
			'`gross_fee` DECIMAL( 20, 6 ) NOT NULL,' .
			'`net_fee` DECIMAL( 20, 6 ) NOT NULL,' .
			'`protected_amount_decimal` INT NOT NULL,' .
			'`protection_duration_int` INT NOT NULL,' .
			'`ts_product_id` TEXT NOT NULL,' .
			'`creation_date` VARCHAR( 25 ) NOT NULL);';

		Db::getInstance()->Execute($query);

		$query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . TSCommon::DB_APPLI . '` (' .
			'`id_application` INT NOT NULL PRIMARY KEY,' .
			'`ts_id` VARCHAR( 33 ) NOT NULL,' .
			'`id_order` INT NOT NULL,' .
			'`statut_number` INT NOT NULL DEFAULT \'0\',' .
			'`creation_date` DATETIME NOT NULL,' .
			'`last_update` DATETIME NOT NULL);';

		Db::getInstance()->Execute($query);

		//add hidden category
		$category = new Category();

		foreach (self::$available_languages as $iso => $lang) {
			$language = Language::getIdByIso(strtolower($iso));

			$category->name[$language] = 'Trustedshops';
			$category->link_rewrite[$language] = 'trustedshops';
		}

		// If the default lang is different than available languages :
		// (Bug occurred otherwise)
		if (!array_key_exists(Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT')), self::$available_languages)) {
			$language = (int)Configuration::get('PS_LANG_DEFAULT');

			$category->name[$language] = 'Trustedshops';
			$category->link_rewrite[$language] = 'trustedshops';
		}

		$category->id_parent = 0;
		$category->level_depth = 0;
		$category->active = 0;
		$category->add();

		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'CAT_ID', intval($category->id));
		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'SECURE_KEY', strtoupper(Tools::passwdGen(16)));

		return (RatingAlert::createTable());
	}

	public function uninstall()
	{
		foreach (self::$available_languages as $iso => $lang)
			Configuration::deleteByName(TSCommon::PREFIX_TABLE . 'CERTIFICATE_' . strtoupper($iso));

		$category = new Category((int)TSCommon::$CAT_ID);
		$category->delete();

		Configuration::deleteByName(TSCommon::PREFIX_TABLE . 'CAT_ID');
		Configuration::deleteByName(TSCommon::PREFIX_TABLE . 'SHOPSW');
		Configuration::deleteByName(TSCommon::PREFIX_TABLE . 'ET_CID');
		Configuration::deleteByName(TSCommon::PREFIX_TABLE . 'ET_LID');
		Configuration::deleteByName(TSCommon::PREFIX_TABLE . 'ENV_API');
		Configuration::deleteByName(TSCommon::PREFIX_TABLE . 'SECURE_KEY');

		return (RatingAlert::dropTable());
	}

	/**
	 * Just for return the file path
	 * @return string
	 */
	public function getCronFilePath()
	{
		return $this->site_url . 'modules/' . self::$module_name . '/cron_garantee.php?secure_key=' . Configuration::get(TSCommon::PREFIX_TABLE . 'SECURE_KEY');
	}

	/**
	 * This method is used to access of TrustedShops API
	 * from a SoapClient object.
	 *
	 * @uses TSCommon::$webservice_urls with TSCommon::$ENV_API
	 *         To get the api url according to the environment (test or production)
	 * @param string $type
	 * @return SoapClient
	 */
	private function _getClient($type = TSCommon::WEBSERVICE_BO)
	{
		$url = TSCommon::$webservice_urls[$type][TSCommon::$ENV_API];
		$client = false;

		try {
			$client = new SoapClient($url);
		} catch (SoapFault $fault) {
			$this->errors[] = $this->l('Code #') . $fault->faultcode . ',<br />' . $this->l('message:') . $fault->faultstring;
		}

		return $client;
	}

	private function _isValidCertificateID($certificate)
	{
		if (!preg_match('/^(X){1}([0-9A-Z]{32})$/', $certificate))
			return false;

		foreach (self::$CERTIFICATES as $cert_info) {
			if (isset($cert_info['tsID']) && ($cert_info['tsID'] == $certificate))
				return false;
		}

		return true;
	}

	/**
	 * Checks the Trusted Shops IDs entered in the shop administration
	 * and returns the characteristics of the corresponding certificate.
	 *
	 * @uses TSCommon::_getClient()
	 * @param string $certificate certificate code already send by Trusted Shops
	 */
	private function _checkCertificate($certificate, $lang)
	{
		$array_state = array(
			'PRODUCTION' => $this->l('The certificate is valid'),
			'CANCELLED' => $this->l('The certificate has expired'),
			'DISABLED' => $this->l('The certificate has been disabled'),
			'INTEGRATION' => $this->l('The shop is currently being certified'),
			'INVALID_TS_ID' => $this->l('No certificate has been allocated to the Trusted Shops ID'),
			'TEST' => $this->l('Test certificate'),
		);

		$client = $this->_getClient();
		$validation = false;

		if ($lang == '')
			$this->errors[] = $this->l('Select language');
		elseif (!in_array($lang, self::$available_languages_for_adding))
			$this->errors[] = $this->l('This language is not in list of available languages for certificates');
		elseif ($this->_isValidCertificateID($certificate)) {
			try {
				$validation = $client->checkCertificate($certificate);
			} catch (SoapFault $fault) {
				$this->errors[] = $this->l('Code #') . $fault->faultcode . ',<br />' . $this->l('message:') . $fault->faultstring;
				return false;
			}

			if (is_int($validation))
				throw new TSBPException($validation, TSBPException::ADMINISTRATION);
			if (!$validation OR array_key_exists($validation->stateEnum, $array_state)) {
				if ($validation->stateEnum === 'TEST' ||
					$validation->stateEnum === 'PRODUCTION' ||
					$validation->stateEnum === 'INTEGRATION'
				) {
					$this->confirmations[] = $array_state[$validation->stateEnum];
					return $validation;
				} elseif ($validation->stateEnum == 'INVALID_TS_ID') {

					$filename = $this->getTempWidgetFilename($certificate);
					$cache = new WidgetCache(_PS_MODULE_DIR_ . $filename, $certificate);

					if (!$cache->isFresh())
						$cache->refresh();

					if (filesize(_PS_MODULE_DIR_ . $filename) > 1000) {
						$validation->certificationLanguage = $lang;
						$validation->stateEnum = 'PRODUCTION';
						$validation->typeEnum = 'UNKNOWN';

						return $validation;
					} else {
						$this->errors[] = $array_state[$validation->stateEnum];
						return false;
					}
				} else {
					$this->errors[] = $array_state[$validation->stateEnum];
					return false;
				}
			} else
				$this->errors[] = $this->l('Unknown error.');
		} else {
			$this->errors[] = $this->l('Invalid Certificate ID.');
		}
	}

	/**
	 * Checks the shop's web service access credentials.
	 *
	 * @uses TSCommon::_getClient()
	 * @param string $ts_id
	 * @param string $user
	 * @param string $password
	 */
	private function _checkLogin($ts_id, $user, $password)
	{
		$client = $this->_getClient();
		$return = 0;

		try {
			$return = $client->checkLogin($ts_id, $user, $password);
		} catch (SoapClient $fault) {
			$this->errors[] = $this->l('Code #') . $fault->faultcode . ',<br />' . $this->l('message:') . $fault->faultstring;
		}

		if ($return < 0)
			throw new TSBPException($return, TSBPException::ADMINISTRATION);

		return true;
	}

	/**
	 * Returns the characteristics of the buyer protection products
	 * that are allocated individually to each certificate by Trusted Shops.
	 *
	 * @uses TSCommon::_getClient()
	 * @param string $ts_id
	 */
	private function _getProtectionItems($ts_id)
	{
		$client = $this->_getClient();

		try {
			$items = $client->getProtectionItems($ts_id);

			// Sometimes an object could be send for the item attribute if there is only one result
			if (isset($items) && !is_array($items->item))
				$items->item = array(0 => $items->item);
		} catch (SoapFault $fault) {
			$this->errors[] = $this->l('Code #') . $fault->faultcode . ',<br />' . $this->l('message:') . $fault->faultstring;
		}

		return (isset($items->item)) ? $items->item : false;
	}

	/**
	 * Check validity for params required for TSCommon::_requestForProtectionV2()
	 *
	 * @param array $params
	 */
	private function _requestForProtectionV2ParamsValidator($params)
	{
		$bool_flag = true;

		$mandatory_keys = array(
			array('name' => 'tsID', 'validator' => array('isCleanHtml')),
			array('name' => 'tsProductID', 'validator' => array('isCleanHtml')),
			array('name' => 'amount', 'validator' => array('isFloat')),
			array('name' => 'currency', 'length' => 3, 'validator' => array('isString')),
			array('name' => 'paymentType', 'validator' => array('isString')),
			array('name' => 'buyerEmail', 'validator' => array('isEmail')),
			array('name' => 'shopCustomerID', 'validator' => array('isInt')),
			array('name' => 'shopOrderID', 'validator' => array('isInt')),
			array('name' => 'orderDate', 'ereg' => '#[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}#'),
			array('name' => 'shopSystemVersion', 'validator' => array('isCleanHtml')),
			array('name' => 'wsUser', 'validator' => array('isCleanHtml')),
			array('name' => 'wsPassword', 'validator' => array('isCleanHtml'))
		);

		foreach ($mandatory_keys as $key) {
			$bool_flag = (array_key_exists($key['name'], $params)) ? $bool_flag : false;

			if ($bool_flag) {
				if (isset($key['length']))
					$bool_flag = strlen((string)$params[$key['name']]) === $key['length'];
				if (isset($key['length-min']))
					$bool_flag = strlen((string)$params[$key['name']]) > $key['length-min'];
				if (isset($key['length-max']))
					$bool_flag = strlen((string)$params[$key['name']]) < $key['length-max'];
				if (isset($key['validator']))
					foreach ($key['validator'] as $validator)
						if (method_exists('Validate', $validator))
							$bool_flag = !Validate::$validator((string)$params[$key['name']]) ? false : $bool_flag;
				if (isset($key['ereg']))
					$bool_flag = !preg_match($key['ereg'], $params[$key['name']]) ? false : $bool_flag;
			}

			if (!$bool_flag) {
				$this->errors[] = sprintf($this->l('The field %s is wrong, please ensure it was correctly filled.'), $key['name']);
				break;
			}
		}
		return $bool_flag;
	}

	/**
	 * Create the Buyer Protection application by the web service.
	 * Applications are saved by Trusted Shops and are processed at regular intervals.
	 *
	 * @uses TSCommon::_getClient()
	 * @uses TSCommon::_requestForProtectionV2ParamsValidator()
	 *         to check required params
	 * @see TSCommon::cronTasks()
	 * @param array $params
	 */
	private function _requestForProtectionV2($params)
	{
		$code = 0;
		$client = $this->_getClient(TSCommon::WEBSERVICE_FO);
		$testing_params = $this->_requestForProtectionV2ParamsValidator($params);

		$query = '
			SELECT `id_order`' .
			'FROM `' . _DB_PREFIX_ . TSCommon::DB_APPLI . '`' .
			'WHERE `id_order` = "' . (int)$params['shopOrderID'] . '"';

		// If an order was already added, no need to continue.
		// Otherwise a new application is created by TrustedShops.
		// this can occurred when order confirmation page is reload.
		if (Db::getInstance()->getValue($query))
			return false;

		if ($testing_params) {
			try {
				$code = $client->requestForProtectionV2(
					$params['tsID'], $params['tsProductID'], $params['amount'],
					$params['currency'], $params['paymentType'], $params['buyerEmail'],
					$params['shopCustomerID'], $params['shopOrderID'], $params['orderDate'],
					$params['shopSystemVersion'], $params['wsUser'], $params['wsPassword']);

				if ($code < 0)
					throw new TSBPException($code, TSBPException::FRONT_END);
			} catch (SoapFault $fault) {
				$this->errors[] = $this->l('Code #') . $fault->faultcode . ',<br />' . $this->l('message:') . $fault->faultstring;
			} catch (TSBPException $e) {
				$this->errors[] = $e->getMessage();
			}

			if ($code > 0) {
				$date = date('Y-m-d H:i:s');

				$query = 'INSERT INTO `' . _DB_PREFIX_ . TSCommon::DB_APPLI . '` ' .
					'(`id_application`, `ts_id`, `id_order`, `creation_date`, `last_update` ) ' .
					'VALUES ("' . pSQL($code) . '", "' . pSQL($params['tsID']) . '", "' . pSQL($params['shopOrderID']) . '", "' . pSQL($date) . '", "' . pSQL($date) . '")';

				Db::getInstance()->Execute($query);

				// To reset product quantity in database.
				$query = 'SELECT `id_product` ' .
					'FROM `' . _DB_PREFIX_ . TSCommon::DB_ITEMS . '` ' .
					'WHERE `ts_product_id` = "' . pSQL($params['tsProductID']) . '"
					AND `ts_id` = "' . pSQL($params['tsID']) . '"';

				if (($id_product = Db::getInstance()->getValue($query))) {
					$product = new Product($id_product);
					$product->quantity = 1000;
					$product->update();
					unset($product);
				}
			}
		} else
			$this->errors[] = $this->l('Some parameters sending to "requestForProtectionV2" method are wrong or missing.');
	}

	/**
	 * With the getRequestState() method,
	 * the status of a guarantee application is requested
	 * and in the event of a successful transaction,
	 * the guarantee number is returned.
	 *
	 * @uses TSCommon::_getClient()
	 * @param array $params
	 * @throws TSBPException
	 */
	private function _getRequestState($params)
	{
		$client = $this->_getClient(TSCommon::WEBSERVICE_FO);
		$code = 0;

		try {
			$code = $client->getRequestState($params['tsID'], $params['applicationID']);

			if ($code < 0)
				throw new TSBPException($code, TSBPException::FRONT_END);
		} catch (SoapFault $fault) {
			$this->errors[] = $this->l('Code #') . $fault->faultcode . ',<br />' . $this->l('message:') . $fault->faultstring;
		} catch (TSBPException $e) {
			$this->errors[] = $e->getMessage();
		}

		return $code;
	}

	/**
	 * Check statut of last applications
	 * saved with TSCommon::_requestForProtectionV2()
	 *
	 * Negative value means an error occurred.
	 * Error code are managed in TSBPException.
	 * @see (exception) TSBPException::_getFrontEndMessage() method
	 *
	 * Trusted Shops recommends that the request
	 * should be automated by a cronjob with an interval of 10 minutes.
	 * @see /../cron_garantee.php
	 *
	 * A message is added to the sheet order in Back-office,
	 * @see Message class
	 *
	 * @uses TSCommon::_getRequestState()
	 * @uses Message class
	 * @return void
	 */
	public function cronTask()
	{
		// get the last 20min to get the api number (to be sure)
		$mktime = mktime(date('H'), date('i') - 20, date('s'), date('m'), date('d'), date('Y'));
		$date = date('Y-m-d H:i:s', $mktime);
		$db_name = _DB_PREFIX_ . TSCommon::DB_APPLI;

		$query = 'SELECT * ' .
			'FROM `' . $db_name . '` ' .
			'WHERE `last_update` >= "' . pSQL($date) . '" ' .
			'OR `statut_number` <= 0';

		$to_check = Db::getInstance()->ExecuteS($query);

		foreach ($to_check as $application) {
			$code = $this->_getRequestState(array('tsID' => $application['ts_id'], 'applicationID' => $application['id_application']));

			if (!empty($this->errors)) {
				$return_message = '<p style="color:red;">' . $this->l('Trusted Shops API returns an error concerning the application #') . $application['id_application'] . ': <br />' . implode(', <br />', $this->errors) . '</p>';
				$this->errors = array();
			} elseif ($code > 0)
				$return_message = sprintf($this->l('Trusted Shops application number %1$d was successfully processed. The guarantee number is: %2$d'), $application['id_application'], $code);

			$query = 'UPDATE `' . $db_name . '` ' .
				'SET `statut_number` = "' . pSQL($code) . '" ' .
				'WHERE `id_application` >= "' . pSQL($application['id_application']) . '"';

			Db::getInstance()->Execute($query);

			$msg = new Message();
			$msg->message = $return_message;
			$msg->id_order = (int)$application['id_order'];
			$msg->private = 1;
			$msg->add();
		}
	}

	/**
	 * Registration link to Trusted Shops
	 *
	 * @param string $shopsw
	 * @param string $et_cid
	 * @param string $et_lid
	 * @param string $lang
	 * @return boolean|string boolean in case of $lang is not supported by Trusted Shops
	 *           string return is the url to access of form subscription
	 */
	private function _makeRegistrationLink($shopsw, $et_cid, $et_lid, $lang)
	{
		if (array_key_exists($lang, $this->registration_link))
			return $this->registration_link[$lang] . sprintf('?shopsw=%s&et_cid=%s&et_lid=%s', urlencode($shopsw), urlencode($et_cid), urlencode($et_lid));
		return false;
	}

	/**
	 * Method to display or redirect the subscription link.
	 *
	 * @param string $link
	 */
	private function _getRegistrationLink($link)
	{
		return '<script type="text/javascript" >$().ready(function(){window.open("' . $link . '");});</script>
		<noscript><p><a href="' . $link . '" target="_blank" title="' . $this->l('Registration Link') . '" class="link">' . $this->l('Click to get the Registration Link') . '</a><p></noscript>';
	}

	/**
	 * saved paramter to acces of particular subscribtion link.
	 *
	 * @return string the registration link.
	 */
	private function _submitRegistrationLink()
	{
		// @todo : ask for more infos about values types
		TSCommon::$SHOPSW = (Validate::isCleanHtml(Tools::getValue('shopsw'))) ? Tools::getValue('shopsw') : '';
		TSCommon::$ET_CID = (Validate::isCleanHtml(Tools::getValue('et_cid'))) ? Tools::getValue('et_cid') : '';
		TSCommon::$ET_LID = (Validate::isCleanHtml(Tools::getValue('et_lid'))) ? Tools::getValue('et_lid') : '';

		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'SHOPSW', TSCommon::$SHOPSW);
		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'ET_CID', TSCommon::$ET_CID);
		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'ET_LID', TSCommon::$ET_LID);

		$link_registration = $this->_makeRegistrationLink(TSCommon::$SHOPSW, TSCommon::$ET_CID, TSCommon::$ET_LID, Tools::getValue('lang'));
		$this->confirmations[] = $this->l('Registration link has been created. Follow this link if you were not redirected earlier:') . '&nbsp;<a href="' . $link_registration . '" class="link">&gt;' . $this->l('Link') . '&lt;</a>';

		return $link_registration;
	}

	/**
	 * Save in special database each buyer protection product for a certificate,
	 * Each Trusted Shops particular characteristics is saved.
	 * Create a product in Prestashop database to allow added each of them in cart.
	 *
	 * @param array|stdClass $protection_items
	 * @param string $ts_id
	 */
	private function _saveProtectionItems($protection_items, $ts_id)
	{
		$query = 'DELETE ts, p, pl ' .
			'FROM `' . _DB_PREFIX_ . TSCommon::DB_ITEMS . '` AS ts ' .
			'LEFT JOIN `' . _DB_PREFIX_ . 'product` AS p ON ts.`id_product` = p.`id_product` ' .
			'LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` AS pl ON ts.`id_product` = pl.`id_product` ' .
			'WHERE ts.`ts_id`="' . pSQL($ts_id) . '"';

		Db::getInstance()->Execute($query);

		foreach ($protection_items as $item) {
			//add hidden product
			$product = new Product();

			foreach (self::$available_languages as $iso => $lang) {
				$language = Language::getIdByIso(strtolower($iso));

				if ((int)$language !== 0) {
					$product->name[$language] = 'TrustedShops guarantee';
					$product->link_rewrite[$language] = 'trustedshops_guarantee';
				}
			}

			// If the default lang is different than available languages :
			// (Bug occurred otherwise)
			if (!array_key_exists(Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT')), self::$available_languages)) {
				$product->name[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Trustedshops';
				$product->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = 'trustedshops';
			}

			// Add specifics translations
			$id_lang = Language::getIdByIso('de');
			if ((int)$id_lang > 0) $product->name[$id_lang] = 'Trusted Shops Käuferschutz';
			$id_lang = Language::getIdByIso('en');
			if ((int)$id_lang > 0) $product->name[$id_lang] = 'Trusted Shops buyer protection';
			$id_lang = Language::getIdByIso('fr');
			if ((int)$id_lang > 0) $product->name[$id_lang] = 'Trusted Shops protection acheteur';
			$id_lang = Language::getIdByIso('it');
			if ((int)$id_lang > 0) $product->name[$id_lang] = 'Trusted Shops protezione acquirenti';

			$product->quantity = 1000;
			$product->price = Tools::convertPrice($item->grossFee, Currency::getIdByIsoCode($item->currency));
			$product->id_category_default = TSCommon::$CAT_ID;
			$product->active = true;
			$product->visibility = 'none';
			$product->id_tax = 0;
			$product->add();

			if ($product->id) {
				$query = 'INSERT INTO `' . _DB_PREFIX_ . TSCommon::DB_ITEMS . '` ' .
					'(`creation_date`, `id_product`, `ts_id`, `id`, `currency`, `gross_fee`, `net_fee`, ' .
					'`protected_amount_decimal`, `protection_duration_int`, `ts_product_id`) ' .
					'VALUES ("' . pSQL($item->creationDate) . '", "' . pSQL($product->id) . '", "' . pSQL($ts_id) . '", ' .
					'"' . (int)$item->id . '", "' . pSQL($item->currency) . '", "' . pSQL($item->grossFee) . '", ' .
					'"' . pSQL($item->netFee) . '", "' . pSQL($item->protectedAmountDecimal) . '", ' .
					'"' . pSQL($item->protectionDurationInt) . '", "' . pSQL($item->tsProductID) . '")';

				Db::getInstance()->Execute($query);

				if (class_exists('StockAvailable')) {
					$id_stock_available = Db::getInstance()->getValue('
						SELECT s.`id_stock_available` FROM `' . _DB_PREFIX_ . 'stock_available` s
						WHERE s.`id_product` = ' . (int)$product->id);

					$stock = new StockAvailable($id_stock_available);
					$stock->id_product = $product->id;
					$stock->out_of_stock = 1;
					$stock->id_product_attribute = 0;
					$stock->quantity = 1000000;
					$stock->id_shop = Context::getContext()->shop->id;
					if ($stock->id)
						$stock->update();
					else
						$stock->add();
				}
			} else
				$this->errors['products'] = $this->l('Product wasn\'t saved.');
		}
	}

	/**
	 * Check a Trusted Shops certificate in shop for preview.
	 *
	 * @uses TSCommon::_getProtectionItems()
	 *         to get all buyer protection products from Trusted Shops
	 * @uses TSCommon::_saveProtectionItems()
	 *         to save buyer protection products in shop
	 * @return boolean true if certificate is added successfully, false otherwise
	 */
	private function _submitAddCertificate()
	{
		$checked_certificate = false;

		try {
			$checked_certificate = $this->_checkCertificate(ToolsCore::getValue('new_certificate'), Tools::getValue('lang'));
		} catch (TSBPException $e) {
			$this->errors[] = $e->getMessage();
		}

		return (bool)$checked_certificate;
	}

	/**
	 * Check, confirm and add a Trusted Shops certificate in shop.
	 *
	 * @uses TSCommon::_getProtectionItems()
	 *         to get all buyer protection products from Trusted Shops
	 * @uses TSCommon::_saveProtectionItems()
	 *         to save buyer protection products in shop
	 * @return boolean true if certificate is added successfully, false otherwise
	 */
	private function _submitConfirmCertificate()
	{
		$checked_certificate = false;

		try {
			$checked_certificate = $this->_checkCertificate(ToolsCore::getValue('new_certificate'), Tools::getValue('lang'));
		} catch (TSBPException $e) {
			$this->errors[] = $e->getMessage();
		}

		if ($checked_certificate) {
			TSCommon::$CERTIFICATES[strtoupper($checked_certificate->certificationLanguage)] = array(
				'stateEnum' => $checked_certificate->stateEnum,
				'typeEnum' => $checked_certificate->typeEnum,
				'tsID' => $checked_certificate->tsID,
				'url' => $checked_certificate->url,
				'user' => '',
				'password' => '',
				'variant' => 'default',
				'display_rating_front_end' => '1',
				'display_rating_oc' => '1',
				'send_separate_mail' => '0',
				'send_seperate_mail_delay' => '0',
				'send_seperate_mail_order_state' => Configuration::get('PS_OS_SHIPPING')
			);

			// update the configuration var
			Configuration::updateValue(TSCommon::PREFIX_TABLE . 'CERTIFICATE_' . strtoupper($checked_certificate->certificationLanguage), Tools::htmlentitiesUTF8(Tools::jsonEncode(TSCommon::$CERTIFICATES[strtoupper($checked_certificate->certificationLanguage)])));
			unset(self::$available_languages_for_adding[strtoupper($checked_certificate->certificationLanguage)]);
			$this->confirmations[] = $this->l('Certificate has been added successfully.');

			if ($checked_certificate->typeEnum !== 'UNKNOWN') {
				mail('trustedshops@silbersaiten.de', 'trusted shops buyer protection activation', $checked_certificate->tsID, "From: trustedshop@silbersaiten.de\r\nContent-type: text/plain\r\n");
			}

			if ($checked_certificate->typeEnum === 'EXCELLENCE') {
				try {
					$protection_items = $this->_getProtectionItems($checked_certificate->tsID);

					if ($protection_items)
						$this->_saveProtectionItems($protection_items, $checked_certificate->tsID);
				} catch (TSBPException $e) {
					$this->errors[] = $e->getMessage();
				}
			}
		}
		return (bool)$checked_certificate;
	}

	/**
	 * Change the certificate values.
	 * concerns only excellence certificate
	 * for payment type, login and password values.
	 *
	 * @uses TSCommon::_checkLogin()
	 * @return true;
	 */
	private function _submitChangeCertificate()
	{
		$all_payment_type = Tools::getValue('choosen_payment_type');
		$iso_lang = Tools::getValue('iso_lang');
		$password = Tools::getValue('password');
		$user = Tools::getValue('user');

		if ($user != '' AND $password != '') {
			TSCommon::$CERTIFICATES[$iso_lang]['payment_type'] = array();
			$check_login = false;

			if ($all_payment_type)
				if (is_array($all_payment_type))
					foreach ($all_payment_type as $key => $module_id)
						TSCommon::$CERTIFICATES[$iso_lang]['payment_type'][(string)$key] = $module_id;

			try {
				$check_login = $this->_checkLogin(TSCommon::$CERTIFICATES[$iso_lang]['tsID'], $user, $password);
			} catch (TSBPException $e) {
				$this->errors[] = $e->getMessage();
			}

			if ($check_login) {
				TSCommon::$CERTIFICATES[$iso_lang]['user'] = $user;
				TSCommon::$CERTIFICATES[$iso_lang]['password'] = $password;

				Configuration::updateValue(TSCommon::PREFIX_TABLE . 'CERTIFICATE_' . $iso_lang, Tools::htmlentitiesUTF8(Tools::jsonEncode(TSCommon::$CERTIFICATES[$iso_lang])));
				$this->confirmations[] = $this->l('Certificate login has been successful.');

			} else
				$this->errors[] = $this->l('Certificate login failed');
		} else
			$this->errors[] = $this->l('You have to set a username and a password before any changes can be made.');

		return true;
	}

	private function _submitChangeOptionsCertificate()
	{
		$variant = Tools::getValue('variant', 'default');
		$display_rating_front_end = Tools::getValue('display_rating_front_end', '0');
		$display_rating_oc = Tools::getValue('display_rating_oc', '0');
		$send_separate_mail = Tools::getValue('send_separate_mail', '0');
		$send_seperate_mail_delay = Tools::getValue('send_seperate_mail_delay', '0');
		$send_seperate_mail_order_state = Tools::getValue('send_seperate_mail_order_state', '0');
		$iso_lang = strtoupper(Tools::getValue('iso_lang'));


		TSCommon::$CERTIFICATES[$iso_lang] = array_merge(TSCommon::$CERTIFICATES[$iso_lang], array(
			'variant' => $variant,
			'display_rating_front_end' => $display_rating_front_end,
			'display_rating_oc' => $display_rating_oc,
			'send_separate_mail' => $send_separate_mail,
			'send_seperate_mail_delay' => $send_seperate_mail_delay,
			'send_seperate_mail_order_state' => $send_seperate_mail_order_state
		));


		//update the configuration var
		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'CERTIFICATE_' . $iso_lang, Tools::htmlentitiesUTF8(Tools::jsonEncode(TSCommon::$CERTIFICATES[$iso_lang])));

		//print_r(Configuration::get(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.$iso_lang)); exit;

		$this->confirmations[] = $this->l('Certificate options has been modified successfully.');


		//$this->errors[] = $this->l('You have to set a username and a password before any changes can be made.');

		return true;
	}


	/**
	 * Change the environment for working.
	 * Not use anymore but keeped
	 * @return true
	 */
	private function _submitEnvironment()
	{
		TSCommon::$ENV_API = Tools::getValue('env_api');
		Configuration::updateValue(TSCommon::PREFIX_TABLE . 'ENV_API', TSCommon::$ENV_API);

		return true;
	}

	/*
	 ** Update the env_api
	 */
	public function _setEnvApi($env_api)
	{
		if (Configuration::get(TSCommon::PREFIX_TABLE . 'ENV_API') != $env_api)
			Configuration::updateValue(TSCommon::PREFIX_TABLE . 'ENV_API', $env_api);
		TSCommon::$ENV_API = $env_api;
	}

	/**
	 * Dispatch post process depends on each formular
	 *
	 * @return array depend on the needs about each formular.
	 */
	private function _preProcess()
	{
		$posts_return = array();

		/*if (Tools::isSubmit('submit_registration_link'))
			$posts_return['registration_link'] = $this->_submitRegistrationLink();*/

		//add certificate
		if (Tools::isSubmit('submit_add_certificate'))
			$posts_return['add_certificate'] = $this->_submitAddCertificate();

		//confirm certificate
		if (Tools::isSubmit('submit_confirm_certificate'))
			$posts_return['confirm_certificate'] = $this->_submitConfirmCertificate();

		$edit = Tools::getValue('certificate_edit', '');
		$delete = Tools::getValue('certificate_delete', '');
		$options = Tools::getValue('certificate_options', '');

		// delete certificate
		if (($delete != '') AND isset(TSCommon::$CERTIFICATES[$delete]['tsID'])) {
			$certificate_to_delete = TSCommon::$CERTIFICATES[$delete]['tsID'];
			Configuration::deleteByName(TSCommon::PREFIX_TABLE . 'CERTIFICATE_' . strtoupper($delete));
			unset(TSCommon::$CERTIFICATES[$delete]);
			$this->confirmations[] = $this->l('The certificate')
				. ' "' . $certificate_to_delete . '" (' . $this->l('language') . ' : ' . $delete . ') '
				. $this->l('has been deleted successfully');
		}

		// edit cert
		if ($edit != '') {
			$posts_return['edit_certificate'] = $edit;
		}

		// set options of cert
		if ($options != '') {
			$posts_return['options_certificate'] = $options;
		}


		if (Tools::isSubmit('submit_change_certificate'))
			$posts_return['change_certificate'] = $this->_submitChangeCertificate();

		if (Tools::isSubmit('submit_changeoptions_certificate'))
			$posts_return['changeoptions_certificate'] = $this->_submitChangeOptionsCertificate();

		return $posts_return;
	}

	/**
	 * Display each formaular in back-office
	 *
	 * @see Module::getContent()
	 * @return string for displaying form.
	 */
	public function getContent()
	{
		$bool_display_certificats = false;
		$posts_return = $this->_preProcess();

		$out = $this->_displayPresentation();
		$out .= $this->_displayFormAddCertificate(isset($posts_return['add_certificate']) && $posts_return['add_certificate']);

		if (is_array(self::$CERTIFICATES))
			foreach (self::$CERTIFICATES as $certif)
				$bool_display_certificats = (isset($certif['tsID']) && $certif['tsID'] != '') ? true : $bool_display_certificats;

		if ($bool_display_certificats)
			$out .= $this->_displayFormCertificatesList();

		if (isset($posts_return['edit_certificate']))
			$out .= $this->_displayFormEditCertificate($posts_return['edit_certificate']) . '<br />';

		if (isset($posts_return['options_certificate']))
			$out .= $this->_displayFormOptionsCertificate($posts_return['options_certificate']) . '<br />';


		$out .= $this->_displayInfoCronTask();

		//Context::getContext()->smarty->registerFilter('output', array($this, 'smarty_outputfilter_zoo'));

		return $out;
	}

	/*function smarty_outputfilter_zoo($output, Smarty_Internal_Template $template)
	{
		return str_replace('[gallery:id]',
			'test custom tag displayed', $output);
	}*/

	private function _getLinkConfigureModule()
	{
		return Context::getContext()->link->getAdminLink('AdminModules', true) .
		'&configure=' . urlencode(self::$translation_object->name) .
		'&tab_module=' . self::$translation_object->tab .
		'&module_name=' . urlencode(self::$translation_object->name) .
		'&token=' . Tools::getAdminTokenLite('AdminModules');
	}

	private function _displayPresentation()
	{
		return '<div class="row">' . $this->l('You are currently using the mode :') . ' <b>' . TSCommon::$ENV_API . '</b></div>';
	}

	private function _displayFormRegistrationLink($link = false)
	{
		$out = '
		<form action="' . $this->_makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab) . '" method="post" >
			<fieldset>
				<legend><img src="../img/admin/cog.gif" alt="" />' . $this->l('Get the Registration Link') . '</legend>
				<p>' . $this->l('This variable was sent to you via e-mail by TrustedShops') . '</p>
				<label>' . $this->l('Internal identification of shop software at Trusted Shops') . '</label>
				<div class="margin-form">
					<input type="text" name="shopsw" value="' . TSCommon::$SHOPSW . '"/>
				</div>
				<br />
				<br class="clear" />
				<label>' . $this->l('Etracker channel') . '</label>
				<div class="margin-form">
					<input type="text" name="et_cid" value="' . TSCommon::$ET_CID . '"/>
				</div>
				<br class="clear" />
				<label>' . $this->l('Etracker campaign') . '</label>
				<div class="margin-form">
					<input type="text" name="et_lid" value="' . TSCommon::$ET_LID . '"/>
				</div>
				<label>' . $this->l('Language') . '</label>
				<div class="margin-form">
					<select name="lang" >';

		foreach (self::$available_languages as $iso => $lang)
			if (is_array($lang))
				$out .= '<option value="' . $iso . '" ' . ((int)$lang['id_lang'] === TSCommon::$DEFAULT_LANG ? 'selected' : '') . '>' . $lang['name'] . '</option>';

		$out .= '</select>
				</div>
				<div style="text-align:center;">';
		// If Javascript is deactivated
		if ($link !== false)
			$out .= $this->_getRegistrationLink($link);
		$out .= '<input type="submit" name="submit_registration_link" class="button" value="' . $this->l('send') . '"/>
				</div>
			</fieldset>
		</form>';

		return $out;
	}

	private function _displayFormAddCertificate($preview = false)
	{
		$lang_options = '<option value="">'.$this->l('select language').'</option>';

		foreach (TSCommon::$available_languages_for_adding as $iso) {
			$lang_options .= '<option value=\''.$iso.'\' '.((Tools::getValue('lang') == $iso)?'selected':'').'>'.$iso.'</option>';
		}

		$out = '
		<form action="' . $this->_makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab) . '" method="post" class="form-horizontal">
			<div class="panel">
			    <div class="panel-heading">' . $this->l('Add Trusted Shops certificate') . '</div>' .
			'<div class="form-wrapper">
			<div class="form-group">
				<label class="control-label col-lg-3">' . $this->l('New certificate') . '</label>
                        <div class="col-lg-4">
                            <input type="text" name="new_certificate" value="" maxlength="33"/>&nbsp;&nbsp;
                        </div>
                        <div class="col-lg-2">
                            <select name="lang">
                            '.$lang_options.'
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <input type="submit" name="submit_confirm_certificate" class="btn btn-default" value="' . $this->l('Add it') . '"/>
                        </div>
                    </div>
                </div>
			</div>
		</form>';

		return $out;
	}

	private function _displayFormCertificatesList()
	{
		$out = '
		<script type="text/javascript">
			$().ready(function()
			{
				$(\'#certificate_list\').find(\'input[type=checkbox]\').click(function()
				{
					$(\'#certificate_list\').find(\'input[type=checkbox]\').not($(this)).removeAttr(\'checked\');
				});
			});
		</script>
		<form action="' . $this->_makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab) . '" class="form-horizontal" method="post" >
		<div class="panel">
			<div class="panel-heading">' . $this->l('Manage Trusted Shops certificates') . '</div>
				<table width="100%" class="table">
					<thead>
						<tr style="text-align:center;">
							<th>' . $this->l('Certificate') . '</th>
							<th>' . $this->l('Language') . '</th>
							<th>' . $this->l('State') . '</th>
							<th>' . $this->l('Type') . '</th>
							<th>' . $this->l('Shop url') . '</th>
							<th>' . $this->l('Variant') . '</th>
							<th>' . $this->l('Edit') . '</th>
							<th>' . $this->l('Options') . '</th>
							<th>' . $this->l('Delete') . '</th>
						</tr>
					</thead>
					<tbody id="certificate_list">';

		foreach (TSCommon::$CERTIFICATES as $lang => $certificate) {
			$certificate = (array)$certificate;

			if (isset($certificate['tsID']) AND $certificate['tsID'] !== '') {
				$out .= '
						<tr style="text-align:center;">
							<td>' . $certificate['tsID'] . '</td>
							<td>' . $lang . '</td>
							<td>' . $certificate['stateEnum'] . '</td>
							<td>' . $certificate['typeEnum'] . '</td>
							<td>' . $certificate['url'] . '</td>
							<td>' . $certificate['variant'] . '</td>
							<td>';

				if ($certificate['typeEnum'] === 'EXCELLENCE') {
					$out .= '<a href="' . $this->_getLinkConfigureModule() . '&certificate_edit=' . $lang . '" class="btn btn-default">' . $this->l('Edit') . '</a>';
					$out .= $certificate['user'] == '' ? '<br /><b style="color:red;font-size:0.7em;">' . $this->l('Login or password missing') . '</b>' : '';
				} else
					$out .= $this->l('No need');

				$out .= '
							</td>
							<td>';
				if ($certificate['typeEnum'] === 'EXCELLENCE' || $certificate['typeEnum'] === 'CLASSIC' || $certificate['typeEnum'] === 'UNKNOWN')
					$out .= '<a href="' . $this->_getLinkConfigureModule() . '&certificate_options=' . $lang . '" class="btn btn-default">' . $this->l('Options') . '</a>';
				else
					$out .= $this->l('No need');

				$out .= '
							</td>
                            <td>';
				if ($certificate['typeEnum'] === 'EXCELLENCE' || $certificate['typeEnum'] === 'CLASSIC' || $certificate['typeEnum'] === 'UNKNOWN')
					$out .= '<a href="' . $this->_makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab) . '&certificate_delete=' . $lang . '" class="btn btn-default">' . $this->l('Delete') . '</a>';
				else
					$out .= $this->l('No need');

				$out .= '
							</td>
						</tr>';
			}
		}

		$out .= '
					</tbody>
				</table>
				<!--div style="text-align:center;"><input type="submit" name="submit_edit_certificate" class="btn btn-default" value="' . $this->l('Edit certificate') . '"/></div-->
			</div>
		</form>
		';

		return $out;
	}

	/**
	 * Check if a module is payment module.
	 *
	 * Method instanciate a $module by its name,
	 * Module::getInstanceByName() rather than Module::getInstanceById()
	 * is used for cache improvement and avoid an sql request.
	 *
	 * Method test if PaymentMethod::getCurrency() is a method from the module.
	 *
	 * @see Module::getInstanceByName() in classes/Module.php
	 * @param string $module name of the module
	 */
	private static function _isPaymentModule($module)
	{
		$return = false;
		$module = Module::getInstanceByName($module);

		if (method_exists($module, 'getCurrency'))
			$return = clone $module;

		unset($module);

		return $return;
	}

	private function _displayFormEditCertificate($lang)
	{
		$certificate = TSCommon::$CERTIFICATES[$lang];

		$payment_module_collection = array();
		$installed_modules = Module::getModulesInstalled();

		foreach ($installed_modules as $value)
			if ($return = TSCommon::_isPaymentModule($value['name']))
				$payment_module_collection[$value['id_module']] = $value;

		$out = '
		<script type="text/javascript" src="' . $this->site_url . 'modules/trustedshops/js/payment.js" ></script>
		<script type="text/javascript">
			$().ready(function()
			{
				TSPayment.payment_type = $.parseJSON(\'' . Tools::jsonEncode(TSCommon::$payments_type) . '\');
				TSPayment.payment_module = $.parseJSON(\'' . Tools::jsonEncode($payment_module_collection) . '\');
				$(\'.payment-module-label\').css(TSPayment.module_box.css).fadeIn();
				$(\'.choosen_payment_type\').each(function()
				{
					TSPayment.deleteModuleFromList($(this).val());
					TSPayment.setLabelModuleName($(this).val());
				});
				TSPayment.init();
			});

		</script>
		<form action="' . $this->_makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab) . '" class="form-horizontal" method="post" >
		<div class="panel">
			<div class="panel-heading">' . $this->l('Edit certificate') . '</div>
			<div class="form-wrapper">
				<input type="hidden" name="iso_lang" value="' . $lang . '" />
				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Language') . '</label>
                    <div class="col-lg-4">' . $lang . '</div>
				</div>
				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Shop url') . '</label>
				    <div class="col-lg-4">' . $certificate['url'] . '</div>
				</div>
				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Certificate id') . '</label>
				    <div class="col-lg-4">' . $certificate['tsID'] . '</div>
				</div>
				<div class="form-group">
                    <label class="control-label col-lg-3 required">' . $this->l('User Name') . '</label>
				    <div class="col-lg-4"><input type="text" name="user" value="' . $certificate['user'] . '"/></div>
				</div>
				<div class="form-group">
				    <label class="control-label col-lg-3 required">' . $this->l('Password') . '</label>
				    <div class="col-lg-4"><input type="text" name="password" value="' . $certificate['password'] . '" style="width:300px;"/></div>
				</div>
				<div id="payment-type" class="form-group">
					<label class="control-label col-lg-3 required">' . $this->l('Payment type to edit') . '</label>
					<div class="col-lg-4">
						<select name="payment_type">';

		foreach (TSCommon::$payments_type as $type => $translation)
			$out .= '	<option value="' . $type . '" >' . $translation . '</option>';

		$out .= '		</select>&nbsp;'
			. $this->l('with')
			. '&nbsp;
						<select name="payment_module">';

		foreach ($payment_module_collection as $module_info)
			$out .= '		<option value="' . $module_info['id_module'] . '" >' . $module_info['name'] . '</option>';

		$out .= '		</select>&nbsp;'
			. $this->l('payment module')
			. '&nbsp;<input type="button" value="' . $this->l('Add it') . '" class="btn btn-default" name="add_payment_module" />
					</div><!-- .margin-form -->
					<div id="payment_type_list" class="form-group">';
		$input_output = '';

		if (isset($certificate['payment_type']) AND !empty($certificate['payment_type'])) {
			foreach ($certificate['payment_type'] as $payment_type => $modules) {
				$out .= '	<label style="clear:both;" class="payment-type-label control-label col-lg-3" >' . TSCommon::$payments_type[$payment_type] . '</label>';
				$out .= '	<div class="col-lg-4" id="block-payment-' . $payment_type . '">';
				foreach ($modules as $module_id) {
					$out .= '<b class="payment-module-label" id="label-module-' . $module_id . '"></b>';
					$input_output .= '<input type="hidden" value="' . $module_id . '" class="choosen_payment_type" name="choosen_payment_type[' . $payment_type . '][]">';
				}
				$out .= '	</div><!-- .margin-form -->';
			}
		}

		$out .= '</div><!-- #payment_type_list -->
			</div><!-- #payment-type -->
			<p id="input-hidden-val" style="display:none;">' . $input_output . '</p>
			<p style="text-align:center;">
				<input type="submit" name="submit_change_certificate" class="btn btn-default" value="' . $this->l('Update it') . '"/>
			</p>
			</div>
			</div>
		</form>';

		return $out;
	}

	private function _displayFormOptionsCertificate($lang)
	{

		$certificate = TSCommon::$CERTIFICATES[$lang];
		$out = '';
		// JAVASCRIPT
		$javascript = '<script language="javascript">';

		if (!$certificate['send_separate_mail'])
			$javascript .= '$("document").ready( function() { $("#send_seperate_mail_infos").hide(); });';

		$javascript .= 'function toggleSendMailInfos()
					 	 {
							if (!$("input[name=send_separate_mail]").attr("checked")) {
							    $("#send_seperate_mail_infos").hide();
								alert("' . $this->l('Warning, all the existing rating alerts will be deleted') . '");
					        } else {
					           $("#send_seperate_mail_infos").show();
					        }
						}
						</script>';

		$out .= $javascript;

		$out .= '
		<form action="' . $this->_makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab) . '" class="form-horizontal" method="post" >
		<div class="panel">
			<div class="panel-heading">' . $this->l('Edit options') . '</div>
			<div class="form-wrapper">
				<input type="hidden" name="iso_lang" value="' . $lang . '" />
				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Language') . '</label>
                    <div class="col-lg-4">' . $lang . '</div>
				</div>
				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Shop url') . '</label>
				    <div class="col-lg-4">' . $certificate['url'] . '</div>
				</div>
				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Certificate id') . '</label>
				    <div class="col-lg-4">' . $certificate['tsID'] . '</div>
				</div>
				<div class="form-group">
				    <div class="col-lg-offset-1"><strong>' . $this->l('Trusted Shops Seal of Approval') . '</strong></div>
				</div>
				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Variant') . '</label>
				    <div class="col-lg-4"><select name="variant">';
		foreach ($this->available_seal_variants as $k => $v) {
			$out .= "<option value='" . $k . "'" . (($k == $certificate['variant']) ? 'selected' : '') . ">" . $v . "</option>";
		}


		/*

		 Configuration::get('TS_DISPLAY_RATING_FRONT_END'
		 Configuration::get('TS_TAB0_DISPLAY_RATING_OC')
		 Configuration::get('TS_TAB0_SEND_SEPERATE_MAIL')
		 Configuration::get('TS_TAB0_SEND_SEPERATE_MAIL_DELAY')
		*/

		$out .= '</select>
				    </div>
				</div>
				<div class="form-group">
				    <div class="col-lg-offset-1"><strong>' . $this->l('Trusted Shops Customer Rating') . '</strong></div>
				</div>
				<div class="form-group">
				    <div class="col-lg-offset-1">' . $this->l('Start collecting 100% real customer ratings now! Integrate rating request and rating widget in your shop and show honest interest in you customer\'s opinions.') . '</div>
				</div>
				<div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Display rating link in shop front-end') . '</label>
                    <div class="col-lg-4">
                        <input type="checkbox" name="display_rating_front_end" value="1" ' . ($certificate['display_rating_front_end'] ? 'checked' : '') . '/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Display rating link on order confirmation page') . '</label>
                    <div class="col-lg-4">
                        <input type="checkbox" name="display_rating_oc" value="1" ' . ($certificate['display_rating_oc'] ? 'checked' : '') . '/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3">' . $this->l('Send rating link in separate e-mail') . '</label>
                    <div class="col-lg-4">
                        <input onclick="toggleSendMailInfos()" type="checkbox" name="send_separate_mail" value="1" ' . ($certificate['send_separate_mail'] ? 'checked' : '') . '/> <br />
                        <div id="send_seperate_mail_infos">' .
			$this->l('Send the email after') .
			'<input class="" size="2" type="text" name="send_seperate_mail_delay" value="' . (int)($certificate['send_seperate_mail_delay']) . '" />' .
			$this->l('days') . ' of setting order to state : ' .
			'<select name="send_seperate_mail_order_state">';
		foreach (OrderState::getOrderStates(Context::getContext()->language->id) as $order_state) {
			$out .= '<option value="' . $order_state['id_order_state'] . '"' .
				(($order_state['id_order_state'] == (int)($certificate['send_seperate_mail_order_state'])) ? ' selected="selected"' : '') .
				'>' . $order_state['name'] . '</option>';
		}
		$out .= '</select>' .
			'<span style="color: #CC0000; font-weight: bold;">' . $this->l('IMPORTANT:') . '</span> ' . $this->l('Put this URL in crontab or call it manually daily:') . '<br />'
			. self::getHttpHost(true, true) . _MODULE_DIR_ . self::$module_name . '/cron.php?secure_key=' . Configuration::get(TSCommon::PREFIX_TABLE . 'SECURE_KEY') .
			'</div>
		  </div>
		</div>
	';
		$out .= '</div>
			</div>
			<p style="text-align:center;">
				<input type="submit" name="submit_changeoptions_certificate" class="btn btn-default" value="' . $this->l('Update it') . '"/>
			</p>
			</div>
			</div>
		</form>';

		return $out;
	}

	private function _displayInfoCronTask()
	{
		$out = '<div class="panel">
			        <div class="panel-heading"><img src="../img/admin/warning.gif" alt="" />' . $this->l('Cronjob configuration') . '</div>';
		$out .= '<p>'
			. $this->l('If you are using a Trusted Shops EXCELLENCE cetificate in your shop, set up a cron job on your web server.') . '<br />'
			. $this->l('Run the script file ') . ' <b style="color:red;">' . $this->getCronFilePath() . '</b> ' . $this->l('with an interval of 10 minutes.') . '<br /><br />'
			. $this->l('The corresponding line in your cron file may look like this:') . ' <br /><b style="color:red;">*/10 * * * * ' . $this->getCronFilePath() . '>/dev/null 2>&1</b><br />'
			. '</p>';
		$out .= '</div>';

		return $out;
	}

	public function hookRightColumn($params)
	{
		$iso_lang = $iso_cert = strtoupper(Language::getIsoById($params['cookie']->id_lang));

		$tab_id = false;

		if (isset(TSCommon::$CERTIFICATES[strtoupper($iso_cert)]['tsID']))
			$tab_id = TSCommon::$CERTIFICATES[strtoupper($iso_cert)]['tsID'];

		if (!$tab_id)
			return false;

		if (isset(TSCommon::$CERTIFICATES[$iso_cert]['tsID'])) {

			TSCommon::$smarty->assign('trusted_shops_id', TSCommon::$CERTIFICATES[$iso_cert]['tsID']);
			TSCommon::$smarty->assign('variant', isset(TSCommon::$CERTIFICATES[$iso_cert]['variant']) ? (in_array(TSCommon::$CERTIFICATES[$iso_cert]['variant'], array_keys($this->available_seal_variants)) ? TSCommon::$CERTIFICATES[$iso_cert]['variant'] : 'default') : 'default');
			TSCommon::$smarty->assign('onlineshop_name', ConfigurationCore::get('PS_SHOP_NAME'));

			$url = str_replace(array('#shop_id#', '#shop_name#'), array(
					TSCommon::$CERTIFICATES[$iso_cert]['tsID'],
					urlencode(str_replace('_', '-', ConfigurationCore::get('PS_SHOP_NAME')))
				),
				TSCommon::$certificate_link[$iso_lang]);
			TSCommon::$smarty->assign('trusted_shops_url', $url);

			if (isset(TSCommon::$CERTIFICATES[$iso_cert])) {
				$certificate = TSCommon::$CERTIFICATES[$iso_cert];

				if (isset($certificate['tsID']) && ($certificate['typeEnum'] == 'CLASSIC' ||
						$certificate['typeEnum'] == 'UNKNOWN' ||
						($certificate['typeEnum'] == 'EXCELLENCE' && $certificate['user'] != '' && $certificate['password'] != ''))
				)
					return TrustedShops::displaySeal();
			}
		}

		return '';
	}

	public function hookFooter($params)
	{
		return $this->hookRightColumn($params);
	}

	/**
	 * For Excellence certificate display Buyer protection products.
	 * An error message if the certificate is not totally filled
	 *
	 * @param array $params
	 * @return string tpl content
	 */
	public function hookPaymentTop($params)
	{
		$lang = strtoupper(Language::getIsoById($params['cookie']->id_lang));

		if (!isset(TSCommon::$CERTIFICATES[$lang]) ||
			!isset(TSCommon::$CERTIFICATES[$lang]['typeEnum'])
		)
			return '';

		// This hook is available only with EXCELLENCE certificate.
		if (TSCommon::$CERTIFICATES[$lang]['typeEnum'] == 'CLASSIC' ||
			(TSCommon::$CERTIFICATES[$lang]['stateEnum'] !== 'INTEGRATION' &&
				TSCommon::$CERTIFICATES[$lang]['stateEnum'] !== 'PRODUCTION' &&
				TSCommon::$CERTIFICATES[$lang]['stateEnum'] !== 'TEST')
		)
			return '';

		// If login parameters missing for the certificate an error occurred
		if ((TSCommon::$CERTIFICATES[$lang]['user'] == '' ||
				TSCommon::$CERTIFICATES[$lang]['password'] == '') &&
			TSCommon::$CERTIFICATES[$lang]['typeEnum'] == 'EXCELLENCE'
		)
			return '';

		// Set default value for an unexisting item
		TSCommon::$smarty->assign('item_exist', false);
		if (array_key_exists($lang, self::$available_languages)) {
			$currency = new Currency((int)$params['cookie']->id_currency);

			$query = '
				SELECT * ' .
				'FROM `' . _DB_PREFIX_ . TSCommon::DB_ITEMS . '` ' .
				'WHERE ts_id ="' . pSQL(TSCommon::$CERTIFICATES[$lang]['tsID']) . '" ' .
				'AND `protected_amount_decimal` >= "' . (int)$params['cart']->getOrderTotal(true, Cart::BOTH) . '" ' .
				'AND `currency` = "' . pSQL($currency->iso_code) . '" ' .
				'ORDER BY `protected_amount_decimal` ASC';

			// If amout is bigger, get the max one requested by TS
			if (!$item = Db::getInstance()->getRow($query)) {
				$query = '
					SELECT *, MAX(protected_amount_decimal) ' .
					'FROM `' . _DB_PREFIX_ . TSCommon::DB_ITEMS . '` ' .
					'WHERE ts_id ="' . pSQL(TSCommon::$CERTIFICATES[$lang]['tsID']) . '" ' .
					'AND `currency` = "' . pSQL($currency->iso_code) . '"';

				$item = Db::getInstance()->getRow($query);
			}

			if ($item && count($item))
				TSCommon::$smarty->assign(array(
						'item_exist' => true,
						'shop_id' => TSCommon::$CERTIFICATES[$lang]['tsID'],
						'buyer_protection_item' => $item,
						'currency', $currency)
				);
		}

		/**
		 * We need to clean the cart of other TSCommon product, in case the customer wants to change the currency
		 * The price of a TSCommon product is different for each currency, the conversion_rate won't change anything
		 */

		$query = 'SELECT id_product ' .
			'FROM `' . _DB_PREFIX_ . TSCommon::DB_ITEMS . '`';

		$product = Db::getInstance()->ExecuteS($query);

		$product_protection = array();

		foreach ($product as $item)
			$product_protection[] = $item['id_product'];

		// TODO : REWRITE this part because it's completely not a good way (Control  + R, add Product dynamically)
		foreach ($params['cart']->getProducts() as $item)
			if (in_array($item['id_product'], $product_protection))
				$params['cart']->deleteProduct($item['id_product']);

		return $this->display(TSCommon::$module_name, '/views/templates/front/display_products.tpl');
	}

	/**
	 * This prepare values to create the Trusted Shops web service
	 * for Excellence certificate.
	 *
	 * @see TSCommon::_requestForProtectionV2() method
	 * @param array $params
	 * @param string $lang
	 * @return string empty if no error occurred or no item was set.
	 */
	private function _orderConfirmationExcellence($params, $lang)
	{
		$currency = new Currency((int)$params['objOrder']->id_currency);
		$order_products = $params['objOrder']->getProducts();
		$order_item_ids = array();

		foreach ($order_products as $product)
			$order_item_ids[] = (int)$product['product_id'];

		$query = 'SELECT * ' .
			'FROM `' . _DB_PREFIX_ . TSCommon::DB_ITEMS . '` ' .
			'WHERE `id_product` IN (' . implode(',', $order_item_ids) . ') ' .
			'AND `ts_id` ="' . pSQL(TSCommon::$CERTIFICATES[$lang]['tsID']) . '" ' .
			'AND `currency` = "' . pSQL($currency->iso_code) . '"';

		if (!($item = Db::getInstance()->getRow($query)))
			return '';

		$customer = new Customer($params['objOrder']->id_customer);
		$payment_module = Module::getInstanceByName($params['objOrder']->module);
		$arr_params = array();

		$arr_params['paymentType'] = '';
		foreach (TSCommon::$CERTIFICATES[$lang]['payment_type'] as $payment_type => $id_modules)
			if (in_array($payment_module->id, $id_modules)) {
				$arr_params['paymentType'] = (string)$payment_type;
				break;
			}

		if ($arr_params['paymentType'] == '')
			$arr_params['paymentType'] = 'OTHER';

		$arr_params['tsID'] = TSCommon::$CERTIFICATES[$lang]['tsID'];
		$arr_params['tsProductID'] = $item['ts_product_id'];
		$arr_params['amount'] = $params['total_to_pay'];
		$arr_params['currency'] = $currency->iso_code;
		$arr_params['buyerEmail'] = $customer->email;
		$arr_params['shopCustomerID'] = $customer->id;
		$arr_params['shopOrderID'] = $params['objOrder']->id;
		$arr_params['orderDate'] = date('Y-m-d\TH:i:s', strtotime($params['objOrder']->date_add));
		$arr_params['shopSystemVersion'] = 'Prestashop ' . _PS_VERSION_;
		$arr_params['wsUser'] = TSCommon::$CERTIFICATES[$lang]['user'];
		$arr_params['wsPassword'] = TSCommon::$CERTIFICATES[$lang]['password'];

		$this->_requestForProtectionV2($arr_params);

		if (!empty($this->errors))
			return '<p style="color:red">' . implode('<br />', $this->errors) . '</p>';

		return '';
	}

	/**
	 * Trusted Shops Buyer Protection is integrated at the end of the checkout
	 * as a form on the order confirmation page.
	 * At the moment the customer clicks the registration button,
	 * the order data is processed to Trusted Shops.
	 * The customer confirms the Buyer Protection on the Trusted Shops site.
	 * The guarantee is then booked and the customer receives an email by Trusted Shops.
	 *
	 * @param array $params
	 * @param string $lang
	 * @return string tpl content
	 */
	private function _orderConfirmationClassic($params, $lang)
	{
		$payment_type = 'OTHER';

		// Payment type for native module list
		/*
		DIRECT_DEBIT                               Lastschrift / Bankeinzug
		CREDIT_CARD                                Kreditkarte
		INVOICE                                    Rechnung
		CASH_ON_DELIVERY                           Nachnahme
		PREPAYMENT                                 Vorauskasse / Überweisung
		CHEQUE                                     Verrechnungsscheck
		PAYBOX                                     Paybox Integrationshandbuch Shopbetreiber
		PAYPAL                                     PayPal
		AMAZON_PAYMENTS                            Amazon Payments
		CASH_ON_PICKUP                             Zahlung bei Abholung
		FINANCING                                 Finanzierung
		LEASING                                   Leasing
		T_PAY                                     T-Pay
		CLICKANDBUY                               Click&Buy
		GIROPAY                                   Giropay
		GOOGLE_CHECKOUT                           Checkout
		SHOP_CARD                                 Zahlungskarte des Online Shops
		DIRECT_E_BANKING                          SOFORT Überweisung
		MONEYBOOKERS                              moneybookers.com
		DOTPAY                                    Dotpay
		PLATNOSCI                                 Płatności
		PRZELEWY24                                Przelewy24
		OTHER                                     Andere Zahlungsart
		*/

		$payment_type_list = array(
			'bankwire' => 'PREPAYMENT',
			'authorizeaim' => 'CREDIT_CARD',
			'buyster' => 'CREDIT_CARD',
			'cashondelivery' => 'CASH_ON_DELIVERY',
			'dibs' => 'CREDIT_CARD',
			'cheque' => 'CHEQUE',
			'gcheckout' => 'GOOGLE_CHECKOUT',
			'hipay' => 'CREDIT_CARD',
			'moneybookers' => 'MONEYBOOKERS',
			'kwixo' => 'CREDIT_CARD',
			'paypal' => 'CREDIT_CARD',
			'paysafecard' => 'CREDIT_CARD',
			'wexpay' => 'CREDIT_CARD',
			'banktransfert' => 'DIRECT DEBIT'
		);

		if (array_key_exists($params['objOrder']->module, $payment_type_list))
			$payment_type = $payment_type_list[$params['objOrder']->module];

		$customer = new Customer($params['objOrder']->id_customer);
		$currency = new Currency((int)$params['objOrder']->id_currency);

		$arr_params = array(
			'amount' => $params['total_to_pay'],
			'buyer_email' => $customer->email,
			'charset' => 'UTF-8',
			'currency' => $currency->iso_code,
			'customer_id' => $customer->id,
			'order_id' => $params['objOrder']->id,
			'payment_type' => $payment_type,
			'shop_id' => TSCommon::$CERTIFICATES[$lang]['tsID']
		);

		TSCommon::$smarty->assign(
			array(
				'tax_label' => 'TTC',
				'buyer_protection' => $arr_params
			)
		);

		return $this->display(TSCommon::$module_name, '/views/templates/front/order-confirmation-tsbp-classic.tpl');
	}


	/**
	 * Order confirmation displaying and actions depend on the certificate type.
	 *
	 * @uses TSCommon::_orderConfirmationClassic() for Classic certificate
	 * @uses TSCommon::_orderConfirmationExcellence for Excellence certificate.
	 * @param array $params
	 * @return string depend on which certificate is used.
	 */
	public function hookOrderConfirmation($params)
	{
		$out = '';

		$lang = strtoupper(Language::getIsoById($params['objOrder']->id_lang));

		// Security check to avoid any useless warning, a certficate tab will always exist for a configured language
		if (!isset(TSCommon::$CERTIFICATES[$lang]) || !count(TSCommon::$CERTIFICATES[$lang]))
			$out .= '';

		if (((isset(TSCommon::$CERTIFICATES[$lang]['typeEnum'])) && (TSCommon::$CERTIFICATES[$lang]['typeEnum'] == 'EXCELLENCE') &&
			TSCommon::$CERTIFICATES[$lang]['user'] != '' &&
			TSCommon::$CERTIFICATES[$lang]['password'] != ''))
		{
			self::$smarty->assign(array(
					'ratenow_url' => $this->getRatenowUrl($lang, (int)$params['objOrder']->id),
					'ratelater_url' => $this->getRatelaterUrl($lang, (int)$params['objOrder']->id),
					'img_rateshopnow' => _MODULE_DIR_ . 'trustedshops/img/' . strtoupper($lang) . '/rate_now_' . strtolower($lang) . '_190.png',
					'img_rateshoplater' => _MODULE_DIR_ . 'trustedshops/img/' . strtoupper($lang) . '/rate_later_' . strtolower($lang) . '_190.png',
				)
			);
			$out .= $this->display(self::$module_name, '/views/templates/front/order-confirmation.tpl');
			$out .= $this->_orderConfirmationExcellence($params, $lang);
		} else if ((isset(TSCommon::$CERTIFICATES[$lang]['typeEnum'])) &&
			(TSCommon::$CERTIFICATES[$lang]['typeEnum'] == 'CLASSIC' || TSCommon::$CERTIFICATES[$lang]['typeEnum'] == 'UNKNOWN') &&
			(TSCommon::$CERTIFICATES[$lang]['stateEnum'] == 'INTEGRATION' ||
				TSCommon::$CERTIFICATES[$lang]['stateEnum'] == 'PRODUCTION' ||
				TSCommon::$CERTIFICATES[$lang]['stateEnum'] == 'TEST'))
		{
			self::$smarty->assign(array(
					'ratenow_url' => $this->getRatenowUrl($lang, (int)$params['objOrder']->id),
					'ratelater_url' => $this->getRatelaterUrl($lang, (int)$params['objOrder']->id),
					'img_rateshopnow' => _MODULE_DIR_ . 'trustedshops/img/' . strtoupper($lang) . '/rate_now_' . strtolower($lang) . '_190.png',
					'img_rateshoplater' => _MODULE_DIR_ . 'trustedshops/img/' . strtoupper($lang) . '/rate_later_' . strtolower($lang) . '_190.png',
				)
			);
			$out .= $this->display(self::$module_name, '/views/templates/front/order-confirmation.tpl');
			$out .= $this->_orderConfirmationClassic($params, $lang);
		}
		return $out;
	}

	public static function getHttpHost($http = false, $entities = false)
	{
		if (method_exists('Tools', 'getHttpHost'))
			return call_user_func(array('Tools', 'getHttpHost'), array($http, $entities));

		$host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']);

		if ($entities)
			$host = htmlspecialchars($host, ENT_COMPAT, 'UTF-8');
		if ($http)
			$host = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $host;

		return $host;
	}

	private function _getLastOrderId($id_customer)
	{
		$query = 'SELECT `id_order` ' .
			'FROM `' . _DB_PREFIX_ . 'orders` ' .
			'WHERE `id_customer` = ' . (int)$id_customer . ' ' .
			'ORDER BY `date_add` DESC';

		return (int)Db::getInstance()->getValue($query);
	}

	private function getAllowedIsobyId($id_lang)
	{
		$lang = Language::getIsoById($id_lang);
		$lang = in_array($lang, self::$available_languages) ? $lang : 'en';

		return $lang;
	}

	/**
	 * Reset all data for new submited tsid even if it's the same one
	 * @param $id_lang
	 */
	public function resetTSID($id_lang)
	{
		Configuration::deleteByName(self::PREFIX_CONF_NAME . (int)$id_lang, '');
		Configuration::deleteByName(self::PREFIX_ACTIF_CONF_NAME . (int)$id_lang, '');
	}

	private function _validateTrustedShopId($ts_id, $iso_lang)
	{
		$result = strtoupper(TrustedShopsSoapApi::validate(self::PARTNER_PACKAGE, $ts_id));

		if ($result != TrustedShopsSoapApi::RT_OK)
			switch ($result) {
				case TrustedShopsSoapApi::RT_INVALID_TSID:
					$this->error_soap_call = $this->l('Invalid Trusted Shops ID') . ' [' . Language::getIsoById($iso_lang) . ']. ' . $this->l('Please register') . ' <a href="' . $this->getApplyUrl() . '">' . $this->l('here') . '</a> ' . $this->l('or contact service@trustedshops.co.uk.');
					break;
				case TrustedShopsSoapApi::RT_NOT_REGISTERED:
					$this->error_soap_call = $this->l('Customer Rating has not yet been activated for this Trusted Shops ID') . ' [' . Language::getIsoById($iso_lang) . ']. ' . $this->l('Please register') . ' <a href="' . $this->getApplyUrl() . '">' . $this->l('here') . '</a> ' . $this->l('or contact service@trustedshops.co.uk.');
					break;
				default:
					$this->error_soap_call = $this->l('An error has occurred');
			}

		return $result;
	}

	private function hasSOAPCallError()
	{
		return (bool)!empty($this->error_soap_call);
	}

	public function getApplyUrl()
	{
		global $cookie;

		$lang = $this->getAllowedIsobyId($cookie->id_lang);

		return $this->apply_url_base[$lang] . '?partnerPackage=' . self::PARTNER_PACKAGE . '&shopsw=' . self::SHOP_SW . '&website=' .
		urlencode(_PS_BASE_URL_ . __PS_BASE_URI__) . '&firstName=' . urlencode($cookie->firstname) . '&lastName=' .
		urlencode($cookie->lastname) . '&email=' . urlencode(Configuration::get('PS_SHOP_EMAIL')) . '&language=' . strtoupper(Language::getIsoById((int)($cookie->id_lang))) .
		'&ratingProduct=RATING_PRO' . $this->apply_url_tracker[$lang];
	}

	public function getRatenowUrl($iso_cert, $id_order = '')
	{
		global $cookie;
		$buyer_email = '';

		if (Context::getContext()->customer->isLogged()) {
			if (empty($id_order) && !empty($cookie->id_customer))
				$id_order = (int)$this->_getLastOrderId((int)$cookie->id_customer);

			$buyer_email = $cookie->email;
		}

		return $this->getRatingUrlWithBuyerEmail((int)$cookie->id_lang, $iso_cert, (int)$id_order, $buyer_email);
	}

	public function getRatelaterUrl($iso_cert, $id_order = '')
	{
		$buyer_email = '';

		if (Context::getContext()->customer->isLogged()) {
			if (empty($id_order) && !empty(Context::getContext()->cookie->id_customer))
				$id_order = (int)$this->_getLastOrderId((int)Context::getContext()->cookie->id_customer);

			$buyer_email = Context::getContext()->cookie->email;
		}

		//$language = strtoupper(Language::getIsoById((int)Context::getContext()->cookie->id_lang));
		return 'http://www.trustedshops.com/reviews/rateshoplater.php?shop_id=' . TSCommon::$CERTIFICATES[$iso_cert]['tsID'] .
		'&buyerEmail=' . urlencode(base64_encode($buyer_email)) .
		'&shopOrderID=' . urlencode(base64_encode((int)$id_order)) .
		'&orderDate=' . urlencode(base64_encode(date('Y-m-d H:i:s'))) .
		'&days=10';
	}

	public function getRatingUrlWithBuyerEmail($id_lang, $iso_cert, $id_order = '', $buyer_email = '')
	{
		$language = strtoupper(Language::getIsoById((int)$id_lang));
		$base_url = $this->rating_url_base[$language] . TSCommon::$CERTIFICATES[$iso_cert]['tsID'] . '.html';

		if (!empty($buyer_email))
			$base_url .= '&buyerEmail=' . urlencode(base64_encode($buyer_email)) .
				($id_order ? '&shopOrderID=' . urlencode(base64_encode((int)$id_order)) : '') .
				'&orderDate=' . urlencode(base64_encode(date('Y-m-d H:i:s')));

		return $base_url;
	}

	public function hookLeftColumn($params)
	{
		global $cookie;


		if (isset($cookie) && is_object($cookie))
			$id_lang = (int)$cookie->id_lang;
		else if (Tools::getValue('id_lang'))
			$id_lang = (int)Tools::getValue('id_lang');
		else
			$id_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		$iso_lang = $iso_cert = Language::getIsoById((int)$id_lang);

		$tab_id = false;

		if (isset(TSCommon::$CERTIFICATES[strtoupper($iso_cert)]['tsID']))
			$tab_id = TSCommon::$CERTIFICATES[strtoupper($iso_cert)]['tsID'];

		if (!$tab_id)
			return false;

		$display_in_shop = 1;
		$display_rating_frontend = TSCommon::$CERTIFICATES[strtoupper($iso_cert)]['display_rating_front_end'];

		self::$smarty->assign('display_widget', $display_in_shop);

		if ($display_in_shop) {
			$filename = $this->getWidgetFilename(strtoupper($iso_cert));
			$cache = new WidgetCache(_PS_MODULE_DIR_ . $filename, $tab_id);

			if (!$cache->isFresh())
				$cache->refresh();

			if (file_exists(_PS_MODULE_DIR_ . $filename))
				self::$smarty->assign(array('ts_id' => $tab_id, 'filename' => _MODULE_DIR_ . $filename));
		}

		self::$smarty->assign('display_rating_link', (int)$display_rating_frontend);

		if ($display_rating_frontend)
			self::$smarty->assign(array('rating_url' => $this->getRatenowUrl($iso_cert), 'language' => $iso_lang));

		if (TSCommon::$CERTIFICATES[strtoupper($iso_cert)]) {
			return $this->display(self::$module_name, 'views/templates/front/widget.tpl');
		}

		return '';
	}


	public function getWidgetFilename($iso_cert)
	{
		return self::$module_name . '/cache/' . TSCommon::$CERTIFICATES[$iso_cert]['tsID'] . '.gif';
	}

	public function getTempWidgetFilename($tsID)
	{
		return self::$module_name . '/cache/' . $tsID . '.gif';
	}

	public function hookActionOrderStatusPostUpdate($params)
	{
		//'newOrderStatus' => $new_os,'id_order' => (int)$order->id

		$order = new Order((int)($params['id_order']));
		$iso = Language::getIsoById((int)$order->id_lang);
		$iso_upper = Tools::strtoupper($iso);

		if (!isset(TSCommon::$CERTIFICATES[$iso_upper]['send_separate_mail']) OR
			TSCommon::$CERTIFICATES[$iso_upper]['send_separate_mail'] != 1 OR
			TSCommon::$CERTIFICATES[$iso_upper]['send_seperate_mail_order_state'] != (int)($params['newOrderStatus']->id)
		)

			return false;

		RatingAlert::save((int)$order->id, $iso_upper);
	}


	public function getL($key)
	{
		$translations = array(
			'title_part_1' => $this->l('Are you satisfied with'),
			'title_part_2' => $this->l('? Please write a review!')
		);

		return $translations[$key];
	}
}