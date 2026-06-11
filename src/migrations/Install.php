<?php

namespace justinholtweb\updatepermissionsreminder\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $table = '{{%updatepermissionsreminder_reminders}}';

        if ($this->db->tableExists($table)) {
            return true;
        }

        $this->createTable($table, [
            'id' => $this->primaryKey(),
            'type' => $this->string(40)->notNull(),
            'itemName' => $this->string(255)->notNull()->defaultValue(''),
            'message' => $this->text()->notNull(),
            'ctaUrl' => $this->string(255)->notNull()->defaultValue('settings/users'),
            'dismissed' => $this->boolean()->notNull()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(
            'idx_updatepermissionsreminder_dismissed',
            $table,
            ['dismissed'],
        );

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%updatepermissionsreminder_reminders}}');

        return true;
    }
}
