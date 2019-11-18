<?php

namespace App\Controller;

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

/**
 * Controller managing the user profile.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class ProfileController extends AbstractController
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
    public function showAction()
    {
        return $this->_oBaseController->showAction();
    }

    /**
     * Edit the user.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function editAction(Request $request)
    {
		if ($request->getMethod() == 'POST') {
			$oUser = $this->getUser();
			$aInput = $request->get('fos_user_profile_form');
			$sRawPassword = $aInput['current_password'];
			$sNewPassword = ($aInput['plainPassword']['first'] ?? '');
			if (trim($sNewPassword)) {
				/** @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoder $encoder */
				$encoder = $this->_oContainer->get('security.password_encoder');
				$bCurrentPasswordIsValid = $encoder->isPasswordValid($oUser, $sRawPassword);
				if (!$bCurrentPasswordIsValid) {
					$this->addFlash('notice', $this->_oContainer->get('translator')->trans('Current password is invalid'));
					return $this->redirectToRoute('fos_user_profile_edit');
				}
			}
		}
        return $this->_oBaseController->editAction($request);
    }
}
