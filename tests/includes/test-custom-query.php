<?php

use CustomQuery\CustomQueryHandler;
use CustomQuery\TemplateLoader;

/**
 * Class TestCustomQueryHandler
 *
 * @package CustomQuery
 */

/**
 * Tests for the CustomQueryHandler class
 */
class TestCustomQueryHandler extends WP_UnitTestCase {

    function setUp() {

        parent::setUp();

        $this->template_loader = new TemplateLoader(
            array(CUSTOM_QUERY_TEMPLATE_PATH)
        );

        $this->setUpTestData();

    }

    public function setUpTestData() {

        wp_insert_post(array(
            'post_title' => '1. Lorem',
            'post_name' => 'lorem',
            'post_status' => 'publish',
            'post_content' => 'Lorem'
        ));
        wp_insert_post(array(
            'post_title' => '2. Ipsum',
            'post_name' => 'ipsum',
            'post_status' => 'publish',
            'post_content' => 'Ipsum'
        ));
        wp_insert_post(array(
            'post_title' => '3. Dolor',
            'post_name' => 'dolor',
            'post_status' => 'publish',
            'post_content' => 'Dolor'
        ));

    }

    public function test_have_posts() {

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            array(
                'posts_per_page' => 2,
                'post_status' => 'publish',
                'orderby' => 'post_title',
                'order' => 'asc'
            )
        );

        $actual = $query_handler->have_posts();
        $this->assertTrue($actual);

    }

    public function test_the_post() {

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            array(
                'posts_per_page' => 2,
                'post_status' => 'publish',
                'orderby' => 'post_title',
                'order' => 'asc'
            )
        );

        $query_handler->the_post();
        ob_start();
        the_title();
        $actual = ob_get_clean();
        $expected = '1. Lorem';
        $this->assertEquals($expected, $actual);

    }

    public function test_the_post_posts_consumed() {

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            array(
                'posts_per_page' => 2,
                'post_status' => 'publish',
                'orderby' => 'post_title',
                'order' => 'asc'
            )
        );

        $query_handler->the_post();
        $query_handler->the_post();

        try {
            $query_handler->the_post();
            throw new Exception("Exception not raised");
        }
        catch (Exception $e) {
            $actual = $e->getMessage();
            $expected = "All posts consumed";
            $this->assertEquals($expected, $actual);
        }

    }

    public function test_posts_pagination() {

        global $wp;

        $wp->request = '/path/to/page';

        $_GET = array(
            'foo' => 'bar'
        );

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            array(
                'posts_per_page' => 2,
                'post_status' => 'publish',
                'orderby' => 'post_title',
                'order' => 'asc'
            )
        );

        ob_start();
        $query_handler->posts_pagination();
        $output = ob_get_clean();

        $this->assertOutputContains(
            '<span aria-current="page" class="page-numbers current">1</span>',
            $output
        );

        $this->assertOutputContains(
            '<a class="page-numbers" href="/path/to/page?foo=bar&#038;query_page=2">2</a>',
            $output
        );

        $this->assertOutputContains(
            '<a class="next page-numbers" href="/path/to/page?foo=bar&#038;query_page=2">Next &raquo;</a>',
            $output
        );

    }

    public function test_posts_pagination_with_page() {

        global $wp;

        $wp->request = '/path/to/page';

        $_GET = array(
            'foo' => 'bar',
            'query_page' => '2'
        );

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            array(
                'posts_per_page' => 2,
                'post_status' => 'publish',
                'orderby' => 'post_title',
                'order' => 'asc'
            )
        );

        ob_start();
        $query_handler->posts_pagination();
        $output = ob_get_clean();

        $this->assertOutputContains(
            '<span aria-current="page" class="page-numbers current">2</span>',
            $output
        );

        $this->assertOutputContains(
            '<a class="page-numbers" href="/path/to/page?foo=bar&#038;query_page=1">1</a>',
            $output
        );

        $this->assertOutputContains(
            '<a class="prev page-numbers" href="/path/to/page?foo=bar&#038;query_page=1">&laquo; Previous</a>',
            $output
        );

    }

    public function test_posts_pagination_with_page_var() {

        global $wp;

        $wp->request = '/path/to/page';

        $_GET = array(
            'foo' => 'bar'
        );

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            array(
                'posts_per_page' => 2,
                'post_status' => 'publish',
                'orderby' => 'post_title',
                'order' => 'asc'
            ),
            array(
                'page_var' => 'page'
            )
        );

        ob_start();
        $query_handler->posts_pagination();
        $output = ob_get_clean();

        $this->assertOutputContains(
            '<span aria-current="page" class="page-numbers current">1</span>',
            $output
        );

        $this->assertOutputContains(
            '<a class="page-numbers" href="/path/to/page?foo=bar&#038;page=2">2</a>',
            $output
        );

        $this->assertOutputContains(
            '<a class="next page-numbers" href="/path/to/page?foo=bar&#038;page=2">Next &raquo;</a>',
            $output
        );

    }

    public function test_posts_pagination_no_prev_next() {

        global $wp;

        $wp->request = '/path/to/page';

        $_GET = array(
            'foo' => 'bar'
        );

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            array(
                'posts_per_page' => 2,
                'post_status' => 'publish',
                'orderby' => 'post_title',
                'order' => 'asc'
            ),
            array(
                'prev_next' => false
            )
        );

        ob_start();
        $query_handler->posts_pagination();
        $output = ob_get_clean();

        $this->assertOutputContains(
            '<span aria-current="page" class="page-numbers current">1</span>',
            $output
        );

        $this->assertOutputContains(
            '<a class="page-numbers" href="/path/to/page?foo=bar&#038;query_page=2">2</a>',
            $output
        );

        $this->assertNotOutputContains(
            '<a class="next page-numbers" href="/path/to/page?foo=bar&#038;query_page=2">Next &raquo;</a>',
            $output
        );

    }

    public function assertOutputContains($value, $output) {

        $this->assertTrue(false !== strpos($output, $value));

    }

    public function assertNotOutputContains($value, $output) {

        $this->assertTrue(false === strpos($output, $value));

    }

}
