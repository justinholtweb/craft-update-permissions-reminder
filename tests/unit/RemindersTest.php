<?php

namespace justinholtweb\updatepermissionsreminder\tests\unit;

use justinholtweb\updatepermissionsreminder\services\Reminders;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Craft-free static logic in the Reminders service:
 * change-type definitions, message generation, permission routing, and
 * settings-driven watched-type filtering.
 */
final class RemindersTest extends TestCase
{
    /**
     * Every settings flag that gates a change type, all enabled.
     *
     * @return array<string, bool>
     */
    private function allWatched(): array
    {
        return [
            'watchSections' => true,
            'watchEntryTypes' => true,
            'watchGlobalSets' => true,
            'watchVolumes' => true,
            'watchCategoryGroups' => true,
            'watchUserGroups' => true,
            'watchPlugins' => true,
        ];
    }

    // ---- definitions --------------------------------------------------------

    public function testChangeTypesAreComplete(): void
    {
        $this->assertEqualsCanonicalizing(
            [
                Reminders::TYPE_SECTION,
                Reminders::TYPE_ENTRY_TYPE,
                Reminders::TYPE_GLOBAL_SET,
                Reminders::TYPE_VOLUME,
                Reminders::TYPE_CATEGORY_GROUP,
                Reminders::TYPE_USER_GROUP,
                Reminders::TYPE_PLUGIN,
            ],
            Reminders::changeTypes(),
        );
    }

    public function testEveryTypeHasLabelAttributeAndUrl(): void
    {
        foreach (Reminders::changeTypes() as $type) {
            $this->assertTrue(Reminders::isKnownType($type));
            $this->assertNotSame('item', Reminders::label($type), "Missing label for {$type}");
            $this->assertNotSame('', Reminders::settingsAttribute($type), "Missing settings attribute for {$type}");
            $this->assertNotSame('', Reminders::permissionUrl($type), "Missing permission URL for {$type}");
        }
    }

    public function testUnknownTypeIsHandledGracefully(): void
    {
        $this->assertFalse(Reminders::isKnownType('nope'));
        $this->assertSame('item', Reminders::label('nope'));
        $this->assertSame('', Reminders::settingsAttribute('nope'));
        $this->assertSame('settings/users', Reminders::permissionUrl('nope'));
    }

    // ---- messages -----------------------------------------------------------

    public function testMessageIncludesItemNameAndLabel(): void
    {
        $message = Reminders::messageFor(Reminders::TYPE_SECTION, 'Blog');

        $this->assertStringContainsString('Blog', $message);
        $this->assertStringContainsString('section', $message);
        $this->assertStringContainsString('permission', strtolower($message));
    }

    public function testPluginMessageIsSpecific(): void
    {
        $message = Reminders::messageFor(Reminders::TYPE_PLUGIN, 'Commerce');

        $this->assertStringContainsString('Commerce', $message);
        $this->assertStringContainsString('installed', $message);
    }

    public function testUserGroupMessageIsSpecific(): void
    {
        $message = Reminders::messageFor(Reminders::TYPE_USER_GROUP, 'Editors');

        $this->assertStringContainsString('Editors', $message);
        $this->assertStringContainsString('user group', $message);
    }

    public function testMessageFallsBackToLabelWhenNameBlank(): void
    {
        $message = Reminders::messageFor(Reminders::TYPE_VOLUME, '   ');

        // Falls back to the type label ("asset volume") rather than an empty name.
        $this->assertStringContainsString('asset volume', $message);
        $this->assertStringNotContainsString('“”', $message);
    }

    // ---- watched-type filtering --------------------------------------------

    public function testIsWatchedReflectsSettings(): void
    {
        $watchMap = $this->allWatched();
        $watchMap['watchPlugins'] = false;

        $this->assertTrue(Reminders::isWatched(Reminders::TYPE_SECTION, $watchMap));
        $this->assertFalse(Reminders::isWatched(Reminders::TYPE_PLUGIN, $watchMap));
    }

    public function testWatchedTypesReturnsOnlyEnabled(): void
    {
        $watchMap = [
            'watchSections' => true,
            'watchEntryTypes' => false,
            'watchGlobalSets' => false,
            'watchVolumes' => true,
            'watchCategoryGroups' => false,
            'watchUserGroups' => false,
            'watchPlugins' => false,
        ];

        $this->assertSame(
            [Reminders::TYPE_SECTION, Reminders::TYPE_VOLUME],
            Reminders::watchedTypes($watchMap),
        );
    }

    public function testWatchedTypesEmptyWhenAllDisabled(): void
    {
        $this->assertSame([], Reminders::watchedTypes([]));
    }

    public function testAllTypesWatchedWhenAllEnabled(): void
    {
        $this->assertEqualsCanonicalizing(
            Reminders::changeTypes(),
            Reminders::watchedTypes($this->allWatched()),
        );
    }

    public function testUnknownTypeIsNeverWatched(): void
    {
        $this->assertFalse(Reminders::isWatched('nope', $this->allWatched()));
    }
}
