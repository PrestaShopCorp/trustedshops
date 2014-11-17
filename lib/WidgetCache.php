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

class WidgetCache
{
	private $_fileName;

	public function __construct($_fileName, $_ts_id)
	{
		$this->_fileName = $_fileName;
		$this->_ts_id = $_ts_id;
	}

	public function isFresh($timeout = 10800)
	{
		if (file_exists($this->_fileName))
			return ((time() - filemtime($this->_fileName)) < $timeout);
		return false;
	}

	public function refresh()
	{
		if ($content = file_get_contents('https://www.trustedshops.com/bewertung/widget/widgets/' . $this->_ts_id . '.gif')) {
			file_put_contents($this->_fileName, $content);
			@chmod($this->_fileName, 0644);
			return true;
		}
		return false;
	}
}

