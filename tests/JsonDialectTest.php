<?php

class JsonDialectTest extends PHPUnit_Framework_TestCase
{
    /**
     * Assert that defined JSON attributes are properly parsed and given.
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
}
