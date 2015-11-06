<?php

class JsonDialectTest extends PHPUnit_Framework_TestCase
{
    /**
     * Assert that defined JSON attributes are properly parsed and exposed through
     * mutators.
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
        $this->assertArrayNotHasKey('testColumn', $mock->toArray());
        $this->assertArrayHasKey('foo', $mock->toArray());
        $this->assertEquals($mock->foo, 'bar');
    }

    /**
     * Assert that the json columns show up when configured to do so
     */
    public function testDisableHiddenJsonColumns()
    {
        // Mock the model with data
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['testColumn']);
        $mock->setAttribute('testColumn', json_encode(['foo' => 'bar']));
        $mock->showJsonColumns(true);

        // Execute the insepect call
        $mock->inspectJsonColumns();

        // Assert that the testColumn shows up
        $this->assertArrayHasKey('testColumn', $mock->toArray());
    }

    /**
     * Assert that the json attributes do not show up when configured to do so
     */
    public function testEnableHiddenJsonAttributes()
    {
        // Mock the model with data
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['testColumn']);
        $mock->setAttribute('testColumn', json_encode(['foo' => 'bar']));
        $mock->showJsonAttributes(false);

        // Execute the insepect call
        $mock->inspectJsonColumns();

        // Assert that attribute isn't there
        $this->assertArrayNotHasKey('foo', $mock->toArray());
    }

    /**
     * Assert that an exception is thrown when given invalid json as a
     * structure hint
     *
     * @expectedException Eloquent\Dialect\InvalidJsonException
     */
    public function testInvalidJsonAttribute()
    {
        // Mock the model with data
        $mock = new MockJsonDialectModel;
        $mock->hintJsonStructure( 'testColumn', json_encode(['foo'=>null]) );

        // Set testColumn to invalid json
        $mock->setAttribute('testColumn', '{');

        // Try to access a property on invalid json - we should get an
        // exception
        $mock->foo;
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
     * Assert that attributes with JSON operators are properly recognized as JSON
     * attributes
     */
    public function testGetMutator()
    {
        // Mock the model with data
        $mock = new MockJsonDialectModel;
        $this->assertTrue($mock->hasGetMutator("testColumn->>'foo'"));
    }

    /**
     * Assert that defined JSON attributes are properly parsed and exposed
     * through mutators.
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

        $this->assertNull( $mock->foo );

        // Set a value for foo
        $mock->foo = 'bar';

        // assert that the column has been set properly
        $this->assertEquals( $mock->foo, 'bar' );
        $this->assertEquals( $mock->testColumn, json_encode(['foo'=>'bar']) );
    }

    /**
     * Assert that an exception is thrown when given invalid json as a
     * structure hint
     *
     * @expectedException Eloquent\Dialect\InvalidJsonException
     */
    public function testInvalidHint()
    {
        // Mock the model with data
        $mock = new MockJsonDialectModel;
        $mock->hintJsonStructure( 'testColumn', '{' );

        // Execute the hint call
        $mock->addHintedAttributes();
    }
}
