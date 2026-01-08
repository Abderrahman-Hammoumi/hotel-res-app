<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251226154004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $roomTable = $schemaManager->introspectTable('room');

        if ($roomTable->hasColumn('capacity')) {
            return;
        }

        $this->addSql('ALTER TABLE room ADD capacity INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $roomTable = $schemaManager->introspectTable('room');

        if (!$roomTable->hasColumn('capacity')) {
            return;
        }

        $this->addSql('ALTER TABLE room DROP capacity');
    }
}
