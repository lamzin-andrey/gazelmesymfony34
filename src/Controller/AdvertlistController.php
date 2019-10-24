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
		$qb = $repository->createQueryBuilder('m');
		
		/*$aWhere = [
			'isDeleted' => 0,
			'isHide' => 0,
			'isModerate' => 1
		];*/
		$oQuery = $qb->select('m.id, m.title, m.addtext, m.price, m.people, m.box, m.term, m.far, m.near, m.piknik, m.image, m.name, m.phone, m.pinned, m.codename')
			->where( $qb->expr()->eq('m.isDeleted', 0) )
			->where( $qb->expr()->eq('m.isHide', 0) )
			->where( $qb->expr()->eq('m.isModerate', 1) )
			->leftJoin('App\Entity\Cities', 'c', \Doctrine\ORM\Query\Expr\Join::WITH, 'c.id = m.city')
			->addSelect('c.cityName')
			//->leftJoin('App:Regions', 'r', 'on', 'r.id = m.region')
			->getQuery();
		
		$aCollection = $oQuery->getResult();
		var_dump($aCollection);
		die;/**/
			
		
		return $aCollection;
		
		if ($sRegion) {
			$this->_setCityConditionAndInitCyrValues($aWhere, $sRegion, $sCity);
		}
		
		$oSession = $oRequest->getSession();
		if (intval($oSession->get('people', 0))) {
			$aWhere['people'] = 1;
		}
		if (intval($oSession->get('box', 0))) {
			$aWhere['box'] = 1;
		}
		if (intval($oSession->get('term', 0))) {
			$aWhere['term'] = 1;
		}
		if (intval($oSession->get('far', 0))) {
			$aWhere['far'] = 1;
		}
		if (intval($oSession->get('near', 0))) {
			$aWhere['near'] = 1;
		}
		if (intval($oSession->get('piknik', 0))) {
			$aWhere['piknik'] = 1;
		}
		
		
		
		$aCollection = $repository->findBy($aWhere, [
			'delta' => 'DESC',
		], $limit, 0);
		
		return $aCollection;
	}
	/**
	 * Добавит в $aWhere фильтр по городу и/или региону
	 * Инициализует кириллические имена города и региона
	 * @param array &$aWhere для запроса выборки объявлений, @see _loadAdvList
	 * @param string $sRegion = '' код региона латинскими буквами
     * @param string $sCity = ''   код города латинскими буквами
	*/
	private function _setCityConditionAndInitCyrValues(array &$aWhere, string $sRegion = '', string $sCity = '') : void
	{
		$oGazelMeService = $this->get('App\Service\GazelMeService');
		$sCyrRegionName = '';
		$sCyrCityName = '';
		$oGazelMeService->setCityConditionAndInitCyrValues($aWhere, $sCyrRegionName, $sCyrCityName, $sRegion, $sCity);
		$this->_sCyrRegionName = $sCyrRegionName;
		$this->_sCyrCityName = $sCyrCityName;
	}
	
}
