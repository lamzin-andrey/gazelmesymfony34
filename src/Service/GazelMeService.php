<?php
namespace App\Service;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Main;
use \Landlib\Text2Png;
use Doctrine\Common\Collections\Criteria;
use Landlib\RusLexicon;
use Landlib\SymfonyToolsBundle\Service\FileUploaderService;

class GazelMeService
{

	/** @property FormInterface $_oForm Сюда можно передать форму для более простой работы с ними */
	private $_oForm;

	public function __construct(ContainerInterface $container, ViewDataService $oViewDataService, FileUploaderService $oFileUploaderService)
	{
		$this->oContainer = $container;
		$this->translator = $container->get('translator');
		$this->oViewDataService = $oViewDataService;
		$this->oFileUploaderService = $oFileUploaderService;
	}
	/**
	 * Выводит тип перевозки (например, "Грузовая, термобудка" или "Пассажирская")
	 * @param \App\Entity\Main $oItem
	 * @return string
	*/
	public function getCarsTypes(Main $oItem) : string
	{
		$a = [];
		if ($oItem->getBox()) {
			$a[] = $this->translator->trans('Avenger'); 
		}
		
		if ($oItem->getPeople()) {
			$a[] = $this->translator->trans('Passenger'); 
		}
		
		if ($oItem->getTerm()) {
			$a[] = $this->translator->trans('Termobox'); 
		}
		$s = join($a, ', ');
		$s = mb_strtolower($s, 'utf-8');
		$s = $this->capitalize($s);
		return $s;
	}
	/**
	 * Возвращает строку байтов PNG изображения номера телефона
	 * @param int $nId идентификатор объявления 
	 * @return void
	*/
	public function getPhoneAsImage(int $nId):void
	{
		$oRepository = $this->oContainer->get("doctrine")->getRepository("App:Main");
		$aPhone = $oRepository->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->setParameter('id', $nId)
            ->select('m.phone')
            ->getQuery()
			->getOneOrNullResult();
		$sPhone = $this->translator->trans('Phone not found');
		if ($aPhone) {
			$sPhone = ($aPhone['phone'] ?? $sPhone);
			$sPhone = $this->formatPhone($sPhone);
		}
		$oT2p = new Text2Png($sPhone);
		$oT2p->setFontSize(24);
		$oT2p->pngResponse();
	}
	/**
	 * Возвращает отформатированый телефонный номер, например "8 (xxx)xxx-xx-xx"
	 * @param string sPhone телефон
	 * @return string
	*/
	public function formatPhone(string $sPhone):string
	{
		$s = $sPhone;
		if (strlen($s) < 11) {
			return $s; 
		}
		$a = [];
		for ($i = strlen($s) - 1, $j = 1; $i > -1; $i--, $j++) {
			$a[] = $s[$i];
			if ($j == 2 || $j == 4) {
				$a[] = '-';
			}
			if ($j == 10) {
				$a[] = '(';
			}
			if ($j == 7) {
				$a[] = ')';
			}
		}
		$s = join('', array_reverse($a));
		return $s;
	}
	/**
	 * Получает актуальный тайтл для страницы со списком объявлений
	 * @param Request $request
	 * @param string $sRegionName кириллическое значение
	 * @param string $sCityName кириллическое значение
	 * @param string string $sAdvTitle = '' Заголовок объявления (указанный пользователем при подаче)
	 * @return string
	*/
	public function getTiltle(Request $request, string $sRegionName, string $sCityName, string $sAdvTitle = '') : string
	{
		$sUrl = $request->server->get('REQUEST_URI');
		$aUrl = explode('?', $sUrl);
		$sUrl = $aUrl[0];
		if ($sUrl == '/') {
			return $this->translator->trans('Default title');
		}
		return $this->getMainHeading($request, $sRegionName, $sCityName, $sAdvTitle);
	}
	/**
	 * Получает актуальный заголовок для страницы со списком объявлений
	 * @param Request $request
	 * @param string $sRegionName кириллическое значение
	 * @param string $sCityName кириллическое значение
	 * @param string string $sAdvTitle = '' Заголовок объявления (указанный пользователем при подаче)
	 * @return string
	*/
	public function getMainHeading(Request $request, string $sRegionName, string $sCityName, string $sAdvTitle = '') : string
	{
		$sUrl = $request->server->get('REQUEST_URI');
		$aUrl = explode('?', $sUrl);
		$sUrl = $aUrl[0];
		if ($sUrl == '/') {
			return $this->translator->trans('Default heading');
		}
		$s = '';
		if ($sCityName && $sRegionName) {
			$s = $this->_modCityName($sCityName);
			$s .= ', ' . $sRegionName;
		} else if ($sRegionName) {
			$s = $this->_modCityName($sRegionName);
		} else  {
			return $this->translator->trans('Default heading');
		}
		$sAdvTitle = trim($sAdvTitle);
		if ($sAdvTitle) {
			$sAdvTitle = ' | ' . $sAdvTitle;
		}
		return $this->translator->trans('Get car in') . ' ' . $s . $sAdvTitle;
	}
	
