<?php
namespace App\Twig;

use App\Entity\Cities;
use \Symfony\Component\DependencyInjection\ContainerInterface;
use App\Service\GazelMeService;
use App\Entity\Main;
use Landlib\RusLexicon;

class GazelmeExtension extends \Twig\Extension\AbstractExtension
{

	public function __construct(ContainerInterface $container, GazelMeService $oGazelService)
	{
		
		$this->container = $container;
		//$this->translator = $translator;
		$this->translator = $container->get('translator');
		$this->oGazelService = $oGazelService;
		$this->_oViewDataService = $oGazelService->getViewDataService();
	}

    public function getFilters() : array
    {
        return [
			new \Twig_SimpleFilter('advlink', array($this, 'advlinkFilter')),
			new \Twig_SimpleFilter('rouble', array($this, 'roubleFilter')),
			new \Twig_SimpleFilter('location_name', array($this, 'locationName')),
			new \Twig_SimpleFilter('location_name_by_location_objects', array($this, 'locationNameByLocationObjects')),
			new \Twig_SimpleFilter('type_transfer', array($this, 'typeTransfer')),
			new \Twig_SimpleFilter('type_transfer_by_advert', array($this, 'typeTransferByAdvert')),
			new \Twig_SimpleFilter('distance', array($this, 'distanceFilter')),
			new \Twig_SimpleFilter('translite_url', array($this, 'transliteUrl')),
			new \Twig_SimpleFilter('is_city_equ_zero', array($this, 'isCityEquZero')),
			new \Twig_SimpleFilter('get_translite_location_name', array($this, 'getTransliteLocationName')),
			new \Twig_SimpleFilter('get_location_name', array($this, 'getLocationName')),
			new \Twig_SimpleFilter('pluralize_hours', array($this, 'pluralizeHours')),
			new \Twig_SimpleFilter('get_uid', array($this, 'getUid')),
			new \Twig_SimpleFilter('raise_times', array($this, 'raiseTimes')),
		];
    }
	/**
	 * @param int $nId
	 * @param int string $sCityCodename
	 * @param int string $sRegionCodename
	 * @param int string $sAdvCodename
	 * @param int int $nCityId
	 * @return string
	*/
    public function advlinkFilter(int $nId, $sCityCodename, $sRegionCodename, $sAdvCodename,  $nCityId) : string
    {
		$sCity = '';
		$globals = $this->container->get('twig');
		$vars = $globals->getGlobals();
		$nSpecialCityId = $vars['city_zero_id'];
		if ($nCityId != $nSpecialCityId && $sCityCodename) {
			$sCity = '/' . $sCityCodename;
		}
		return ('/' . $sRegionCodename . $sCity . '/' . $sAdvCodename . '/' . $nId);
	}

	/**
	 * Выводит имя локации (например, "Тверская область Тверь" или "Москва")
	 * @param string $sRegionName
	 * @param Cities $oCity
	 * @return string
	 */
	/*public function locationName(string $sRegionName, ?Cities $oCity) : string
	{
		$sCity = '';
		$globals = $this->container->get('twig');
		$vars = $globals->getGlobals();
		$nSpecialCityId = $vars['city_zero_id'];
		$sCityName = $oCity ? $oCity->getCityName() : '';
		$nCityId = $oCity ? $oCity->getId() : 0;
		if ($nCityId != $nSpecialCityId && $sCityName) {
			$sCity = (' ' . $sCityName);
		}
		return ($sRegionName . $sCity);
	}*/

	/**
	 * Выводит имя локации (например, "Тверская область Тверь" или "Москва")
	 * @param string $sRegionName
	 * @param int $nCity
	 * @param string $sCityName
	 * @return string
	*/
	public function locationName(string $sRegionName, $nCity, $sCityName) : string
	{
		$sCity = '';
		$globals = $this->container->get('twig');
		$vars = $globals->getGlobals();
		$nSpecialCityId = $vars['city_zero_id'];
		$nCityId = $nCity;
		if ($nCityId != $nSpecialCityId && $sCityName) {
			$sCity = (' ' . $sCityName);
		}
		return ($sRegionName . $sCity);
	}

	/**
	 * Выводит имя локации (например, "Тверская область Тверь" или "Москва")
	 * @param string $sRegionName
	 * @param Cities $oCity
	 * @return string
	 */
	public function locationNameByLocationObjects(string $sRegionName, ?Cities $oCity) : string
	{
		$sCity = '';
		$globals = $this->container->get('twig');
		$vars = $globals->getGlobals();
		$nSpecialCityId = $vars['city_zero_id'];
		$sCityName = $oCity ? $oCity->getCityName() : '';
		$nCityId = $oCity ? $oCity->getId() : 0;
		if ($nCityId != $nSpecialCityId && $sCityName) {
			$sCity = (' ' . $sCityName);
		}
		return ($sRegionName . $sCity);
	}

	/**
	 * Получает имя локации (а это может быть как city_name, так и region_name)
	 * @param  string $sRegionName
	 * @param  int $nCity
	 * @param  string $sCityName
	 * @return string
	 */
	/*public function getLocationName(string $sRegionName, int $nCity, string $sCityName) : string
	{
		if ($this->container->getParameter('app.city_zero_id') != $nCity) {
			return $sCityName;
		}
		return $sRegionName;
	}*/

