<?php

namespace justinholtweb\updatepermissionsreminder\models;

use craft\base\Model;

/**
 * A pending reminder describing one schema change that may require a
 * permissions review.
 */
class Reminder extends Model
{
    public ?int $id = null;
    public string $type = '';
    public string $itemName = '';
    public string $message = '';
    public string $ctaUrl = 'settings/users';
    public bool $dismissed = false;
    public ?string $dateCreated = null;
}
