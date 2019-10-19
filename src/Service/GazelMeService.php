<?php
namespace App\Service;

use App\Entity\Main;
use \Symfony\Component\DependencyInjection\ContainerInterface;
use \LamzinforkPriler\Text2Image\Magic AS Text2Image;


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
		$s = '';
		$s .= $oItem->getBox() ? ($this->translator->trans('Avenger') . ', ') : '';
		$s .= $oItem->getPeople() ? ($this->translator->trans('Passenger') . ', ') : '';
		$s .= $oItem->getTerm() ? $this->translator->trans('Termobox') : '';
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
		$oT2i = new Text2Image($sPhone);
		$oT2i->width = 261;
		$oT2i->output();
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
}