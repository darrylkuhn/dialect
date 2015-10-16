<?php

class JsonDialectTest extends PHPUnit_Framework_TestCase
{
    /**
     * Assert that defined JSON attributes are properly parsed and exposed through mutators.
     */
    public function testHintedJsonColumns()
    {
        // Mock the model with data
        $mock = new MockJsonDialectModel;
        $mock->hintJsonStructure( 'testColumn', json_encode(['foo'=>null]) );

        // Execute the hint call
        $mock->addHintedAttributes();

        // Assert that the column were properly parsed and various bits have
        // been set on the model
        $this->assertTrue($mock->hasGetMutator('foo'));
        $this->assertContains('foo', $mock->getMutatedAttributes());
        $this->assertContains('testColumn', $mock->getHidden());

        // Set a value for foo
        $mock->foo = 'bar';

        // assert that the column has been set properly
        $this->assertEquals( $mock->foo, 'bar' );
        $this->assertEquals( $mock->testColumn, json_encode(['foo'=>'bar']) );
    }

    /**
     * Assert that defined JSON attributes are properly parsed and exposed through mutators.
     */
    public function testInspectJsonColumns()
    {
        // Mock the model with data
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['testColumn']);
        $mock->setAttribute('testColumn', json_encode(['foo' => 'bar']));

        // Execute the insepect call
        $mock->inspectJsonColumns();

        // Assert that the column were properly parsed and various bits have
        // been set on the model
        $this->assertTrue($mock->hasGetMutator('foo'));
        $this->assertContains('foo', $mock->getMutatedAttributes());
        $this->assertContains('testColumn', $mock->getHidden());
        $this->assertEquals($mock->foo, 'bar');
    }

    /**
     * Assert that JSON attributes can be set through mutators
     */
    public function testSetAttribute()
    {
        // Mock the model with data
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['testColumn']);
        $mock->setAttribute('testColumn', json_encode(['foo' => 'bar']));

        // Execute the insepect call
        $mock->inspectJsonColumns();

        $mock->foo = 'baz';
        $mock->setJsonAttribute('testColumn', 'fizz', 'buzz');

        // Assert that the column were properly parsed and various bits have
        // been set on the model
        $this->assertEquals($mock->foo, 'baz');
        $this->assertEquals($mock->fizz, 'buzz');
    }

    /**
     * Assert that attributes with JSON operators are properly recognized as JSON attributes
     */
    public function testGetMutator()
    {
        // Mock the model with data
        $mock = new MockJsonDialectModel;
        $this->assertTrue($mock->hasGetMutator("testColumn->>'foo'"));
    }
}
