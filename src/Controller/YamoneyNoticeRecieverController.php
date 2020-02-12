<?php

namespace App\Controller;

use App\Service\PayService;
use App\Service\GazelMeService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;


class YamoneyNoticeRecieverController extends Controller
{
	/**
	 * Обработка уведомлений от сервиса Yandex Money
	 * @Route("/yamoney/notice/reciever", name="yamoney_notice_reciever")
	 */
	public function index(PayService $oService, GazelMeService $oAppService)
	{

		$this->_oAppService = $oAppService;
		$oService->setHttpNoticeEntityClassName('App\Entity\YaHttpNotice');
		$oService->setPayTransactionEntityClassName('App\Entity\PayTransaction');
		$oService->setUserEntityClassName('App\Entity\Users');
		$oService->setYandexNotificationEntityClassName('App\Entity\YaNotificationType');
		$oService->setOperationEntityClassName('App\Entity\Operations');
		return $oService->processYandexNotice($this, 'setUpCount');
	}
	/**
	 * @param array $aInfo  {user_id, sum, email, phone, order_id, operation_id} - можно использовать например  для отправки письма
	 */
	public function setUpCount(array $aInfo)
	{
		//Устанавливаем количество возможностей поднять объявление
		$oRepository = $this->getDoctrine()->getRepository('App\Entity\Users');
		$oUser = $oRepository->find($aInfo['user_id']);
		$nUpcount = 1;
		$nSum = intval($aInfo['sum']);
		switch($nSum) {
			case 200:
				$nUpcount = 5;
				break;

			case 700:
				$nUpcount = 31;
				break;
		}
		$nSafeUpcount = $oUser->getUpcount();
		$nSafeUpcount = $nSafeUpcount < 0 ? 0 : $nSafeUpcount;
		$oUser->setUpcount($nSafeUpcount + $nUpcount);
		$oEm = $this->getDoctrine()->getManager();
		$oEm->persist($oUser);
		$oEm->flush();


	}
}
