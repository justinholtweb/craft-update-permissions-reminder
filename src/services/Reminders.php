<?php

namespace justinholtweb\updatepermissionsreminder\services;

use craft\base\Component;
use justinholtweb\updatepermissionsreminder\models\Reminder;
use justinholtweb\updatepermissionsreminder\records\ReminderRecord;

/**
 * Records and manages permission-review reminders.
 *
 * The static half of this class (definitions, labels, message text, watched-type
 * filtering) is intentionally free of any Craft container access so it can be
 * unit tested in isolation. The instance half persists reminders to the
 * database.
 */
class Reminders extends Component
{
    public const TYPE_SECTION = 'section';
    public const TYPE_ENTRY_TYPE = 'entryType';
    public const TYPE_GLOBAL_SET = 'globalSet';
    public const TYPE_VOLUME = 'volume';
    public const TYPE_CATEGORY_GROUP = 'categoryGroup';
    public const TYPE_USER_GROUP = 'userGroup';
    public const TYPE_PLUGIN = 'plugin';

    /**
     * Definition of every change type the plugin understands.
     *
     * - `label`: human-readable singular noun used in messages
     * - `settingsAttribute`: the Settings flag that enables/disables this type
     * - `permissionUrl`: where an admin should go to act on it
     *
     * @var array<string, array{label: string, settingsAttribute: string, permissionUrl: string}>
     */
    private const DEFINITIONS = [
        self::TYPE_SECTION => [
            'label' => 'section',
            'settingsAttribute' => 'watchSections',
            'permissionUrl' => 'settings/users',
        ],
        self::TYPE_ENTRY_TYPE => [
            'label' => 'entry type',
            'settingsAttribute' => 'watchEntryTypes',
            'permissionUrl' => 'settings/users',
        ],
        self::TYPE_GLOBAL_SET => [
            'label' => 'global set',
            'settingsAttribute' => 'watchGlobalSets',
            'permissionUrl' => 'settings/users',
        ],
        self::TYPE_VOLUME => [
            'label' => 'asset volume',
            'settingsAttribute' => 'watchVolumes',
            'permissionUrl' => 'settings/users',
        ],
        self::TYPE_CATEGORY_GROUP => [
            'label' => 'category group',
            'settingsAttribute' => 'watchCategoryGroups',
            'permissionUrl' => 'settings/users',
        ],
        self::TYPE_USER_GROUP => [
            'label' => 'user group',
            'settingsAttribute' => 'watchUserGroups',
            'permissionUrl' => 'settings/users',
        ],
        self::TYPE_PLUGIN => [
            'label' => 'plugin',
            'settingsAttribute' => 'watchPlugins',
            'permissionUrl' => 'settings/users',
        ],
    ];

    // region Pure logic (no Craft access — unit tested)

    /**
     * @return string[] Every supported change type key.
     */
    public static function changeTypes(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    public static function isKnownType(string $type): bool
    {
        return isset(self::DEFINITIONS[$type]);
    }

    public static function label(string $type): string
    {
        return self::DEFINITIONS[$type]['label'] ?? 'item';
    }

    public static function settingsAttribute(string $type): string
    {
        return self::DEFINITIONS[$type]['settingsAttribute'] ?? '';
    }

    public static function permissionUrl(string $type): string
    {
        return self::DEFINITIONS[$type]['permissionUrl'] ?? 'settings/users';
    }

    /**
     * Builds the reminder message shown to admins.
     */
    public static function messageFor(string $type, string $name): string
    {
        $name = trim($name) !== '' ? $name : self::label($type);

        return match ($type) {
            self::TYPE_PLUGIN => sprintf(
                'The “%s” plugin was installed. It may add new permissions — review your user groups to grant access.',
                $name,
            ),
            self::TYPE_USER_GROUP => sprintf(
                'A new user group “%s” was created. Review its permissions so it has the access it needs.',
                $name,
            ),
            default => sprintf(
                'A new %s “%s” was added. Review your user groups to grant the appropriate permissions.',
                self::label($type),
                $name,
            ),
        };
    }

    /**
     * Whether a change type should trigger reminders, given a settings watch map.
     *
     * @param array<string, bool> $watchMap settingsAttribute => enabled
     */
    public static function isWatched(string $type, array $watchMap): bool
    {
        $attribute = self::settingsAttribute($type);

        return $attribute !== '' && !empty($watchMap[$attribute]);
    }

    /**
     * Filters all change types down to the ones enabled in a watch map.
     *
     * @param array<string, bool> $watchMap settingsAttribute => enabled
     * @return string[]
     */
    public static function watchedTypes(array $watchMap): array
    {
        return array_values(array_filter(
            self::changeTypes(),
            static fn(string $type): bool => self::isWatched($type, $watchMap),
        ));
    }

    // endregion

    // region Persistence

    /**
     * Records a reminder for a schema change, skipping exact duplicates that are
     * still pending.
     */
    public function record(string $type, string $name): ?Reminder
    {
        if (!self::isKnownType($type)) {
            return null;
        }

        $existing = ReminderRecord::find()
            ->where(['type' => $type, 'itemName' => $name, 'dismissed' => false])
            ->one();

        if ($existing instanceof ReminderRecord) {
            return $this->toModel($existing);
        }

        $record = new ReminderRecord();
        $record->type = $type;
        $record->itemName = $name;
        $record->message = self::messageFor($type, $name);
        $record->ctaUrl = self::permissionUrl($type);
        $record->dismissed = false;
        $record->save(false);

        return $this->toModel($record);
    }

    /**
     * @return Reminder[]
     */
    public function getPending(): array
    {
        $records = ReminderRecord::find()
            ->where(['dismissed' => false])
            ->orderBy(['dateCreated' => SORT_DESC])
            ->all();

        return array_map([$this, 'toModel'], $records);
    }

    public function getPendingCount(): int
    {
        return (int)ReminderRecord::find()->where(['dismissed' => false])->count();
    }

    public function dismiss(int $id): bool
    {
        $record = ReminderRecord::findOne(['id' => $id]);

        if (!$record instanceof ReminderRecord) {
            return false;
        }

        $record->dismissed = true;

        return $record->save(false);
    }

    public function dismissAll(): int
    {
        return ReminderRecord::updateAll(['dismissed' => true], ['dismissed' => false]);
    }

    private function toModel(ReminderRecord $record): Reminder
    {
        $model = new Reminder();
        $model->id = (int)$record->id;
        $model->type = (string)$record->type;
        $model->itemName = (string)$record->itemName;
        $model->message = (string)$record->message;
        $model->ctaUrl = (string)$record->ctaUrl;
        $model->dismissed = (bool)$record->dismissed;
        $model->dateCreated = $record->dateCreated;

        return $model;
    }

    // endregion
}
