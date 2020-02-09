<?php

namespace App\Controller;

use App\Form\AjaxFileUploadFormType;
use App\Service\AdvertEditorService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use \App\Entity\Main as Advert;
use \App\Entity\Cities;
use \App\Entity\Regions;

use App\Service\GazelMeService;
use App\Service\RegionsService;
use App\Service\ViewDataService;

use Doctrine\Common\Collections\Criteria;

use App\Form\AdvertForm;

class AdvertController extends Controller  implements IAdvertController
{

	/** @property \App\Entity\Advert $_oAdvert */
	private $_oAdvert;

	/** @property string $_subdir Каталог для загрузки файлов из настроек (без DOCUMENT_ROOT и подкаталогов ГОД/МЕСЯЦ ) */
	private $_subdir;



	/** @property GazelMeService $_oGazelMeService сервис приложения, содержит методы общие для всех контроллеров */
	private $_oGazelMeService = null;

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
		if (!$advert) {
			throw $this->createNotFoundException('Advert not found');
		}
		$siteName = $this->getParameter('app.site_name');
		
		$sCyrRegionName = '';
		$sCyrCityName = '';
		$nCityId = 0;
		$nRegionId = 0;
		$oCriteria = Criteria::create();
		$oGazelMeService->setCityConditionAndInitCyrValues($oCriteria , $sCyrRegionName, $sCyrCityName, $sRegion, $sCity, $nCityId, $nRegionId);
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
	/**
	 * Ajax загрузка фото
	 * @Route("/fileupload.json", name="upload_auto_photo")
	*/
	public function uploadPhoto(Request $oRequest,  \App\Service\GazelMeService $oGazelMeService, AdvertEditorService $oAdvertEditorService)
	{
		$this->_subdir = $this->getParameter('app.uploadfiledir') . '/' . date('Y/m');
		$aTData = [];
		$oForm = $this->_getAjaxForm($oGazelMeService, $oAdvertEditorService);
		$aData = [];
		if ($oRequest->getMethod() == 'POST') {
			$oForm->handleRequest($oRequest);
			//$oForm->submit($oRequest->request->get($oForm->getName()));

			if ($oForm->isValid()) {
				$oFile = $oForm['autophotoFileImmediately']->getData();
				if ($oFile) {
					$sFileName = $oGazelMeService->getFileUploaderService()->upload($oFile);
					$aData['path'] = '/' . $this->_subdir . '/' . $sFileName;
					$oSession = $oRequest->getSession();
					$oSession->set('lastAdvertImage', $aData['path']);
					$aData['status'] = 'ok';
				} else {
					$aData['status'] = 'error';
					$aData['message'] = 'NoN FiLe';
				}
			} else {
				$aData['status'] = 'error';
				$aData['message'] = 'Invalid file';
				$aData['count_errors'] = $oForm->getErrors(true)->count();
				if ($aData['count_errors'] > 0) {
					$aData['message'] = $oForm->getErrors(true)->current()->getMessage();
				}
			}
		}
		$oResponse = new Response( json_encode($aData) );
		$oResponse->headers->set('Content-Type', 'application/json');
		return $oResponse;
	}
	/**
	 * Форма подачи объявления
	 * @Route("/podat_obyavlenie", name="podat_obyavlenie")
	*/
	public function add(Request $oRequest, \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $oEncoder, AdvertEditorService $oAdvertEditorService)
	{
		$this->_oAdvert = new Advert();
		$oAdvertEditorService->setController($this);
		$aData = $oAdvertEditorService->pageAdvertForm($oRequest, $oEncoder, $this->_oAdvert);
		return $this->render('advert/form.html.twig', $aData);
	}
	/*
	 * @return FormInterface форма для обработки ajax загрузки файла
	*/
	private function _getAjaxForm( $oGazelMeService, $oAdvertEditorService) : FormInterface
	{
		$oAdvertEditorService->setController($this);
		return $oAdvertEditorService->getAjaxForm();
	}

	public function createFormEx(string $sFormTypeClass, $oEntity, array $aOptions)
	{
		return $this->createForm($sFormTypeClass, $oEntity, $aOptions);
	}

	public function addFlashEx(string $sType, string $sMessage)
	{
		return $this->addFlash($sType, $sMessage);
	}
}
