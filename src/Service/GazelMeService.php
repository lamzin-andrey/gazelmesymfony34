<?php
namespace App\Service;

use App\Controller\IAdvertController;
use App\Form\ProfileFormType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
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
			if (!$oCurrentError) {
				continue;
			}
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

	/**
	 * Сохраняет модели в базе
	 * Аргументы - оюбъекты Entity
	*/
	public function save()
	{
		$oEm = $this->oContainer->get('doctrine')->getManager();
		$nSz = func_num_args();
		for ($i = 0; $i < $nSz; $i++) {
			$o = func_get_arg($i);
			if ($o) {
				$oEm->persist($o);
			}
		}
		$oEm->flush();
	}
	/**
	 *
	 * @param int  $nAdvertId
	 * @param int  $nUserId
	 * @param bool $bImmediateleSave= true
	 * @param bool $bForceUp = false
	 * @return  App\Entity\Main or null
	*/
	public function upAdvert(int $nAdvertId, int $nUserId, bool $bImmediateleSave= true, bool $bForceUp = false)
	{
		$oRepository = $this->oContainer->get('doctrine')->getRepository('App:Main');
		/** @var \App\Entity\Main $oAdvert */
		$oAdvert = $oRepository->find($nAdvertId);
		if ($oAdvert && ($bForceUp || $oAdvert->getUserId() == $nUserId) ) {
			$oQueryBuilder = $oRepository->createQueryBuilder('m');
			$aResult = $oQueryBuilder->select('max(m.delta) ')->getQuery()->getSingleResult();
			$n = intval( $aResult[1] ?? 0 );
			if ($n) {
				$oAdvert->setDelta($n + 1);
				if ($bImmediateleSave) {
					$this->save($oAdvert);
				}
			}
		}
		return $oAdvert;
	}
	/**
	 *
	 * @param int  $nAdvertId
	 * @param int  $nUserId
	 * @param bool $bVisibleFlag
	 * @param bool $bImmediateleSave= true
	 * @param bool $bForce = false
	 * @return  App\Entity\Main or null
	 */
	public function setAdvertShown(int $nAdvertId, int $nUserId, bool $bVisibleFlag, bool $bImmediateleSave = true, $bForce = false)
	{
		$oRepository = $this->oContainer->get('doctrine')->getRepository('App:Main');
		/** @var \App\Entity\Main $oAdvert */
		$oAdvert = $oRepository->find($nAdvertId);
		if ($oAdvert && ($bForce || $oAdvert->getUserId() == $nUserId) ) {
			$oAdvert->setIsHide( (!$bVisibleFlag) );
			if ($bImmediateleSave) {
				$this->save($oAdvert);
			}
		}
		return $oAdvert;
	}

	/**
	 *
	 * @param int  $nAdvertId
	 * @param int  $nUserId
	 * @param bool $bImmediateleSave= true
	 * @param bool $bForce = false
	 * @return  App\Entity\Main or null
	 */
	public function setAdvertAsDeleted(int $nAdvertId, int $nUserId, bool $bImmediateleSave= true, bool $bForce = false)
	{
		$oRepository = $this->oContainer->get('doctrine')->getRepository('App:Main');
		/** @var \App\Entity\Main $oAdvert */
		$oAdvert = $oRepository->find($nAdvertId);
		if ($oAdvert && ($bForce || $oAdvert->getUserId() == $nUserId) ) {
			$oAdvert->setIsDeleted(true);
			if ($bImmediateleSave) {
				$this->save($oAdvert);
			}
		}
		return $oAdvert;
	}

	/**
	 * Изменить значение переменной var в request_uri
	 * @param string $sVarName имя переменной в querystring
	 * @param string $sValue значение переменной в querystring
	 * @return string
	**/
	public function setUrlVar($sVarName, $sValue) : string
	{
		$oRequest = $this->oContainer->get('request_stack')->getCurrentRequest();
		//$a = explode("?", $_SERVER["REQUEST_URI"]);
		$sUri = $oRequest->server->get('REQUEST_URI');
		$a = explode('?', $sUri);
		$base = $a[0];
		$data = [];
		/** @var Request $oRequest */
		//$sGet = $oRequest->getQueryString();
		$_GET[$sVarName] = $sValue;
		if ($sValue == 1) {
			unset($_GET[$sVarName]);
		}
		foreach ($_GET as $k => $i) {
			$data[] = "$k=$i";
		}
		if (count ($_GET)) {
			$base .= "?" . join('&', $data);
		}
		return $base;
	}
	/**
	 * @param int $nPage
	 * @param int $nTotalPage
	 * @param int $nPerPage
	 * @param int $nItemInLine
	 * @param string $sPrevLabel = '<<'
	 * @param string $sNextLabel = '>>'
	 * @return StdClass {pageData: array of {n, text, active}, nMaxpage: номер последней страницы}
	*/
	public function preparePaging(int $nPage, int $nTotalPage, int $nPerPage, int $nItemInLine, string $sPrevLabel = '<<', string $sNextLabel = '>>') : \StdClass
	{
		$oResult = new \StdClass();
		$oResult->pageData = [];
		$oResult->nMaxpage = 0;
		$p = $nPage;
		$nMaxpage = $nMaxnum = ceil($nTotalPage / $nPerPage);
		if ($nMaxnum <= 1) {
			return $oResult;
		}
		$start = $p - floor($nItemInLine / 2);
		$start = $start < 1 ? 1: $start;
		$end = $p + floor($nItemInLine / 2);
		$end = $end > $nMaxnum ? $nMaxnum : $end;

		$data = [];
		if ($start >  2) {
			$o = $this->_getPagelineItemData();
			$o->n = 1;
			$data[] = $o;
		}
		if ($start > 1) {
			$o = $this->_getPagelineItemData();
			$o->n = $start - 1;
			$o->text = $sPrevLabel;
			$data[] = $o;
		}
		for ($i = $start; $i <= $end; $i++) {
			$o = $this->_getPagelineItemData();
			$o->n = $i;
			if ($i == $p) {
				$o->active = 1;
			}
			$data[] = $o;
		}
		if ($end + 1 < $nMaxnum) {
			$o = $this->_getPagelineItemData();
			$o->n = $end + 1;
			$o->text = $sNextLabel;
			$data[] = $o;
		}
		if (/*$end != $maxnum - 1 &&*/ $end != $nMaxnum) {
			$o = $this->_getPagelineItemData();
			$o->n = $nMaxnum;
			$data[] = $o;
		}
		$oResult->pageData = $data;
		$oResult->nMaxpage = $nMaxpage;
		return $oResult;
	}
	/**
	 *
	 * @param $
	 * @return  {text, active, n}
	*/
	private function _getPagelineItemData() : \StdClass
	{
		$o = new \StdClass();
		$o->n = 0;
		$o->text = '';
		$o->active = 0;
		return $o;
	}
	/**
	 * Получить общее количество записей выбираемых QueryBuilder без учета offset limit
	 * @param \Doctrine\ORM\QueryBuilder $oQueryBuilder
	 * @param string $sAlias Псевдоним таблицы переданный ранее в QueryBuilder при его создании
	 * @return
	*/
	public function getCountByQb(\Doctrine\ORM\QueryBuilder $oQueryBuilder, string $sAlias) : int
	{
		$oQueryBuilder->select('COUNT(' . $sAlias . '.id)');
		$oEm = $this->oContainer->get('doctrine')->getManager();
		$oQ = $oEm->createQuery( $oQueryBuilder->getQuery()->getDQL() );
		$a = $oQ->getSingleResult();
		return intval($a[1] ?? 0);
	}

	/**
	 * Устанавливает переменные viewData свЯзанные со строкой пагинации
	 * @param array &$aData данные для viewData
	 * @param \StdClass $oPageData данные полученные вызовом preparePaging
	*/
	public function setPageData(array &$aData, \StdClass $oPageData)
	{
		$aData['pageData'] = $oPageData->pageData;
		$aData['maxpage'] = $oPageData->nMaxpage;
		$aData['page'] = intval($this->oContainer->get('request_stack')->getCurrentRequest()->get('page', 1) );
		$aData['limit'] = $this->oContainer->getParameter('app.records_per_page', 10);
	}

	/**
	 *
	 * @return  new \DateTime в перспективе с учетом летнего времени при необходимости
	*/
	public function now() : \DateTimeInterface
	{
		return new \DateTime();
	}

	/**
	 *
	 * @param $
	 * @return string
	*/
	public function checkValidPassword(string $sPassword, IAdvertController $oController) : string
	{
		$oTempUser = new \App\Entity\Users();
		$oValidateForm = $oController->createFormEx(ProfileFormType::class, $oTempUser, []);
		$aData = [
			'display_name' => 'fdsjmkfhnsdkjfsdjkfhgfd',
			'_token' => $oValidateForm->createView()->children['_token']->vars['value']
		];
		$oTempUser->setUsername('usernameusername');
		$oTempUser->setPassword($sPassword);
		$oTempUser->setDisplayName('usernameusername');
		$oValidateForm->submit($aData);
		if (!$oValidateForm->isValid() ) {
			$aErrors = $this->getFormErrorsAsArray($oValidateForm);
			if (count($aErrors)) {
				return current($aErrors);
			}
		}
		return '';
	}

	/**
	 *
	 * @param string $id for example 'App:Users'
	 * @return ?ServiceEntityRepositoryInterface
	*/
	public function repository(string $id) : ?EntityRepository
	{
		return $this->oContainer->get('doctrine')->getRepository($id);
	}
}
