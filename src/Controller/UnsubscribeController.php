<?php

namespace App\Controller;

use App\Entity\Unsubscribe;
use App\Service\GazelMeService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class UnsubscribeController extends Controller
{
    /**
     * @Route("/unsubscribe", name="unsubscribe")
     */
    public function index(Request $oRequest, TranslatorInterface $t, GazelMeService $oGazelMeService)
    {
		$this->_oForm = $oForm = $this->createForm(\App\Form\UnsubscribeFormType::class);
		if ($oRequest->getMethod() == 'POST') {
			$oForm->handleRequest($oRequest);
			if ($oForm->isValid()) {
				$oRepository = $oGazelMeService->repository('App:Unsubscribe');
				$sEmail = $oRequest->get('unsubscribe_form')['email'];
				$oEntity = $oRepository->findOneBy([
					'email' => $sEmail
				]);
				if (!$oEntity) {
					$oEntity = new Unsubscribe();
					$oEntity->setEmail($sEmail);
					$oEntity->setN(1);
					$oGazelMeService->save($oEntity);
				}
			}
		}

        return $this->render('unsubscribe/index.html.twig', [
            'form' => $oForm->createView(),
        ]);
    }
}
