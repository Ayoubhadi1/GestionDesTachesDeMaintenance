<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210110211402 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        $this->addSql('ALTER TABLE tache ADD responsable_id INT NOT NULL');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT FK_9387207553C59D72 FOREIGN KEY (responsable_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_9387207553C59D72 ON tache (responsable_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY FK_9387207553C59D72');
        $this->addSql('DROP INDEX IDX_9387207553C59D72 ON tache');
        $this->addSql('ALTER TABLE tache DROP responsable_id');
    }
}
