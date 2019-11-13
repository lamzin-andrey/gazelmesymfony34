<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\UserBundle\Controller\ResettingController as BaseResettingController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use ReCaptcha\ReCaptcha;
use \Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Controller managing the resetting of the password.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class ResettingController extends AbstractController
{
	/**
     * @var BaseResettingController
    */
    private $resettingController;
	
    private $eventDispatcher;
    private $formFactory;
    private $userManager;
    private $tokenGenerator;
    private $mailer;
	private $oContainer;

    /**
     * @var int
     */
    private $retryTtl;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param FactoryInterface         $formFactory
     * @param UserManagerInterface     $userManager
     * @param TokenGeneratorInterface  $tokenGenerator
     * @param MailerInterface          $mailer
     * @param int                      $retryTtl
     * @param ContainerInterface		   $container
     */
    public function __construct(BaseResettingController $baseController, EventDispatcherInterface $eventDispatcher, FactoryInterface $formFactory, UserManagerInterface $userManager, TokenGeneratorInterface $tokenGenerator, MailerInterface $mailer, $retryTtl, ContainerInterface $container)
    {
		$this->resettingController = $baseController;
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer = $mailer;
        $this->retryTtl = $retryTtl;
		$this->oContainer = $container;
    }

    /**
     * Request reset user password: show form.
     */
    public function requestAction()
    {
        //return $this->render('@FOSUser/Resetting/request.html.twig');
		return $this->resettingController->requestAction();
    }

    /**
     * Request reset user password: submit form and send email.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function sendEmailAction(Request $oRequest)
    {	
		$bCaptchaIsOn = $this->oContainer->getParameter('app.google_recaptcha_on');
		$bCaptchaIsOn = $bCaptchaIsOn == 'false' ? false : $bCaptchaIsOn;
		if (!$bCaptchaIsOn) {
			return $this->resettingController->sendEmailAction($oRequest);
		}
		$sGRecaptchaResponse = $oRequest->get('g-recaptcha-response');
		$sPhone = $oRequest->get('username');
		$secret = $this->oContainer->getParameter('app.google_recaptcha_secret_key');
		$sRemoteIp = $oRequest->server->get('REMOTE_ADDR');
		$sDomain = $this->oContainer->getParameter('app.domain');
		
		//check user by phone
		$aUsers = $this->getDoctrine()->getRepository('App:Users')->findBy(['username' => $sPhone]);
		$oUser = $aUsers[0] ?? null;
		if (!$oUser) {
			return $this->_redirectToFailRoute('User with phone not found');
		}
		if (!$sGRecaptchaResponse) {
			return $this->_redirectToFailRoute('missing-input-response');
		}
		
		$oRecaptcha = new ReCaptcha($secret);
		$oResponse = $oRecaptcha->setExpectedHostname($sDomain)
						  ->verify($sGRecaptchaResponse, $sRemoteIp);
		if ($oResponse->isSuccess()) {
			// Verified!
			return $this->resettingController->sendEmailAction($oRequest);
		}
		$aErrors = $oResponse->getErrorCodes();
		$oTranslator = $this->oContainer->get('translator');
		$aErrors = array_map(function($sMessage, $oTranslator){
			return $oTranslator->trans($sMessage);
		}, $aErrors, [$oTranslator]);
		return $this->_redirectToFailRoute( '<p>' . join('</p></p>', $aErrors) . '</p>');
    }

    /**
     * Tell the user to check his email provider.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function checkEmailAction(Request $request)
    {
        return $this->resettingController->checkEmailAction($request);
    }

    /**
     * Reset user password.
     *
     * @param Request $request
     * @param string  $token
     *
     * @return Response
     */
    public function resetAction(Request $request, $token)
    {
        return $this->resettingController->checkEmailAction($request);
    }
	/**
     * Redirect in to resetting_request and set flash with error text
     *
     * @param string  $sMessage - with error text
     *
     * @return Redirect
     */
	private function _redirectToFailRoute(string $sMessage)
	{
		$sFailRoute = 'fos_user_resetting_request';
		$sMessage = $this->oContainer->get('translator')->trans($sMessage);
		$this->addFlash('notice', $sMessage);
		return $this->redirectToRoute($sFailRoute);
	}
}