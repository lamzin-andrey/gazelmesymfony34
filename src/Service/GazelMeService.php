<?php
namespace App\Service;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Main;
use \Landlib\Text2Png;


class GazelMeService
{

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
		$this->translator = $container->get('translator');
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
		$oRepository = $this->container->get("doctrine")->getRepository("App:Main");
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
	//candidate
	/**
	 * Изменяет имя города в соответствии с правилами русского языка, ответ на вопрос "где?"
	 * @param string $s ожидается например "Астрахань, Астраханская область" или "Астраханская область" или "Астрахань"
	 * @return string
	*/
	private function _modCityName(string $s) : string
	{
		//Не изменяется
		if ($s == 'Марий Эл') {
			return $s;
		}
		$s = trim($s);
		$g = 'аеёиоуыэюя';
		$sg = 'бвгджзйклмнпрстфхцчшщъь';
		$g = $this->_cp1251($g);//TODO
		$sg = $this->_cp1251($sg);
		$s = $this->_cp1251($s);
		
		if (strpos($s, ' ') !== false) {
			$ar = explode(' ', $s);
			$first = str_replace(
				array( $this->_cp1251('ой '), $this->_cp1251('ая '),  $this->_cp1251('ое '), $this->_cp1251('ый '), $this->_cp1251('ие '), $this->_cp1251('ые '), $this->_cp1251('кий '), $this->_cp1251('ий '),  ),
				array( $this->_cp1251('ом '), $this->_cp1251('ой '), $this->_cp1251('ом '), $this->_cp1251('ом '), $this->_cp1251('их '), $this->_cp1251('ых '), $this->_cp1251('ком '),  $this->_cp1251('ем ') ),
				$ar[ count($ar) - 2] . ' '
			);
			$second = $ar[ count($ar) - 1];
			if ($second == $this->_cp1251('Яр')) return $this->_utf8($s);
			$this->_modLastLetter($second, $sg);
			$s = $this->_utf8(trim($first)) . ' ' . $this->_utf8($second); 
		} else {
			$this->_modLastLetter($s, $sg);
			$s = $this->_utf8($s);
		}
		return $s;
	}
	/**
	 * Изменяет последнюю букву в имени города или региона
	 * @param string &$second
	 * @param string $sg
	 * @return string
	*/
	private function _modLastLetter(&$second, $sg) : void
	{
		$secondSRep = 0;
		$lastLetter = isset($second[strlen($second) - 1]) ? $second[strlen($second) - 1] : '';
		$preLastLetter = ($second[ strlen($second) - 2] ?? '');
		$preLastLetter2 = ($second[ strlen($second) - 3] ?? '' );
		$msog = $this->_cp1251('н');
		if ( strpos($sg, $lastLetter) === false ) {
			if ($lastLetter == $this->_cp1251('е')) {
				$secondSRep = 1;
				$second = str_replace(
					array( $this->_cp1251('ае '),  $this->_cp1251('ое '), $this->_cp1251('ый '), $this->_cp1251('ие '), $this->_cp1251('ые ') ),
					array($this->_cp1251('ае'), $this->_cp1251('ом'), $this->_cp1251('ом'), $this->_cp1251('их'), $this->_cp1251('ых') ),
					$second . ' ',
					$cnt
				);
			}
			if ($lastLetter == $this->_cp1251('а')) {
				$lastLetter = $this->_cp1251('е');
			}
			if ($lastLetter == $this->_cp1251('ы')) {
				$lastLetter = $this->_cp1251('ах');
			}
			if ($lastLetter == $this->_cp1251('и')) {
				if (strpos($msog, $preLastLetter) === false) {
					$lastLetter = $this->_cp1251('ах');
				} else {
					$lastLetter = $this->_cp1251('ях');
				}
			}
			if ($lastLetter == $this->_cp1251('я') && $preLastLetter != $this->_cp1251('а')) {
				$lastLetter = $this->_cp1251('и');
			}
		} else { 
			if ($lastLetter == $this->_cp1251('ь')) {
				if (strpos($msog, $preLastLetter) !== false) {
					$lastLetter = $this->_cp1251('и');
				} else {
					$lastLetter = $this->_cp1251('е');
				}
			}else if ($lastLetter == $this->_cp1251('й')) {
				$secondSRep = 1;
				$second = str_replace(
					array( $this->_cp1251('ий '),  $this->_cp1251('ай '), $this->_cp1251('ый '), $this->_cp1251('ой '), $this->_cp1251('ей') ),
					array($this->_cp1251('ом'), $this->_cp1251('ае'), $this->_cp1251('ом'), $this->_cp1251('ом'), $this->_cp1251("ее") ),
					$second . ' '
				);
			}else {
				$lastLetter .= $this->_cp1251('е');
			}
		}
		if (!$secondSRep) {
			if ( strlen($lastLetter) == 1 ) {
				$second[ strlen($second) - 1 ] = $lastLetter;
			} else {
				$second = substr($second, 0, strlen($second) - 1);
				$second .= $lastLetter;
			}
		}
		trim($second);
	}
	/**
	 * @param string $s
	 * @return
	**/
	private function _cp1251(string $s) : string
	{
		return mb_convert_encoding($s, 'WINDOWS-1251', 'UTF-8');
	}
	/**
	 * Конвертит win1251 utf8 если строка в windows-1251
	 * @param string $s
	 * @return
	**/
	private function _utf8(string $s) : string
	{
		return mb_convert_encoding($s, 'UTF-8', 'WINDOWS-1251');
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
}
