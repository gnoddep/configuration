<?php
namespace Nerdman\Configuration;

use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    private Configuration $SUT;

    public function setUp(): void
    {
        $this->SUT = new Configuration();
    }

    public function testSetGet()
    {
        $this->SUT->set('key', 'value');
        self::assertEquals('value', $this->SUT->get('key'));

        $this->SUT->set('array', ['test' => 'value']);
        self::assertEquals(['test' => 'value'], $this->SUT->get('array'));
        self::assertEquals('value', $this->SUT->get('array.test'));

        $this->SUT->set('deeper.array.value', 'test');
        self::assertEquals('test', $this->SUT->get('deeper.array.value'));
        self::assertEquals(['array' => ['value' => 'test']], $this->SUT->get('deeper'));
    }

    public function testDelete()
    {
        $this->SUT->set('key', ['value1' => 1, 'array' => ['value2' => 2, 'value3' => 3]]);
        $this->SUT->delete('key.array.value2');
        self::assertEquals(['value1' => 1, 'array' => ['value3' => 3]], $this->SUT->get('key'));
    }

    public function testOverwrite()
    {
        $this->SUT->set('array', ['test' => 'value']);
        $this->SUT->set('array.test', 'other');
        self::assertEquals('other', $this->SUT->get('array.test'));
    }

    public function testLoadFilesSingleDirectory()
    {
        $this->SUT->load(__DIR__ . '/config/common');
        self::assertEquals(
            [
                'main' => [
                    'test' => 'test',
                    'testarray1' => [
                        'test1' => 'test1',
                        'test2' => 'test2',
                    ],
                    'numeric' => [1, 2, 3],
                ],
                'second' => [
                    'second' => true,
                ],
                'third' => [
                    'test' => [
                        'testtest' => 'test',
                    ],
                ],
            ],
            $this->SUT->getAll()
        );
    }

    public function testLoadFilesMultipleDirectory()
    {
        $this->SUT->load(__DIR__ . '/config/common');
        $this->SUT->load(__DIR__ . '/config/dev');
        self::assertEquals(
            [
                'main' => [
                    'test' => 'dev',
                    'testarray1' => [
                        'test1' => 'test1',
                        'test2' => 'test123',
                        'test3' => 'test3',
                    ],
                    'testarray2' => [
                        'test' => 'test',
                    ],
                    'numeric' => [4, 5, 6],
                ],
                'second' => [
                    'second' => true,
                ],
                'third' => [
                    'test' => [
                        'testtest' => 'test',
                    ],
                ],
                'debug' => true,
            ],
            $this->SUT->getAll()
        );
    }
}

