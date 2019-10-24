<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use \App\Entity\Main;
use \App\Entity\Cities;
use \App\Entity\Regions;

use App\Service\GazelMeService;
use App\Service\RegionsService;

class DefaultController extends Controller
{
	
	/** @property string _sCyrCityName кирилическое имя локации */
	private $_sCyrCityName = '';
	
	/** @property string _sCyrRegionName кирилическое имя локации */
	private $_sCyrRegionName = '';
	
	//Страницы выбора региона
	
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
	 * Показать форму установки параметров объявления
	 * @Route("/showfilter", name="showfilter") 
	*/
	public function showfilter(Request $oRequest)
	{
		$oSession = $oRequest->getSession();
		$aData = $this->_getDefaultTemplateData($oRequest);
		$sTitle = $this->get('translator')->trans('Set filter page');
		$aData['title'] = $sTitle;
		$aData['nIspage100Percents'] = 1;
		$aData['h1'] = $sTitle;
		$aData['people'] = ( intval($oSession->get('people', 0)) ? 'checked="checked"' : '');
		$aData['box'] = ( intval($oSession->get('box', 0)) ? 'checked="checked"' : '');
		$aData['term'] = ( intval($oSession->get('term', 0)) ? 'checked="checked"' : '');
		$aData['far'] = ( intval($oSession->get('far', 0)) ? 'checked="checked"' : '');
		$aData['near'] = ( intval($oSession->get('near', 0)) ? 'checked="checked"' : '');
		$aData['piknik'] = ( intval($oSession->get('piknik', 0)) ? 'checked="checked"' : '');
		return $this->render('list/filterform.html.twig', $aData);
	}
	
