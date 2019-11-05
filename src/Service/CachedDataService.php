<?php
namespace App\Service;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;


/***
 * @class CachedDataService содержит методы для получения объектов Doctrine 
 * с уже включенным по умолчанию cacheble
*/
class CachedDataService  {
	
	public function __construct(ContainerInterface $oContainer)
	{
		$this->oContainer = $oContainer;
	}
	
	/**
	 * @param string $sClass
	 * @param string $sAlias
	 * @return \Doctrine\ORM\QueryBuilder cachable : true
	*/
	public function createQueryBuilder(string $sClass, string $sAlias) : \Doctrine\ORM\QueryBuilder
	{
		$oRepository = $this->oContainer->get('doctrine')->getRepository($sClass);
		$oQueryBuilder = $oRepository->createQueryBuilder($sAlias);
		$oQueryBuilder->setCacheable(true);
		return $oQueryBuilder;
	}
	
	/**
	 * @param string $sql
	 * @param \Doctrine\ORM\Query\ResultSetMapping $oRsm
	 * @return \Doctrine\ORM\NativeQuery cachable : true
	*/
	public function createNativeQuery(string $sql, \Doctrine\ORM\Query\ResultSetMapping $oRsm) : \Doctrine\ORM\NativeQuery
	{
		$oEm = $this->oContainer->get('doctrine')->getManager();
		$oQuery = $oEm->createNativeQuery($sql, $oRsm);
		$oQuery->setCacheable(true);
		return $oQuery;
	}
}