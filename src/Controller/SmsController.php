<?php

namespace App\Controller;

use App\Entity\SmsCode;
use App\Service\GazelMeService;
use App\Service\ViewDataService;
use Doctrine\Common\Collections\Criteria;
use Landlib\RusLexicon;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Smspilot;

class SmsController extends Controller
{
	const   LAST_SMS_REQUEST_TIME  = 'LAST_SMS_REQUEST_TIME';

	/** @property int _timeoutMinutes сколько минут осталось до возможности следующего запроса sms */
	public $_timeoutMinutes;

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
	 * @Route("/smsverify/verify", name="smsverify_sendcode")
	 */
	public function verifysms(Request $oRequest,  ViewDataService $oViewDataService, GazelMeService $oGazelMeService)
	{
		return $this->render('sms/sendcode.html.twig', $aData);
	}
    /**
	 * @Route("/smsverify/getsms", name="smsverify_getsms")
	*/
	public function getsms(Request $oRequest,  ViewDataService $oViewDataService, GazelMeService $oGazelMeService)
	{
		$sApiKey = $this->getParameter('app.smspilotkey');


		$oSmsPilot = new Smspilot($sApiKey);
		$oRequest->getSession()->set('smscode', rand(1000, 9999));
		//$oSmsPilot->send($sPhoneNumber, $this->_getSmsText());

		//------
		$aData = $oViewDataService->getDefaultTemplateData($oRequest);
		$sPhoneNumber = $oRequest->getSession()->get('activePhone');
		//$sPhoneNumber = preg_replace("#^8#", '7', $sPhoneNumber);
		//если интервал после последнего запроса прошел,
		if ($this->_timeout($oRequest->getSession())) {
			if ($oRequest) {
				//генерим код и кладем в сессию
				$sCode = rand(1000, 9999);
				$oRequest->getSession()->set('smscode', $sCode);
				$oRequest->getSession()->set(static::LAST_SMS_REQUEST_TIME, time());
				//кладем или обновляем во временной таблице запись phone | code
				$this->_setCodeInDb($sPhoneNumber, $sCode);
				$this->_timeout($oRequest->getSession());
			}
		}
		$sMsg = 'Did not receive sms? After %_timeoutMinutes%, click on the &laquo;Receive SMS&raquo; button to send SMS to number %phone% to confirm that it is really yours';
		$aData['infoMessage'] = $this->get('translator')->trans($sMsg, [
			'%_timeoutMinutes%' => $this->_timeoutMinutes,
			'%phone%' => $oGazelMeService->formatPhone($sPhoneNumber)
		]);
		$aData['timeoutMinutes'] = $this->_timeoutMinutes;
		//$aData['invalidCodeMessage'] = $this->get('translator')->trans('Invalid code');
		$aData['invalidCodeMessage'] = '';
		//показываем кнопку получить смc
		//показываем надпись Вам отправлено смс с кодом, введите код в это поле
		//показываем надпись Повторная отправка смс возможна через 15 минут (вычисляемое значение)
		//$this->timeoutMinutes = Устанавливается в _timeout
		//$this->innerTpl = TPLS . '/sms/sendCode.tpl.php';
		return $this->render('sms/sendcode.html.twig', $aData);
	}

	private function _getSmsText()
	{
		return '';
	}
	/**
	 * Устанавливает timeoutMinutes в минутах или секундах, если секунд менее 60-ти. Также устанавливает ед. измерения.
	 * @return bool true если с момента последнего запроса прошло более SMS_INTERVAL секунд
	*/
	private function _timeout($oSession) : bool
	{
		$nSeconds =  time() - intval($oSession->get(static::LAST_SMS_REQUEST_TIME, 0));
		$nSmsInterval = $this->getParameter('app.sms_interval');
		$result  = ($nSeconds > $nSmsInterval ? true : false);
		$nSeconds = $nSmsInterval - $nSeconds;
		$minutes = floor($nSeconds / 60);
		$meas = RusLexicon::getMeasureWordMorph($minutes, 'минуту', 'минуты', 'минут');
		$this->_timeoutMinutes = $minutes . ' ' . $meas;
		if ($nSeconds < 60) {
			$meas = RusLexicon::getMeasureWordMorph($nSeconds, 'секунду', 'секунды', 'секунд');
			$this->_timeoutMinutes = $nSeconds . ' ' . $meas;
		}
		return $result;
	}
	/**
	 * Сохраняет код для sms в базе данных
	 * @param string $sPhone
	 * @param string $sCode
	*/
	private function _setCodeInDb(string $sPhone, string $sCode)
	{
		//$query = "INSERT INTO sms_code (phone, code) VALUES('{$phone}', {$code}) ON DUPLICATE KEY UPDATE code = {$code}";
		//query($query);
		$oEm = $this->getDoctrine()->getManager();
		$oCriteria = new Criteria();
		$oExpr = Criteria::expr();
		$oCriteria->where( $oExpr->eq('code', $sCode) );
		$oRepository = $this->getDoctrine()->getRepository('App:SmsCode');
		$oCode = $oRepository->matching($oCriteria)->get(0);
		if (!$oCode) {
			$oCode = new SmsCode();
			$oCode->setPhone($sPhone);
		}
		$oCode->setCode(intval($sCode));
		$oEm->persist($oCode);
		$oEm->flush();
	}
}
