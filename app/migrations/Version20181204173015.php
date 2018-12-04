<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20181204173015 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ideas_workshop_ideas_categories (idea_id INT UNSIGNED NOT NULL, category_id INT NOT NULL, INDEX IDX_5B4A52265B6FEF7D (idea_id), INDEX IDX_5B4A522612469DE2 (category_id), PRIMARY KEY(idea_id, category_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ideas_workshop_ideas_categories ADD CONSTRAINT FK_5B4A52265B6FEF7D FOREIGN KEY (idea_id) REFERENCES ideas_workshop_idea (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ideas_workshop_ideas_categories ADD CONSTRAINT FK_5B4A522612469DE2 FOREIGN KEY (category_id) REFERENCES ideas_workshop_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ideas_workshop_idea DROP FOREIGN KEY FK_CA001C7212469DE2');
        $this->addSql('DROP INDEX IDX_CA001C7212469DE2 ON ideas_workshop_idea');
        $this->addSql('ALTER TABLE ideas_workshop_idea DROP category_id');
        $this->addSql('DROP INDEX theme_slug_unique ON ideas_workshop_theme');
        $this->addSql('ALTER TABLE ideas_workshop_theme DROP canonical_name, DROP slug');
        $this->addSql('CREATE UNIQUE INDEX theme_name_unique ON ideas_workshop_theme (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ideas_workshop_ideas_categories');
        $this->addSql('ALTER TABLE ideas_workshop_idea ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ideas_workshop_idea ADD CONSTRAINT FK_CA001C7212469DE2 FOREIGN KEY (category_id) REFERENCES ideas_workshop_category (id)');
        $this->addSql('CREATE INDEX IDX_CA001C7212469DE2 ON ideas_workshop_idea (category_id)');
        $this->addSql('DROP INDEX theme_name_unique ON ideas_workshop_theme');
        $this->addSql('ALTER TABLE ideas_workshop_theme ADD canonical_name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, ADD slug VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('CREATE UNIQUE INDEX theme_slug_unique ON ideas_workshop_theme (slug)');
    }
}
