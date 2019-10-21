<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use \App\Entity\Main;
use App\Service\GazelMeService;

class DefaultController extends Controller
{
	/**
	  *  TODO тут скорее всего /{slug}/{slug}/{slug} - для oblast/moskva/perevezu
	  * Короче, надо будет думать. потому что иногда два slug
      * @Route("/")
    */
    public function index(Request $request)
    {
		//adverts data
		$adverts = $this->_loadAdvList();

		//for links
		$s = $request->server->get('REQUEST_URI');
		$a = explode('?', $s);
		$currentTail =  ($a[1] ?? '');

		$siteName = $limit = $this->getParameter('app.site_name', 10);

        return $this->render('list/mainlist.html.twig', [
			'title' => 'Go away!',
			'assetsVersion' => 0,
			'additionalCss' => '',
			'additionalJs' => '',
			'csrf' => '',
			'uid' => '0',
			'regionId' => '',
			'cityId' => '',
			'h1' => 'RUn RUN RUN',
			'politicDoc' => '/images/Politika_zashity_i_obrabotki_personalnyh_dannyh_2019-08-14.doc',
			'isLocalhost' => true,
			'isAgreementPage' => 0,
			'list' => $adverts,
			'nCountAdverts' => count($adverts),
			'currentTail' => $currentTail,
			'siteName' => $siteName,
			/*'' => '',
			'' => '',*/
		]);
	}

	/**
	 * @Route("/phones/{nId}") 
	*/
	public function phones(int $nId, GazelMeService $oGazelMeService)
	{
		$response = new Response($sData);
		$response->headers->set("Content-Type", "image/png");//jpg?
		$sData = $oGazelMeService->getPhoneAsImage($nId);
		return $response;
	}
	/**
	 * @return array
	*/
	private function _loadAdvList() : array
	{
		$limit = $this->getParameter('app.records_per_page', 10);
		
		$repository = $this->getDoctrine()->getRepository('App:Main');
		$aCollection = $repository->findBy([
			'isDeleted' => 0,
			'isHide' => 0,
			'isModerate' => 1
		], [
			'delta' => 'DESC',
		], $limit, 0);
		/*var_dump($aCollection[0]->getRegionObject()->getRegionName());*/
		/*var_dump($aCollection[0]);
		die;/**/
		return $aCollection;
	}
}
