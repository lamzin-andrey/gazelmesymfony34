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
	 * @Route("/cabinet/delete/{nAdvertId}/", name="cabinet_delete")
	*/
	public function delete(int $nAdvertId, GazelMeService $oGazelMeService, TranslatorInterface $t)
	{
		$oUser = $this->getUser();

		if ($oUser->hasRole('ROLE_SUPER_ADMIN')) {
			$oGazelMeService->setAdvertAsDeleted($nAdvertId, 0, true, true);
			$sReferer = $oGazelMeService->getReferer('/private/newadv');
			return $this->redirect($sReferer);
		}

		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData();
		/** @var \App\Entity\Users $oUser */
		$oAdvert = $oGazelMeService->setAdvertAsDeleted($nAdvertId, $oUser->getId(), true);
		$this->addFlash('success', $t->trans('Your ad will deleted after one second from your cabinet, after one hour from site pages.'));
		return $this->redirectToRoute('cabinet');
	}

	/**
	 * @Route("/cabinet/hide/{nAdvertId}/", name="cabinet_hide")
	*/
	public function hide(int $nAdvertId, GazelMeService $oGazelMeService, TranslatorInterface $t)
	{
		$oUser = $this->getUser();
		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData();
		/** @var \App\Entity\Users $oUser */
		$oAdvert = $oGazelMeService->setAdvertShown($nAdvertId, $oUser->getId(), false, true);
		$this->addFlash('success', $t->trans('Your ad is hide'));
		return $this->redirectToRoute('cabinet');
	}

	/**
	 * @Route("/cabinet/show/{nAdvertId}/", name="cabinet_show")
	*/
	public function show(int $nAdvertId, GazelMeService $oGazelMeService, TranslatorInterface $t)
	{
		$oUser = $this->getUser();
		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData();
		/** @var \App\Entity\Users $oUser */
		$oAdvert = $oGazelMeService->setAdvertShown($nAdvertId, $oUser->getId(), true, true);
		$this->addFlash('success', $t->trans('Your ad is show'));
		return $this->redirectToRoute('cabinet');
	}

	/**
	 * @Route("/cabinet/up/{nAdvertId}/", name="cabinet_up")
    */
	public function up(int $nAdvertId, GazelMeService $oGazelMeService, TranslatorInterface $t)
	{
		$oUser = $this->getUser();

		if ($oUser->hasRole('ROLE_SUPER_ADMIN')) {
			$oGazelMeService->upAdvert($nAdvertId, 0, true, true);
			$sReferer = $oGazelMeService->getReferer('/private/newadv');
			return $this->redirect($sReferer);
		}

		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData();
		/** @var \App\Entity\Users $oUser */
		$nUpcount = $oUser->getUpcount();
		if ($nUpcount > 0) {
			$nUpcount--;
			$nUpcount = $nUpcount < 0 ? 0 : $nUpcount;
			$oUser->setUpcount($nUpcount);

			$oAdvert = $oGazelMeService->upAdvert($nAdvertId, $oUser->getId(), false);

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
			$aData['list'] = $oQueryBuilder->getQuery()->enableResultCache(1)->getResult();
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
		$t = $this->get('translator');
		if ($oAdvert && $this->getUser()->getId() != $oAdvert->getUserId()) {
			$this->addFlash('notice', $t->trans('You have not access to thid advert'));
			return $this->redirectToRoute('home');
		}
		$oAdvertEditorService->setController($this);
		$aData = $oAdvertEditorService->pageAdvertForm($oRequest, $oEncoder, $this->_oAdvert, true);
		$aData['title'] = $t->trans('Editing an advert');
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
		$sMessage = $this->get('translator')->trans($sMessage);
		return $this->addFlash($sType, $sMessage);
	}

	/**
	 * Добавить запись в pay_transaction и вернуть идентификатор записи
	 * @Route("/startpaytransaction.json", name="startpaytransaction")
	 */
	public function startpaytransaction(Request $oRequest, PayService $oPayService, TranslatorInterface $t, GazelMeService $oGazelMeService)
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
		} else {
			$sRawMethod = $oRequest->get('method', '');
			$sPhone = $oRequest->get('phone', '');
			if ($sRawMethod == 'MC') {
				$oMessage = new \Swift_Message();
				$oMessage->setSubject('Новое сообщение в пуще');
				$oMessage->setBody('Абонент ' . $oGazelMeService->formatPhone($sPhone) . ' просит перезвонить ему.', 'text/html', 'UTF-8');
				$oMessage->setFrom( $this->getParameter('app.site_sender_email'));
				$oMessage->setTo( $this->getParameter('app.site_recipient_email'));
				$this->get('mailer')->send($oMessage);
			}
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
