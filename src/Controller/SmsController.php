<?php

namespace App\Controller;

use App\Entity\SmsCode;
use App\Form\SmsCodeFormType;
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
		$oForm = $this->createForm(get_class(new SmsCodeFormType()), null);
		$aData = $oViewDataService->getDefaultTemplateData();
		$aData['invalidCodeMessage'] = '';
		$aData['timeoutMinutes'] = '';
		$oSession = $oRequest->getSession();
		$sPhone = $oSession->get('activePhone');
		$this->_setInfoMessage($aData, $oGazelMeService, $sPhone);
		$aData['form'] = $oForm->createView();
		$id = $oSession->get('verified_adv_id');
		if (!$sPhone || !$id) {
			$sMsg = 'Sorry, we can’t determine the phone number that you provided when submitting the ad. The browser may have cookies disabled. Turn on cookies and try again';
			$this->addFlash('notice', $this->get('translator')->trans($sMsg));
			return $this->render('sms/sendcode.html.twig', $aData);
		}
		if ($oRequest->getMethod() == 'POST') {
			$oForm->handleRequest($oRequest);
			if ($oForm->isValid()) {
				$reqCode = $oForm['code']->getData();
				if ($oSession->get('smscode') == $reqCode) {
					$oEm = $this->getDoctrine()->getManager();

					/* Мутная логика if (sess('up_adv_flag') == true) {
						query("UPDATE users SET is_sms_verify = 1 WHERE phone = {$phone}");
						$status = CUpAction::up($id);
						utils_302("/cabinet?status={$status}");
					} else {*/
						$oMainRepository = $this->getDoctrine()->getRepository('App:Main');
						$oAdvert = $oMainRepository->find($id);
						if ($oAdvert) {
							// и обновляем по id строку в main сделав объявление не удаленным
							//query("UPDATE main SET is_deleted = 0 WHERE id = {$id}");
							$oAdvert->setIsDeleted(0);
							$oUser = $oAdvert->getUserObject();
							$oEm->persist($oAdvert);
							if ($oUser) {
								// и обновляем по номеру телефона запись в users.is_verify = 1
								//query("UPDATE users SET is_sms_verify = 1 WHERE phone = {$phone}");
								$oUser->setIsSmsVerify(true);
								$oEm->persist($oUser);
								$oEm->flush();
								//показываем сообщение как на странице подачи объявления сейчас
								$this->addFlash('success', $this->get('translator')->trans('Your ad has been added and will be placed on the site after verification'));
							}
						} else {
							//написать что что-то пошло не так
							$this->addFlash('notice', $this->get('translator')->trans('You entered the correct code, but something went wrong, enable cookies and try submitting the ad again'));
							return $this->redirectToRoute('podat_obyavlenie');
						}
					//}
				} else {
					// иначе пишем что код не совпал и показываем все как в getsms
					if ($reqCode){
						$aData['invalidCodeMessage'] = $this->get('translator')->trans('Invalid code');
					}
					$this->_timeout($oRequest); //установить кол-во минут для надписи, сколько еще нужно ждать.
					$aData['timeoutMinutes'] = $this->_timeoutMinutes;
				}
			} else {
				$aErrors = $oGazelMeService->getFormErrorsAsArray($oForm);
				if (count($aErrors)) {
					$aData['invalidCodeMessage'] = current($aErrors);
				}
			}
		}
		return $this->render('sms/sendcode.html.twig', $aData);
	}
    /**
	 * @Route("/smsverify/getsms", name="smsverify_getsms")
	*/
	public function getsms(Request $oRequest,  ViewDataService $oViewDataService, GazelMeService $oGazelMeService)
	{
		$sPhoneNumber = $oRequest->getSession()->get('activePhone', '');
		$aData = $oViewDataService->getDefaultTemplateData($oRequest);
		$this->_setInfoMessage($aData, $oGazelMeService, $sPhoneNumber);
		$aData['timeoutMinutes'] = $this->_timeoutMinutes;
		$aData['invalidCodeMessage'] = '';
		$oForm = $this->createForm(get_class(new SmsCodeFormType()), null);
		$aData['form'] = $oForm->createView();
		if (!$sPhoneNumber) {
			$sMsg = 'Sorry, we can’t determine the phone number that you provided when submitting the ad. The browser may have cookies disabled. Turn on cookies and try again';
			$this->addFlash('notice', $this->get('translator')->trans($sMsg));
			return $this->render('sms/sendcode.html.twig', $aData);
		}
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
		$aData['timeoutMinutes'] = $this->_timeoutMinutes;

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
	 * @param array &$aData viewData
	 * @param GazelMeService $oGazelMeService
	 * @param string $sPhoneNumber
	 * @return void
	 */
	private function _setInfoMessage(array &$aData, GazelMeService $oGazelMeService, string $sPhoneNumber) : void
	{
		$sMsg = 'Did not receive sms? After %_timeoutMinutes%, click on the &laquo;Receive SMS&raquo; button to send SMS to number %phone% to confirm that it is really yours';
		$aData['infoMessage'] = $this->get('translator')->trans($sMsg, [
			'%_timeoutMinutes%' => $this->_timeoutMinutes,
			'%phone%' => $oGazelMeService->formatPhone($sPhoneNumber)
		]);
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
		$oCriteria->where( $oExpr->eq('phone', $sPhone) );
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
