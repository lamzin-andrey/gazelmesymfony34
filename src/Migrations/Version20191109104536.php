<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191109104536 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Заполняем телефонами и email-ами поля FOSUser';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('UPDATE users SET username_canonical = phone, email_canonical = email, enabled = 1');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
