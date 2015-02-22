# Dialect

Dialect provides JSON datatype support for the [Eloquent ORM](http://laravel.com/docs/eloquent).

## Installation

Require this package in your `composer.json` file:

`"darrylkuhn/dialect": "dev-master"`

...then run `composer update` to download the package to your vendor directory.

## Usage

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
 
You can also establish relationships on a model like this:
```php
public function user()
{
    return $this->hasOne( 'User', 'id', "json_data->>'user_id'" );
}
```
The one caveat is when you're setting an attribute not previously set already in the JSON document then no setter is created. For example if you defined a json column called "additional_details" which had a value of "{'foo':'bar'}" and wanted to set 'fizz' so you would need to call:
```php
$referral->setJsonAttribute( 'additional_details', 'fizz', 'buzz' );
```
At this point this implementation is pretty bare bones and only supports PostgreSQL. There are lots of opportunities to enhance and improve. If you're interested in contributing please submit merge/pull requests.