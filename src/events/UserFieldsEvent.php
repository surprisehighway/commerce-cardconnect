<?php
namespace jmauzyk\commerce\cardconnect\events;

use yii\base\Event;

class UserFieldsEvent extends Event
{
    public $order;
    public $userFields;
}
