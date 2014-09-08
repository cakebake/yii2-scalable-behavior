Scalable Database Schema
========================
Key-Value storage is a very simplistic, but very powerful model.
Use this behavior to expand your Yii 2 model without changing the structure.

Data can be queried and saved with "virtual attributes".
These are stored serialized in a configured table column.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist cakebake/yii2-scalable-behavior "*"
```

or add

```
"cakebake/yii2-scalable-behavior": "*"
```

to the require section of your `composer.json` file.

Preparation
-----

Create a column in your desired table. It is recommended to use the type "text" or "longtext"
in order to save as much data as possible.

Usage
-----

Once the extension is installed, simply use it in your model by adding:

```php
    use cakebake\behaviors\ScalableBehavior;

    public function behaviors()
    {
        return [
            ...
            'scaleable' => [
                'class' => ScalableBehavior::className(),
                'scalableAttribute' => 'value', // The owner object's attribute / the column of the corresponding table, which are used as storage for the virtual attributes
                'virtualAttributes' => ['about_me', 'birthday'] // Definition of virtual attributes that are added to the owner object
            ],
            ...
        ];
    }
```

Now we can proceed similarly with the virtual attributes like normal.

```php
    public function rules()
    {
        return [
            ['about_me', 'required'],
            ['about_me', 'string'],
            ['birthday', 'string', 'max' => 60],
        ];
    }
```

Piece of advice
-----

This technique should be used only for metadata.
Improper use may change the application performance negatively.