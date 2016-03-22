<?php

namespace Eloquent\Dialect;

trait Json
{
    /**
     * List of known PSQL JSON operators. This is used when determining if
     * a column reference matches that of a JSON pattern (e.g.
     * test_column->>'value').
     *
     * @var array
     */
    public static $jsonOperators = [
        '->',
        '->>',
        '#>',
        '#>>', ];

    /**
     * Holds the map of attributes and the JSON colums they are stored in. This
     * will take the form of:
     *  [ 'json_element_1' => 'original_column',
     *    'json_element_2' => 'original_column' ].
     *
     * @var array
     */
    private $jsonAttributes = [];

    /**
     * Holds a list of column names and the structure they *may* contain (e.g.
     * ['json_column' => "{'foo':null}"].
     *
     * @var array
     */
    private $hintedJsonAttributes = [];

    /**
     * By default this trait will hide the json columns when rendering the
     * model using toArray() or toJson() only exposing the underlying JSON
     * parameters as top level paremters on the model. Set this parameter to
     * true if you want to change that behavior.
     *
     * @var bool
     */
    private $showJsonColumns = false;

    /**
     * By default this trait will append the json attributes when rendering the
     * model using toArray() or toJson(). Set this parameter to false if you
     * want to change that behavior.
     *
     * @var bool
     */
    private $showJsonAttributes = true;

    /**
     * Create a new model instance that is existing.
     * Overrides parent to set Json columns.
     *
     * @param array       $attributes
     * @param string|null $connection
     *
     * @return static
     */
    public function newFromBuilder($attributes = array(), $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);
        $model->inspectJsonColumns();
        $model->addHintedAttributes();

