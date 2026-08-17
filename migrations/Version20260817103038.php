<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817103038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Ajouter la colonne SANS contrainte NOT NULL pour l'instant
        $this->addSql('ALTER TABLE livre ADD vendeur_id INT DEFAULT NULL');

        // 2. Assigner tous les livres existants au vendeur de test (remplace X par le bon id)
        $this->addSql('UPDATE livre SET vendeur_id = 6 WHERE vendeur_id IS NULL');

        // 3. Maintenant seulement, rendre la colonne obligatoire
        $this->addSql('ALTER TABLE livre MODIFY vendeur_id INT NOT NULL');

        // 4. Ajouter la contrainte de clé étrangère
        $this->addSql('ALTER TABLE livre ADD CONSTRAINT FK_AC634F99858C065E FOREIGN KEY (vendeur_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_AC634F99858C065E ON livre (vendeur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE livre DROP FOREIGN KEY FK_AC634F99858C065E');
        $this->addSql('DROP INDEX IDX_AC634F99858C065E ON livre');
        $this->addSql('ALTER TABLE livre DROP vendeur_id');
    }
}
