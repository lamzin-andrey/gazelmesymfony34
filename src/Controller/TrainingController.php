<?php

namespace App\Controller;


use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;

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
        //var_dump($aResult->get(0));die;
        /** @var $o Doctrine\ORM\LazyCriteriaCollection */
        /*foreach($aResult as $o) {
            var_dump($o);
        };*/
        //die;
        return $this->render('empty.html.twig', ['res' => $aResult->get(0)]);
    }
    
    /**
     * @Route("/training/countqueries", name="training_countqueries")
    */
    public function testCountQueries()
    {
        //SELECT * FROM main WHERE people = 1
	//SELECT * FROM main WHERE people = 1
        /** @var \Doctrine\ORM\EntityRepository $oRepository  */
        $oRepository = $this->getDoctrine()->getRepository('App:Main');
	$oQueryBuilder = $oRepository->createQueryBuilder('m');
	$oQueryBuilder->where($oQueryBuilder->expr()->eq('m.people', 1));
		
        $aResult = $oQueryBuilder->getQuery()->/*setCacheable(true)->*/execute();
	
	/*$oEm = $this->getDoctrine()->getManager();
	$aResult[0]->setTitle('I Am Caching 6!!!');
	$oEm->persist($aResult[0]);
	$oEm->flush();
	$oEm->clear();*/
	
	
	
	$aResult2 = $oQueryBuilder->getQuery()->/*setCacheable(true)->*/execute();
	//var_dump($aResult2[0]->getTitle());die;
	
	
        return $this->render('empty.html.twig', ['res' => $aResult2]);
    }
}
