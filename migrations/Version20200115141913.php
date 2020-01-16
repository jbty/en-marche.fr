<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20200115141913 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE adherents DROP programmatic_foundation_privilege');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE adherents ADD programmatic_foundation_privilege TINYINT(1) DEFAULT \'0\' NOT NULL');
    }
}
