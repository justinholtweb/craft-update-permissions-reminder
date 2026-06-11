<?php

namespace justinholtweb\updatepermissionsreminder\controllers;

use Craft;
use craft\web\Controller;
use justinholtweb\updatepermissionsreminder\Plugin;
use yii\web\Response;

/**
 * Control panel actions for listing and dismissing reminders.
 *
 * Only admins can manage reminders, since only admins edit permissions.
 */
class RemindersController extends Controller
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireAdmin(false);

        return true;
    }

    /**
     * Lists pending reminders in the plugin's CP section.
     */
    public function actionIndex(): Response
    {
        return $this->renderTemplate('update-permissions-reminder/_index', [
            'reminders' => Plugin::getInstance()->reminders->getPending(),
            'plugin' => Plugin::getInstance(),
        ]);
    }

    /**
     * Dismisses a single reminder.
     */
    public function actionDismiss(): Response
    {
        $this->requirePostRequest();

        $id = (int)$this->request->getRequiredBodyParam('id');
        Plugin::getInstance()->reminders->dismiss($id);

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'remaining' => Plugin::getInstance()->reminders->getPendingCount(),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('update-permissions-reminder', 'Reminder dismissed.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Dismisses every pending reminder.
     */
    public function actionDismissAll(): Response
    {
        $this->requirePostRequest();

        Plugin::getInstance()->reminders->dismissAll();

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true, 'remaining' => 0]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('update-permissions-reminder', 'All reminders dismissed.'));

        return $this->redirectToPostedUrl();
    }
}
