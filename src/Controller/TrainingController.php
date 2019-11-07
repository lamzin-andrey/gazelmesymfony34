<?php

namespace App\Controller;


use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Query\ResultSetMapping;

class TrainingController extends Controller
{
    /**
     * @Route("/training/group_count", name="training_group_count")
     */
    public function index()
    {
        //SELECT COUNT(id) AS cnt FROM main GROUP BY region
        $oRepository = $this->getDoctrine()->getRepository('App:Main');
        $oQueryBuilder = $oRepository->createQueryBuilder('m');
        $oQueryBuilder->select('COUNT(m.id) AS cnt')->
			groupBy('m.region');
		$aResult = $oQueryBuilder->getQuery()->getResult();
		var_dump($aResult);
		die;
    }
    
    /**
     * @Route("/training/leftjoinwith", name="training_leftjoinwith")
     */
    public function leftjoinwith()
    {
        /*SELECT m.title, c.city_name, r.region_name, r.is_city FROM main AS m
LEFT JOIN cities AS c ON c.id = m.city 
LEFT JOIN regions AS r ON r.id = m.region 
WHERE m.is_deleted = 1 LIMIT 10, 10;*/
        $oRepository = $this->getDoctrine()->getRepository('App:Main');
        $oQueryBuilder = $oRepository->createQueryBuilder('m');
        $oQueryBuilder->select('m.id, m.title, c.cityName, r.regionName, r.isCity')->
			where( $oQueryBuilder->expr()->eq('m.isDeleted', 1) )->
			leftJoin('App:Cities', 'c', 'WITH', 'm.city = c.id')->
			leftJoin('App:Regions', 'r', 'WITH', 'm.region = r.id')->
			setMaxResults(10)->
			setFirstResult(10);
			
		$aResult = $oQueryBuilder->getQuery()->getResult();
		var_dump($aResult);
		die;
    }
    
    /**
     * @Route("/training/innerjoinwith", name="training_innerjoinwith")
     */
    public function innerjoinwith()
    {
        /*SELECT m.title, c.city_name, r.region_name, r.is_city FROM main AS m
INNER JOIN cities AS c ON c.id = m.city 
INNER JOIN regions AS r ON r.id = m.region 
WHERE m.is_deleted = 1 LIMIT 10, 10;*/
        $oRepository = $this->getDoctrine()->getRepository('App:Main');
        $oQueryBuilder = $oRepository->createQueryBuilder('m');
        $oQueryBuilder->select('m.id, m.title, c.cityName, r.regionName, r.isCity')->
			where( $oQueryBuilder->expr()->eq('m.isDeleted', 1) )->
			innerJoin('App:Cities', 'c', 'WITH', 'm.city = c.id')->
			innerJoin('App:Regions', 'r', 'WITH', 'm.region = r.id')->
			setMaxResults(10)->
			setFirstResult(10);
			
		$aResult = $oQueryBuilder->getQuery()->getResult();
		var_dump($aResult);
		die;
    }
    
    /**
     * @Route("/training/concat", name="training_concat")
     */
    public function conac()
    {
        /*SELECT CONCAT(phone, ', ', email) FROM users LIMIT 10;*/
        $oRepository = $this->getDoctrine()->getRepository('App:Users');
        $oQueryBuilder = $oRepository->createQueryBuilder('u');
        $oQueryBuilder->select("CONCAT(u.phone, ', ',  u.email)")->
			setMaxResults(10);
			
		$aResult = $oQueryBuilder->getQuery()->getResult();
		var_dump($aResult);
		die;
    }
    
