<?php

use Corcel\Acf\Field\Text;
use Corcel\Acf\FieldFactory;
use Corcel\Model\Post;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;

class ScalarFieldsTest extends PHPUnit\Framework\TestCase
{
    private $resolver;

    private $connection;

    private $post;

    protected function setUp(): void
    {
        $this->resolver = Model::getConnectionResolver();
        $database = new Manager();
        $database->addConnection(['driver' => 'sqlite', 'database' => ':memory:'], 'range_fields');
        $database->bootEloquent();
        $this->connection = $database->getConnection('range_fields');
        $this->connection->getSchemaBuilder()->create('postmeta', function ($table) {
            $table->increments('meta_id');
            $table->unsignedInteger('post_id');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
        });
        $this->post = (new Post())->setConnection('range_fields')->forceFill(['ID' => 1]);
    }

    protected function tearDown(): void
    {
        if ($this->resolver === null) {
            Model::unsetConnectionResolver();
        } else {
            Model::setConnectionResolver($this->resolver);
        }
        $this->connection->disconnect();
    }

    public function testRangeValuesUseTheExistingScalarField(): void
    {
        foreach (['0', '35', '100', '12.5', ''] as $value) {
            $this->connection->table('postmeta')->updateOrInsert(
                ['post_id' => 1, 'meta_key' => 'opacity'],
                ['meta_value' => $value]
            );

            $field = FieldFactory::make('opacity', $this->post, 'range');

            $this->assertInstanceOf(Text::class, $field);
            $this->assertSame($value, $field->get());
        }
    }

    public function testMissingRangeValueRemainsNull(): void
    {
        $field = FieldFactory::make('missing', $this->post, 'range');

        $this->assertInstanceOf(Text::class, $field);
        $this->assertNull($field->get());
    }

    public function testStreamdiverValuesUseTheExistingScalarField(): void
    {
        $uuid = '7978e170-c0b2-4658-9ab8-ef210e55a09a';
        $asset = ['uuid' => $uuid, 'width' => 1080, 'height' => 1920, 'aspect_ratio' => '9:16'];
        $values = [[$uuid, $uuid], [serialize($asset), $asset], [json_encode($asset), json_encode($asset)]];
        foreach ($values as $pair) {
            $this->connection->table('postmeta')->updateOrInsert(
                ['post_id' => 1, 'meta_key' => 'video'],
                ['meta_value' => $pair[0]]
            );

            $field = FieldFactory::make('video', $this->post, 'streamdiver_search');

            $this->assertInstanceOf(Text::class, $field);
            $this->assertSame($pair[1], $field->get());
        }
    }
}
