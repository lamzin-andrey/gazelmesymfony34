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

use \Doctrine\Common\Collections\Criteria;

class AdvertlistController extends Controller
{
	
	/** @property string _sCyrCityName кирилическое имя локации */
	private $_sCyrCityName = '';
	
	/** @property string _sCyrRegionName кирилическое имя локации */
	private $_sCyrRegionName = '';
	

	/**
      * @Route("/", name="home")
    */
    public function index(Request $request, GazelMeService $oGazelMeService)
    {
		return $this->_advListPage($request, $oGazelMeService);
		
	}
	/**
      * @param string $sCity
      * @param Request $request
	  * @param GazelMeService $oGazelMeService
    */
    public function megapolis(string $sCity, Request $request, GazelMeService $oGazelMeService)
    {
		return $this->_advListPage($request, $oGazelMeService, $sCity);
	}
    /**
      * @param string $sRegion
      * @param string $sCity
      * @param Request $request
	  * @param GazelMeService $oGazelMeService
    */
    public function city(string $sRegion, string $sCity, Request $request, GazelMeService $oGazelMeService)
    {
		return $this->_advListPage($request, $oGazelMeService, $sRegion, $sCity);
	}
	
	/**
	  * Общая логика для главной и для страницы списка объявлений
	  * @param Request $oRequest
	  * @param GazelMeService $oGazelMeService
	  * @param string $sRegion = '' код региона латинскими буквами
      * @param string $sCity = ''   код города латинскими буквами
    */
	private function _advListPage(Request $oRequest, GazelMeService $oGazelMeService, string $sRegion = '', string $sCity = ''/*, RegionsService $oRegionsService*/)
	{
		$oRegionsService = $this->get('App\Service\RegionsService');
		//adverts data
		$oSession = $oRequest->getSession();
		$oSession->set('people', intval( $oRequest->get('people', 0) ));
		$oSession->set('box', intval( $oRequest->get('box', 0) ));
		$oSession->set('term', intval( $oRequest->get('term', 0) ));
		$oSession->set('far', intval( $oRequest->get('far', 0) ));
		$oSession->set('near', intval( $oRequest->get('near', 0) ));
		$oSession->set('piknik', intval( $oRequest->get('piknik', 0) ));
		
		
		$adverts = $this->_loadAdvList($sRegion, $sCity, $oRequest);
		$oRegionsService->saveSelectedLocation($sRegion, $sCity, $oRequest, $this->_sCyrRegionName, $this->_sCyrCityName);

		//for links
		$s = $oRequest->server->get('REQUEST_URI');
		$a = explode('?', $s);
		$currentTail =  ($a[1] ?? '');//??

		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData($oRequest);
		
		$sTitle = $this->get('translator')->trans('Set filter page');
		$aData['title'] = $oGazelMeService->getTiltle($oRequest, $this->_sCyrRegionName, $this->_sCyrCityName);
		$aData['h1'] = $oGazelMeService->getMainHeading($oRequest, $this->_sCyrRegionName, $this->_sCyrCityName);
		
		$sCyrLocation = $oRegionsService->getDisplayLocationFromSession($oRequest);
		if ($sCyrLocation) {
			$aData['nIsSetLocaton'] = 1;
			$aData['sDisplayLocation'] = $sCyrLocation;
		} else {
			$aData['nIsSetLocaton'] = 0;
			$aData['sDisplayLocation'] = '';
		}
		
		$aData['list'] = $adverts;
		$aData['nCountAdverts'] = count($adverts);
		
		
		$aData['nRegionId'] = 0;
		$aData['nCityId'] = 0;
		$aData['nIsCity'] = 0;
		
		if ($sRegion && !$sCity) {
			$aRegions = $this->get('App\Repository\RegionsRepository')->findByCodename($sRegion);
			$oRegion = current($aRegions);
			if ($oRegion) {
				$aData['nRegionId'] = $oRegion->getId();
				$aData['nIsCity'] = intval( $oRegion->getIsCity() );
			}
		}
		if ($sCity) {
			/*$oRepository = $this->getDoctrine()->getRepository('App:Cities');
			$aCities = $oRepository->findBy([
				'codename' => $sCity
			]);*/
			$aCities = $this->get('App\Repository\CitiesRepository')->findByCodename($sCity);
			$oCity = current($aCities);
			if ($oCity) {
				$aData['nCityId'] = $oCity->getId();
			}
		}
        return $this->render('list/mainlist.html.twig', $aData);
	}
	/**
	 * 
	 * @param string $sRegion = '' код региона латинскими буквами
     * @param string $sCity = ''   код города латинскими буквами
	 * @return array
	*/
	private function _loadAdvList(string $sRegion = '', string $sCity = '', Request $oRequest) : array 
	{
		$limit = $this->getParameter('app.records_per_page', 10);
		$repository = $this->getDoctrine()->getRepository('App:Main');
		/*$oQueryBuilder = $repository->createQueryBuilder('m');
		$oQueryBuilder = $oQueryBuilder->select()
			->where( $oQueryBuilder->expr()->eq('m.isDeleted', 0) )
			->andWhere( $oQueryBuilder->expr()->eq('m.isHide', 0) )
			->andWhere( $oQueryBuilder->expr()->eq('m.isModerate', 1) )
			->orderBy('m.delta','DESC')
			->setMaxResults($limit)
			->setFirstResult(0);*/
		$oCriteria = Criteria::create();
		$e = Criteria::expr();
		$oCriteria->where( $e->eq('isDeleted', 0) )
			->andWhere( $e->eq('isHide', 0) )
			->andWhere( $e->eq('isModerate', 1) )
			->orderBy(['delta' => Criteria::DESC])
			->setMaxResults($limit)
			->setFirstResult(0);
		
	
		if ($sRegion) {
            //Тут в зависимости от http запроса может быть ещё пару раз вызван
            //$oQueryBuilder->andWhere();
			$this->_setCityConditionAndInitCyrValues($oCriteria, $sRegion, $sCity);
		}
		
		$oSession = $oRequest->getSession();
		$aOrWhereType = [];
		$aOrWhereDistance = [];
		if (intval($oSession->get('people', 0))) {
			//$aOrWhereType[] = 'm.people = 1';
			$aOrWhereType[] = $e->eq('people', 1);
		}
		if (intval($oSession->get('box', 0))) {
			//$aOrWhereType[] = 'm.box = 1';
			$aOrWhereType[] = $e->eq('box', 1);
		}
		if (intval($oSession->get('term', 0))) {
			//$aOrWhereType[] = 'm.term = 1';
			$aOrWhereType[] = $e->eq('term', 1);
		}
		if (intval($oSession->get('far', 0))) {
			//$aOrWhereDistance[] = 'm.far = 1';
			$aOrWhereDistance[] = $e->eq('far', 1);
		}
		if (intval($oSession->get('near', 0))) {
			//$aOrWhereDistance[] = 'm.near = 1';
			$aOrWhereDistance[] = $e->eq('near', 1);
		}
		if (intval($oSession->get('piknik', 0))) {
			//$aOrWhereDistance[] = 'm.piknik = 1';
			$aOrWhereDistance[] = $e->eq('piknik', 1);
		}
		if ($aOrWhereType) {
            //Добавляем первые скобки с OR
			//$oQueryBuilder->andWhere($oQueryBuilder->expr()->andX(   join(' OR ', $aOrWhereType) ) );
			$oCriteria->andWhere(call_user_func_array([$e, 'orX'], $aOrWhereType) );
		}
		if ($aOrWhereDistance) {
            //Добавляем вторые скобки с OR
			//$oQueryBuilder->andWhere($oQueryBuilder->expr()->andX(   join(' OR ', $aOrWhereDistance) ) );
			$oCriteria->andWhere(call_user_func_array([$e, 'orX'], $aOrWhereDistance) );
		}
		
		//$aCollection = $oQueryBuilder->getQuery()->execute();
		$aCollection = $repository->matching($oCriteria)->toArray();
		return $aCollection;
	}
	/**
	 * Добавит в $aWhere фильтр по городу и/или региону
	 * Инициализует кириллические имена города и региона
	 * @param \Doctrine\ORM\QueryBuilder $oQueryBuilder ('Main:App') для запроса выборки объявлений, @see _loadAdvList
	 * @param string $sRegion = '' код региона латинскими буквами
     * @param string $sCity = ''   код города латинскими буквами
	*/
	private function _setCityConditionAndInitCyrValues(Criteria $oCriteria, string $sRegion = '', string $sCity = '') : void
	{
		$oGazelMeService = $this->get('App\Service\GazelMeService');
		$sCyrRegionName = '';
		$sCyrCityName = '';
		$oGazelMeService->setCityConditionAndInitCyrValues($oCriteria, $sCyrRegionName, $sCyrCityName, $sRegion, $sCity);
		$this->_sCyrRegionName = $sCyrRegionName;
		$this->_sCyrCityName = $sCyrCityName;
	}
	
}
