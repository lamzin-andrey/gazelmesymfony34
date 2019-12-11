<?php

namespace App\Controller;

use App\Service\GazelMeService;
use App\Service\ViewDataService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Smspilot;

class SmsController extends Controller
{
    /**
     * @Route("/smsverify", name="sms")
     */
    public function index(Request $oRequest,  ViewDataService $oViewDataService, GazelMeService $oGazelMeService)
    {
    	$aData = $oViewDataService->getDefaultTemplateData($oRequest);
    	$aData['infoMessage'] = $this->get('translator')->trans('Confirm that the number %phone% is really yours. To do this, click on the "Get SMS" button', [
    		'%phone%' => $oGazelMeService->formatPhone($oRequest->getSession()->get('activePhone') )
		]);
        return $this->render('sms/getsmsbutton.html.twig', $aData);
    }
	/**
	 * @Route("/smsverify/getsms", name="smsverify_getsms")
	*/
	public function getsms(Request $oRequest,  ViewDataService $oViewDataService, GazelMeService $oGazelMeService)
	{
		$sApiKey = $this->getParameter('app.smspilotkey');
		$sPhoneNumber = $oRequest->getSession()->get('activePhone');
		$sPhoneNumber = preg_replace("#^8#", '7', $sPhoneNumber);
		$oSmsPilot = new Smspilot($sApiKey);
		$oRequest->getSession()->set('smscode', rand(1000, 9999));
		//$oSmsPilot->send($sPhoneNumber, $this->_getSmsText());
		return $this->render('sms/sendcode.html.twig', $aData);
	}

	private function _getSmsText()
	{
		return '';
	}
}
