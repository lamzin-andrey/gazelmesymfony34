<?php
namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use Entities;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ExpressionBuilder;
//use \Doctrine\ORM\Mapping\ClassMetadata;
//use \Doctrine\ORM\EntityManagerInterface;



class RegionsRepository extends ServiceEntityRepository {
	
	/** @property  \Doctrine\Common\Collections\Criteria $_oCriteria */
	private $_oCriteria;
	
	/** @property  \Doctrine\Common\Collections\ExpressionBuilder $_e */
	private $_oE;
	
	public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, 'App:Regions');
    }
	
    public function findByCodename(string $sCodename)
	{
		/** @var \Doctrine\Common\Collections\Criteria $oCriteria */
		$this->_init();
		$e = $this->_oE;
		$this->_oCriteria->where( $e->eq('codename', $sCodename) );
		return $this->matching($this->_oCriteria)->toArray();
	}
	
	private function _init()
	{
		$this->_oCriteria = Criteria::create();
		$this->_oE = Criteria::expr();
	}
}