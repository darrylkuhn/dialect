<?php

class MockJsonDialectModel extends Illuminate\Database\Eloquent\Model
{
    use \Eloquent\Dialect\Json;

    protected $jsonColumns;

    public function __construct(array $attributes = array())
    {
        static::$booted[get_class($this)] = true;
        parent::__construct($attributes);
    }

    public function setJsonColumns(Array $columns)
    {
        $this->jsonColumns = $columns;
    }
}
