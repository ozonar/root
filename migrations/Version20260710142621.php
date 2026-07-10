<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260710142621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE status DROP CONSTRAINT fk_7b00651ca76ed395');
        $this->addSql('DROP INDEX uniq_7b00651c4fefcdf0');
        $this->addSql('DROP INDEX idx_7b00651ca76ed395');
        $this->addSql('ALTER TABLE status RENAME COLUMN user_id TO project_id');
        $this->addSql('ALTER TABLE status ADD CONSTRAINT FK_7B00651C166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_7B00651C166D1F9C ON status (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE status DROP CONSTRAINT FK_7B00651C166D1F9C');
        $this->addSql('DROP INDEX IDX_7B00651C166D1F9C');
        $this->addSql('ALTER TABLE status RENAME COLUMN project_id TO user_id');
        $this->addSql('ALTER TABLE status ADD CONSTRAINT fk_7b00651ca76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_7b00651c4fefcdf0 ON status (system_name)');
        $this->addSql('CREATE INDEX idx_7b00651ca76ed395 ON status (user_id)');
    }
}
