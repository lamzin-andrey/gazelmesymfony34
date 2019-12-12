<?php
namespace App\Service;

use App\Form\AjaxFileUploadFormType;
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

	/** @property FormInterface $_oForm Сюда можно передать форму для более простой работы с ними */
	private $_oForm;

	public function __construct(ContainerInterface $container, ViewDataService $oViewDataService, FileUploaderService $oFileUploaderService, GazelMeService $oGazelMeService)
	{
		$this->oContainer = $container;
		$this->translator = $container->get('translator');
		$this->oViewDataService = $oViewDataService;
		$this->oFileUploaderService = $oFileUploaderService;
		$this->_oGazelMeService = $oGazelMeService;
	}

	public function pageAdvertForm(Request $oRequest, \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $oEncoder, App\Entity\Main $oAdvert)
	{
		if (!$this->_oController) {
			throw new \Exception('Need call AdvertEditorService::setController');
			return;
		}
		$this->_oEncoder =  $oEncoder;
		$this->_oAdvert = $oAdvert;
		$this->_oForm = $oForm = $this->createForm(get_class(new AdvertForm()), $this->_oAdvert, [
			'app_service' => $this->_oGazelMeService,
			'uploaddir' => $this->_subdir
		]);
		$oAjaxForm = $this->getAjaxForm();//TODO stop here
		$oSession = $oRequest->getSession();

		/** @var \Symfony\Component\HttpFoundation\Session\Session $oSession **/
		$aData = $oViewDataService->getDefaultTemplateData($oRequest);
		if ($oRequest->getMethod() == 'POST') {
			$oForm->handleRequest($oRequest);
			if ($oForm->isValid()) {
				if ($this->_isAdditionalValid()) {
					$this->_saveAdvertData($oForm, $oGazelMeService, $oRequest);
					$this->addFlash('success', $this->get('translator')->trans('You need to confirm your phone number. You will now be redirected to the confirmation page'));
					$oRequest->getSession()->set('activePhone', $this->_oAdvert->getPhone());
					$oRequest->getSession()->set('verified_adv_id', $this->_oAdvert->getId());
					$aData['redirectToConfirmPhone'] = '1';
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

		$aData['form'] = $oForm->createView();
		$aData['ajax_form'] = $oAjaxForm->createView();
		$aData['image'] = 'images/gazel.jpg';

		$aData['nRegionId'] = $oRegionService->getRegionIdFromSession($oRequest);
		$aData['aRegionId'] = [
			'value' => $aData['nRegionId'],
			'attr' => [
				'class' => 'hide'
			]

		];
		$aData['nCityId'] = $oRegionService->getCityIdFromSession($oRequest);
		$aData['aCityId'] = [
			'value' => $aData['nCityId']
		];
		$aData['nIsCity'] = (intval($aData['nRegionId']) && !intval($aData['nCityId']));
		//$aData['nIsSetLocation'] = $oGazelMeService
	}

	public function setController($oController)
	{
		$this->_oController = $oController;
		$this->_subdir = $this->_oController->getParameter('app.uploadfiledir') . '/' . date('Y/m');
	}
	/*
	 * @return FormInterface форма для обработки ajax загрузки файла
	*/
	public function getAjaxForm($oAdvertEditorService) : FormInterface
	{
		if (!$this->_oController) {
			throw new \Exception('Need call AdvertEditorService::setController');
			return;
		}
		return $this->_oController->createForm(get_class(new AjaxFileUploadFormType()), null, [
			'app_service' => $this->_oGazelMeService,
			'uploaddir' => $this->_subdir
		]);
	}
}
