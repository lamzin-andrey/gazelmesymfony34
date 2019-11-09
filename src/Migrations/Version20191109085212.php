<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Заполняем телефонами поле username
 */
final class Version20191109085212 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Заполняем телефонами поле username';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('UPDATE users SET username = phone');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
