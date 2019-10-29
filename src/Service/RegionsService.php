<?php
namespace App\Service;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;

use App\Entity\Main;
use App\Entity\Regions;
use App\Entity\Cities;

class RegionsService  {
	/** @property array $aData список регионов */
	public  $data = [];
	/** @property int $nWordsOnLetter сколько регионов показывать под буквой */
	public  $wordsOnLetter = 5;
	/** @property bool $nIsRegionInner true когда пользователь находимся на странице выбора населённого пункта внутри региона */
	public  $isRegionInner = 0;
	/** @property string $sRegionInnerName имя региона в разделе выбора населённого пункта внутри которого находится пользователь */
	public  $regionInnerName = '';
	/** @property bool $bIsShortData Принимает true когда страница с буквой (как следствие на ней немного данных и надо установиить "растяжку для футера") */
	public  $bIsShortData = false;
	/** @property int $_nDefaultWordsOnLetter сколько регионов показывать под буквой по умолчанию */
	private $_defaultWordsOnLetter = 5;
	/** @property int $_aBreadCrumbs ассоциативный массив хлебных крошек */
	private $_aBreadCrumbs = [];
	
	public function __construct(ContainerInterface $oContainer, GazelMeService $oGazelMeService)
	{
		$this->oContainer = $oContainer;
		$this->oTranslator = $oContainer->get('translator');
		$this->oGazelMeService = $oGazelMeService;
	}
	
	public function buildData(Request $oRequest) : void
	{
		$this->oRequest = $oRequest;
		$this->_aBreadCrumbs = ['/regions' => 'Регионы'];
		
		/*$sql = "SELECT id, region_name, codename, delta, is_city FROM regions WHERE country = 3 AND is_deleted = 0 ORDER BY region_name";
		$rows = query($sql);
		$data = [];
		foreach ($rows as $row) {
			$ch = mb_substr($row['region_name'], 0, 1, 'UTF-8');
			$ch = mb_strtoupper($ch, 'UTF-8');
			if (!isset($data[$ch])) {
				$data[$ch] = [];
			}
			$data[$ch][] = $row;
		}*/
		
		$oRepository = $this->oContainer->get('doctrine')->getRepository('App:Regions');
		//$rows = query($sql);
		$aRawData = $oRepository->findBy([
			'country' => 3,
			'isDeleted' => 0,
		],
		[
			'regionName' => 'ASC'
		]);
		$data = [];
		foreach ($aRawData as $oRow) {
			$ch = mb_substr($oRow->getRegionName(), 0, 1, 'UTF-8');
			$ch = mb_strtoupper($ch, 'UTF-8');
			if (!isset($data[$ch])) {
				$data[$ch] = [];
			}
			$data[$ch][] = $oRow;
		}
		$this->data = $data;
		
		if (!$this->setLetter() ) {
			if ($this->_setCity() ) {
				$this->_setCityLetter();
			}
		}
	}
	
