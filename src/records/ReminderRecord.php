<?php

namespace justinholtweb\updatepermissionsreminder\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $type
 * @property string $itemName
 * @property string $message
 * @property string $ctaUrl
 * @property bool $dismissed
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 */
class ReminderRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%updatepermissionsreminder_reminders}}';
    }
}