    /**
     * @Route("/training/groupconcat", name="training_groupconcat")
     */
    public function groupconcat()
    {
        /*SELECT m.phone, GROUP_CONCAT(m.title, ';;;') AS titles, GROUP_CONCAT(m.id, ';;;') AS idlist FROM main AS m 
					GROUP BY (m.phone)*/
        $oEm = $this->getDoctrine()->getRepository('App:Main');
        $oQueryBuilder = $oEm->createQueryBuilder('m');
        $oQueryBuilder->select("m.phone, GROUP_CONCAT(m.title, ';;;') AS titles, GROUP_CONCAT(m.id, ';;;') AS idlist")
			->groupBy('m.phone');
		$oQuery = $oQueryBuilder->getQuery();
		$aResult = $oQuery->getResult();
		var_dump($aResult);
		die;
    }

    /**
     * @Route("/training/criteria/group_count", name="training_criteria_group_count")
    */
    public function criteria1()
    {
        //SELECT COUNT(id) AS cnt FROM main GROUP BY region
        $oRepository = $this->getDoctrine()->getRepository('App:Main');
        $oCriteria = Criteria::create();
        $oExpression = Criteria::expr();
        $oCriteria->where($oExpression->eq('isDeleted', 0));

        $aResult = $oRepository->matching($oCriteria);
        /** @var $o Doctrine\ORM\LazyCriteriaCollection */
        /*foreach($aResult as $o) {
            var_dump($o);
        };*/
        die;
    }
    /**
     * @Route("/training/andoror", name="training_andoror")
    */
    public function testAndOrWhere()
    {
        //SELECT * FROM main WHERE (people = 1 OR box = 1) AND (near = 1 OR far = 1)
        /** @var \Doctrine\ORM\EntityRepository $oRepository  */
        $oRepository = $this->getDoctrine()->getRepository('App:Main');
        $oQueryBuilder = $oRepository->createQueryBuilder('m');
        $orCond1 = $oQueryBuilder->expr()->orX(); 
        $orCond2 = $oQueryBuilder->expr()->orX();
        $orCond1->add($oQueryBuilder->expr()->eq('m.people', 1));//совсем по фэншую!
        $orCond1->add('m.box = 1');//не совсем по феншую
        $orCond2->add('m.near = 1');
        $orCond2->add('m.far = 1');
        $oAndCond = $oQueryBuilder->expr()->andX();
        $oAndCond->add($orCond1);
        $oAndCond->add($orCond2);
        $oQueryBuilder->andWhere($oAndCond);
        $oQuery = $oQueryBuilder->getQuery();
        $aResult = $oQuery->execute();
        return $this->render('empty.html.twig', ['res' => $aResult]);
    }
    /**
     * @Route("/training/criteria/andoror", name="training_criteria_testoror")
    */
    public function criteriaAndOrOr()
    {
        //SELECT * FROM main WHERE (people = 1 OR box = 1) AND (near = 1 OR far = 1)
        $oRepository = $this->getDoctrine()->getRepository('App:Main');
        $oCriteria = Criteria::create();
        $oExpression = Criteria::expr();
        $oCriteria->where( $oExpression->andX(
                $oExpression->orX(
                    $oExpression->eq('people', 1),
                    $oExpression->eq('box', 1)
                ),
                $oExpression->orX(
                    $oExpression->eq('far', 1),
                    $oExpression->eq('near', 1)
                )
        ));
        $aResult = $oRepository->matching($oCriteria);
		
		//Делаем то же самое, для чистоты эксперимента создан новый 
		//объект Criteria и Expression
		$oCriteria2 = Criteria::create();
        $oExpression2 = Criteria::expr();
        $oCriteria2->where( $oExpression2->andX(
                $oExpression2->orX(
                    $oExpression2->eq('people', 1),
                    $oExpression2->eq('box', 1)
                ),
                $oExpression2->orX(
                    $oExpression2->eq('far', 1),
                    $oExpression2->eq('near', 1)
                )
        ));
		
        $aResult2 = $oRepository->matching($oCriteria2);
        return $this->render('empty.html.twig', ['res' => $aResult->get(0)]);
    }
    
