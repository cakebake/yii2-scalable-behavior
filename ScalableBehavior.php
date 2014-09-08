<?php

namespace cakebake\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\base\Behavior;
use yii\helpers\ArrayHelper;

/**
* Scalable Database Schema
*
* Key-Value storage is a very simplistic, but very powerful model.
* Use this behavior to expand your Yii 2 model without changing the structure.
*
* @example
* ~~~
* use cakebake\behaviors\ScalableBehavior;
*
* public function behaviors()
* {
*     return [
*         'scaleable' => [
*             'class' => ScalableBehavior::className(),
*             'scalableAttribute' => 'value',
*             'virtualAttributes' => ['about_me', 'birthday']
*         ],
*     ];
* }
* ~~~
*
* @link https://github.com/cakebake
* @copyright Copyright (c) 2014 cakebake (Jens A.)
* @license LGPL-3.0
* @author cakebake (Jens A.) <cakebake.dev@gmail.com>
* @since 2.0
*/
class ScalableBehavior extends Behavior
{
    /**
    * @var string The owner object's attribute / the column of the corresponding table, which are used as storage for the virtual attributes
    */
    public $scalableAttribute = 'value';

    /**
    * @var array Definition of virtual attributes that are added to the owner object
    */
    public $virtualAttributes = [];

    /**
    * @inheritdoc
    */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'virtualToScalable',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'virtualToScalable',
            ActiveRecord::EVENT_AFTER_FIND => 'scalableToVirtual',
            ActiveRecord::EVENT_AFTER_INSERT => 'scalableToVirtual',
        ];
    }

    /**
    * Converts configured attributes before saving them to database
    *
    * @param mixed $event
    */
    public function virtualToScalable($event)
    {
        if (($this->scalableAttributeName() !== null) &&
        ($this->virtualAttributesNames() !== null)) {

            $data = [];
            foreach ($this->virtualAttributesNames() as $name) {
                if (!empty($this->owner->{$name})) {
                    $data[$name] = $this->owner->{$name};
                }
            }

            $this->owner->{$this->scalableAttributeName()} = $this->convert($data);
        }
    }


    /**
    * Checks if the defined attributes are unserializeable and unserializes their values
    *
    * @param mixed $event
    */
    public function scalableToVirtual($event = null)
    {
        if (($this->scalableAttributeName() !== null) &&
        ($this->virtualAttributesNames() !== null)) {

            $virtualAttributesConf = [];
            foreach ($this->virtualAttributesNames() as $name) {
                $virtualAttributesConf[$name] = '';
            }

            $virtualAttributesNames = ArrayHelper::merge(
                $virtualAttributesConf,
                (($a = $this->unConvert($this->owner->{$this->scalableAttributeName()})) !== null) ? $a : []
            );

            foreach ($virtualAttributesNames as $name => $value) {
                if (in_array($name, $this->virtualAttributesNames())) {
                    $this->owner->{$name} = $value;
                }
            }
        }
    }

    /**
    * Converts some input to a serialized string
    *
    * @param mixed $data Input as an array, String, Object, and so on
    * @return null|string null at fault, string on success
    */
    public function convert($data)
    {
        if (empty($data) || ($out = @serialize($data)) === false)
            return null;

        return $out;
    }

    /**
    * Unconverts a serialized string into an array
    *
    * @param string $data Serialized string to convert
    * @return null|array null at fault, array on success
    */
    public function unConvert($data)
    {
        if (empty($data) || !is_string($data) || ($out = @unserialize($data)) === false)
            return null;

        if (!is_array($out) || empty($out))
            return null;

        return $out;
    }

    /**
    * @var array Internal
    */
    protected $_scalableAttribute = null;

    /**
    * Verifies the configured scalable attribute
    * @return null|string
    */
    public function scalableAttributeName()
    {
        if ($this->_scalableAttribute !== null)
            return $this->_scalableAttribute;

        if (in_array((string)$this->scalableAttribute, $this->objAttributesNames()))
            return $this->_scalableAttribute = $this->scalableAttribute;

        return null;
    }

    /**
    * @var array Internal
    */
    protected $_virtualAttributes = [];

    /**
    * Verifies the configured virtual attributes
    * @return mixed
    */
    public function virtualAttributesNames()
    {
        if (!empty($this->_virtualAttributes))
            return $this->_virtualAttributes;

        if (!is_array($this->virtualAttributes) || empty($this->virtualAttributes))
            return null;

        $attributes = [];
        foreach ($this->virtualAttributes as $a) {
            if (!in_array($a, $this->objAttributesNames()) && !in_array($a, $attributes)) {
                $attributes[] = $a;
            }
        }

        return !empty($attributes) ? $this->_virtualAttributes = $attributes : null;
    }

    /**
    * @var array Internal
    */
    protected $_objAttributes = [];

    /**
    * Get the object's attributes / the columns of the corresponding table
    */
    public function objAttributesNames()
    {
        if (!empty($this->_objAttributes))
            return $this->_objAttributes;

        $attributes = $this->owner->attributes();

        return !empty($attributes) ? $this->_objAttributes = $attributes : null;
    }

    /**
    * @inheritdoc
    */
    public function canGetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->virtualAttributesNames()) ? true : parent::canGetProperty($name, $checkVars);
    }

    /**
    * @var array Internal
    */
    protected $_virtualCache = [];

    /**
    * @inheritdoc
    */
    public function __get($name)
    {
        if (!in_array($name, $this->virtualAttributesNames()))
            return parent::__get($name);

        if (array_key_exists($name, $this->_virtualCache))
            return $this->_virtualCache[$name];

        $this->scalableToVirtual();

        return $this->_virtualCache[$name] = $this->owner->{$name};
    }

    /**
    * @inheritdoc
    */
    public function canSetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->virtualAttributesNames()) ? true : parent::canSetProperty($name, $checkVars);
    }

    /**
    * @inheritdoc
    */
    public function __set($name, $value)
    {
        if (in_array($name, $this->virtualAttributesNames())) {
            $this->owner->{$name} = $value;
        } else {
            parent::__set($name, $value);
        }
    }
}
