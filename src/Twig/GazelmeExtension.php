<?php
namespace App\Twig;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use App\Service\GazelMeService;

class GazelmeExtension extends \Twig_Extension
{

	public function __construct(ContainerInterface $container, GazelMeService $oGazelService)
	{
		
		$this->container = $container;
		//$this->translator = $translator;
		$this->translator = $container->get('translator');
		$this->oGazelService = $oGazelService;
	}

    public function getFilters() : array
    {
        return [
			new \Twig_SimpleFilter('advlink', array($this, 'advlinkFilter')),
			new \Twig_SimpleFilter('rouble', array($this, 'roubleFilter')),
			new \Twig_SimpleFilter('location_name', array($this, 'locationName')),
			new \Twig_SimpleFilter('type_transfer', array($this, 'typeTransfer')),
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
	
}