	/**
	 *@desc  Пытается найти имя города после regions 
	 *@param 
	 *@return
	**/
	private function _setCity() : bool
	{
		//$url = $_SERVER['REQUEST_URI'];
		$url = $this->oRequest->server->get('REQUEST_URI');
		$a = explode('/', $url);
		if (count($a) > 2 ) {
			$s = $a[2];
			$region_name = '';
			if ($nRegId = $this->_checkRegion(strval($s), $region_name)) {
				//$sql = "SELECT id, city_name AS region_name, codename, delta, 0 AS is_city FROM cities WHERE country = 3 AND region = {$reg_id} AND is_deleted = 0 ORDER BY city_name";
				$oRepository = $this->oContainer->get('doctrine')->getRepository('App:Cities');
				//$rows = query($sql);
				$rows = $oRepository->findBy([
						'country' => 3,
						'isDeleted' => 0,
						'region' => $nRegId 
					],
					[
						'cityName' => 'ASC'
					]
				);
				$data = [];
				foreach ($rows as $nKey => $row) {
					//TODO тут использовался алиас а теперь в твиге фильтр применим с помощью method_exists($oItem, 'getRegionName')
					$ch = mb_substr($row->getCityName(), 0, 1, 'UTF-8');
		 			$ch = mb_strtoupper($ch, 'UTF-8');
		 			if (!isset($data[$ch])) {
						$data[$ch] = [];
					}
		 			$data[$ch][] = $row;
		 		}
		 		$this->data = $data;
		 		$this->wordsOnLetter = $this->_defaultWordsOnLetter;
		 		$aValues = array_keys($this->_aBreadCrumbs);
		 		$bc_url = end($aValues);
		 		$this->_aBreadCrumbs["{$bc_url}/{$s}"] = $region_name;
		 		$this->isRegionInner = 1;
		 		$this->regionInnerName = "{$s}/";
		 		return true;
			}
		}
		return false;
	}
	/**
	 * @param $region_codename
	 * @param string &$sRegionName
	 * @return mixed
	**/
	private function _checkRegion(string $sRegionCodename, string &$sRegionName)
	{
		//$query = "SELECT id, region_name FROM regions WHERE country = 3 AND codename = '{$region_codename}' AND is_city = 0";
		$oRepository = $this->oContainer->get('doctrine')->getRepository('App:Regions');
		//$row = dbrow($query);
		$aRows = $oRepository->findBy([
			'country' => 3,
			'codename' => $sRegionCodename,
			'isCity' => 0
		]);
		if (count($aRows)) {
			$row = current($aRows);
			$sRegionName = $row->getRegionName();
			return $row->getId();
		}
		return false;
	}
	/**
	 * Читает первые две или одну букву после /regions/буква(ы)/ перезаписывает в data data[$ch] 
	 * @return bool true если найдена буква после regions
	**/
	private function setLetter() : bool
	{
		//$url = $_SERVER['REQUEST_URI'];
		$url = $this->oRequest->server->get('REQUEST_URI');
		$a = explode('/', $url);
		if (count($a) > 2 ) {
			$s = $a[2];
			if (strlen($s) < 3) {
				foreach ($this->data as $key => $item) {
					if ( $this->oGazelMeService->translite_url( $key ) == $s) {
						$this->data = [$key => $item];
						$this->wordsOnLetter = 3000000;
						$this->_aBreadCrumbs = array('/regions' => 'Регионы', "/regions/{$s}" => $key);
						$this->bIsShortData = true;
						return true;
					}
				}
			}
		}
		return false;
	}
	/**
	 * Читает первые две или одну букву после /regions/имя_региона/буква(ы)/ перезаписывает в data data[$ch] 
	**/
	private function _setCityLetter() : void
	{
		$this->bIsShortData = false;
		//$url = $_SERVER['REQUEST_URI'];
		$url = $this->oRequest->server->get('REQUEST_URI');
		$a = explode('/', $url);
		if (count($a) > 3 ) {
			$s = $a[3];
			if (strlen($s) < 3) {
				foreach ($this->data as $key => $item) {
					if ( $this->oGazelMeService->translite_url( $key ) == $s) {
						$this->data = array($key => $item);
						$this->wordsOnLetter = 3000000;
						$aKeys = array_keys($this->_aBreadCrumbs);
						$bc_url = end($aKeys);
						$this->_aBreadCrumbs["{$bc_url}/{$s}"] = $key;
						$this->bIsShortData = true;
					}
				}
			}
		}
	}
	/**
 	 *
 	 * @param
 	 * @return
 	**/
	public function breadCrumbs() : string
	{
		$a = $this->_aBreadCrumbs;
		$i = 0;
		$links = [];
		foreach ($a as $link => $text) {
			if ($i < count($a) - 1) {
				$links[] = '<a href="' . $link . '">' . $text . '</a>';
			} else {
				$links[] = '<span >' . $text . '</span>';
			}
			$i++;
		}
		$s = join(' / ', $links);
		return $s;
	}
	/**
	 * Получает из сессии выбранный пользователем регион или мегаполис (имя латинскими буквами в нижнем регистре)
	 * @param Request $oRequest
	*/
	public function getRegionCodenameFromSession(Request $oRequest) : string
	{
		$oSession = $oRequest->getSession();
		return $oSession->get('sRegionCodename', '');
	}
	/**
	 * Получает из сессии выбранный пользователем населенный пункт (имя латинскими буквами в нижнем регистре)
	 * @param Request $oRequest
	*/
	public function getCityCodenameFromSession(Request $oRequest) : string
	{
		$oSession = $oRequest->getSession();
		return $oSession->get('sCityCodename', '');
	}
	/**
	 * Получает из сессии выбранный пользователем населенный пункт (кириллица или локализованое значение)
	 * @param Request $oRequest
	*/
	public function getDisplayLocationFromSession(Request $oRequest) : string
	{
		$oSession = $oRequest->getSession();
		return $oSession->get('sCyrLocation', '');
	}
	/**
	 * Устанавливает в сессии выбранный пользователем город только в том случае, если человек пришел со страницы /regions/*
	 * @param string $sRegion кодовое имя региона (латинскими буквами (это не стандартный транслит!) )
	 * @param string $sCity кодовое имя города (латинскими буквами (это не стандартный транслит!) )
	 * @param Request $oRequest
	 * @param string $sCyrRegionName
	 * @param string $sCyrCityName
	*/
	public function saveSelectedLocation(string $sRegion, string $sCity, Request $oRequest, string $sCyrRegionName, string $sCyrCityName = '') : void
	{
		$sReferer = $oRequest->server->get('HTTP_REFERER');
		$sUrl = explode('?', $oRequest->server->get('REQUEST_URI') )[0];
		if ($sUrl != '/') {
			$aUrl = parse_url($sReferer);
			$sPath = ($aUrl['path'] ?? '');
			if (strpos($sPath, '/regions') === 0) {
				//set location
				$aReqUrl = explode('/', $sUrl);
				$oSession = $oRequest->getSession();
				$nSz = count($aReqUrl);
				if ($nSz == 3) {
					$oSession->set('sRegionCodename', $aReqUrl[1]);
					$oSession->set('sCityCodename', $aReqUrl[2]);
					$this->_setCyrLocationValue($oSession, $aReqUrl[1], $aReqUrl[2], $sCyrRegionName, $sCyrCityName);
				} else if ($nSz == 2) {
					$oSession->set('sRegionCodename', $aReqUrl[1]);
					$oSession->set('sCityCodename', '');
					$this->_setCyrLocationValue($oSession, $aReqUrl[1], '', $sCyrRegionName, $sCyrCityName);
				} else {
					$oSession->set('sRegionCodename', '/');
					$oSession->set('sCityCodename', '');
					$oSession->set('sCyrLocation', '');
				}
			}
		}
	}
	/**
	 * Устанавливает в сессии выбранный пользователем город (без учета реферера, используется при отправки vue формы)
	 * @param string $sRegion кодовое имя региона (латинскими буквами (это не стандартный транслит!) )
	 * @param string $sCity кодовое имя города (латинскими буквами (это не стандартный транслит!) )
	 * @param Request $oRequest
	 * @param string $sCyrRegionName
	 * @param string $sCyrCityName
	*/
	public function setLocationUrl(string $sRegion, string $sCity, Request $oRequest, string $sCyrRegionName, string $sCyrCityName = '') : void
	{
		$oSession = $oRequest->getSession();
		if ($sRegion && $sCity && $sCyrRegionName && $sCyrCityName) {
			$oSession->set('sRegionCodename', $sRegion);
			$oSession->set('sCityCodename', $sCity);
			$this->_setCyrLocationValue($oSession, $sRegion, $sCity, $sCyrRegionName, $sCyrCityName);
		} else if ($sRegion && $sCyrRegionName) {
			$oSession->set('sRegionCodename', $sRegion);
			$oSession->set('sCityCodename', '');
			$this->_setCyrLocationValue($oSession, $sRegion, '', $sCyrRegionName, '');
		} else {
			$oSession->set('sRegionCodename', '/');
			$oSession->set('sCityCodename', '');
			$oSession->set('sCyrLocation', '');
		}
	}
	/**
	 * Устанавливает в сессии строку, которая показывается на форме фильтра как наименование локации
	 * @param $oSession (Request::getSession() )
	 * @param string $sRegion кодовое (транслитированое)  имя региона или мегаполиса латинскими буквами
	 * @param string string $sCity = '' кодовое (транслитированое)  имя города  латинскими буквами
	 * @param string $sCyrRegionName = ''
	 * @param string $sCyrCityName = ''
	*/
	private function _setCyrLocationValue($oSession, string $sRegion, string $sCity = '', $sCyrRegionName = '', $sCyrCityName = '') : void
	{
		if (!$sCyrRegionName) {
			$a = [];
			$this->oGazelMeService->setCityConditionAndInitCyrValues($a, $sCyrRegionName, $sCyrCityName, $sRegion, $sCity);
		}	
		if ($sCyrRegionName) {
			if ($sCyrCityName) {
				$oSession->set('sCyrLocation', $sCyrRegionName . ', ' . $sCyrCityName);
			} else {
				$oSession->set('sCyrLocation', $sCyrRegionName);
			}
		}
	}
}
