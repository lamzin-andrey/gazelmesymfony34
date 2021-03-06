<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
	 * Установить url региона при отправки vue 2 формы
	 * @Route("/setregionjs", name="setregionjs") 
	*/
	public function setregionjs(Request $oRequest, GazelMeService $oGazelMeService, RegionsService $oRegionsService)
	{
		$nRegionId = intval($oRequest->get('regionId', 0) );
		$nCityId = intval($oRequest->get('cityId', 0) );
		$nIsCity = intval($oRequest->get('isCity', 0) );
		
		if ($nRegionId) {
			$oRepository = $this->getDoctrine()->getRepository('App:Regions');
			$oRegion = $oRepository->find($nRegionId);
			if ($oRegion) {
				$oRegionsService->setLocationUrl($oRegion->getCodename(), '', $oRequest, $nRegionId, 0, $oRegion->getRegionName());
				return new RedirectResponse('/' . $oRegion->getCodename());
			}
			return $this->redirectToRoute('home');
		}
		
		if ($nCityId) {
			$oRepository = $this->getDoctrine()->getRepository('App:Cities');
			$oCity = $oRepository->find($nCityId);
			if ($oCity) {
				$oRegion = $oCity->getRegionObject();
				if ($oCity && $oRegion) {
					$oRegionsService->setLocationUrl($oRegion->getCodename(), $oCity->getCodename(), $oRequest, 
														$oRegion->getId(), $nCityId,
														$oRegion->getRegionName(), $oCity->getCityName());
					return new RedirectResponse('/' . $oRegion->getCodename() . '/' . $oCity->getCodename());
				}
			}
			return $this->redirectToRoute('home');
		}
		$sE = '';
		$oRegionsService->setLocationUrl($sE, $sE, $oRequest, 0, 0, $sE, $sE);
		return $this->redirectToRoute('home');
	}
	/**
	 * JSON
	 * Запрос населенных пунктов начинающихся на переданную в substring строку
	 * @Route("getcitinamesbysubstr") 
	*/
	public function getcitinamesbysubstr(Request $oRequest, GazelMeService $oGazelMeService, RegionsService $oRegionsService)
	{
		$s = $oRequest->get('s', '');
		$oResponse = new Response( json_encode(['status' => 'ok', 'list' => [] ]) );
		if (strlen($s) > 3) {
			$aList = $this->_loadCitylistBySubstring($s);
			$oResponse = new Response( json_encode(['status' => 'ok', 'list' => $aList]) );
		}
		$oResponse->headers->set('Content-Type', 'application/json');
		return $oResponse;
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
		if ($oRegionsService->bIsNotFound) {
			throw $this->createNotFoundException('Location not found');
		}
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
	/**
	 * TODO тут все равно не сущности, то есть встроенный кеш невозможно использовать.
	 * Подумать, что тут лучше.
	 * Правильным кажется получать параметры кэширования second-level-cache (expires в остновном)
	 * и кешировать используя компонент кеша
	 * Получить населенные пункты, в наименования которых входит подстрока $s
	 * @param string $s
	*/
	private function _loadCitylistBySubstring(string $s) : array
	{
		//Запрос из таблицы регионов крупных городов
		$oRepository = $this->getDoctrine()->getRepository('App:Regions');
		$oQueryBuilder = $oRepository->createQueryBuilder('r');
		$oQueryBuilder = $oQueryBuilder->select('r.id, r.regionName AS region_name, r.isCity AS is_city');
		$oQueryBuilder->where($oQueryBuilder->expr()->eq('r.isCity', 1 ));
		$oQueryBuilder->andWhere($oQueryBuilder->expr()->like('r.regionName', ':s' ));
		$oQueryBuilder->setParameter('s', ($s . '%'));
		$aData = $oQueryBuilder->getQuery()->getResult();
		
		//Запрос из таблицы городов
		$oRepository = $this->getDoctrine()->getRepository('App:Cities');
		$oQueryBuilder = $oRepository->createQueryBuilder('c');
		$oQueryBuilder = $oQueryBuilder->select('c.id, c.cityName AS city_name, r.regionName AS r_region_name, r.id AS r_id, r.isCity AS r_is_city,
			CASE WHEN c.cityName = :raw THEN 1 ELSE 0 END AS HIDDEN sortCondition');
		$oQueryBuilder->where($oQueryBuilder->expr()->like('c.cityName', ':s' ));
		$oQueryBuilder->setParameter('s', ($s . '%'));
		$oQueryBuilder->setParameter('raw', $s );
		$oQueryBuilder->leftJoin('App:Regions', 'r', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.region = r.id');
		$oQueryBuilder->orderBy('sortCondition', 'DESC' );
		$aCityData = $oQueryBuilder->getQuery()->getResult();
		
		$aData = array_merge($aData, $aCityData);
		
		/*var_dump($aData);
		die;*/
		
		return $aData;
	}
}
