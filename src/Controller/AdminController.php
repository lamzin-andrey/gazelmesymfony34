<?php

namespace App\Controller;

use App\Service\GazelMeService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class AdminController extends AdvertlistController
{
	/** @property string _sViewName путь к шаблону для функции render */
	protected $_sViewName = 'admin/adminlist.html.twig';

    /**
	 * Выводит только те объявления у которых установлен флаг automoderate = 1
     * @Route("/private/autoadv", name="private_autoadv")
    */
    public function autoadv(Request $oRequest, GazelMeService $oGazelMeService)
    {
		return $this->_advListPage($oRequest, $oGazelMeService);
    }
	/**
	 * Устанавливает базовое условие SQL запроса
	 * @param QueryBuilder $oQueryBuilder
	 * @return
	*/
	protected function _setBasicSqlWhere(QueryBuilder $oQueryBuilder)
	{
		$oQueryBuilder->where( $oQueryBuilder->expr()->eq('m.automoderate', true) );
		$oQueryBuilder->select('m.automoderate', 'm.isHide', 'm.isModerate', 'u.username');
	}

	/**
	 * @Route("admin/automoderation/{id}", name="admin_automoderation")
	 * @param int $id
	 * @param Request $oRequest
	 * @param TranslatorInterface $t
	 * @return
	*/
	public function admin_automoderation(int $id, Request $oRequest, TranslatorInterface $t, GazelMeService $oGazelService)
	{
		//UPDATE main SET is_moderate = 1, automoderate = 0 WHERE id = $
		$oAdv = $oGazelService->repository('App:Main')->find($id);
		$oAdv->setIsModerate(true);
		$oAdv->setAutomoderate(false);
		$oGazelService->save($oAdv);
		return $this->redirectToRoute('private_autoadv');
	}
}
