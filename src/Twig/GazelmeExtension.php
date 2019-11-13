<?php
namespace App\Twig;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use App\Service\GazelMeService;
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
			new \Twig_SimpleFilter('type_transfer', array($this, 'typeTransfer')),
			new \Twig_SimpleFilter('distance', array($this, 'distanceFilter')),
			new \Twig_SimpleFilter('translite_url', array($this, 'transliteUrl')),
			new \Twig_SimpleFilter('is_city_equ_zero', array($this, 'isCityEquZero')),
			new \Twig_SimpleFilter('get_translite_location_name', array($this, 'getTransliteLocationName')),
			new \Twig_SimpleFilter('get_location_name', array($this, 'getLocationName')),
			new \Twig_SimpleFilter('pluralize_hours', array($this, 'pluralizeHours')),
			new \Twig_SimpleFilter('get_uid', array($this, 'getUid')),
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
    public function advlinkFilter(int $nId, string $sCityCodename, string $sRegionCodename, string $sAdvCodename, int $nCityId) : string
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
	 * Выводит имя локации (например, "Тверская область Тверь" или "Москва")
	 * @param int string $sRegionName
	 * @param int string $sCityName
	 * @param int int $nCityId
	 * @return string
	*/
    public function locationName(string $sRegionName, string $sCityName, int $nCityId) : string
    {
		$sCity = '';
		$globals = $this->container->get('twig');
		$vars = $globals->getGlobals();
		$nSpecialCityId = $vars['city_zero_id'];
		if ($nCityId != $nSpecialCityId && $sCityName) {
			$sCity = (' ' . $sCityName);
		}
		return ($sRegionName . $sCity);
	}
	/**
	 * Выводит тип перевозки (например, "Грузовая, термобудка" или "Пассажирская")
	 * @param \App\Entity\Main $oItem
	 * @return string
	*/
    public function typeTransfer(\App\Entity\Main $oItem) : string
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
	
	public function getUid()
	{
		return $this->_oViewDataService->getUid();
	}
}
