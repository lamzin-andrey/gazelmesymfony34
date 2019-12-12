<?php
namespace App\Service;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use App\Entity\Main;
use App\Entity\Regions;
use App\Entity\Cities;

/***
 * @class ViewDataService содержит методы для получения данных viewData, общих для многих страниц. Используется многими контроллерами.
*/
class ViewDataService  {
	
	public function __construct(ContainerInterface $oContainer, CsrfTokenManagerInterface $oTokenManager)
	{
		$this->oContainer = $oContainer;
		$this->oTranslator = $oContainer->get('translator');
		$this->_oTokenManager = $oTokenManager;
	}
	
	/**
	 * Возвращает переменные, которые есть в мастер шаблоне (то есть они есть практически на каждой странице)
	*/
	public function getDefaultTemplateData(?Request $oRequest = null) : array
	{
		if (!$oRequest) {
			$oRequest = $this->oContainer->get('request_stack')->getCurrentRequest();
		}
		$siteName = $this->oContainer->getParameter('app.site_name', '');
		$oSession = $oRequest->getSession();
		$nUid = $this->getUid();
		
		$aData = [
			'assetsVersion' => 0,
			'additionalCss' => '',
			'additionalJs' => '',
			'csrf_token' => $this->_oTokenManager ? $this->_oTokenManager->getToken('authenticate')->getValue() : '',
			'uid' => $nUid,
			'politicDoc' => '/images/Politika_zashity_i_obrabotki_personalnyh_dannyh_2019-08-14.doc',
			'isAgreementPage' => $this->_getIsAgreementPage(),
			'siteName' => $siteName,
			'sLocationUrl' => $this->_getLocationUrl($oSession),
			'sFilterQueryString' => $this->_getFilterQueryString($oSession),
			'isLocalhost' => true
		];
		
		$sCyrLocation = $this->oContainer->get('App\Service\RegionsService')->getDisplayLocationFromSession($oRequest);
		if ($sCyrLocation) {
			$aData['nIsSetLocaton'] = 1;
			$aData['sDisplayLocation'] = $sCyrLocation;
		} else {
			$aData['nIsSetLocaton'] = 0;
			$aData['sDisplayLocation'] = '';
		}
		
		return $aData;
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
		$sRegionCodename = $sRegionCodename ? $sRegionCodename : '/';
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
	 * Вернет 0 если пользователь не авторизован и id авторизованного пользователя, если пользователь авторизован
	 * @return int
	*/
	public function getUid() : int
	{
		$nUid = 0;
		$oUser = $this->oContainer->get('security.token_storage')->getToken()->getUser();
		if (!is_string($oUser)) {
			$nUid = $oUser->getId();
		}
		return $nUid;
	}
}
