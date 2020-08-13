<?php

use CustomQuery\PersistentQuery;

class TestPersistentQuery extends WP_UnitTestCase {

    function setUp() {

        parent::setUp();

        $this->redis = Mockery::mock('Redis');

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

    }

    public function test_save() {

        $query_fields = array(
            'post_type' => 'string',
            'post_status' => 'string',
            'posts_per_page' => 'integer',
            'meta_query' => 'array'
        );

        $persistent_query = new PersistentQuery(
            $this->redis,
            $query_fields,
            'cq:qid'
        );

        $meta_query = array(
            'meta_key' => 'the-key',
            'meta_value' => 'the-value'
        );

        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'meta_query' => $meta_query
        );

        $this->redis->shouldReceive('connect')->times(1);
        $this->redis->shouldReceive('select')->times(1);
        $this->redis->shouldReceive('hset')
                    ->andReturnUsing(function($key, $field, $value) use($query_fields, $meta_query) {
                        switch ($field) {
                            case 'fields':
                                $this->assertEquals(serialize($query_fields), $value);
                                break;
                            case 'post_type':
                                $this->assertEquals('post', $value);
                                break;
                            case 'post_status':
                                $this->assertEquals('publish', $value);
                                break;
                            case 'posts_per_page':
                                $this->assertEquals('3', $value);
                                break;
                            case 'meta_query':
                                $this->assertEquals(serialize($meta_query), $value);
                                break;
                        }
                    });

        $actual = $persistent_query->save($args);
        $this->assertTrue(UUID::is_valid($actual));

    }

    public function test_load() {

        $query_fields = array(
            'post_type' => 'string',
            'post_status' => 'string',
            'posts_per_page' => 'integer',
            'meta_query' => 'array'
        );

        $persistent_query = new PersistentQuery(
            $this->redis,
            array(),
            'cq:qid'
        );

        $meta_query = array(
            'meta_key' => 'the-key',
            'meta_value' => 'the-value'
        );

        $data = array(
            'fields' => serialize($query_fields),
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => '3',
            'meta_query' => serialize($meta_query)
        );

        $this->redis->shouldReceive('connect')->times(1);
        $this->redis->shouldReceive('select')->times(1);
        $this->redis->shouldReceive('hgetall')->andReturn($data);

        $actual = $persistent_query->load(UUID::v4());
        $expected = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'meta_query' => $meta_query
        );
        $this->assertEquals($expected, $actual);

    }

}