	/**
	 * Изменяет имя города в соответствии с правилами русского языка, ответ на вопрос "где?"
	 * @param string $s ожидается например "Астрахань, Астраханская область" или "Астраханская область" или "Астрахань"
	 * @return string
	*/
	private function _modCityName(string $s) : string
	{
		return RusLexicon::getCityNameFor_In_the_City($s);
	}
	/**
	 * @param string $s
	 * @return
	**/
	public function cp1251(string $s) : string
	{
		return RusLexicon::cp1251($s);
	}
	//candidate
	/**
	 * Перевести первый символ в верхний регистр
	**/
	public function capitalize(string $s) : string
	{
		$enc = mb_detect_encoding($s, array('UTF-8', 'Windows-1251'));
		$us = mb_convert_case($s, 0, $enc);
		$first_char = mb_substr($us, 0, 1, $enc);
		$tail = mb_substr($s, 1, 1000000, $enc);
		return ($first_char . $tail);
	}
	/**
	 * Тут приходится использовать "кривой" транслит, потому что он уже есть в базе и сайт при этом есть в выдаче Яндекса
	**/
	public function translite_url(string $string) : string
	{
		$string = str_replace('ё','e',$string);
		$string = str_replace('й','i',$string);
		$string = str_replace('ю','yu',$string);
		$string = str_replace('ь','',$string);
		$string = str_replace('ч','ch',$string);
		$string = str_replace('щ','sh',$string);
		$string = str_replace('ц','c',$string);
		$string = str_replace('у','u',$string);
		$string = str_replace('к','k',$string);
		$string = str_replace('е','e',$string);
		$string = str_replace('н','n',$string);
		$string = str_replace('г','g',$string);
		$string = str_replace('ш','sh',$string);
		$string = str_replace('з','z',$string);
		$string = str_replace('х','h',$string);
		$string = str_replace('ъ','',$string);
		$string = str_replace('ф','f',$string);
		$string = str_replace('ы','i',$string);
		$string = str_replace('в','v',$string);
		$string = str_replace('а','a',$string);
		$string = str_replace('п','p',$string);
		$string = str_replace('р','r',$string);
		$string = str_replace('о','o',$string);
		$string = str_replace('л','l',$string);
		$string = str_replace('д','d',$string);
		$string = str_replace('ж','j',$string);
		$string = str_replace('э','е',$string);
		$string = str_replace('я','ya',$string);
		$string = str_replace('с','s',$string);
		$string = str_replace('м','m',$string);
		$string = str_replace('и','i',$string);
		$string = str_replace('т','t',$string);
		$string = str_replace('б','b',$string);
		$string = str_replace('Ё','E',$string);
		$string = str_replace('Й','I',$string);
		$string = str_replace('Ю','YU',$string);
		$string = str_replace('Ч','CH',$string);
		$string = str_replace('Ь','',$string);
		$string = str_replace('Щ','SH',$string);
		$string = str_replace('Ц','C',$string);
		$string = str_replace('У','U',$string);
		$string = str_replace('К','K',$string);
		$string = str_replace('Е','E',$string);
		$string = str_replace('Н','N',$string);
		$string = str_replace('Г','G',$string);
		$string = str_replace('Ш','SH',$string);
		$string = str_replace('З','Z',$string);
		$string = str_replace('Х','H',$string);
		$string = str_replace('Ъ','',$string);
		$string = str_replace('Ф','F',$string);
		$string = str_replace('Ы','I',$string);
		$string = str_replace('В','V',$string);
		$string = str_replace('А','A',$string);
		$string = str_replace('П','P',$string);
		$string = str_replace('Р','R',$string);
		$string = str_replace('О','O',$string);
		$string = str_replace('Л','L',$string);
		$string = str_replace('Д','D',$string);
		$string = str_replace('Ж','J',$string);
		$string = str_replace('Э','E',$string);
		$string = str_replace('Я','YA',$string);
		$string = str_replace('С','S',$string);
		$string = str_replace('М','M',$string);
		$string = str_replace('И','I',$string);
		$string = str_replace('Т','T',$string);
		$string = str_replace('Б','B',$string);
		$string = str_replace(' ','_',$string);
		$string = str_replace('"','',$string);
		$string = str_replace('.','',$string);
		$string = str_replace("'",'',$string);
		$string = str_replace(",",'',$string);
		$string = str_replace('\\', '', $string);
		$string = str_replace('?', '', $string);
		return strtolower($string);
	}
	/**
	 * @description
	 * @param 
	 * @return ViewDataService
	*/
	public function getViewDataService() : ViewDataService
	{
		return $this->oViewDataService;
	}
	/**
	 * @description
	 * @param 
	 * @return ViewDataService
	*/
	public function getFileUploaderService() : FileUploaderService
	{
		return $this->oFileUploaderService;
	}
	/**
	 * Добавит в $aWhere фильтр по городу и/или региону
	 * Инициализует кириллические имена города и региона
	 * @param \Doctrine\Common\Collections\Criteria $oCriteria
	 * @param string &$sCyrRegionName
	 * @param string &$sCyrCityName
	 * @param string $sRegion = '' код региона латинскими буквами
     * @param string $sCity = ''   код города латинскими буквами
	*/
	public function setCityConditionAndInitCyrValues($oCriteria, &$sCyrRegionName, &$sCyrCityName, $sRegion, $sCity, &$nCityId, &$nRegionId)
	{
		if ($sRegion) {
			$aRegions = $this->oContainer->get('App\Repository\RegionsRepository')->findByCodename($sRegion);
			if ($aRegions) {
				$oRegion = current($aRegions);
				if ($oRegion) {
					//$aWhere['region'] = $oRegion->getId();
					$e = Criteria::expr();
					$nRegionId = $oRegion->getId();
					$oCriteria->andWhere( $e->eq('region', $oRegion->getId()) );
					$sCyrRegionName = $oRegion->getRegionName();
					if ($sCity) {
						//Тут в любом случае будет не более десятка записей для сел типа Крайновка или Калиновка. Отфильровать на php
						$aCities = $oRegion->getCities();
						foreach($aCities as $oCity) {
							if ($oCity->getCodename() == $sCity) {
								$sCyrCityName = $oCity->getCityName();
								//$aWhere['city'] = $oCity->getId();
								$nCityId = $oCity->getId();
								$oCriteria->andWhere( $e->eq('city', $oCity->getId()) );
								break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Добавит в $aWhere фильтр по городу и/или региону
	 * Инициализует кириллические имена города и региона
	 * @param \Doctrine\ORM\QueryBuilder $oQueryBuilder ('App:Main AS  m') для запроса выборки объявлений, @see AdvertListController::_loadAdvList
	 * @param string &$sCyrRegionName
	 * @param string &$sCyrCityName
	 * @param string $sRegion = '' код региона латинскими буквами
	 * @param string $sCity = ''   код города латинскими буквами
	 */
	public function setCityConditionAndInitCyrValuesByQueryBuilder($oQueryBuilder, &$sCyrRegionName, &$sCyrCityName, $sRegion, $sCity, &$nCityId, &$nRegionId)
	{
		if ($sRegion) {
			$aRegions = $this->oContainer->get('App\Repository\RegionsRepository')->findByCodename($sRegion);
			if ($aRegions) {
				$oRegion = current($aRegions);
				if ($oRegion) {
					//$aWhere['region'] = $oRegion->getId();
					$e = $oQueryBuilder->expr();
					$nRegionId = $oRegion->getId();
					$oQueryBuilder->andWhere( $e->eq('m.region', $oRegion->getId()) );
					$sCyrRegionName = $oRegion->getRegionName();
					if ($sCity) {
						//Тут в любом случае будет не более десятка записей для сел типа Крайновка или Калиновка. Отфильровать на php
						$aCities = $oRegion->getCities();
						foreach($aCities as $oCity) {
							if ($oCity->getCodename() == $sCity) {
								$sCyrCityName = $oCity->getCityName();
								//$aWhere['city'] = $oCity->getId();
								$nCityId = $oCity->getId();
								$oQueryBuilder->andWhere( $e->eq('m.city', $oCity->getId()) );
								break;
							}
						}
					}
				}
			}
		}
	}
	
	public function getAuthUser()
	{
		$oUser = $this->oContainer->get('security.token_storage')->getToken()->getUser();
		return $oUser;
	}
	/**
	 * Добавляет $oBuilder поле для загрузки файла со всеми необъодимыми параметрами
	*/
	public function addAdvertPhotoField(string $sUploadDirectory, FormBuilder $oBuilder, string $sFieldName = 'imagefile')
	{
		$oFileUploader = $this->getFileUploaderService();
		$oFileUploader->setTranslationDomain('Adform');
		$oRequest = $this->oContainer->get('request_stack')->getCurrentRequest();
		$oFileUploader->addAllowMimetype('image/jpeg');
		$oFileUploader->addAllowMimetype('image/png');
		$oFileUploader->addAllowMimetype('image/gif');
		$oFileUploader->setFileInputLabel('Append file!');
		$oFileUploader->setMimeWarningMessage('Choose allowed file type');
		$oFileUploader->addLiipBundleFilter('max_width');

		$subdir = $sUploadDirectory;
		$sTargetDirectory = $oRequest->server->get('DOCUMENT_ROOT') . '/' . $subdir;

		$oFileUploader->setTargetDirectory($sTargetDirectory);

		$aOptions = $oFileUploader->getFileTypeOptions();
		$aOptions['attr'] = [
			'style' => 'width:173px;',
			'v-if'  => '!vueFileInputIsEnabled'
		];
		$aOptions['translation_domain'] = 'Adform';
		$oBuilder->add($sFieldName, \Symfony\Component\Form\Extension\Core\Type\FileType::class, $aOptions);
	}
	/**
	 * @param string $sError
	 * @param string $sField
	 * @param FormInterface $oForm
	 **/
	public function addFormError(string $sError, string $sField, ?FormInterface $oForm = null)
	{
		if ($oForm) {
			$this->setForm($oForm);
		}
		$oError = new \Symfony\Component\Form\FormError($this->translator->trans($sError));
		$this->_oForm->get($sField)->addError($oError);
	}
	/**
	 * @param string $sError
	 * @param string $sField
	 * @param FormInterface $oForm
	 **/
	public function setForm(FormInterface $oForm)
	{
		$this->_oForm = $oForm;
	}
	/**
	 * Получить ассоциативный массив сообщений об ошибках
	 * @param FormInterface $oForm
	 * @return array
	*/
	public function getFormErrorsAsArray(FormInterface $oForm) : array
	{
		$aResult = [];
		$nSz = $oForm->getErrors(true)->count();
		if ($nSz) {
			$oCurrentError = $oForm->getErrors(true)->current();
			$sKey = $oCurrentError->getOrigin()->getConfig()->getName();
			$sMessage = $oCurrentError->getMessage();
			$aResult[$sKey] = $sMessage;
		}
		for ($i = 0; $i < $nSz - 1; $i ++) {
			$oCurrentError = $oForm->getErrors(true)->next();
			$sKey = $oCurrentError->getOrigin()->getConfig()->getName();
			$sMessage = $oCurrentError->getMessage();
			$aResult[$sKey] = $sMessage;
		}
		return $aResult;
	}
	/**
	 * Удаляет из номера телефона всё, кроме цифр. Ведущий +7 меняет на 8.
	 * @param string $sPhone
	 * @return string
	*/
	public function normalizePhone(string $sPhone) : string
	{
		$phone = trim($sPhone);
		$plus = 0;
		if (isset($phone[0]) && $phone[0] == '+') {
			$plus = 1;
		}
		$s = trim(preg_replace("#[\D]#", "", $phone));
		if ($plus && strlen($s) > 10) {
			$code = substr($s, 0, strlen($s) - 10 );
			$tail = substr($s, strlen($s) - 10 );
			$code++;
			$s = $code . $tail;
		} elseif($plus) {
			$s = '';
		}
		return $s;
	}
	/**
	 * Время жизни result_cache для enableResultCache
	 * @return int
	*/
	public function ttl() : int
	{
		return $this->oContainer->getParameter('app.resuilt_cache_ttl');
	}
}
