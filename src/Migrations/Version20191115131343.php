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
final class Version20191115131343 extends AbstractMigration  implements ContainerAwareInterface
{
    public function getDescription() : string
    {
        return 'Заполняем users.display_name значениями из main.name';
    }

    public function up(Schema $schema) : void
    {
        //Заполняем display_name значениями из main.name
		$oUsersRep = $this->container->get('doctrine')->getRepository('App:Users');
		$aUsers = $oUsersRep->findAll();
		$oMainRep = $this->container->get('doctrine')->getRepository('App:Main');
		$oEm = $this->container->get('doctrine')->getManager();
		foreach ($aUsers as $oUser) {
			$sPhone = $oUser->getPhone();
			$adverts = $oMainRep->findBy(['phone' => $sPhone]);
			foreach ($adverts as $oMain) {
				$sName = $oMain->getName();
				if (trim($sName)) {
					$oUser->setDisplayName(trim($sName));
					$oEm->persist($oUser);
					$oEm->flush();
					break;
				}
			}
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
