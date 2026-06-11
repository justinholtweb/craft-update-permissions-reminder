<?php

namespace justinholtweb\updatepermissionsreminder\models;

use craft\base\Model;

/**
 * Update Permissions Reminder settings.
 *
 * The `watch*` flags map 1:1 to the change types in
 * {@see \justinholtweb\updatepermissionsreminder\services\Reminders}. Toggling
 * one off stops new reminders of that type from being recorded.
 */
class Settings extends Model
{
    // Display
    public bool $enableStickyBar = true;
    public bool $enableModal = true;

    // Which schema changes trigger a reminder
    public bool $watchSections = true;
    public bool $watchEntryTypes = true;
    public bool $watchGlobalSets = true;
    public bool $watchVolumes = true;
    public bool $watchCategoryGroups = true;
    public bool $watchUserGroups = true;
    public bool $watchPlugins = true;

    /**
     * @return array<string, bool> Map of settings attribute => enabled, as
     *                             consumed by Reminders::watchedTypes().
     */
    public function watchMap(): array
    {
        return [
            'watchSections' => $this->watchSections,
            'watchEntryTypes' => $this->watchEntryTypes,
            'watchGlobalSets' => $this->watchGlobalSets,
            'watchVolumes' => $this->watchVolumes,
            'watchCategoryGroups' => $this->watchCategoryGroups,
            'watchUserGroups' => $this->watchUserGroups,
            'watchPlugins' => $this->watchPlugins,
        ];
    }

    protected function defineRules(): array
    {
        return [
            [
                [
                    'enableStickyBar', 'enableModal',
                    'watchSections', 'watchEntryTypes', 'watchGlobalSets',
                    'watchVolumes', 'watchCategoryGroups', 'watchUserGroups',
                    'watchPlugins',
                ],
                'boolean',
            ],
        ];
    }
}