    /**
     * @Route("/training/countqueries", name="training_countqueries")
    */
    public function testCountQueries(\App\Service\CachedDataService $oCachedData)
    {
        //SELECT * FROM main WHERE people = 1
		//SELECT * FROM main WHERE people = 1
        /** @var \App\Service\CachedDataService $oCachedData  */
        $oQueryBuilder = $oCachedData->createQueryBuilder('App:Main', 'm');
		$oQueryBuilder->where($oQueryBuilder->expr()->eq('m.people', 1));
		
        $aResult = $oQueryBuilder->getQuery()->execute();
		
		$aResult2 = $oQueryBuilder->getQuery()->setCacheable(false)->execute();
		
		return $this->render('empty.html.twig', ['res' => $aResult2]);
	}
	/**
     * @Route("/training/criteria/countqueries", name="training_criteria_countqueries")
    */
    public function testCriteriaCountQueries(\App\Service\CachedDataService $oCachedData)
    {
		//need
        //SELECT * FROM main WHERE people = 1
		//SELECT * FROM main WHERE people = 1
		/** @var \Doctrine\ORM\EntityRepository $oRepository */
		$oRepository = $this->getDoctrine()->getRepository('App:Main');
		//$oRepository->
		
		$oCriteria = Criteria::create();
		$e = Criteria::expr();
		var_dump($e);die;
		$oCriteria->where($e->eq('people', 1));
		
		/** @var \Doctrine\ORM\LazyCriteriaCollection $aResult */
		$aResult = $oRepository->matching($oCriteria);
		//$aResult->
		$aResult->get(0);
		
		$oCriteria2 = Criteria::create();
		$e2 = Criteria::expr();
		$oCriteria2->where($e2->eq('people', 1));
		$aResult2 = $oRepository->matching($oCriteria2);
		$aResult2->get(0);
		
		return $this->render('empty.html.twig', ['res' => $aResult]);
	}
	/**
     * @Route("/training/findby/countqueries", name="training_findby_countqueries")
    */
    public function testFindByntQueries()
	{
		$oRepository = $this->getDoctrine()->getRepository('App:Regions');
		//TODO use Criteria. How use RegionsRepository??
		$sRegion = 'moskva';
		$aRegions = $oRepository->findBy([
			'codename' => $sRegion
		]);
		
		$aRegions2 = $oRepository->findBy([
			'codename' => $sRegion
		]);
		return $this->render('empty.html.twig', ['res' => $aRegions]);
	}
	
	/**
     * @Route("/training/removedoubles", name="training_removedoubles")
    */
    public function remobeDoubles()
	{
		$oRepository = $this->getDoctrine()->getRepository('App:Users');
		$oEm = $this->getDoctrine()->getManager();
		$aUsers  = $oRepository->findAll();
		$aMap = [];
		$aDoubles = [];
		foreach ($aUsers as $oUser) {
			$nId = $oUser->getId();
			$sEmail = strtolower($oUser->getEmail());
			if (!isset($aMap[$sEmail])) {
				$aMap[$sEmail] = [];
			}
			$aMap[$sEmail][] = $nId;
			if (count($aMap[$sEmail]) > 1) {
				$aDoubles[$sEmail] = $sEmail;
			}
		}
		var_dump($aDoubles);//die;
		
		//every doubles undouble
		foreach ($aDoubles as $sEmail) {
			$aItems = $aMap[$sEmail];
			if ($sEmail == '') { //delete it
				/*foreach ($aItems as $nId) {
					$oUser = $oRepository->find($nId);
					$oEm->remove($oUser);
					$oEm->flush();
				}*/
			} else {
				//sort
				sort($aItems);
				$n = 0;
				$nSz = count($aItems) - 2;
				foreach ($aItems as $nId) {
					$oUser = $oRepository->find($nId);
					$sNewEmail = $sEmail . $n;
					$oUser->setEmail($sNewEmail);
					$oUser->setUsernameCanonical($sNewEmail);
					$oEm->persist($oUser);
					$oEm->flush();
					$n++;
				}
			}
		}
		die;
		return $this->render('empty.html.twig', ['res' => $aRegions]);
	}
}
