<?php

use CustomQuery\CustomQuery;
use CustomQuery\QuerySitemapProvider;

/**
 * Class TestQuerySitemapProvider
 *
 * @package CustomQuery
 */

/**
 * Tests for the QuerySitemapProvider class
 */
class TestQuerySitemapProvider extends WP_UnitTestCase {

    function setUp() {

        parent::setUp();

        $this->sitemap_provider = new QuerySitemapProvider();

        $this->setUpTestData();

        add_filter('custom_query_sitemap_queries', array($this, 'register_sitemap_queries'));

    }

    function tearDown() {

        parent::tearDown();

        remove_filter('custom_query_sitemap_queries', array($this, 'register_sitemap_queries'));

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

    public function test_get_object_subtypes() {

        $subtypes = $this->sitemap_provider->get_object_subtypes();
        $actual = array_keys($subtypes);
        $expected = array('post-index');
        $this->assertEquals($expected, $actual);

    }

    public function test_get_url_list() {

        $actual = $this->sitemap_provider->get_url_list(1, 'post-index');
        $expected = array(
            array(
                'loc' => 'http://example.org'
            ),
            array(
                'loc' => 'http://example.org?query_page=2'
            )
        );
        $this->assertEquals($expected, $actual);

    }

    public function test_get_url_list_subtype_not_found() {

        $actual = $this->sitemap_provider->get_url_list(1, 'foo');
        $expected = array();
        $this->assertEquals($expected, $actual);

    }

    function register_sitemap_queries($queries) {

        $query = new CustomQuery(
            array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 2
            )
        );

        array_push(
            $queries,
            array(
                'name' => 'post-index',
                'base_url' => site_url(),
                'query' => $query
            )
        );

        return $queries;

    }

}
