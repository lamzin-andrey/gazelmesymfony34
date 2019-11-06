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

use Doctrine\Common\Collections\Criteria;

class AdvertController extends Controller
{
    /**
      * @param string $sRegion
      * @param string $sCity
      * @param string $sTitle
      * @param int $nAdv
      * @param Request $request
	  * @param GazelMeService $oGazelMeService
    */
    public function advincity(string $sRegion, string $sCity, string $sTitle, int $nAdvId, Request $request, GazelMeService $oGazelMeService)
    {
		return $this->_advPage($request, $oGazelMeService, $nAdvId, $sTitle, $sRegion, $sCity);
	}
	/**
      * @param string $sRegion
      * @param string $sTitle
      * @param int $nAdv
      * @param Request $request
	  * @param GazelMeService $oGazelMeService
    */
    public function advinmegapolis(string $sRegion, string $sTitle, int $nAdvId, Request $request, GazelMeService $oGazelMeService)
    {
		return $this->_advPage($request, $oGazelMeService, $nAdvId, $sTitle, $sRegion);
	}
	/**
	  * Общая логика для для страницы одного объявлений (оно может быть в регионе и городе, а может быть просто в городе)
	  * @param Request $request
	  * @param GazelMeService $oGazelMeService
	  * @param int $nAdvId идентификатор объявления
	  * @param string $sTitle main.codename
	  * @param string $sRegion = '' код региона латинскими буквами
      * @param string $sCity = ''   код города латинскими буквами
    */
	private function _advPage(Request $oRequest, GazelMeService $oGazelMeService, int $nAdvId, string $sTitle, string $sRegion = '', string $sCity = '')
	{
		//advert data
		$oRepository = $this->getDoctrine()->getRepository('App:Main');
		
		$advert = $oRepository->find($nAdvId);
		if (!$advert) {//TODO 404!
			die('TODO 404 ' . __FILE__ . __LINE__);
		}
		$siteName = $this->getParameter('app.site_name');
		
		$sCyrRegionName = '';
		$sCyrCityName = '';
		$oCriteria = Criteria::create();
		$oGazelMeService->setCityConditionAndInitCyrValues($oCriteria , $sCyrRegionName, $sCyrCityName, $sRegion, $sCity);
		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData($oRequest);
		$aData['title'] = $oGazelMeService->getTiltle($oRequest, $sCyrRegionName, $sCyrCityName);
		$aData['h1'] = $oGazelMeService->getMainHeading($oRequest, $sCyrRegionName, $sCyrCityName, $advert->getTitle());
		$aData['advert'] = $advert;
		$aData['nCountAdverts'] = 1;
		$aData['nIspage100Percents'] = 1;
		$aData['backLink'] = $this->_getBackLink($sRegion, $sCity);
		return $this->render('advert.html.twig', $aData);
	}
	/**
	 * Строит ссылку на список объявлений региона @see _advPage
	 * @param string $sRegion
	 * @param string $sCity
	 * @return string
	*/
	private function _getBackLink(string $sRegion, string $sCity) : string
	{
		if (!$sCity) {
			return '/' . $sRegion;
		}
		return ('/' . $sRegion . '/' . $sCity);
	}
}
