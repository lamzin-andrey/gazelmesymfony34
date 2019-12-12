<?php

namespace App\Controller;


use App\Service\GazelMeService;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Query\ResultSetMapping;

use Symfony\Bundle\SwiftmailerBundle;

class CabinetController extends Controller
{
	//TODO access_control in security!
    /**
     * @Route("/cabinet", name="cabinet")
    */
    public function index(GazelMeService $oGazelMeService)
    {
    	$oUser = $this->getUser();
		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData();
		$aData['list'] = [];
    	if ($oUser) {
    		$oUserRepository = $this->getDoctrine()->getRepository('App:Main');
    		$oCriteria = Criteria::create();
    		$oExpr = Criteria::expr();
    		$oCriteria->where( $oExpr->andX($oExpr->eq('userId', $oUser->getId()),  $oExpr->eq('isDeleted', 0)) );
			$aData['list'] = $oUserRepository->matching($oCriteria)->toArray();
		}
		$aData['nCountAdverts'] = count($aData['list']);
		return $this->render('cabinet/list.html.twig', $aData);
    }
	/**
     * @Route("/cabinet_setting", name="cabinet_setting")
    */
    public function setting()
    {

    }
}
