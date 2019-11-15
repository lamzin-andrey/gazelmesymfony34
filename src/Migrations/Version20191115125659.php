<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191115125659 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Удаляем записи с пустым email_canonical';
    }

    public function up(Schema $schema) : void
    {
        // Удаляем записи с пустым email_canonical
		$this->addSql('DELETE FROM users WHERE email_canonical = \'\'');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
