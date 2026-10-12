<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918110843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, display_name VARCHAR(120) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, last_lesson_slug CLOB DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON app_user (email)');
        $this->addSql('CREATE TABLE lesson_completion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, lesson_slug VARCHAR(180) NOT NULL, completed_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_35DF7EAFA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_lesson ON lesson_completion (user_id, lesson_slug)');
        $this->addSql('CREATE INDEX IDX_35DF7EAFA76ED395 ON lesson_completion (user_id)');
        $this->addSql('CREATE TABLE quiz_attempt (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, lesson_slug VARCHAR(180) NOT NULL, score INTEGER NOT NULL, total_questions INTEGER NOT NULL, answers CLOB NOT NULL, attempted_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_AB6AFC6A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_AB6AFC6A76ED395 ON quiz_attempt (user_id)');
        $this->addSql('CREATE TABLE task (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(140) NOT NULL, description CLOB DEFAULT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(10) NOT NULL, due_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, owner_id INTEGER NOT NULL, category_id INTEGER DEFAULT NULL, CONSTRAINT FK_527EDB257E3C61F9 FOREIGN KEY (owner_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_527EDB2512469DE2 FOREIGN KEY (category_id) REFERENCES task_category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_527EDB257E3C61F9 ON task (owner_id)');
        $this->addSql('CREATE INDEX IDX_527EDB2512469DE2 ON task (category_id)');
        $this->addSql('CREATE TABLE task_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(80) NOT NULL, owner_id INTEGER NOT NULL, CONSTRAINT FK_468CF38D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX uniq_category_owner_name ON task_category (owner_id, name)');
        $this->addSql('CREATE INDEX IDX_468CF38D7E3C61F9 ON task_category (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE lesson_completion');
        $this->addSql('DROP TABLE quiz_attempt');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE task_category');
    }
}
