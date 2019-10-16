<?php
namespace App\Twig;

class AdvlinkExtension extends \Twig_Extension
{
    public function getFilters() : array
    {
        return [
            new \Twig_SimpleFilter('advlink', array($this, 'advlinkFilter')),
		];
    }

    public function advlinkFilter(int $nId, string $sCityName, $sRegionName) : string
    {
		
		return '#';
    }
}