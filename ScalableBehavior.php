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
*             ...
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
    * @var int Compression of the virtual attributes. Can be given as 0 for no compression up to 9 for maximum compression.
    */
    public $compressionLevel = 0;

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
        if (($scalableAttribute = $this->scalableAttributeName()) === null)
            return false;

        if ($this->virtualAttributesNames() === null)
            return false;

        $virtualAttributesArray = [];
        foreach ($this->virtualAttributesNames() as $virtualAttribute) {
            $virtualAttributesArray[$virtualAttribute] = $this->owner->{$virtualAttribute};
        }

        if (($scalableValue = $this->convert($virtualAttributesArray)) !== false) {
            $this->owner->{$scalableAttribute} = $scalableValue;
        }
    }


    /**
    * Checks if the defined attributes are unserializeable and unserializes their values
    *
    * @param mixed $event
    */
    public function scalableToVirtual($event)
    {
        if (($scalableAttribute = $this->scalableAttributeName()) === null)
            return false;

        if ($this->virtualAttributesNames() === null)
            return false;

        $virtualAttributesNames = [];
        foreach ($this->virtualAttributesNames() as $virtualAttribute) {
            $virtualAttributesNames[$virtualAttribute] = '';
        }

        $virtualAttributesArray = ArrayHelper::merge(
            $virtualAttributesNames,
            (($a = $this->unConvert($this->owner->{$scalableAttribute})) !== false) ? $a : []
        );

        foreach ($virtualAttributesArray as $key => $value) {
            $this->owner->{$key} = $value;
        }
    }

    /**
    * Converts data
    *
    * @param mixed $data
    * @return string|false
    */
    public function convert($data)
    {
        if (empty($data))
            return false;

        if (($data = @serialize($data)) === false)
            return false;

        if ((int)$this->compressionLevel != 0 && function_exists('gzcompress')) {
            $data = (($compressed = @gzcompress($data, (int)$this->compressionLevel)) !== false) ? $compressed : $data;
        }

        return $data;
    }

    /**
    * Unconverts data
    *
    * @param mixed $data
    * @return mixed
    */
    public function unConvert($data)
    {
        if (empty($data))
            return false;

        if (($data = @unserialize($data)) === false)
            return false;

        if ((int)$this->compressionLevel != 0 && function_exists('gzuncompress')) {
            $data = (($uncompressed = @gzuncompress($data, (int)$this->compressionLevel)) !== false) ? $uncompressed : $data;
        }

        if (!is_array($data) || empty($data))
            return false;

        return $data;
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

        if (!is_string($this->scalableAttribute) || empty($this->scalableAttribute))
            return null;

        if (!in_array($this->scalableAttribute, $this->objAttributesNames()))
            return null;

        return $this->scalableAttribute;
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
    public function __set($name, $value)
    {
        if (in_array($name, $this->virtualAttributesNames())) {
            $this->owner->{$name} = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
    * @inheritdoc
    */
    public function canSetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->virtualAttributesNames()) ? true : parent::canSetProperty($name, $checkVars);
    }
}
