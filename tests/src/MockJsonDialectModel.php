<?php

class MockJsonDialectModel extends Illuminate\Database\Eloquent\Model
{
    use \Eloquent\Dialect\Json;

    protected $jsonColumns;

    public function setJsonColumns(Array $columns)
    {
        $this->jsonColumns = $columns;
    }
}
