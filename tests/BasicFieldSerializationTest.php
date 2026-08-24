<?php

use Corcel\Acf\Field\BasicField;
use Corcel\Model\Post;

class BasicFieldSerializationTest extends PHPUnit\Framework\TestCase
{
    public function testSerializedEmptyArrayIsUnserialized()
    {
        $field = $this->makeField();

        $this->assertSame([], $field->deserializeArray('a:0:{}'));
    }

    public function testFetchValueReturnsSerializedEmptyArray()
    {
        $field = $this->makeField();
        $field->usePostMetaValue('a:0:{}');

        $this->assertSame([], $field->fetchValue('empty_gallery'));
    }

    public function testNestedScalarArrayIsUnserialized()
    {
        $field = $this->makeField();
        $value = ['headline' => 'Falstaff', 'items' => [1, 2, 3]];

        $this->assertSame($value, $field->deserializeArray(serialize($value)));
    }

    public function testObjectPayloadsAreNotInstantiated()
    {
        $field = $this->makeField();
        $value = $field->deserializeArray(serialize([new stdClass]));

        $this->assertIsArray($value);
        $this->assertInstanceOf(__PHP_Incomplete_Class::class, $value[0]);
    }

    public function testNonArrayValueIsNotUnserialized()
    {
        $field = $this->makeField();

        $this->assertNull($field->deserializeArray('plain text'));
        $this->assertNull($field->deserializeArray('b:0;'));
    }

    private function makeField()
    {
        return new class(new Post) extends BasicField
        {
            public function usePostMetaValue($value)
            {
                $this->postMeta = new class($value)
                {
                    private $value;

                    public function __construct($value)
                    {
                        $this->value = $value;
                    }

                    public function where()
                    {
                        return $this;
                    }

                    public function first()
                    {
                        return (object) ['meta_value' => $this->value];
                    }
                };
            }

            public function deserializeArray($value)
            {
                return $this->unserializeArray($value);
            }
        };
    }
}
