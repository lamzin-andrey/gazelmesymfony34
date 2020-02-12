<?php

namespace App\Controller;

use App\Form\ProfileFormType;
use App\Service\GazelMeService;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use FOS\UserBundle\Controller\ProfileController as BaseProfileController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Container\ContainerInterface AS PsrContainerInterface;
use App\Controller\IAdvertController;

/**
 * Controller managing the user profile.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class ProfileController extends AbstractController implements IAdvertController
{
    private $eventDispatcher;
    private $formFactory;
    private $userManager;
	private $_oBaseController;
	private $_oContainer;

    public function __construct(BaseProfileController $oBaseController, EventDispatcherInterface $eventDispatcher, FactoryInterface $formFactory, UserManagerInterface $userManager, ContainerInterface $oContainer)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
		$this->_oBaseController = $oBaseController;
		$this->_oContainer = $oContainer;
    }

    /**
     * Show the user.
     */
    public function showAction(Request $oRequest)
    {
		return $this->redirectToRoute('fos_user_profile_edit');
        //return $this->_oBaseController->showAction();
    }

    /**
     * Edit the user.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function editAction(Request $oRequest, GazelMeService $oGazelMeService)
    {
		if ($oRequest->getMethod() == 'POST') {
			$aInput = $oRequest->get('fos_user_profile_form');
			$sRawPassword = $aInput['current_password'];
			$sNewPassword = ($aInput['plainPassword']['first'] ?? '');
			if (trim($sNewPassword)) {
				$oUser = $this->getUser();
				$t = $this->_oContainer->get('translator');
				/** @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoder $encoder */
				$encoder = $this->_oContainer->get('security.password_encoder');
				//Это проверка, совпадает ли текущий пароль с настоящим
				$bCurrentPasswordIsValid = $encoder->isPasswordValid($oUser, $sRawPassword);

				//Validate Password
				//Это проверка, удовлетворяет ли новый пароль правилам валидации.
				$sPasswordValidationError = $oGazelMeService->checkValidPassword($sNewPassword, $this);
				if (!$bCurrentPasswordIsValid || $sPasswordValidationError) {
					if ($sPasswordValidationError) {
						$this->addFlash('notice', $sPasswordValidationError);
					} else {
						$this->addFlash('notice', $t->trans('Current password is invalid'));
					}
					return $this->redirectToRoute('fos_user_profile_edit');
				}
				$aUserData = [];
				$aUserData['email'] = $safeEmail = $oUser->getEmail();
				$aUserData['display_name'] = $safeDisplayName = $oUser->getDisplayName();
				$aUserData['password_hash'] = $oUser->getPassword();
				$oRequest->getSession()->set('safeUserData', $aUserData);
			}
		} else {
			$aUserData = $oRequest->getSession()->get('safeUserData');
			$oRequest->getSession()->remove('safeUserData');
			$oUser = $this->getUser();
			$safePassword = ($aUserData['password_hash'] ?? '');
			$safeDisplayName = ($aUserData['display_name'] ?? '');
			$safeEmail = ($aUserData['email'] ?? '');
			/** @var \Symfony\Component\Translation\DataCollectorTranslator $t */
			$t = $this->_oContainer->get('translator');

			$sPassword = $oUser->getPassword();
			if (trim($safePassword) && $safePassword != $sPassword) {

				$this->addFlash('info', $t->trans('change_password.flash.success', [], 'FOSUserBundle'));
			}

			if (trim($safeDisplayName) && $safeDisplayName != $oUser->getDisplayName()) {
				$this->addFlash('info', $t->trans('Display name was update', [], null));
			}
			if (trim($safeEmail) && $safeEmail != $oUser->getEmail()) {
				$this->addFlash('info', $t->trans('Email was updated', [], null));
			}
		}
        return $this->_oBaseController->editAction($oRequest);
		/*$request = $oRequest;
		$user = $this->getUser();
		if (!is_object($user) || !$user instanceof UserInterface) {
			throw new AccessDeniedException('This user does not have access to this section.');
		}

		$event = new GetResponseUserEvent($user, $request);
		$this->eventDispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_INITIALIZE, $event);

		if (null !== $event->getResponse()) {
			return $event->getResponse();
		}

		$form = $this->formFactory->createForm();
		$form->setData($user);

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$event = new FormEvent($form, $request);
			$this->eventDispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_SUCCESS, $event);

			$this->userManager->updateUser($user);

			if (null === $response = $event->getResponse()) {
				$url = $this->generateUrl('fos_user_profile_show');
				$response = new RedirectResponse($url);
			}

			$this->eventDispatcher->dispatch(FOSUserEvents::PROFILE_EDIT_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

			return $response;
		}

		return $this->render('@FOSUser/Profile/edit.html.twig', array(
			'form' => $form->createView(),
		));*/
    }
	/**
	 * Проверяет, авторизован ли пользователь
	**/
	public function getauthstate()
	{
		$aData = ['uid' => 0];
		$oUser = $this->get("security.token_storage")->getToken()->getUser();
		if (!is_string($oUser)) {
			$aData['uid'] = $oUser->getId();
		}
		$oResponse = new Response( json_encode($aData) );
		$oResponse->headers->set('Content-Type', 'application/json');
		return $oResponse;
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
