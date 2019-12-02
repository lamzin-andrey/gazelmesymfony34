<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use App\Entity\Main;
use App\Entity\Users;


/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191202135933 extends AbstractMigration implements ContainerAwareInterface
{
    public function getDescription() : string
    {
        return 'Установить users.id в main.user_id';
    }

    public function up(Schema $schema) : void
    {
        $oEm = $this->container->get('doctrine')->getManager();
		$oUserRepository = $this->container->get('doctrine')->getRepository('App:Users');
		$aUsers = $oUserRepository->findAll();
		
		foreach ($aUsers as $oUser) {
			$oEm->createQuery('UPDATE App:Main AS m SET m.userId = :uid WHERE m.phone = :phone')->setParameters([
				':uid' => $oUser->getId(),
				':phone' => $oUser->getPhone(),
			])->execute();
		}
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
	
	public function setContainer(ContainerInterface $container = null)
	{
		$this->container = $container;
	}
}
