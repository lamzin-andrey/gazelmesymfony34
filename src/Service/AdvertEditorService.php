<?php
namespace App\Service;

use App\Entity\Users;
use App\Form\AdvertForm;
use App\Form\AjaxFileUploadFormType;
use ReCaptcha\ReCaptcha;
use \Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Main;
use \Landlib\Text2Png;
use Doctrine\Common\Collections\Criteria;
use Landlib\RusLexicon;
use Landlib\SymfonyToolsBundle\Service\FileUploaderService;

class AdvertEditorService
{
	/** @property \Doctrine\ORM\EntityManager $_oEm */
	private $_oEm;

	/** @property \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $_oEncoder */
	private $_oEncoder;

	/** @property FormInterface $_oForm = $this->createForm( new AdvertForm() ) Сюда можно передать форму для более простой работы с ними */
	private $_oForm;

	/** @property bool $_bNeedUpdatePassword true  когда надо обновить пароль и email пользователя, который подавал ранее объявления не указывая email и пароль */
	private $_bNeedUpdatePassword = false;

	/** @property ?App\Entity\Users $_oAnonymousUser может содержать объект с данными о пользователе, который подавал ранее объявления не указывая email и пароль */
	private $_oAnonymousUser = null;

	/** @property bool $_bNeedCreateAccount true  когда надо создавать запись в users (объявление подаёт ранее никогда не публиковавшийся пользователь) */
	private $_bNeedCreateAccount = false;

	/** @property int $_nExistsUserId идентификатор уже существующего пользователя */
	private $_nExistsUserId = 0;

	/** @property $_oController need  implements IAdvertController */
	private $_oController = null;

	/** @property \App\Entity\Main $_oAdvert  */
	private $_oAdvert = null;