        return $model;
    }

    /**
     * Decodes each of the declared JSON attributes and records the attributes
     * on each.
     */
    public function inspectJsonColumns()
    {
        foreach ($this->jsonColumns as $col) {
            if (!$this->showJsonColumns) {
                $this->hidden[] = $col;
            }
            $obj = json_decode($this->$col);

            if (is_object($obj)) {
                foreach ($obj as $key => $value) {
                    $this->flagJsonAttribute($key, $col);
                    if ($this->showJsonAttributes) {
                        $this->appends[] = $key;
                    }
                }
            }
        }
    }

    /**
     * Schema free data architecture give us tons of flexibility (yay) but
     * makes it hard to inspect a structure and build getters/setters.
     * Therefore you can "hint" the structure to make life easier.
     */
    public function addHintedAttributes()
    {
        foreach ($this->hintedJsonAttributes as $col => $structure) {
            if (!$this->showJsonColumns) {
                $this->hidden[] = $col;
            }

            if (json_decode($structure) === null) {
                throw new InvalidJsonException();
            }

            $obj = json_decode($structure);

            if (is_object($obj)) {
                foreach ($obj as $key => $value) {
                    $this->flagJsonAttribute($key, $col);
                    if ($this->showJsonAttributes) {
                        $this->appends[] = $key;
                    }
                }
            }
        }
    }

    /**
     * Sets a hint for a given column.
     *
     * @param string $column    name of column that we're hinting
     * @param string $structure json encoded structure
     *
     * @throws InvalidJsonException
     */
    public function hintJsonStructure($column, $structure)
    {
        if (json_decode($structure) === null) {
            throw new InvalidJsonException();
        }

        $this->hintedJsonAttributes[$column] = $structure;

        // Run the call to add hinted attributes to the internal json
        // attributes array. This allows callers to get/set parameters when
        // working with new models
        $this->addHintedAttributes();
    }

    /**
     * Record that a given JSON element is found on a particular column.
     *
     * @param string $key attribute name within the JSON column
     * @param string $col name of JSON column
     */
    public function flagJsonAttribute($key, $col)
    {
        $this->jsonAttributes[$key] = $col;
    }

    /**
     * Include JSON column in the list of attributes that have a get mutator.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasGetMutator($key)
    {
        $jsonPattern = '/'.implode('|', self::$jsonOperators).'/';

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
     * Check if the key is a known json attribute and return that value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     *
     * @throws InvalidJsonException
     */
    protected function mutateAttribute($key, $value)
    {
        $jsonPattern = '/'.implode('|', self::$jsonOperators).'/';

        // Test for JSON operators and reduce to end element
        $containsJsonOperator = false;

        if (preg_match($jsonPattern, $key)) {
            $elems = preg_split($jsonPattern, $key);
            $key = end($elems);
            $key = str_replace(['>', "'"], '', $key);

            $containsJsonOperator = true;
        }

        if (!parent::hasGetMutator($key) && array_key_exists($key, $this->jsonAttributes) != false) {

            // Get the content of the column associated with this JSON
            // attribute and parse it into an object
            $value = $this->{$this->jsonAttributes[$key]};
            $obj = json_decode($this->{$this->jsonAttributes[$key]});

            // Make sure we were able to parse the json. It's possible here
            // that we've only hinted at an attribute and the column that will
            // hold that attribute is actually null. This isn't really a parse
            // error though the json_encode method will return null (just like)
            // a parse error. To distenguish the two states see if the original
            // value was null (indicating there was nothing there to parse in
            // the first place)
            if ($value !== null && $obj === null) {
                throw new InvalidJsonException();
            }

            // Again it's possible the key will be in the jsonAttributes array
            // (having been hinted) but not present on the actual record.
            // Therefore test that the key is set before returning.
            if (isset($obj->$key)) {
                return $obj->$key;
            } else {
                return;
            }
        } elseif ($containsJsonOperator) {
            return;
        }

        return parent::mutateAttribute($key, $value);
    }

    /**
     * Set a given attribute on the known JSON elements.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setAttribute($key, $value)
    {
        if (array_key_exists($key, $this->jsonAttributes) !== false && !parent::hasSetMutator($key)) {
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
     * @param mixed  $value
     */
    public function setJsonAttribute($attribute, $key, $value)
    {
        // Pull the attribute and decode it
        $decoded = json_decode($this->{$attribute});

        switch (gettype($decoded)) {
            // It's possible the attribute doesn't exist yet (since we can hint at
            // structure). In that case we build an object to set values on as a
            // starting point
            case 'NULL':
                $decoded = json_decode('{}');
                $decoded->$key = $value;
                break;

            case 'array':
                $decoded[$key] = $value;
                break;

            default:
                $decoded->$key = $value;
                break;
        }

        $this->flagJsonAttribute($key, $attribute);
        $this->{$attribute} = json_encode($decoded);

        return;
    }

    /**
     * Add json attributes to the list of things that have changed (when
     * they've changed).
     *
     * @return array
     */
    public function getDirty($includeJson = false)
    {
        $dirty = parent::getDirty();

        if (!$includeJson) {
            return $dirty;
        }

        foreach (array_unique($this->jsonAttributes) as $attribute) {
            $originals[$attribute] = json_decode(array_get($this->original, $attribute, 'null'), true);
        }

        foreach ($this->jsonAttributes as $jsonAttribute => $jsonColumn) {
            if ($this->$jsonAttribute !== null &&
                $this->$jsonAttribute !== array_get($originals[$jsonColumn], $jsonAttribute)) {
                $dirty[$jsonAttribute] = json_encode($this->$jsonAttribute);
            }
        }

        return $dirty;
    }

    /**
     * Allows you to specify if the actual JSON column housing the attributes
     * should be shown on toArray() and toJson() calls. Set this value in the
     * models constructor (to make sure it is set before newFromBuilder() is
     * called). This is false by default.
     *
     * @param bool $show
     *
     * @return bool
     */
    public function showJsonColumns($show)
    {
        return $this->showJsonColumns = $show;
    }

    /**
     * Allows you to specify if the attributes within various json columns
     * should be shown on toArray() and toJson() calls. Set this value in the
     * models constructor (to make sure it is set before newFromBuilder() is
     * called). This is true by default.
     *
     * @param  bool show
     *
     * @return bool
     */
    public function showJsonAttributes($show)
    {
        return $this->showJsonAttributes = $show;
    }
}