	/**
	 * @param float $v
	 * @return string
	*/
	public function roubleFilter(float $v) : string
    {
		$sUnit = $this->translator->trans('Roubles');

		if (intval($v) == 0 || !$v) {
			$v = 1;
		}
        $v = str_replace('.', ',', $v);
		$a = explode(',', $v);
		$s = $a[0];
		$q = [];
		for ($i = strlen($s) - 1, $j = 1; $i > -1; $i--, $j++) {
			$q[] = $s[$i];
			if ($j % 3 == 0) $q[] = ' ';
		}
		$a[0] = join('', array_reverse($q));
		$sZero = ($a[1] ?? '');
		$sRouble = ' ' . $sUnit . ' ';

		if ($sZero == '00') {
			return $a[0] . $sRouble;
		}
		$v = join('', $a);
		return $v . $sRouble;
	}
	/**
	 * Выводит тип перевозки (например, "Грузовая, термобудка" или "Пассажирская")
	 * @oaram int $nBox
	 * @oaram int $nTerm
	 * @oaram int $nPeople
	 * @return string
	*/
    public function typeTransfer(int $nBox, int $nTerm, int $nPeople) : string
    {
		/** @var \App\Entity\Main $oItem */
		$oItem = new Main();
		$oItem->setBox($nBox);
		$oItem->setTerm($nTerm);
		$oItem->setPeople($nPeople);
		return $this->oGazelService->getCarsTypes($oItem);
	}
	/**
	 * Выводит тип перевозки (например, "Грузовая, термобудка" или "Пассажирская")
	 * @param \App\Entity\Main $oItem
	 * @return string
	 */
	public function typeTransferByAdvert(\App\Entity\Main $oItem) : string
	{
		return $this->oGazelService->getCarsTypes($oItem);
	}
	/**
	 * 
	 * Выводит тип дистанций (например, "По городу, межгород" или "Пикник")
	 * @param \App\Entity\Main $oItem
	 * @return string
	*/
    public function distanceFilter(\App\Entity\Main $oItem) : string
    {
		$a = [];
		if ($oItem->getNear()) {
			$a[] = $this->translator->trans('In city only'); 
		}
		
		if ($oItem->getFar()) {
			$a[] = $this->translator->trans('Far'); 
		}
		
		if ($oItem->getPiknik()) {
			$a[] = $this->translator->trans('Piknik'); 
		}
		$s = join($a, ', ');
		$s = mb_strtolower($s, 'utf-8');
		$s = $this->oGazelService->capitalize($s);
		return $s;
	}
	/**
	 * Транслитирует текст используя метод транслитерации GazelService, это важно так как url уже в Яндексе и неплохо индлексируются
	 * @param \App\Entity\Main $oItem
	 * @return string
	*/
	public function transliteUrl(string $letter) : string 
	{
		return $this->oGazelService->translite_url($letter);
	}
	/**
	 * Должен проверить, есть ли в объекте метод getIssCity и если да вернуть isCity == 0. Если нет такого вернуть true
	 * (Это странная логика по причине адаптации объекта = RegionsService::buildData() который выдлает неплохие результаты, но их трудно приспособить в twig реалиям)
	 * @param  $oItem
	 * @return bool
	*/
	public function isCityEquZero($oItem) : bool
	{
		if (method_exists($oItem, 'getIsCity')) {
			return ($oItem->getIsCity() == 0);
		}
		return true;
	}
	/**
	 * Получает имя локации (а это может быть как city_name, так и region_name) и транслитирует его
	 * @param  $oItem
	 * @return string
	*/
	public function getTransliteLocationName($oItem) : string
	{
		return $this->oGazelService->translite_url( $this->getLocationName($oItem) );
	}
	/**
	 * Получает имя локации (а это может быть как city_name, так и region_name)
	 * @param  $oItem
	 * @return string
	*/
	public function getLocationName($oItem) : string
	{
		if (method_exists($oItem, 'getCityName')) {
			return $oItem->getCityName();
		}
		if (method_exists($oItem, 'getRegionName')) {
			return $oItem->getRegionName();
		}
		return '';
	}
	/**
	 * Изменяет слово "час" в зависмости от количества
	 * @param  int $n
	 * @return string
	*/
	public function pluralizeHours(int $n) : string
	{
		return ($n . ' ' . RusLexicon::getMeasureWordMorph($n, 'час', 'часа', 'часов') );
	}

	/**
	 * Вы можете поднять ваше объявление n раз
	 * @param  int $n
	 * @return string
	*/
	public function raiseTimes(int $n) : string
	{
		$t = $this->translator;
		$sTimes = $n . ' ' . RusLexicon::getMeasureWordMorph($n, $t->trans('time'), $t->trans('times'), $t->trans('time')) ;
		$s = $t->trans('You can raise your ad %n_times%', ['%n%' => $sTimes]);
		return $s;
	}
	
	public function getUid()
	{
		return $this->_oViewDataService->getUid();
	}


}
