<?php
namespace App\Handler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AuthenticationHandler
 * @package App\Handler
 */
class LogoutHandler implements \Symfony\Component\Security\Http\Logout\LogoutHandlerInterface
{
	/**
	 * @var Session
     */
    private $session;


    /**
     * AuthenticationHandler constructor.
     * @param RouterInterface $router
     * @param Session $session
     */
    public function __construct(ContainerInterface $oContainer)
    {
		$this->_oContainer = $oContainer;
        $this->session = $oContainer->get('request_stack')->getCurrentRequest()->getSession();
    }

    /**
     * This method is called by the LogoutListener when a user has requested
     * to be logged out. Usually, you would unset session variables, or remove
     * cookies, etc.
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
		//die('Делай что хочешь!');
    }
   
}