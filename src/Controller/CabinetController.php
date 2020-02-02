<?php

namespace App\Controller;


use App\Entity\Main as Advert;
use App\Service\AdvertEditorService;
use App\Service\GazelMeService;
use App\Service\PayService;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Query\ResultSetMapping;

use Symfony\Bundle\SwiftmailerBundle;
use Symfony\Component\Translation\TranslatorInterface;

class CabinetController extends Controller implements IAdvertController
{

	/**
	 * @Route("/cabinet/up/{nAdvertId}/", name="cabinet_up")
 */
	public function up(int $nAdvertId, GazelMeService $oGazelMeService, TranslatorInterface $t)
	{
		$oUser = $this->getUser();
		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData();
		/** @var \App\Entity\Users $oUser */
		$nUpcount = $oUser->getUpcount();
		if ($nUpcount > 0) {
			$nUpcount--;
			$nUpcount = $nUpcount < 0 ? 0 : $nUpcount;
			$oUser->setUpcount($nUpcount);

			$oRepository = $this->getDoctrine()->getRepository('App:Main');
			/** @var \App\Entity\Main $oAdvert */
			$oAdvert = $oRepository->find($nAdvertId);
			if ($oAdvert->getUserId() == $oUser->getId()) {
				$oQueryBuilder = $oRepository->createQueryBuilder('m');
				$aResult = $oQueryBuilder->select('max(m.delta) ')->getQuery()->getSingleResult();
				$n = intval( $aResult[1] ?? 0 );
				if ($n) {
					$oAdvert->setDelta($n + 1);
				} 

			}

			$oGazelMeService->save($oUser, $oAdvert);
			if ($nUpcount > 0) {
				$this->addFlash('success', $t->trans('Your ad has been raised in search results. You can up your ad yet %n% times', ['%n%' => $nUpcount]));
			} else {
				$this->addFlash('success', $t->trans('You can pay the opportunity to raise ads'));
			}
			return $this->redirectToRoute('cabinet');
		}
		/*$aData['nCountAdverts'] = count($aData['list']);*/
		return $this->render('cabinet/up.html.twig', $aData);
	}


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
    		$oQueryBuilder = $oUserRepository->createQueryBuilder('m');
			$oExpr = $oQueryBuilder->expr();
			$oQueryBuilder->where( $oExpr->andX($oExpr->eq('m.userId', $oUser->getId()),  $oExpr->eq('m.isDeleted', 0)) );
			$oQueryBuilder->leftJoin('App:Cities', 'c', 'WITH', 'm.city = c.id');
			$oQueryBuilder->leftJoin('App:Regions', 'r', 'WITH', 'm.region = r.id');
			$oQueryBuilder->select('m.title, m.image, m.addtext, m.id, c.codename AS ccodename, r.codename AS rcodename, m.city, m.price, c.cityName, r.regionName, m.box, m.term, m.people, m.far, m.near, m.piknik, m.isHide, m.isModerate');
			$aData['list'] = $oQueryBuilder->getQuery()->enableResultCache($oGazelMeService->ttl() )->getResult();
    		/*$oCriteria = Criteria::create();
    		$oExpr = Criteria::expr();
    		$oCriteria->where( $oExpr->andX($oExpr->eq('userId', $oUser->getId()),  $oExpr->eq('isDeleted', 0)) );
			$aData['list'] = $oUserRepository->matching($oCriteria)->toArray();*/
		}
		$aData['nCountAdverts'] = count($aData['list']);
		return $this->render('cabinet/list.html.twig', $aData);
		//return $this->render('empty.html.twig', $aData);
    }
	/**
	 * @Route("/cabinet/edit/{nAdvertId}", name="cabinet_edit_adv")
	 */
	public function editAdvert(int $nAdvertId, Request $oRequest, \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $oEncoder, GazelMeService $oGazelMeService, AdvertEditorService $oAdvertEditorService)
	{
		$oAdvertRepository = $this->getDoctrine()->getRepository('App:Main');
		$oAdvert = $this->_oAdvert = $oAdvertRepository->find($nAdvertId);
		if ($oAdvert && $this->getUser()->getId() != $oAdvert->getUserId()) {
			$t = $this->get('translator');
			$this->addFlash('notice', $t->trans('You have not access to thid advert'));
			return $this->redirectToRoute('home');
		}
		$oAdvertEditorService->setController($this);
		$aData = $oAdvertEditorService->pageAdvertForm($oRequest, $oEncoder, $this->_oAdvert, true);
		return $this->render('advert/form.html.twig', $aData);
	}
	/**
     * @Route("/cabinet_setting", name="cabinet_setting")
    */
    public function setting()
    {

    }
    public function createFormEx(string $sFormTypeClass, $oEntity, array $aOptions)
	{
		return $this->createForm($sFormTypeClass, $oEntity, $aOptions);
	}
	public function addFlashEx(string $sType, string $sMessage)
	{
		return $this->addFlash($sType, $sMessage);
	}

	/**
	 * Добавить запись в pay_transaction и вернуть идентификатор записи
	 * @Route("/startpaytransaction.json", name="startpaytransaction")
	 */
	public function startpaytransaction(Request $oRequest, PayService $oPayService, TranslatorInterface $t)
	{
		$oEm = $this->getDoctrine()->getManager();

		if (!$this->getUser()) {
			return $this->_json([
				'status' => 'error',
				'msg' => $t->trans('Unauth user')
			]);
		}
		$oPayService->setPayTransactionEntityClassName('App\Entity\PayTransaction');
		$oPayService->setOperationEntityClassName('App\Entity\Operations');
		$oTransactionData = $oPayService->createTransaction($this->getUser()->getid(), 0);
		$nTransactionId = $oTransactionData->nPayTransactionId;
		$sPayUrl = $oTransactionData->sPayUrl;

		$aData = [
			'id' => $nTransactionId,
			'ops' => $oTransactionData->nBillId,
			'yn' => $this->getParameter('app.yacache'),
			'url' => $sPayUrl
		];

		if ($oTransactionData->sError) {
			$aData['status'] = 'error';
			$aData['msg'] = $oTransactionData->sError;
		}
		return $this->_json($aData);
	}

	private function _json(array $aData) : Response
	{
		if (!isset($aData['status'])) {
			$aData['status'] = 'ok';
		}
		$oResponse = new Response( json_encode($aData) );
		$oResponse->headers->set('Content-Type', 'application/json');
		return $oResponse;
	}
}
