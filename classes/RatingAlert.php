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

require_once(_PS_MODULE_DIR_.'trustedshops/classes/TSCommon.php');

class RatingAlert
{
	const TABLE_NAME = 'ts_rating_alert';
	const MAIL_TEMPLATE = 'rating_email';

	public static function save($id_order, $iso)
	{
		Db::getInstance()->AutoExecute(_DB_PREFIX_.self::TABLE_NAME, array('id_order' => (int)$id_order, 'iso' => $iso), 'INSERT');
	}

	private static function getAlertsInformations($iso)
	{
		$id_lang = Language::getIdByIso($iso);
		$cert = Configuration::get(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.Tools::strtoupper($iso));

		if ($cert != false)
		{
			$certificate = (array)Tools::jsonDecode(Tools::htmlentitiesDecodeUTF8($cert));

			if (trim($certificate['tsID']) != '')
			{
				$query = '
				SELECT
					a.id_alert,
					a.`iso`,
					c.`email`,
					o.`id_order`,
					o.`id_lang`
				FROM `'._DB_PREFIX_.self::TABLE_NAME.'` a
				LEFT JOIN '._DB_PREFIX_.'orders o ON (a.id_order = o.id_order)
				LEFT JOIN '._DB_PREFIX_.'customer c ON (c.id_customer = o.id_customer)
				WHERE
					o.`id_lang`='.(int)$id_lang.'
					AND
					DATE_ADD(
						o.`date_add`, INTERVAL '.(int)$certificate['send_seperate_mail_delay'].' DAY
					) <= NOW()';

				return Db::getInstance()->ExecuteS($query);
			}
		}
		return false;
	}

	public static function removeAlerts($ids)
	{
		$ids = array_map('intval', $ids);
		$query = 'DELETE '.
			'FROM `'._DB_PREFIX_.self::TABLE_NAME.'` '.
			'WHERE `id_alert` '.
			'IN ('.implode(',', $ids).')';
		return Db::getInstance()->Execute($query);
	}

	public static function executeCronTask()
	{
		$ts_module = new TrustedShops();
		$ts_common = new TSCommon();

		$common_count = 0;

		if (is_array(TSCommon::$available_languages))
		{
			$to_remove = array();

			foreach (array_keys(TSCommon::$available_languages) as $iso)
			{

				$alerts_infos = RatingAlert::getAlertsInformations($iso);

				///print_r($alerts_infos);

				if ($alerts_infos != false)
				{
					$common_count += count($alerts_infos);

					foreach ($alerts_infos as $infos)
					{
						$cert = Configuration::get(TSCommon::PREFIX_TABLE . 'CERTIFICATE_' . Tools::strtoupper($infos['iso']));
						$certificate = (array)Tools::jsonDecode(Tools::htmlentitiesDecodeUTF8($cert));

						$subject = $ts_module->l('title_part_1').' '.Configuration::get('PS_SHOP_NAME').$ts_module->l('title_part_2');
						$template_vars = array('{ts_id}' => $certificate['tsID'],
							'{button_url}' => TSCommon::getHttpHost(true, true)._MODULE_DIR_.$ts_module->name.'/img',
							'{rating_url}' => $ts_common->getRatingUrlWithBuyerEmail($infos['id_lang'], $infos['id_order'], $infos['email']));

						$result = Mail::Send(
							(int)$infos['id_lang'],
							self::MAIL_TEMPLATE,
							$subject,
							$template_vars,
							$infos['email'],
							null,
							Configuration::get('PS_SHOP_EMAIL'),
							Configuration::get('PS_SHOP_NAME'),
							null,
							null,
							dirname(__FILE__) . '/../mails/'
						);

						if ($result)
							$to_remove[] = (int)$infos['id_alert'];
					}
				}
			}

			if (count($to_remove) > 0)
				self::removeAlerts($to_remove);

		}

		return (count($to_remove) == $common_count);
	}

	public static function createTable()
	{
		$query = '
		CREATE TABLE IF NOT EXISTS
		`'._DB_PREFIX_.self::TABLE_NAME.'`
		(
			`id_alert` INT NOT NULL AUTO_INCREMENT,
			`id_order` INT NOT NULL, `iso` varchar(10) NOT NULL,
			PRIMARY KEY (`id_alert`)
		)
		ENGINE = '._MYSQL_ENGINE_;

		return Db::getInstance()->Execute($query);
	}

	public static function dropTable()
	{
		return Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.self::TABLE_NAME.'`');
	}

	public static function truncateTable()
	{
		return Db::getInstance()->Execute('TRUNCATE TABLE `'._DB_PREFIX_.self::TABLE_NAME.'`');
	}
}

