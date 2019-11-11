<?php

namespace App\Controller;


use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Query\ResultSetMapping;

use Symfony\Bundle\SwiftmailerBundle;

class CabinetController extends Controller
{
    /**
     * @Route("/cabinet", name="cabinet")
    */
    public function index()
    {
    }
	/**
     * @Route("/cabinet_setting", name="cabinet_setting")
    */
    public function setting()
    {
    }
}
