<?php

namespace justinholtweb\updatepermissionsreminder;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\CategoryGroupEvent;
use craft\events\EntryTypeEvent;
use craft\events\GlobalSetEvent;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\SectionEvent;
use craft\events\TemplateEvent;
use craft\events\UserGroupEvent;
use craft\events\VolumeEvent;
use craft\services\Categories;
use craft\services\Entries;
use craft\services\Globals;
use craft\services\Plugins;
use craft\services\UserGroups;
use craft\services\Volumes;
use craft\web\UrlManager;
use craft\web\View;
use justinholtweb\updatepermissionsreminder\models\Settings;
use justinholtweb\updatepermissionsreminder\services\Reminders;
use justinholtweb\updatepermissionsreminder\web\assets\reminder\ReminderAsset;
use yii\base\Event;

/**
 * Update Permissions Reminder plugin.
 *
 * Watches for schema changes that introduce new user permissions and reminds
 * admins, via a sticky bar and modal in the control panel, to review user group
 * permissions.
 *
 * @property-read Reminders $reminders
 * @property-read Settings $settings
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '5.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public static function config(): array
    {
        return [
            'components' => [
                'reminders' => Reminders::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->_registerCpRoutes();
        $this->_registerSchemaListeners();
        $this->_registerCpInjection();
    }

    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['label'] = Craft::t('update-permissions-reminder', 'Permission Reminders');

        $count = 0;
        if (Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftUpdatePending()) {
            try {
                $count = $this->reminders->getPendingCount();
            } catch (\Throwable) {
                $count = 0;
            }
        }
        if ($count > 0) {
            $item['badgeCount'] = $count;
        }

        return $item;
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('update-permissions-reminder/settings', [
            'settings' => $this->getSettings(),
            'plugin' => $this,
        ]);
    }

    private function _registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['update-permissions-reminder'] = 'update-permissions-reminder/reminders/index';
            }
        );
    }

    /**
     * Records a reminder whenever a watched schema change introduces new
     * permissions.
     */
    private function _registerSchemaListeners(): void
    {
        $watch = fn(string $type, string $name): ?\justinholtweb\updatepermissionsreminder\models\Reminder => $this->_maybeRecord($type, $name);

        Event::on(Entries::class, Entries::EVENT_AFTER_SAVE_SECTION, function (SectionEvent $e) use ($watch) {
            if ($e->isNew) {
                $watch(Reminders::TYPE_SECTION, $e->section->name);
            }
        });

        Event::on(Entries::class, Entries::EVENT_AFTER_SAVE_ENTRY_TYPE, function (EntryTypeEvent $e) use ($watch) {
            if ($e->isNew) {
                $watch(Reminders::TYPE_ENTRY_TYPE, $e->entryType->name);
            }
        });

        Event::on(Globals::class, Globals::EVENT_AFTER_SAVE_GLOBAL_SET, function (GlobalSetEvent $e) use ($watch) {
            if ($e->isNew) {
                $watch(Reminders::TYPE_GLOBAL_SET, $e->globalSet->name);
            }
        });

        Event::on(Volumes::class, Volumes::EVENT_AFTER_SAVE_VOLUME, function (VolumeEvent $e) use ($watch) {
            if ($e->isNew) {
                $watch(Reminders::TYPE_VOLUME, $e->volume->name);
            }
        });

        Event::on(Categories::class, Categories::EVENT_AFTER_SAVE_GROUP, function (CategoryGroupEvent $e) use ($watch) {
            if ($e->isNew) {
                $watch(Reminders::TYPE_CATEGORY_GROUP, $e->categoryGroup->name);
            }
        });

        Event::on(UserGroups::class, UserGroups::EVENT_AFTER_SAVE_USER_GROUP, function (UserGroupEvent $e) use ($watch) {
            if ($e->isNew) {
                $watch(Reminders::TYPE_USER_GROUP, $e->userGroup->name);
            }
        });

        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN, function (PluginEvent $e) use ($watch) {
            // Ignore this plugin installing itself.
            if ($e->plugin === $this) {
                return;
            }
            $watch(Reminders::TYPE_PLUGIN, $e->plugin->name);
        });
    }

    private function _maybeRecord(string $type, ?string $name): ?\justinholtweb\updatepermissionsreminder\models\Reminder
    {
        /** @var Settings $settings */
        $settings = $this->getSettings();

        if (!Reminders::isWatched($type, $settings->watchMap())) {
            return null;
        }

        return $this->reminders->record($type, (string)$name);
    }

    /**
     * Injects the sticky bar / modal asset bundle on CP pages for admins when
     * there are pending reminders.
     */
    private function _registerCpInjection(): void
    {
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            function (TemplateEvent $event) {
                $this->_injectReminderUi();
            }
        );
    }

    private function _injectReminderUi(): void
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsCpRequest() || $request->getIsAjax()) {
            return;
        }

        /** @var Settings $settings */
        $settings = $this->getSettings();
        if (!$settings->enableStickyBar && !$settings->enableModal) {
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();
        if (!$user || !$user->admin) {
            return;
        }

        $pending = $this->reminders->getPending();
        if (empty($pending)) {
            return;
        }

        $reminders = array_map(static fn($r) => [
            'id' => $r->id,
            'message' => $r->message,
        ], $pending);

        $config = [
            'reminders' => $reminders,
            'reviewUrl' => \craft\helpers\UrlHelper::cpUrl('settings/users'),
            'sectionUrl' => \craft\helpers\UrlHelper::cpUrl('update-permissions-reminder'),
            'dismissUrl' => \craft\helpers\UrlHelper::actionUrl('update-permissions-reminder/reminders/dismiss'),
            'dismissAllUrl' => \craft\helpers\UrlHelper::actionUrl('update-permissions-reminder/reminders/dismiss-all'),
            'csrfTokenName' => Craft::$app->getConfig()->getGeneral()->csrfTokenName,
            'csrfTokenValue' => Craft::$app->getRequest()->getCsrfToken(),
            'showBar' => $settings->enableStickyBar,
            'showModal' => $settings->enableModal,
        ];

        $view = Craft::$app->getView();
        $view->registerAssetBundle(ReminderAsset::class);
        $view->registerJs(
            'window.UpdatePermissionsReminder && window.UpdatePermissionsReminder.init(' .
            \craft\helpers\Json::encode($config) .
            ');'
        );
    }
}