	/**
	 * @Route("/phones/{nId}") 
	*/
	public function phones(int $nId, GazelMeService $oGazelMeService)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'image/png');
		$sData = $oGazelMeService->getPhoneAsImage($nId);
		return $response;
	}
	/**
      * @Route("/", name="home")
    */
    public function index(Request $request, GazelMeService $oGazelMeService)
    {
		return $this->_advListPage($request, $oGazelMeService);
		
	}
	/**
      * @Route("/{sCity}")
      * @param string $sCity
      * @param Request $request
	  * @param GazelMeService $oGazelMeService
    */
    public function megapolis(string $sCity, Request $request, GazelMeService $oGazelMeService)
    {
		return $this->_advListPage($request, $oGazelMeService, $sCity);
	}
    /**
      * @Route("/{sRegion}/{sCity}")
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
      * @Route("/{sRegion}/{sCity}/{sTitle}/{nAdvId}")
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
      * @Route("/{sRegion}/{sTitle}/{nAdvId}")
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
	  * Общая логика для главной и для страницы списка объявлений
	  * @param Request $oRequest
	  * @param GazelMeService $oGazelMeService
	  * @param string $sRegion = '' код региона латинскими буквами
      * @param string $sCity = ''   код города латинскими буквами
    */
	private function _advListPage(Request $oRequest, GazelMeService $oGazelMeService, string $sRegion = '', string $sCity = '')
	{
		//adverts data
		$oSession = $oRequest->getSession();
		$oSession->set('people', intval( $oRequest->get('people', 0) ));
		$oSession->set('box', intval( $oRequest->get('box', 0) ));
		$oSession->set('term', intval( $oRequest->get('term', 0) ));
		$oSession->set('far', intval( $oRequest->get('far', 0) ));
		$oSession->set('near', intval( $oRequest->get('near', 0) ));
		$oSession->set('piknik', intval( $oRequest->get('piknik', 0) ));
		
		
		$adverts = $this->_loadAdvList($sRegion, $sCity, $oRequest);
		$this->_saveSelectedLocation($sRegion, $sCity, $oRequest);

		//for links
		$s = $oRequest->server->get('REQUEST_URI');
		$a = explode('?', $s);
		$currentTail =  ($a[1] ?? '');//??

		
		
		$aData = $this->_getDefaultTemplateData($oRequest);
		$sTitle = $this->get('translator')->trans('Set filter page');
		$aData['title'] = $oGazelMeService->getTiltle($oRequest, $this->_sCyrRegionName, $this->_sCyrCityName);
		$aData['h1'] = $oGazelMeService->getMainHeading($oRequest, $this->_sCyrRegionName, $this->_sCyrCityName);
		
		$sCyrLocation = $oSession->get('sCyrLocation', '');
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
		$a = [];
		$this->_setCityConditionAndInitCyrValues($a, $sRegion, $sCity);
		
		$aData = $this->_getDefaultTemplateData($oRequest);
		$aData['title'] = $oGazelMeService->getTiltle($oRequest, $this->_sCyrRegionName, $this->_sCyrCityName);
		$aData['h1'] = $oGazelMeService->getMainHeading($oRequest, $this->_sCyrRegionName, $this->_sCyrCityName, $advert->getTitle());
		$aData['advert'] = $advert;
		$aData['nCountAdverts'] = 1;
		$aData['nIspage100Percents'] = 1;
		$aData['backLink'] = $this->_getBackLink($sRegion, $sCity);
        return $this->render('advert.html.twig', $aData);
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
		$aWhere = [
			'isDeleted' => 0,
			'isHide' => 0,
			'isModerate' => 1
		];
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
		
		$repository = $this->getDoctrine()->getRepository('App:Main');
		$aCollection = $repository->findBy($aWhere, [
			'delta' => 'DESC',
		], $limit, 0);
		
		return $aCollection;
	}
	/**
	 * Добавит в $aWhere фильтр по городу и/или региону
	 * Инимциализует кириллические имена города и региона
	 * @param array &$aWhere для запроса выборки объявлений, @see _loadAdvList
	 * @param string $sRegion = '' код региона латинскими буквами
     * @param string $sCity = ''   код города латинскими буквами
	 * @return array
	*/
	private function _setCityConditionAndInitCyrValues(array &$aWhere, string $sRegion = '', string $sCity = '') : void
	{
		if ($sRegion) {
			//всегда сначала загружаем по региону
			$oRepository = $this->getDoctrine()->getRepository('App:Regions');
			$aRegions = $oRepository->findBy([
				'codename' => $sRegion
			]);
			if ($aRegions) {
				$oRegion = current($aRegions);
				if ($oRegion) {
					$aWhere['region'] = $oRegion->getId();
					$this->_sCyrRegionName = $oRegion->getRegionName();
					if ($sCity) {
						//Тут в любом случае будет не более десятка записей для сел типа Крайновка или Калиновка. Отфильровать на php
						$aCities = $oRegion->getCities();
						foreach($aCities as $oCity) {
							if ($oCity->getCodename() == $sCity) {
								$this->_sCyrCityName = $oCity->getCityName();
								$aWhere['city'] = $oCity->getId();
								break;
							}
						}
					}
				}
			}
		}
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
	//TODO
	private function _getIsAgreementPage() : int
	{
		return 0;
	}
	/**
	 * Строит ссылку на список объявлений региона @see _advPage
	 * @param $oSesson
	 * @return string
	*/
	private function _getLocationUrl($oSession) : string
	{
		$sRegionCodename = $oSession->get('sRegionCodename', '/');
		$sCityCodename = $oSession->get('sCityCodename', '');
		$sLocationUrl = ($sRegionCodename);
		if ($sCityCodename) {
			$sLocationUrl = ($sLocationUrl . '/' . $sCityCodename);
		}
		if ($sLocationUrl[0] != '/') {
			$sLocationUrl = '/' . $sLocationUrl;
		}
		return $sLocationUrl;
	}
	/**
	 * Строит query string с параметрами фильтра типов машин
	 * @param $oSesson
	 * @return string
	*/
	private function _getFilterQueryString($oSession) : string
	{
		$a = [];
		if (intval($oSession->get('people', 0))) {
			$a[] = 'people=1';
		}
		if (intval($oSession->get('box', 0))) {
			$a[] = 'box=1';
		}
		if (intval($oSession->get('term', 0))) {
			$a[] = 'term=1';
		}
		if (intval($oSession->get('far', 0))) {
			$a[] = 'far=1';
		}
		if (intval($oSession->get('near', 0))) {
			$a[] = 'near=1';
		}
		if (intval($oSession->get('piknik', 0))) {
			$a[] = 'piknik=1';
		}
		if (!$a) {
			return '';
		}
		$s = '?' . join('&', $a);
		return $s;
	}
	/**
	 * Возвращает переменные, которые есть в мастер шаблоне (то есть они есть практически на каждой странице)
	*/
	private function _getDefaultTemplateData(Request $oRequest) : array
	{
		$siteName = $this->getParameter('app.site_name', 10);
		$oSession = $oRequest->getSession();
		return [
			'assetsVersion' => 0,
			'additionalCss' => '',
			'additionalJs' => '',
			'csrf' => '',
			'uid' => 0,
			'regionId' => '',
			'cityId' => '',
			'politicDoc' => '/images/Politika_zashity_i_obrabotki_personalnyh_dannyh_2019-08-14.doc',
			'isAgreementPage' => $this->_getIsAgreementPage(),
			'siteName' => $siteName,
			'sLocationUrl' => $this->_getLocationUrl($oSession),
			'sFilterQueryString' => $this->_getFilterQueryString($oSession),
			'isLocalhost' => true
			/*'' => '',
			'' => '',*/
		];
	}
	/**
	 * Общая логика для запросов вида /regions /regions/a /regions/samarskaya_oblast /regions/samarskaya_oblast/a
	*/
	private function _regionsPage(Request $oRequest, GazelMeService $oGazelMeService, RegionsService $oRegionsService)
	{
		$oSession = $oRequest->getSession();
		$aData = $this->_getDefaultTemplateData($oRequest);
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
	/**
	 * Устанавливает в сессии выбранный пользователем город только в том случае, если человек пришел со страницы /regions/*
	 * @param string $sRegion
	 * @param string $sCity
	 * @param Request $oRequest
	*/
	private function _saveSelectedLocation(string $sRegion, string $sCity, Request $oRequest) : void
	{
		$sReferer = $oRequest->server->get('HTTP_REFERER');
		$sUrl = explode('?', $oRequest->server->get('REQUEST_URI') )[0];
		if ($sUrl != '/') {
			$aUrl = parse_url($sReferer);
			$sPath = ($aUrl['path'] ?? '');
			if (strpos($sPath, '/regions') === 0) {
				//set location
				$aReqUrl = explode('/', $sUrl);
				$oSession = $oRequest->getSession();
				$nSz = count($aReqUrl);
				if ($nSz == 3) {
					$oSession->set('sRegionCodename', $aReqUrl[1]);
					$oSession->set('sCityCodename', $aReqUrl[2]);
					$this->_setCyrLocationValue($oSession, $aReqUrl[1], $aReqUrl[2]);
				} else if ($nSz == 2) {
					$oSession->set('sRegionCodename', $aReqUrl[1]);
					$oSession->set('sCityCodename', '');
					$this->_setCyrLocationValue($oSession, $aReqUrl[1]);
				} else {
					$oSession->set('sRegionCodename');
					$oSession->set('sCityCodename');
					$oSession->set('sCyrLocation', '');
				}
			}
		}
	}
	/**
	 * Устанавливает в сессии строку, которая показывается на форме фильтра как наименование локации
	 * @param $oSession (Request::getSession() )
	 * @param string $sRegion кодовое (транслитированое)  имя региона или мегаполиса латинскими буквами
	 * @param string string $sCity = '' кодовое (транслитированое)  имя города  латинскими буквами
	*/
	private function _setCyrLocationValue($oSession, string $sRegion, string $sCity = '') : void
	{
		if (!$this->_sCyrRegionName) {
			$a = [];
			$this->_setCityConditionAndInitCyrValues($a, $sRegion, $sCity);
		}	
		if ($this->_sCyrRegionName) {
			if ($this->_sCyrCityName) {
				$oSession->set('sCyrLocation', $this->_sCyrRegionName . ' ' . $this->_sCyrCityName);
			} else {
				$oSession->set('sCyrLocation', $this->_sCyrRegionName);
			}
		}
	}
}
