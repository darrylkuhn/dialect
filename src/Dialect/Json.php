<?php namespace Eloquent\Dialect;

use stdClass;

trait Json
{
    /**
     * List of known PSQL JSON operators
     *
     * @var array
     */
    public static $jsonOperators = [
        '->',
        '->>',
        '#>',
        '#>>' ];

    /**
     * @var array
     */
    private $jsonAttributes = [];

    /**
     * Create a new model instance that is existing.
     * Overrides parent to set Json columns.
     *
     * @param  array  $attributes
     * @param  string|null  $connection
     * @return static
     */
    public function newFromBuilder($attributes = array(), $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);
        $model->inspectJsonColumns();
        return $model;
    }

    /**
     * Decodes each of the declared JSON attributes and records the attributes
     * on each
     *
     * @return void
     */
    public function inspectJsonColumns()
    {
        foreach ($this->jsonColumns as $col) {
            $this->hidden[] = $col;
            $obj = json_decode($this->$col);

            if (is_object($obj)) {
                foreach ($obj as $key => $value) {
                    $this->flagJsonAttribute($key, $col);
                    $this->appends[] = $key;
                }
            }
        }

    }

    /**
     * Record that a given JSON element is found on a particular column
     *
     * @param string $key
     * @param string $col
     *
     * @return void
     */
    public function flagJsonAttribute($key, $col)
    {
        $this->jsonAttributes[$key] = $col;
    }

    /**
     * Include JSON column in the list of attributes that have a get mutator.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        $jsonPattern  = '/' . implode('|', self::$jsonOperators) . '/' ;

        if (array_key_exists($key, $this->jsonAttributes) !== false) {
            return true;
        } // In some cases the key specified may not be a simple key but rather a
        // JSON expression (e.g. "jsonField->'some_key'). A common case would
        // be when specifying a relation key. As such we test for JSON
        // operators and expect a mutator if this is a JSON expression
        elseif (preg_match($jsonPattern, $key) != false) {
            return true;
        }

        return parent::hasGetMutator($key);
    }

    /**
     * Include the JSON attributes in the list of mutated attributes for a
     * given instance.
     *
     * @return array
     */
    public function getMutatedAttributes()
    {
        $attributes = parent::getMutatedAttributes();
        $jsonAttributes = array_keys($this->jsonAttributes);
        return array_merge($attributes, $jsonAttributes);
    }

    /**
     * Check if the key is a known json attribute and return that value
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        $jsonPattern  = '/' . implode('|', self::$jsonOperators) . '/' ;

        // Test for JSON operators and reduce to end element
        /* TODO: This only really works for 1-level deep. Should it be more? */
        $isJson = false;

        if (preg_match($jsonPattern, $key)) {
            $elems = preg_split($jsonPattern, $key);
            $key = end($elems);
            $key = str_replace([">", "'"], "", $key);

            $isJson = true;
        }

        if (array_key_exists($key, $this->jsonAttributes) != false) {
            $obj = json_decode($this->{$this->jsonAttributes[$key]});
            if($obj && isset($obj->$key)) {
                return $obj->$key;
            }
            return null;
        } elseif ($isJson) {
            return null;
        }


        return parent::mutateAttribute($key, $value);
    }

    /**
     * Set a given attribute on the known JSON elements.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if (array_key_exists($key, $this->jsonAttributes) !== false) {
            $this->setJsonAttribute($this->jsonAttributes[$key], $key, $value);
            return;
        }

        parent::setAttribute($key, $value);
    }

    /**
     * Set a given attribute on the known JSON elements.
     *
     * @param string $attribute
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setJsonAttribute($attribute, $key, $value)
    {
        $obj = json_decode($this->{$attribute});
        if(!is_object($obj))
            $obj = new stdClass;
        
        $obj->$key = $value;
        $this->flagJsonAttribute($key, $attribute);
        $this->{$attribute} = json_encode($obj);
        return;
    }
}
