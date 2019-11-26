<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use \App\Entity\Main;
use \App\Entity\Cities;
use \App\Entity\Regions;

use App\Service\GazelMeService;
use App\Service\RegionsService;
use App\Service\ViewDataService;

class DefaultController extends Controller
{
	/**
	 * Показать форму установки параметров объявления
	 * @Route("/showfilter", name="showfilter") 
	*/
	public function showfilter(Request $oRequest)
	{
		$oSession = $oRequest->getSession();
		
		$oGazelMeService = $this->get('App\Service\GazelMeService');
		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData($oRequest);
		
		$sTitle = $this->get('translator')->trans('Set filter page');
		$aData['title'] = $sTitle;
		$aData['nIspage100Percents'] = 1;
		$aData['h1'] = $sTitle;
		$aData['people'] = ( intval($oSession->get('people', 0)) ? 'checked="checked"' : '');
		$aData['box'] = ( intval($oSession->get('box', 0)) ? 'checked="checked"' : '');
		$aData['term'] = ( intval($oSession->get('term', 0)) ? 'checked="checked"' : '');
		$aData['far'] = ( intval($oSession->get('far', 0)) ? 'checked="checked"' : '');
		$aData['near'] = ( intval($oSession->get('near', 0)) ? 'checked="checked"' : '');
		$aData['piknik'] = ( intval($oSession->get('piknik', 0)) ? 'checked="checked"' : '');
		return $this->render('list/filterform.html.twig', $aData);
	}
	/**
	 * @Route("/phones/{nId}") 
	*/
	public function phones(int $nId, GazelMeService $oGazelMeService)
	{
		$response = new Response();
		$response->headers->set('Content-Type', 'image/png');
		$sData = $oGazelMeService->getPhoneAsImage($nId);
		return $response;
	}
	/**
	 * @Route("/agreement", name="agreement") 
	*/
	public function agreement()
	{
		//TODO template is temp
		//return $this->render('list/filterform.html.twig', $aData);
		return $this->redirectToRoute("home");
	}
	
}
