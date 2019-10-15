<?php
namespace App\Twig;

class RoubleExtension extends \Twig_Extension
{
    public function getFilters() : array
    {
        return [
            new \Twig_SimpleFilter('rouble', array($this, 'roubleFilter')),
		];
    }

    public function roubleFilter(float $v, string $unit) : string
    {
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
		$sRouble = ' ' . $unit . ' ';

		if ($sZero == '00') {
			return $a[0] . $sRouble;
		}
		$v = join('', $a);
		return $v . $sRouble;
    }
}