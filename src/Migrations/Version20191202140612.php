<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;


/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191202140612 extends AbstractMigration implements ContainerAwareInterface
{
    public function getDescription() : string
    {
        return 'Remove main records with empty user_id';
    }

    public function up(Schema $schema) : void
    {
        $oEm = $this->container->get('doctrine')->getManager();
		$oUserRepository = $this->container->get('doctrine')->getRepository('App:Users');
		$aUsers = $oUserRepository->findAll();
		
		$oEm->createQuery('DELETE FROM App:Main As m WHERE m.userId IS NULL')->execute();

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
