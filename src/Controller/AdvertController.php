<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
			'file_uploader' => $oGazelMeService->getFileUploaderService(),
			'request' => $oRequest,
			'uploaddir' => $this->_subdir
		]);
		
		if ($oRequest->getMethod() == 'POST') {
			$oForm->handleRequest($oRequest);
			if ($oForm->isValid()) {
				if ($this->_isAdditionalValid()) {	//TODO
					$this->_saveAdvertData($oForm, $oGazelMeService);
				}
			} else {
				/** @var \Symfony\Component\Form\FormErrorIterator $errs */
				$errs = $oForm->getErrors(true);
			}
		}
		$aData = $oViewDataService->getDefaultTemplateData($oRequest);
		$aData['form'] = $oForm->createView();
		$aData['image'] = 'images/gazel.jpg';
		$aData['aRegionId'] = [
			'value' => $oRegionService->getRegionIdFromSession($oRequest)
		];
		$aData['aCityId'] = [
			'value' => $oRegionService->getCityIdFromSession($oRequest)
		];
		return $this->render('advert/form.html.twig', $aData);
	}
	/**
	 * Если пользователь не авторизован создаётся пользователь.
	 * Данные формы также сохраняются.
	**/
	private function _saveAdvertData(\Symfony\Component\Form\FormInterface $oForm, \App\Service\GazelMeService $oGazelMeService)
	{
		$this->_oEm = $this->getDoctrine()->getManager();
		
		if (!$this->getUser() && $this->_bNeedCreateAccount) {
			$this->_saveUser();
		}
		$nRegionId = $this->_oAdvert->getRegion();
		$nCityId = $this->_oAdvert->getCity();
		$nRegionId = intval($nRegionId) > 0 ? $nRegionId : null;
		$nCityId = intval($nCityId) > 0 ? $nCityId : null;
		
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
		$sDQuery = 'UPDATE App:Main AS m SET m.region = :r, m.city = :c, m.delta = :id WHERE m.id = :id';
		/** @var \Doctrine\ORM\Query $oQuery */
		$oQuery = $this->_oEm->createQuery($sDQuery);
		$oQuery->setParameters([
			':r' => $nRegionId,
			':c' => $nCityId,
			':id' => $nId
		]);
		$oQuery->execute();
		
		
	}
	/**
	 * TODO что-то не работает, main gott (мскорее всего дело было в пустом пароле)!
	 * Создаётся пользователь.
	**/
	private function _saveUser()
	{
		$aData = $this->_formData();
		$oUserManager = $this->get('fos_user.user_manager');
		$oUser = $oUserManager->createUser();
		$oUser->setUsername($aData['phone']);
		$oUser->setEmail($aData['email']);
		$oUser->setPlainPassword($aData['password']);
		$oUser->setEnabled(true);
		$oUserManager->updateUser($oUser);
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
		
		if ($sEmail || $sPassword) {
			if (!$sEmail || !$sPassword ) {
				$this->_addError('Email and Password required if on from these no empty', 'email');
				return false;
			}
			//TODO test with empty phone (сам по себе, не конкретно случай с паролем ли без - не должна форма приниматсья ни при каких обстоятельствах)
			$oCriteria = new Criteria();
			$oExpr = $oCriteria->expr();
			$oUserRepository = $this->getDoctrine()->getRepository('App:Users');
			
			$oCriteria->where( 
				$oExpr->orX(
						$oExpr->eq('emailCanonical', $sEmail),
						$oExpr->eq('username', $sPhone)
				)
			 );
			$oUser = $oUserRepository->matching($oCriteria)->get(0);
			if ($oUser) {
				//проверяем, не совпал ли пароль
				$bPasswordValid = $this->_oEncoder->isPasswordValid($oUser, $sPassword);
				if (!$bPasswordValid) {
					$this->_addError('User already exists, but password not valid', 'phone');
					return false;
				}
			} else {
				//Нет пользователя с таким логином или паролем - значит надо создать
				$this->_bNeedCreateAccount = true;
			}
			
		} else {
			//Тут норм, неавторизованым подавать позволяем, всё равно на запрос телефона редирект
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
}
