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

class DefaultController extends Controller
{
	
	/** @property string _sCyrCityName кирилическое имя локации */
	private $_sCyrCityName = '';
	
	/** @property string _sCyrRegionName кирилическое имя локации */
	private $_sCyrRegionName = '';
	
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
      * @Route("/")
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
	  * @param Request $request
	  * @param GazelMeService $oGazelMeService
	  * @param string $sRegion = '' код региона латинскими буквами
      * @param string $sCity = ''   код города латинскими буквами
    */
	private function _advListPage(Request $request, GazelMeService $oGazelMeService, string $sRegion = '', string $sCity = '')
	{
		//adverts data
		$adverts = $this->_loadAdvList($sRegion, $sCity, $request);

		//for links
		$s = $request->server->get('REQUEST_URI');
		$a = explode('?', $s);
		$currentTail =  ($a[1] ?? '');

		$siteName = $limit = $this->getParameter('app.site_name', 10);

        return $this->render('list/mainlist.html.twig', [
			'title' => $oGazelMeService->getTiltle($request, $this->_sCyrRegionName, $this->_sCyrCityName),
			'assetsVersion' => 0,
			'additionalCss' => '',
			'additionalJs' => '',
			'csrf' => '',
			'uid' => '0',
			'regionId' => '',
			'cityId' => '',
			'h1' => $oGazelMeService->getMainHeading($request, $this->_sCyrRegionName, $this->_sCyrCityName),
			'politicDoc' => '/images/Politika_zashity_i_obrabotki_personalnyh_dannyh_2019-08-14.doc',
			'isLocalhost' => true,
			'isAgreementPage' => 0,
			'list' => $adverts,
			'nCountAdverts' => count($adverts),
			'siteName' => $siteName,
			/*'' => '',
			'' => '',*/
		]);
	}
	/**
	  * Общая логика для главной и для страницы списка объявлений
	  * @param Request $request
	  * @param GazelMeService $oGazelMeService
	  * @param int $nAdvId идентификатор объявления
	  * @param string $sTitle main.codename
	  * @param string $sRegion = '' код региона латинскими буквами
      * @param string $sCity = ''   код города латинскими буквами
    */
	private function _advPage(Request $request, GazelMeService $oGazelMeService, int $nAdvId, string $sTitle, string $sRegion = '', string $sCity = '')
	{
		//advert data
		$oRepository = $this->getDoctrine()->getRepository('App:Main');
		$advert = $oRepository->find($nAdvId);
		if (!$advert) {
			die('TODO 404 ' . __FILE__ . __LINE__);
		}
		$siteName = $this->getParameter('app.site_name');
		$a = [];
		$this->_setCityConditionAndInitCyrValues($a, $sRegion, $sCity);
		$currentTail = '';//??

        return $this->render('advert.html.twig', [
			'title' => $oGazelMeService->getTiltle($request, $this->_sCyrRegionName, $this->_sCyrCityName, $advert->getTitle()),
			'assetsVersion' => 0,
			'additionalCss' => '',
			'additionalJs' => '',
			'csrf' => '',
			'uid' => '0',
			'regionId' => '',
			'cityId' => '',
			'h1' => $oGazelMeService->getMainHeading($request, $this->_sCyrRegionName, $this->_sCyrCityName, $advert->getTitle()),
			'politicDoc' => '/images/Politika_zashity_i_obrabotki_personalnyh_dannyh_2019-08-14.doc',
			'isLocalhost' => true,
			'isAgreementPage' => 0,
			'advert' => $advert,
			'nCountAdverts' => 1,
			'backLink' => $this->_getBackLink($sRegion, $sCity),
			'siteName' => $siteName,
			/*'' => '',
			'' => '',*/
		]);
	}
	/**
	 * 
	 * @param string $sRegion = '' код региона латинскими буквами
     * @param string $sCity = ''   код города латинскими буквами
	 * @return array
	*/
	private function _loadAdvList(string $sRegion = '', string $sCity = '') : array
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
		
		$repository = $this->getDoctrine()->getRepository('App:Main');
		$aCollection = $repository->findBy($aWhere, [
			'delta' => 'DESC',
		], $limit, 0);
		/*var_dump($aCollection[0]->getRegionObject()->getRegionName());*/
		/*var_dump($aCollection[0]);
		die;/**/
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
}
