<?php

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
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use ReCaptcha\ReCaptcha;

class ResettingController extends AbstractController
{

    public function __construct(BaseResettingController $oBaseController, EventDispatcherInterface $eventDispatcher, FactoryInterface $formFactory, UserManagerInterface $userManager, TokenGeneratorInterface $tokenGenerator, MailerInterface $mailer, $retryTtl, ContainerInterface $oContainer)
    {
		$this->_oBaseController = $oBaseController;
		$this->_eventDispatcher = $eventDispatcher;
		$this->_formFactory = $formFactory;
		$this->_userManager = $userManager;
		$this->_tokenGenerator = $tokenGenerator;
		$this->_mailer = $mailer;
		$this->_retryTtl = $retryTtl;
		$this->_oContainer = $oContainer;
    }


    public function requestAction()
    {
        return $this->_oBaseController->requestAction();
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
		$bCaptchaIsOn = $this->_oContainer->getParameter('app.google_recaptcha_on');
		$bCaptchaIsOn = $bCaptchaIsOn == 'false' ? false : $bCaptchaIsOn;
		if (!$bCaptchaIsOn) {
			/** @var \Symfony\Component\HttpFoundation\RedirectResponse $oResult */
			$oResult = $this->_oBaseController->sendEmailAction($oRequest);
			return $oResult;
		}
		$sGRecaptchaResponse = $oRequest->get('g-recaptcha-response');
		$sPhone = $oRequest->get('username');
		$secret = $this->_oContainer->getParameter('app.google_recaptcha_secret_key');
		$sRemoteIp = $oRequest->server->get('REMOTE_ADDR');
		$sDomain = $this->_oContainer->getParameter('app.domain');

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
			return $this->_resettingController->sendEmailAction($oRequest);
		}
		$aErrors = $oResponse->getErrorCodes();
		$oTranslator = $this->_oContainer->get('translator');
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
		/** @var \Symfony\Component\HttpFoundation\Response $oResult */
		//$oResult = $this->resettingController->checkEmailAction($request);
		$username = $request->query->get('username');

		if (empty($username)) {
			// the user does not come from the sendEmail action
			return new RedirectResponse($this->generateUrl('fos_user_resetting_request'));
		}

		$oRepository = $this->getDoctrine()->getRepository('App:Users');
		$aUsers = $oRepository->findBy(['username' => $username]);
		$oUser = $aUsers[0] ?? null;
		if (!$oUser) {
			return new RedirectResponse($this->generateUrl('fos_user_resetting_request'));
		}
		$aEmail = explode('@', $oUser->getEmail());
		$sEmail = $aEmail[0][0] . '******' . '@'  . $aEmail[1];
		return $this->render('@FOSUser/Resetting/check_email.html.twig', [
			'tokenLifetime' => ceil($this->_retryTtl / 3600),
			'email' => $sEmail
		]);
	}


    public function resetAction(Request $request, $token)
    {
        return $this->_oBaseController->resetAction($request, $token);
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
		$sMessage = $this->_oContainer->get('translator')->trans($sMessage);
		$this->addFlash('notice', $sMessage);
		return $this->redirectToRoute($sFailRoute);
	}
}
