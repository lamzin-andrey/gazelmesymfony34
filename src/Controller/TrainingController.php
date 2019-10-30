<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

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
    
}
