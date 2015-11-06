# Dialect

[![Build Status](https://travis-ci.org/darrylkuhn/dialect.svg?branch=master)](https://travis-ci.org/darrylkuhn/dialect) [![Code Climate](https://codeclimate.com/github/darrylkuhn/dialect/badges/gpa.svg)](https://codeclimate.com/github/darrylkuhn/dialect) [![Test Coverage](https://codeclimate.com/github/darrylkuhn/dialect/badges/coverage.svg)](https://codeclimate.com/github/darrylkuhn/dialect)

Dialect provides JSON datatype support for the [Eloquent ORM](http://laravel.com/docs/eloquent). At this point this implementation is pretty bare bones and has been demonstrated to work with PostgreSQL and MySQL. There are lots of opportunities to enhance and improve. If you're interested in contributing please submit merge/pull requests.

## Installation

Require this package in your `composer.json` file:

`"darrylkuhn/dialect": "dev-master"`

...then run `composer update` to download the package to your vendor directory.

## Usage
### The Basics

The feature is exposed through a trait called which allows you to define attributes on the model which are of the json datatype. When the model is read in it will parse the JSON document and set up getters and setters for each top level attribute making it easy to interact with the various attributes within the document. For example we could create a Photos model like this:

```php
class Photo extends Eloquent
{
    use Eloquent\Dialect\Json;
    protected $jsonColumns = ['json_data'];
}
```
And then this:
```php
$attr = json_decode($photo->json_data);
$attr->key = $value;
$photo->json_data = json_encode($attr);
```
becomes this:
```php
$photo->key = value;
```
Also when calling the toArray() method the attributes are moved to the top level and the 'json_attributes' column is hidden. This essentially hides away the fact that you're using the json datatype and makes it look like we're working with attributes directly.

### Relations
You can also establish relationships on a model like this (only supported in PostgreSQL):
```php
public function user()
{
    return $this->hasOne( 'User', 'id', "json_data->>'user_id'" );
}
```

### Structure Hinting
Sometimes you may have an empty or partially populated record in which case the trait cannot automatically detect and create getters/setters, etc... When getting or setting an attribute not previously set in the JSON document you'll get an exception. You have two choices to deal with this. You can hint at the full structure as in the example below:
```php
class Photo extends Eloquent
{
    use Eloquent\Dialect\Json;
    protected $jsonColumns = ['json_data'];

    public function __construct()
    {
        parent::__construct();
        $this->hintJsonStructure( 'json_data', '{"foo":null}' );
    }
}
```
Once you create a hint you will be able to make calls to get and set json attributes e.g. `$photo->foo = 'bar';` regardless of whether or not they are already defined in the underlying db record. Alternatly if you prefer not to hint structures then you may call `setJsonAttribute()`. For example if you defined a json column called "json_data" and wanted to set an attribute called 'fizz' so you could call:
```php
$photo->setJsonAttribute( 'json_data', 'fizz', 'buzz' );
```
### Showing/Hiding Attributes
One of the aims of the project is to make json attributes "first class" citizens of the model. This means by default we add the attributes to the models appends array so that when you call `$model->toArray()` or `$model->toJson()` the attribute shows up as a part of the structure like a normal attribute. By default we also hide away the json column holding the underlying data. Both of these settings can be changed using the `showJsonColumns()` and `showJsonAttributes()` as shown below:
```php
class Photo extends Eloquent
{
    use Eloquent\Dialect\Json;
    protected $jsonColumns = ['json_data'];

    public function __construct()
    {
        parent::__construct();
        $this->showJsonColumns(true);
        $this->showJsonAttributes(false);
    }
}
```