	public function __construct(ContainerInterface $container, ViewDataService $oViewDataService, FileUploaderService $oFileUploaderService, GazelMeService $oGazelMeService, RegionsService $oRegionsService)
	{
		$this->_oContainer = $container;
		$this->translator = $container->get('translator');
		$this->oViewDataService = $oViewDataService;
		$this->oFileUploaderService = $oFileUploaderService;
		$this->_oGazelMeService = $oGazelMeService;
		$this->_oRegionsService = $oRegionsService;
	}
	/**
	 * Общая логика обработки формы подачи объявления для анонимуса и для авторизованого фага
	 * @param Request $oRequest
	 * @param \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $oEncoder
	 * @param \App\Entity\Main $oAdvert
	 * @param bool $bModeEdit = false
	 * @return array
	*/
	public function pageAdvertForm(Request $oRequest, \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $oEncoder, \App\Entity\Main $oAdvert, bool $bModeEdit = false) : array
	{
		if (!$this->_oController) {
			throw new \Exception('Need call AdvertEditorService::setController');
			return [];
		}
		$this->_oEncoder =  $oEncoder;
		$this->_oAdvert = $oAdvert;
		$this->_oForm = $oForm = $this->_oController->createFormEx(get_class(new AdvertForm()), $this->_oAdvert, [
			'app_service' => $this->_oGazelMeService,
			'uploaddir' => $this->_subdir
		]);
		$oAjaxForm = $this->getAjaxForm();
		$oSession = $oRequest->getSession();

		/** @var ViewDataService $oViewDataService **/
		$oViewDataService = $this->_oGazelMeService->getViewDataService();
		/** @var \Symfony\Component\HttpFoundation\Session\Session $oSession **/
		$aData = $oViewDataService->getDefaultTemplateData($oRequest);
		$aData['redirectToCabinedId'] = '';
		$aData['sActionPath'] = 'podat_obyavlenie';
		$aData['actionPathAttributes'] = [];
		$aData['aPhone'] = [];
		$aData['aCompanyName'] = [];
		$aData['agreeAttrs'] = [];
		$t = $this->translator;
		$aData['title'] = $t->trans('To add an advert');

		if ($oAdvert && $oAdvert->getId() && $this->getUser() && $this->getUser()->getId() != $oAdvert->getUserId()) {
			$aData = $this->_oGazelMeService->getViewDataService()->getDefaultTemplateData($oRequest);
			$this->addFlash('notice', 'You have not access to thid advert');
			return $aData;
		}

		if ($this->getUser()) {
			$aData['aCompanyName']['disabled'] = $aData['aPhone']['disabled']  = 'disabled';
			$aData['agreeAttrs'] = [
				'checked' => 'checked'
			];
			$aData['aPhone']['value'] = $this->getUser()->getUsername();
			$aData['aCompanyName']['value'] = $this->getUser()->getDisplayName();
		}
		//$aData['nModeIsEdit'] = 0;

		if ($oRequest->getMethod() == 'POST') {
			if ($this->getUser()) {
				$this->_patchRequestForEditMode($oForm);
			} else {
				$oForm->handleRequest($oRequest);
			}
			if ($oForm->isValid()) {
				if ($this->_isAdditionalValid($bModeEdit)) {
					$this->_saveAdvertData($oForm, $this->_oGazelMeService, $oRequest, $bModeEdit);
					$oRequest->getSession()->set('activePhone', $this->_oAdvert->getPhone());
					$oRequest->getSession()->set('verified_adv_id', $this->_oAdvert->getId());
					$aData['redirectToConfirmPhone'] = '1';
					$sMsg = 'You need to confirm your phone number. You will now be redirected to the confirmation page';
					if ($this->getUser() && $this->getUser()->getIsSmsVerify() == 1) {
						$aData['redirectToConfirmPhone'] = '0';
						$aData['redirectToCabinedId'] = $this->_oAdvert->getId();
						$sMsg = 'Your ad has been added and will be placed on the site after verification';
					}
					$oSession->remove('lastAdvertImage');
					$this->addFlash('success', $this->translator->trans($sMsg));
				} else {
					$this->addFlash('notice', $this->translator->trans('Advert form has errors'));
				}
			} else {
				$this->addFlash('notice', $this->translator->trans('Advert form has errors'));
			}
			$oSession->remove('is_add_advert_page');
		} else {
			$oSession->set('is_add_advert_page', true);
		}

		$aData['form'] = $oForm->createView();
		$aData['ajax_form'] = $oAjaxForm->createView();
		$aData['image'] = $oSession->get('lastAdvertImage', 'images/gazel.jpg');


		$oRegionService = $this->_oRegionsService;;
		if (!$bModeEdit) {
			$aData['nRegionId'] = $oRegionService->getRegionIdFromSession($oRequest);
		} else {
			$aData['nRegionId'] = $oAdvert->getRegion();
			$aData['image'] = $oAdvert->getImage();
			$aData['sActionPath'] = 'cabinet_edit_adv';
			$aData['actionPathAttributes'] = ['nAdvertId' => $oAdvert->getId()];
		}
		$aData['aRegionId'] = [
			'value' => $aData['nRegionId'],
			'attr' => [
				'class' => 'hide'
			]

		];
		if (!$bModeEdit) {
			$aData['nCityId'] = $oRegionService->getCityIdFromSession($oRequest);
		} else {
			$aData['nCityId'] = $oAdvert->getCity();
			$aData['sDisplayLocation'] = $this->_oRegionsService->getDisplayLocationFromAdvertEntity($oAdvert);
			if ($aData['sDisplayLocation']) {
				$aData['nIsSetLocaton'] = 1;
			}
		}

		$aData['aCityId'] = [
			'value' => $aData['nCityId']
		];
		$aData['aCarTypeProps'] = [
			'attr' => [
				'v-on:change' => 'onChangeCarTypeCheckbox'
			]

		];
		$aData['nIsCity'] = (intval($aData['nRegionId']) && !intval($aData['nCityId']));
		return $aData;
	}
	/**
	 * Дополнительная валидация формы подачи объявления. Если пользователь не авторизован,
	 *	проверяется, не существует ли аккаунт с такими данными.
	 * Проверяется, выбран ли хотя бы один  тип транспорта и хотя бы одно расстояние
	 **/
	private function _isAdditionalValid() : bool
	{
		//Валидация Google Captcha
		if (!$this->_oGazelMeService->checkGoogleCaptcha()) {
			$this->_oController->addFlashEx('notice', 'missing-input-response');
			return false;
		}

		$aData = $this->_formData();
		//валидация заполненности хотя бы одного из чекбоксов
		//Тип авто
		$nType = intval($aData['people'] ?? 0) + intval($aData['box'] ?? 0) + intval($aData['term'] ?? 0);
		$this->_oGazelMeService->setForm($this->_oForm);
		if (!$nType) {
			$this->_oGazelMeService->addFormError('Type auto required', 'people');
			return false;
		}
		//Тип дистанции
		$nDistance = intval($aData['far'] ?? 0) + intval($aData['near'] ?? 0) + intval($aData['piknik'] ?? 0);
		if (!$nDistance) {
			$this->_oGazelMeService->addFormError('Distance required', 'far');
			return false;
		}

		//Должен быть выбран регион и он не должен быть is_city = 1
		$nRegion = $aData['region'];
		if (!$nRegion) {
			$this->_oGazelMeService->addFormError('Need select location', 'region');
			return false;
		}

		//валидация логина и пароля, который может быть введён
		$this->_bNeedCreateAccount = false;
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
				$this->_oGazelMeService->addFormError('Email and Password required if on from these no empty', 'email');
				return false;
			}
			if ($oUser) {
				//проверяем, не совпал ли пароль
				$bPasswordValid = $this->_oEncoder->isPasswordValid($oUser, $sPassword);
				//и пользователь его устанавливал раньше
				if (!$bPasswordValid && !$oUser->getIsAnonymous() ) {
					$this->_oGazelMeService->addFormError('User already exists, but password not valid', 'phone');
					return false;
				}
				//Еcли пароль не подходит и пользователь его не установил ранее (подавал не вводя email / password)
				if (!$bPasswordValid) { // & $oUser->getIsAnonymous()
					$this->_oAnonymousUser = $oUser;
					$this->_nExistsUserId = $oUser->getId();
					$this->_bNeedUpdatePassword = true;
				}
				//Если пароль валиден, но не совпадают телефон или email
				if ($bPasswordValid && !$oUser->getIsAnonymous() ) {
					if ($oUser->getUsername() != $this->_oGazelMeService->normalizePhone($sPhone)) {
						$this->_oGazelMeService->addFormError('Email is busy', 'email');
						return false;
					}
					if ($oUser->getEmail() != $sEmail) {
						$this->_oController->addFlashEx('notice', 'You can change email on your profile after authentication');
					}

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
	 * @return array
	 **/
	private function _formData() : array
	{
		return $this->_request()->get('advert_form');
	}
	/**
	 * @return Request
	 **/
	private function _request() : Request
	{
		return $this->_oContainer->get('request_stack')->getCurrentRequest();
	}
	/**
	 * Если пользователь не авторизован создаётся пользователь.
	 * Данные формы также сохраняются.
	**/
	private function _saveAdvertData(\Symfony\Component\Form\FormInterface $oForm, \App\Service\GazelMeService $oGazelMeService, Request $oRequest, bool $bModeEdit)
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
		$this->_oAdvert->setIsModerate(0);
		if (!$bModeEdit) {
			$this->_oAdvert->setIsDeleted(1);
		}
		if (!$nCityId) {
			$nCityId = intval( $this->_oContainer->getParameter(('app.city_zero_id')) );
			$this->_oAdvert->setCity($nCityId);
		}
		//транслитировать заголовок объявления
		$this->_oAdvert->setCodename($oGazelMeService->translite_url($this->_oAdvert->getTitle()));

		//save file
		$oFile = $this->_oForm['imagefile']->getData();
		if ($oFile) {
			$sFileName = $this->_oGazelMeService->getFileUploaderService()->upload($oFile);
			$this->_oAdvert->setImage('/' . $this->_subdir . '/' . $sFileName);
		} else {
			$s = trim($oRequest->get('advert_form')['imgpath']);
			if ($s) {
				$this->_oAdvert->setImage($s);
			}
		}
		$this->_oAdvert->setPhone( $this->_oGazelMeService->normalizePhone( $this->_oAdvert->getPhone() ) );

		/*$o = new \App\Entity\Main;
		$o->set /**/
		$this->_oAdvert->setCreated( $oGazelMeService->now() );
		$this->_oEm->persist($this->_oAdvert);
		$this->_oEm->flush();

		//Тут непонятный баг, region и city не устанавливаются,
		// хотя при попытке getRegion() перед сохранеием корректное значение (пришедшее из формы).
		//Поэтому допиливаем апдейтом, что конечно безобразие
		$nId = $this->_oAdvert->getId();
		$sDQuery = 'UPDATE App:Main AS m '
			. 'SET m.region = :r, m.city = :c, m.delta = :id, m.userId = :uid '
			. $this->_setIsDeletedAndModeratedFragment()
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
		$oUserManager = $this->_oContainer->get('fos_user.user_manager');
		$oUser = $oUserManager->createUser();
		$oUser->setUsername($aData['phone']);
		$oUser->setPhone($aData['phone']);
		$oUser->setDisplayName($aData['company_name']);
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
	public function setController($oController)
	{
		$this->_oController = $oController;
		$this->_subdir = $this->_oContainer->getParameter('app.uploadfiledir') . '/' . date('Y/m');
	}
	/*
	 * @return FormInterface форма для обработки ajax загрузки файла
	*/
	public function getAjaxForm() : ?FormInterface
	{
		if (!$this->_oController) {
			throw new \Exception('Need call AdvertEditorService::setController');
			return null;
		}
		return $this->_oController->createFormEx(get_class(new AjaxFileUploadFormType()), null, [
			'app_service' => $this->_oGazelMeService,
			'uploaddir' => $this->_subdir
		]);
	}
	/**
	 * Обновить пароль пользователя, который ранее уже подавал объявления, но не указал пароль
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
			$oUserManager = $this->_oContainer->get('fos_user.user_manager');
			$oUserManager->updateUser($oUser);
			$this->_nExistsUserId = $oUser->getId();
		}
	}

	public function getDoctrine()
	{
		return $this->_oContainer->get('doctrine');
	}

	public function getUser() : ?Users
	{
		$oUser = $this->_oContainer->get('security.token_storage')->getToken()->getUser();
		if (is_string($oUser)) {
			return null;
		}
		return $oUser;
	}

	public function addFlash(string $sType, string $sMessage)
	{
		if (!$this->_oController) {
			throw new \Exception('Need call AdvertEditorService::setController');
			return null;
		}
		return $this->_oController->addFlashEx($sType, $sMessage);
	}
	private function _patchRequestForEditMode(FormInterface $oForm)
	{
		$aData = $this->_formData();
		$oUser = $this->getUser();
		$aData['phone'] = $oUser->getPhone();
		//$oForm->setData($aData);
		$oForm->submit($aData);
	}
	/**
	 * Устанавливает фрагмент SQL запроса сохранения данных модерации
	 * Если пользователь уже подтвердил свой номер телефона по sms isDeleted устанавливается в 0
	 * @return string
	*/
	private function _setIsDeletedAndModeratedFragment() : string
	{
		$oUser = $this->getUser();
		if ($oUser) {
			if ($oUser->getIsSmsVerify()) {
				return ', m.isDeleted = 0';
			}
		}
		return '';
	}
}
