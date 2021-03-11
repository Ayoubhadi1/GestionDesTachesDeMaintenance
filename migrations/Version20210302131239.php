<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210302131239 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE materiel (id INT AUTO_INCREMENT NOT NULL, creator_id INT NOT NULL, nom VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, disponible TINYINT(1) NOT NULL, INDEX IDX_18D2B09161220EA6 (creator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tache (id INT AUTO_INCREMENT NOT NULL, responsable_id INT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, etat VARCHAR(255) NOT NULL, progress INT DEFAULT NULL, INDEX IDX_9387207553C59D72 (responsable_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tache_user (tache_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_FFA0B20FD2235D39 (tache_id), INDEX IDX_FFA0B20FA76ED395 (user_id), PRIMARY KEY(tache_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tache_materiel (tache_id INT NOT NULL, materiel_id INT NOT NULL, INDEX IDX_A313A1ACD2235D39 (tache_id), INDEX IDX_A313A1AC16880AAF (materiel_id), PRIMARY KEY(tache_id, materiel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, nom_complet VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, telephone VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE materiel ADD CONSTRAINT FK_18D2B09161220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT FK_9387207553C59D72 FOREIGN KEY (responsable_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tache_user ADD CONSTRAINT FK_FFA0B20FD2235D39 FOREIGN KEY (tache_id) REFERENCES tache (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tache_user ADD CONSTRAINT FK_FFA0B20FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tache_materiel ADD CONSTRAINT FK_A313A1ACD2235D39 FOREIGN KEY (tache_id) REFERENCES tache (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tache_materiel ADD CONSTRAINT FK_A313A1AC16880AAF FOREIGN KEY (materiel_id) REFERENCES materiel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tache_materiel DROP FOREIGN KEY FK_A313A1AC16880AAF');
        $this->addSql('ALTER TABLE tache_user DROP FOREIGN KEY FK_FFA0B20FD2235D39');
        $this->addSql('ALTER TABLE tache_materiel DROP FOREIGN KEY FK_A313A1ACD2235D39');
        $this->addSql('ALTER TABLE materiel DROP FOREIGN KEY FK_18D2B09161220EA6');
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY FK_9387207553C59D72');
        $this->addSql('ALTER TABLE tache_user DROP FOREIGN KEY FK_FFA0B20FA76ED395');
        $this->addSql('DROP TABLE materiel');
        $this->addSql('DROP TABLE tache');
        $this->addSql('DROP TABLE tache_user');
        $this->addSql('DROP TABLE tache_materiel');
        $this->addSql('DROP TABLE user');
    }
}
