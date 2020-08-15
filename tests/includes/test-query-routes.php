<?php

use CustomQuery\QueryRoutesHandler;

/**
 * Class TestQueryRoutesHandler
 *
 * @package CustomQuery
 */

/**
 * Tests for the QueryRoutesHandler class
 */
class TestQueryRoutesHandler extends WP_UnitTestCase {

    function setUp() {

        parent::setUp();

        $this->wp_rewrites = Mockery::mock('WP\WP_Rewrites');

        $this->setUpTestData();

        add_filter('custom_query_routes', array($this, 'register_routes'));

    }

    function tearDown() {

        parent::tearDown();

        Mockery::close();

        remove_filter('custom_query_routes', array($this, 'register_routes'));

        delete_option('custom-query-saved_rules');

    }

    public function setUpTestData() {

        $this->post_ids = array();
        $post_id = wp_insert_post(array(
            'post_title' => '1. Lorem',
            'post_name' => 'lorem',
            'post_status' => 'publish',
            'post_content' => 'Lorem'
        ));
        array_push($this->post_ids, $post_id);

        $post_id = wp_insert_post(array(
            'post_title' => '2. Ipsum',
            'post_name' => 'ipsum',
            'post_status' => 'publish',
            'post_content' => 'Ipsum'
        ));
        array_push($this->post_ids, $post_id);

        $post_id = wp_insert_post(array(
            'post_title' => '3. Dolor',
            'post_name' => 'dolor',
            'post_status' => 'publish',
            'post_content' => 'Dolor'
        ));
        array_push($this->post_ids, $post_id);

    }

    public function test_add_rewrites() {

        $this->wp_rewrites->shouldReceive('add_rewrite_rule')
                          ->with('([a-z0-9-_]+)[/]?$', 'index.php?custom_query=lorem', 'top')
                          ->times(1);

        $this->wp_rewrites->shouldReceive('flush_rewrite_rules')->times(1);

        $routes_handler = new QueryRoutesHandler($this->wp_rewrites);

        $routes_handler->add_rewrites();

        $actual = get_option('custom-query-saved_rules');
        $expected = array('lorem');
        $this->assertEquals($expected, $actual);

        $this->assertNotNull($routes_handler->default_query);

    }

    public function test_add_rewrites_no_new_rules() {

        update_option('custom-query-saved_rules', array('lorem'));

        $this->wp_rewrites->shouldReceive('add_rewrite_rule')
                          ->with('([a-z0-9-_]+)[/]?$', 'index.php?custom_query=lorem', 'top')
                          ->times(1);

        $this->wp_rewrites->shouldReceive('flush_rewrite_rules')->times(0);

        $routes_handler = new QueryRoutesHandler($this->wp_rewrites);

        $routes_handler->add_rewrites();

        $actual = get_option('custom-query-saved_rules');
        $expected = array('lorem');
        $this->assertEquals($expected, $actual);

        $this->assertNotNull($routes_handler->default_query);

    }

    function register_routes($routes) {

        array_push(
            $routes,
            array(
                'name' => '',
                'query_args' => array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => 2
                )
            )
        );

        array_push(
            $routes,
            array(
                'name' => 'lorem',
                'query_args' => array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => 2,
                    'post_name__in' => array('lorem', 'ipsum')
                )
            )
        );

        return $routes;

    }

}
