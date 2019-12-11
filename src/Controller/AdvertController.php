<?php

namespace App\Controller;

use App\Form\AjaxFileUploadFormType;
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

class AdvertController extends Controller
{
	/** @property \Doctrine\ORM\EntityManager $_oEm */
	private $_oEm;
	
	/** @property \App\Entity\Advert $_oAdvert */
	private $_oAdvert;
	
	/** @property \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $_oEncoder */
	private $_oEncoder;
	
	/** @property string $_subdir Каталог для загрузки файлов из настроек (без DOCUMENT_ROOT и подкаталогов ГОД/МЕСЯЦ ) */
	private $_subdir;

	/** @property bool $_bNeedUpdatePassword true  когда надо обновить пароль и email пользователя, который подавал ранее объявления не указывая email и пароль */
	private $_bNeedUpdatePassword = false;

	/** @property ?App\Entity\Users $_oAnonymousUser может содержать объект с данными о пользователе, который подавал ранее объявления не указывая email и пароль */
	private $_oAnonymousUser = null;
	

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
	public function uploadPhoto(Request $oRequest,  \App\Service\GazelMeService $oGazelMeService)
	{
		$this->_subdir = $this->getParameter('app.uploadfiledir') . '/' . date('Y/m');
		$aTData = [];
		$oForm = $this->_getAjaxForm($oGazelMeService);
		$aData = [];
		if ($oRequest->getMethod() == 'POST') {
			$oForm->handleRequest($oRequest);
			//$oForm->submit($oRequest->request->get($oForm->getName()));

			if ($oForm->isValid()) {
				//TODO get file path
				$oFile = $oForm['autophotoFileImmediately']->getData();
				if ($oFile) {
					$sFileName = $oGazelMeService->getFileUploaderService()->upload($oFile);
					$aData['path'] = '/' . $this->_subdir . '/' . $sFileName;
					$aData['status'] = 'ok';
				} else {
					$aData['status'] = 'error';
					$aData['message'] = 'NoN FiLe';
				}
			} else {
				//$oForm->
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
	public function add(Request $oRequest, ViewDataService $oViewDataService, \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $oEncoder, \App\Service\GazelMeService $oGazelMeService, \App\Service\RegionsService $oRegionService)
	{
		$this->_oAdvert = new Advert();
		$this->_oEncoder =  $oEncoder;
		$this->_oGazelMeService = $oGazelMeService;
		$this->_subdir = $this->getParameter('app.uploadfiledir') . '/' . date('Y/m');
		//TODO define _oForm
		$this->_oForm = $oForm = $this->createForm(get_class(new AdvertForm()), $this->_oAdvert, [
			'app_service' => $oGazelMeService,
			'request' => $oRequest,
			'uploaddir' => $this->_subdir
		]);

		$oAjaxForm = $this->_getAjaxForm($oGazelMeService);
		
		$oSession = $oRequest->getSession();
		
		/** @var \Symfony\Component\HttpFoundation\Session\Session $oSession **/
		
		if ($oRequest->getMethod() == 'POST') {
			$oForm->handleRequest($oRequest);
			if ($oForm->isValid()) {
				if ($this->_isAdditionalValid()) {	//TODO
					$this->_saveAdvertData($oForm, $oGazelMeService, $oRequest);
				} else {
					$this->addFlash('notice', $this->get('translator')->trans('Advert form has errors'));
				}
			} else {
				$this->addFlash('notice', $this->get('translator')->trans('Advert form has errors'));
			}
			$oSession->remove('is_add_advert_page');
		} else {
			$oSession->set('is_add_advert_page', true);
		}
		$aData = $oViewDataService->getDefaultTemplateData($oRequest);
		$aData['form'] = $oForm->createView();
		$aData['ajax_form'] = $oAjaxForm->createView();
		$aData['image'] = 'images/gazel.jpg';

		$aData['nRegionId'] = $oRegionService->getRegionIdFromSession($oRequest);
		$aData['aRegionId'] = [
			'value' => $aData['nRegionId']
		];
		$aData['nCityId'] = $oRegionService->getCityIdFromSession($oRequest);
		$aData['aCityId'] = [
			'value' => $aData['nCityId']
		];
		$aData['nIsCity'] = (intval($aData['nRegionId']) && !intval($aData['nCityId']));
		//$aData['nIsSetLocation'] = $oGazelMeService
		return $this->render('advert/form.html.twig', $aData);
	}
	/**
	 * Если пользователь не авторизован создаётся пользователь.
	 * Данные формы также сохраняются.
	**/
	private function _saveAdvertData(\Symfony\Component\Form\FormInterface $oForm, \App\Service\GazelMeService $oGazelMeService, Request $oRequest)
	{
		$this->_oEm = $this->getDoctrine()->getManager();

		if (isset($this->_nExistsUserId) ) {
			$nUserId = $this->_nExistsUserId;
		}

		if (!$this->getUser()) {
			if ($this->_bNeedCreateAccount) {
				$nUserId = $this->_saveUser($oRequest);
				$nUserId = $this->_nExistsUserId;
			}
			if ($this->_bNeedUpdatePassword) {
				$this->_updateUserPassword();
				$nUserId = $this->_oAnonymousUser->getId();
			}
		} else if ($this->getUser()){
			$nUserId = $this->getUser()->getId();
		}
		$nRegionId = $this->_oAdvert->getRegion();
		$nCityId = $this->_oAdvert->getCity();
		$nRegionId = intval($nRegionId) > 0 ? $nRegionId : null;
		$nCityId = intval($nCityId) > 0 ? $nCityId : null;
		
		$this->_oAdvert->setUserId($nUserId);
		
		//транслитировать заголовок объявления
		$this->_oAdvert->setCodename($oGazelMeService->translite_url($this->_oAdvert->getTitle()));
		
		//save file
		$oFile = $this->_oForm['imagefile']->getData();
        if ($oFile) {
            $sFileName = $this->_oGazelMeService->getFileUploaderService()->upload($oFile);
            $this->_oAdvert->setImage('/' . $this->_subdir . '/' . $sFileName);
        }
		
		$this->_oEm->persist($this->_oAdvert);
		$this->_oEm->flush();
		
		//Тут непонятный баг, region и city не устанавливаются, 
		// хотя при попытке getRegion() перед сохранеием корректное значение (пришедшее из формы).
		//Поэтому допиливаем апдейтом, что конечно безобразие
		$nId = $this->_oAdvert->getId();
		$sDQuery = 'UPDATE App:Main AS m '
				. 'SET m.region = :r, m.city = :c, m.delta = :id, m.userId = :uid '
				. 'WHERE m.id = :id';
		/** @var \Doctrine\ORM\Query $oQuery */
		$oQuery = $this->_oEm->createQuery($sDQuery);
		$oQuery->setParameters([
			':r' => $nRegionId,
			':c' => $nCityId,
			':id' => $nId,
			':uid' => $nUserId
		]);
		$oQuery->execute();
		
		
	}
	/**
	 * Создаётся пользователь.
	**/
	private function _saveUser(Request $oRequest)
	{
		$aData = $this->_formData();
		$oUserManager = $this->get('fos_user.user_manager');
		$oUser = $oUserManager->createUser();
		$oUser->setUsername($aData['phone']);
		$sEmail = trim($aData['email'] ?? '');
		if (!$sEmail) {
			//Установим временный email чтобы FOS не ругались
			$sEmail = md5( time() . rand(10000, 99999) . $oRequest->server->get('HTTP_USER_AGENT')) . '@mail.ru';
		}
		$oUser->setEmail($sEmail);
		//если не указан пароль, устанавливать setIsAnonymous(true)
		$sPassword = trim($aData['password'] ?? '');
		if (!$sPassword) {
			$sPassword = md5( $sEmail );
			$oUser->setIsAnonymous(true);
		}
		$oUser->setPlainPassword($sPassword);
		$oUser->setEnabled(true);
		$oUserManager->updateUser($oUser);
		$this->_nExistsUserId = $oUser->getId();
	}
	/**
	 * Дополнительная валидация формы подачи объявления. Если пользователь не авторизован, 
	 *	проверяется, не существует ли аккаунт с такими данными.
	 * Проверяется, выбран ли хотя бы один  тип транспорта и хотя бы одно расстояние
	**/
	private function _isAdditionalValid() : bool
	{
		$aData = $this->_formData();
		//валидация заполненности хотя бы одного из чекбоксов
		//Тип авто
		$nType = intval($aData['people'] ?? 0) + intval($aData['box'] ?? 0) + intval($aData['term'] ?? 0);
		if (!$nType) {
			$this->_addError('Type auto required', 'people');
			return false;
		}
		//Тип дистанции
		$nDistance = intval($aData['far'] ?? 0) + intval($aData['near'] ?? 0) + intval($aData['piknik'] ?? 0);
		if (!$nDistance) {
			$this->_addError('Distance required', 'far');
			return false;
		}
		//валидация логина и пароля, который может быть введён
		$this->_bNeedCreateAccount = false; //TODO add it define
		//Если введён email то должен быть введён и пароль
		//Таких данных в базе быть не должно, с учётом телефона
		$sPhone = $aData['phone'] ?? '';
		$sEmail = trim($aData['email'] ?? '');
		$sPassword = trim($aData['password'] ?? '');

		$oCriteria = new Criteria();
		$oExpr = $oCriteria->expr();
		$oCriteria->orderBy(['id' => 'ASC']);
		$oUserRepository = $this->getDoctrine()->getRepository('App:Users');
		$oCriteria->where(
			$oExpr->orX(
				$oExpr->eq('emailCanonical', $sEmail),
				$oExpr->eq('username', $sPhone)
			)
		);
		$oUser = $oUserRepository->matching($oCriteria)->get(0);
		if ($sEmail || $sPassword) {
			if (!$sEmail || !$sPassword ) {
				$this->_addError('Email and Password required if on from these no empty', 'email');
				return false;
			}
			//TODO test with empty phone (сам по себе, не конкретно случай с паролем ли без - не должна форма приниматсья ни при каких обстоятельствах)

			if ($oUser) {
				//проверяем, не совпал ли пароль
				$bPasswordValid = $this->_oEncoder->isPasswordValid($oUser, $sPassword);
										//и пользователь его устанавливал раньше
				if (!$bPasswordValid && !$oUser->getIsAnonymous()) {
					$this->_addError('User already exists, but password not valid', 'phone');
					return false;
				}
				//Еcли пароль не подходит и пользователь его не установил ранее (подавал не вводя email / password)
				if (!$bPasswordValid) { // & $oUser->getIsAnonymous()
					$this->_oAnonymousUser = $oUser;
					$this->_nExistsUserId = $oUser->getId();
					$this->_bNeedUpdatePassword = true;
				}
				$this->_nExistsUserId = $oUser->getId();
			} else {
				//Нет пользователя с таким логином или паролем - значит надо создать
				$this->_bNeedCreateAccount = true;
			}
			
		} else {
			//Тут норм, неавторизованым подавать позволяем, всё равно на запрос телефона редирект
			if ($oUser) {
				$this->_nExistsUserId = $oUser->getId();
			} else {
				//Нет пользователя с таким телефоном - значит надо создать
				$this->_bNeedCreateAccount = true;
			}
		}
		
		return true;
	}
	/**
	 * @return Request
	**/
	private function _request() : Request
	{
		return $this->get('request_stack')->getCurrentRequest();
	}
	/**
	 * @return array
	**/
	private function _formData() : array
	{
		return $this->_request()->get('advert_form');
	}
	/**
	 * @param string $sError
	 * @param string $sField
	**/
	private function _addError(string $sError, string $sField)
	{
		//TODO -> in service, third arg form = null and method setForm
		$oError = new \Symfony\Component\Form\FormError($this->get('translator')->trans($sError));
		$this->_oForm->get($sField)->addError($oError);
	}
	/**
	 * Обновить пароль пользователя, который ранее уже подавал объявления, но не указал пароль
	 * TODO _bNeedCreateAccount скорее всего надо логику посмотреть заново
	**/
	private function _updateUserPassword()
	{
		$aData = $this->_formData();
		$oUser = $this->_oAnonymousUser;
		if ($oUser) {
			$oUser->setEmail($aData['email']);
			$oUser->setPlainPassword($aData['password']);
			$oUser->setEnabled(true);
			$oUser->setIsAnonymous(false);
			$oUserManager = $this->get('fos_user.user_manager');
			$oUserManager->updateUser($oUser);
			$this->_nExistsUserId = $oUser->getId();
		}
	}
	/*
	 * @return FormInterface форма для обработки ajax загрузки файла
	*/
	private function _getAjaxForm( $oGazelMeService) : FormInterface
	{
		return $this->createForm(get_class(new AjaxFileUploadFormType()), null, [
			'app_service' => $oGazelMeService,
			'uploaddir' => $this->_subdir
		]);
	}
}
