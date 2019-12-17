<?php

namespace App\Controller;

use App\Entity\PayTransaction;
use App\Form\PaymethodFormType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\SetpaysumFormType;
use App\Service\GazelMeService;
use Symfony\Component\HttpFoundation\Request;

class PaymentController extends Controller
{
    /**
     * @Route("/payment", name="payment")
     */
    public function index(Request $oRequest, GazelMeService $oGazelMeService)
    {
		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData();
		$oForm = $this->createForm(get_class(new SetpaysumFormType()), null);
		$aData['setpaysumForm'] = $oForm->createView();
		return $this->render('payment/form.html.twig', $aData);
    }

	/**
	 *  No js обработка выбора суммы платежа
	 * @Route("/payment/setsum", name="setpaysum")
	 */
	public function setpaysum(Request $oRequest, GazelMeService $oGazelMeService)
	{
		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData();
		$oForm = $this->createForm(get_class(new SetpaysumFormType()), null);
		$aData['sum'] = 0;
		if ($oRequest->getMethod() == 'POST') {
			$oForm->handleRequest($oRequest);
			if ($oForm->isValid()) {
				$aData['sum'] = $oRequest->get('s60') ? 60 : $aData['sum'];
				$aData['sum'] = $oRequest->get('s200') ? 200 : $aData['sum'];
				$aData['sum'] = $oRequest->get('s700') ? 700 : $aData['sum'];
			}
			if (!$aData['sum']) {
				$oRequest->getSession()->remove('nojsQUp');
				$oRequest->getSession()->remove('nojsPaysum');
				$this->addFlash('notice', 'Unable get sum of payment, Try set cookie On and resend form again.');
				return $this->redirectToRoute('payment');
			} else {
				$oRequest->getSession()->set('nojsPaysum', $aData['sum']);
				switch ($aData['sum']) {
					case 200:
						$aData['n'] = 5;
						break;
					case 700:
						$aData['n'] = 31;
						break;
					case 60:
						$aData['n'] = 1;
						break;
				}
				$oRequest->getSession()->set('nojsQUp', $aData['n']);
				//TODO add paymethodform
				$oPaymethodForm = $this->createForm(get_class(new PaymethodFormType()), null);
				$aData['paymethodform'] = $oPaymethodForm->createView();
			}
		}
		return $this->render('payment/setpaymentproviderform.html.twig', $aData);
	}
	/**
	 *  No js обработка выбора способа платежа через форму Яндекс-денег (из Я-кошелька или с помощью банковской карты)
	 * @Route("/payment/payprovider", name="payprovider")
	 */
	public function payprovider(Request $oRequest, GazelMeService $oGazelMeService)
	{
		$aData = $oGazelMeService->getViewDataService()->getDefaultTemplateData();
		$aData['paymethod'] = '';
		$oForm = $this->createForm(get_class(new PaymethodFormType()), null);
		$sView = 'payment/yandexform.html.twig';

		if ($oRequest->getMethod() == 'POST') {
			$oForm->handleRequest($oRequest);
			if ($oForm->isValid()) {
				$aData['paymethod'] = $oRequest->get('yad')  ? 'ps' : $aData['paymethod'];
				$aData['paymethod'] = $oRequest->get('card') ? 'bs' : $aData['paymethod'];
				$aData['paymethod'] = $oRequest->get('mob')  ? 'ms' : $aData['paymethod'];
			}
			$aData['sum'] = $nSum = intval($oRequest->getSession()->get('nojsPaysum'));
			$aData['n'] = $nQUp = intval($oRequest->getSession()->get('nojsQUp'));
			if (!$aData['paymethod'] || !$nSum || !$nQUp) {
				//TODO тут копипаста из недоделанного setpaysum, причем для !nSum она похожа на правду
				$oRequest->getSession()->remove('nojsQUp');
				$oRequest->getSession()->remove('nojsPaysum');
				$this->addFlash('notice', 'Unable get sum of payment, Try set cookie On and resend form again.');
				return $this->redirectToRoute('payment');
			} else {
				//если выбран тип платежа - не со счёта мобильного
				if ($aData['paymethod'] != 'ms') {
					$aData['isCache'] = 1;
					$nTransactionId = $this->_createTransactionId($nSum,  $aData['paymethod']);
					$aData['transactionId'] = $nTransactionId;
					if ($aData['paymethod'] == 'bs') {
						$aData['isCache'] = 0;
					}
				} else {
					//TODO тут конь не конь
					$sView = 'payment/rkform.html.twig';
				}
			}
		}
		return $this->render($sView, $aData);
	}
	/**
	 * Создать запись в pay_transaction
	 * @param int $nSum
	 * @param string $sPaymethod
	 * @return int
	*/
	private function _createTransactionId(int $nSum, string $sPaymethod) : int
	{
		$oPayTransaction = new PayTransaction();
		$oUser = $this->getUser();
		$oPayTransaction->setUserId($oUser->getId());
		$oPayTransaction->setCache( $this->getParameter('app.yamoney_number') );
		$oPayTransaction->setSum($nSum);
		$oPayTransaction->setRealSum(0);
		$oPayTransaction->setMethod($sPaymethod);
		$oDt = new \DateTime();
		$oDt->setDate(date('Y'), date('m'), date('d'));
		$oDt->setTime(date('H'), date('i'), date('s'));
		$oPayTransaction->setCreated($oDt);
		$oEm = $this->getDoctrine()->getManager();
		$oEm->persist($oPayTransaction);
		$oEm->flush();
		return $oPayTransaction->getId();
	}

	//TODO PayTransaction.method set on catch ya-notice
	//[notification_type] => p2p-incoming for cache
	//[notification_type] => card-incoming for card
}
