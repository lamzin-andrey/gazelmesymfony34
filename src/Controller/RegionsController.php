<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use \App\Entity\Main;
use \App\Entity\Cities;
use \App\Entity\Regions;

use App\Service\GazelMeService;
use App\Service\RegionsService;
use App\Service\ViewDataService;

//Страницы выбора региона
class RegionsController extends Controller
{
	
	/**
	 * Показать страницу выбора регионов, начинающихся на одну букву (/regions/s), или страницу выбора населенных пунктов региона (/regions/samarskaya_oblast) (вся работа происходит в $oRegionsService)
	 * @Route("/regions/{slug}", name="regions_cities") 
	*/
	public function regionsCities(string $slug, Request $oRequest, GazelMeService $oGazelMeService, RegionsService $oRegionsService)
	{
		return $this->_regionsPage($oRequest, $oGazelMeService, $oRegionsService);
	}
	/**
	 * Показать страницу выбора населенных пунктов региона, начинающегося на какую-то букву (/regions/samarskaya_oblast/a) (вся работа происходит в $oRegionsService)
	 * @Route("/regions/{regionName}/{slug}", name="regions_cities_by_letter") 
	*/
	public function regionsCitiesByLetter(string $regionName, string $slug, Request $oRequest, GazelMeService $oGazelMeService, RegionsService $oRegionsService)
	{
		return $this->_regionsPage($oRequest, $oGazelMeService, $oRegionsService);
	}
	/**
	 * Показать Страницу выбора региона
	 * @Route("/regions", name="regions") 
	*/
	public function regions(Request $oRequest, GazelMeService $oGazelMeService, RegionsService $oRegionsService)
	{
		return $this->_regionsPage($oRequest, $oGazelMeService, $oRegionsService);
	}
	/**
	 * Общая логика для запросов вида /regions /regions/a /regions/samarskaya_oblast /regions/samarskaya_oblast/a
	*/
	private function _regionsPage(Request $oRequest, GazelMeService $oGazelMeService, RegionsService $oRegionsService)
	{
		$oSession = $oRequest->getSession();
		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData($oRequest);
		
		$sTitle = $this->get('translator')->trans('');
		$aData['title'] = $sTitle;
		$aData['h1'] = $sTitle;
		$oRegionsService->buildData($oRequest);
		$aData['data'] = $oRegionsService->data;
		$aData['breadCrumbs'] = $oRegionsService->breadCrumbs();
		$aData['wordsOnLetter'] = $oRegionsService->wordsOnLetter;
		$aData['isRegionInner'] = $oRegionsService->isRegionInner;
		$aData['regionInnerName'] = $oRegionsService->regionInnerName;
		if ($oRegionsService->bIsShortData) {
			$aData['nIspage100Percents'] = 1;
		}
		return $this->render('regions.html.twig', $aData);
	}
}
