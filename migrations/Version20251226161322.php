<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251226161322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $reservationTable = $schemaManager->introspectTable('reservation');
        $reservationAlter = [];

        if (!$reservationTable->hasColumn('options')) {
            $reservationAlter[] = "ADD options JSON DEFAULT NULL COMMENT '(DC2Type:json)'";
        }

        if (!$reservationTable->hasColumn('options_total')) {
            $reservationAlter[] = 'ADD options_total DOUBLE PRECISION DEFAULT 0 NOT NULL';
        }

        if ($reservationAlter) {
            $this->addSql('ALTER TABLE reservation ' . implode(', ', $reservationAlter));
        }

        $roomTable = $schemaManager->introspectTable('room');
        if ($roomTable->hasColumn('capacity')) {
            // Align the column definition; this is safe even if already correct.
            $this->addSql('ALTER TABLE room CHANGE capacity capacity INT NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $reservationTable = $schemaManager->introspectTable('reservation');
        $reservationDrops = [];

        if ($reservationTable->hasColumn('options')) {
            $reservationDrops[] = 'DROP options';
        }

        if ($reservationTable->hasColumn('options_total')) {
            $reservationDrops[] = 'DROP options_total';
        }

        if ($reservationDrops) {
            $this->addSql('ALTER TABLE reservation ' . implode(', ', $reservationDrops));
        }

        $roomTable = $schemaManager->introspectTable('room');
        if ($roomTable->hasColumn('capacity')) {
            $this->addSql('ALTER TABLE room CHANGE capacity capacity INT DEFAULT 1 NOT NULL');
        }
    }
}
