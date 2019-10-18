<?php

use \Symfony\Component\DependencyInjection\ContainerInterface;

namespace App\Twig;

class GazelmeExtension extends \Twig_Extension
{

	public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container/*, \Symfony\Component\Translation\TranslatorInterface $translator*/)
	{
		$this->container = $container;
		//$this->translator = $translator;
		$this->translator = $container->get('translator');
	}

    public function getFilters() : array
    {
        return [
			new \Twig_SimpleFilter('advlink', array($this, 'advlinkFilter')),
			new \Twig_SimpleFilter('rouble', array($this, 'roubleFilter')),
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
}