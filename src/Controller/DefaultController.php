<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
	/**
	  *  TODO тут скорее всего /{slug}/{slug}/{slug} - для oblast/moskva/perevezu
	  * Короче, надо будет думать. потому что иногда два slug
      * @Route("/")
    */
    public function indexAction()
    {
        return $this->render('base.html.twig', [
			'title' => 'Go away!',
			'assetsVersion' => 0,
			'additionalCss' => '',
			'additionalJs' => '',
			'csrf' => '',
			'uid' => '0',
			'regionId' => '',
			'cityId' => '',
			'h1' => 'RUn RUN RUN',
			'politicDoc' => '/d/poli.doc',
			'isLocalhost' => true,
			'isAgreementPage' => false,
			/*'' => '',
			'' => '',
			'' => '',*/

		]);
    }
}
