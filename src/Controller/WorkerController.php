<?php

namespace App\Controller;

use App\Entity\SmsCode;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Response;

class WorkerController extends Controller
{
    /**
     * @Route("/worker", name="worker")
     */
    public function index(Request $oRequest)
    {
		$action = isset($_POST['action']) ? $_POST['action'] : 'automoderate';
		$sAction = $oRequest->get('action');

		switch ($sAction) {
			case 'automoderate': //this legacy - теперь выполняем все действия, в зависимости от включенных констант.
				//$this->_automoderate();
				$this->_sms();
				//$this->_upcountRestore();
				//$this->_testSendEmailToGoogle();
				//json_ok();
				break;
		}/**/
		$oResponse = new Response( json_encode([]) );
		$oResponse->headers->set('Content-Type', 'application/json');
        return $oResponse;
    }
	/**
	 * Рассылка сообщений
	*/
	private function _sms()
	{
		$bSmsOn = $this->getParameter('app.sms_service_on');
		if ($bSmsOn !== true) {
			return;
		}
		//чтобы избежать рассылок на одни и те же номера.
		$file =  $this->getParameter('kernel.cache_dir') . '/smsproc';
		if (file_exists($file)) {
			$time = filemtime($file);
			if (time() - $time < 60) {
				return;//еще и минуты не прошло, как кто-то запустил воркер
			}
		}
		file_put_contents($file, time());

		//берем из базы 100 записей.
		//$list = query('SELECT * FROM sms_code LIMIT 0, 100', $count);
		$oSmsCodeRepository = $this->getDoctrine()->getRepository('App:SmsCode');
		$oCriteria = Criteria::create();
		$oCriteria->setMaxResults(100);
		$oExpr = Criteria::expr();
		$list = $oSmsCodeRepository->matching($oCriteria)->toArray();
		$count = 0;
		foreach ($list as $k => $oItem) {
			if ($oItem->getPhone() == '710637') {
				unset($list[$k]);
				continue;
			}
			$count++;
		}

		if ($count) {
			//отправляем смс
			$results = $this->_sendList($list);
			//если успешно, удаляем из базы номер.
			$numbers = [];
			$numbersSz = 0;
			foreach ($results as $smsResult) {
				$numbers[] = $smsResult->number;
				$numbersSz++;

			}
			if ($numbersSz) {
				$numbers = array_map(function($i){
					return "'{$i}'";
				}, $numbers);
				$sNumbers = join(',', $numbers);
				//query("DELETE FROM sms_code WHERE phone IN({$sNumbers})");
				$oEm = $this->getDoctrine()->getManager();
				$oQuery = $oEm->createQuery('DELETE FROM App:SmsCode AS m WHERE m.phone IN (' . $sNumbers . ')');
				$oQuery->execute();
			}
		}
		@unlink($file);
	}
	/**
	 *
	 * @param array of App\Entity\SmsCode $list
	 * @return array of StdClass {success, number} success - true если удалось отправить смс
		number - номер, чтобы удалить его из- очереди.
	 */
	private function _sendList(array $list) : array
	{
		//Так как ответ невнятный просто возвращаем переформатированный $list
		$result = [];
		if (!count($list)) {
			return $result;
		}
		$request = new Request();
		$messages = [
			'%1% - Код подтверждения на Gazel.Me',
			'Ваш код подтверждения на Gazel.Me %1%',
			'Gazel.Me: Ваш код подтверждения %1%',
			'Спасибо, что вы с нами! Код подтверждения: %1%. Gazel.Me',
			'Спасибо за регистрацию! Код: %1%. Gazel.Me',
		];
		$s = $messages[ rand(0, count($messages) - 1)];
		$sApiKey = $this->getParameter('app.smspilotkey');
		$senderName = $this->getParameter('app.smspilot_sendername');
		$oSmsPilot = new \Smspilot($sApiKey);

		$numbers = [];
		foreach ($list as $oPhoneData) {
			$phone = preg_replace("#^8#", '7', $oPhoneData->getPhone());
			/** @var SmsCode $oPhoneData */
			$s = str_replace('%1%', $oPhoneData->getCode(), $s);
			$o = new \StdClass();
			$o->number = $oPhoneData->getPhone();
			$oSmsPilot->send($phone, $s, $senderName);
			$aStatus = $oSmsPilot->status[0] ?? [];
			$nStatus = intval($aStatus['status'] ?? -1);
			$o->success = $nStatus === 0 ? true : false;
			$result[] = $o;
		}
		return $result;
	}
}
