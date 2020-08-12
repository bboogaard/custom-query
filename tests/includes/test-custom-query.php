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
        $this->persistent_query = Mockery::mock('CustomQuery\PersistentQuery');

        $this->setUpTestData();

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

    public function test_have_posts() {

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            $this->persistent_query,
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
            $this->persistent_query,
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
            $this->persistent_query,
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
            $this->persistent_query,
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
            $this->persistent_query,
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
            $this->persistent_query,
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
            $this->persistent_query,
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

    public function test_posts_navigation_prev_next() {

        global $wp;

        $wp->request = '/path/to/page';

        $qid = UUID::v4();

        $_GET = array(
            'qid' => $qid
        );

        $this->persistent_query->shouldReceive('load')->with($qid)
                               ->andReturn(array(
                                   'posts_per_page' => 3,
                                   'post_status' => 'publish',
                                   'orderby' => 'post_title',
                                   'order' => 'asc'
                               ));

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            $this->persistent_query,
            array()
        );

        ob_start();
        $query_handler->posts_navigation($this->post_ids[1]);
        $output = ob_get_clean();

        $prev_link = sprintf(
            'http://example.org/?p=%d&#038;query_page=%d&#038;qid=%s',
            $this->post_ids[0],
            1,
            $qid
        );
        $this->assertOutputContains($prev_link, $output);
        $this->assertOutputContains('Previous', $output);

        $next_link = sprintf(
            'http://example.org/?p=%d&#038;query_page=%d&#038;qid=%s',
            $this->post_ids[2],
            1,
            $qid
        );
        $this->assertOutputContains($next_link, $output);
        $this->assertOutputContains('Next', $output);

    }

    public function test_posts_navigation_prev_only() {

        global $wp;

        $wp->request = '/path/to/page';

        $qid = UUID::v4();

        $_GET = array(
            'qid' => $qid
        );

        $this->persistent_query->shouldReceive('load')->with($qid)
                               ->andReturn(array(
                                   'posts_per_page' => 3,
                                   'post_status' => 'publish',
                                   'orderby' => 'post_title',
                                   'order' => 'asc'
                               ));

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            $this->persistent_query,
            array()
        );

        ob_start();
        $query_handler->posts_navigation($this->post_ids[2]);
        $output = ob_get_clean();

        $prev_link = sprintf(
            'http://example.org/?p=%d&#038;query_page=%d&#038;qid=%s',
            $this->post_ids[1],
            1,
            $qid
        );
        $this->assertOutputContains($prev_link, $output);
        $this->assertOutputContains('Previous', $output);

        $this->assertNotOutputContains('Next', $output);

    }

    public function test_posts_navigation_next_only() {

        global $wp;

        $wp->request = '/path/to/page';

        $qid = UUID::v4();

        $_GET = array(
            'qid' => $qid
        );

        $this->persistent_query->shouldReceive('load')->with($qid)
                               ->andReturn(array(
                                   'posts_per_page' => 3,
                                   'post_status' => 'publish',
                                   'orderby' => 'post_title',
                                   'order' => 'asc'
                               ));

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            $this->persistent_query,
            array()
        );

        ob_start();
        $query_handler->posts_navigation($this->post_ids[0]);
        $output = ob_get_clean();

        $this->assertNotOutputContains('Previous', $output);

        $next_link = sprintf(
            'http://example.org/?p=%d&#038;query_page=%d&#038;qid=%s',
            $this->post_ids[1],
            1,
            $qid
        );
        $this->assertOutputContains($next_link, $output);
        $this->assertOutputContains('Next', $output);

    }

    public function test_posts_navigation_with_page() {

        global $wp;

        $wp->request = '/path/to/page';

        $qid = UUID::v4();

        $_GET = array(
            'qid' => $qid,
            'query_page' => 2
        );

        $post_ids = $this->post_ids;
        $post_id = wp_insert_post(array(
            'post_title' => '4. Sit',
            'post_name' => 'sit',
            'post_status' => 'publish',
            'post_content' => 'Sit'
        ));
        array_push($post_ids, $post_id);

        $this->persistent_query->shouldReceive('load')->with($qid)
                               ->andReturn(array(
                                   'posts_per_page' => 2,
                                   'post_status' => 'publish',
                                   'orderby' => 'post_title',
                                   'order' => 'asc'
                               ));

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            $this->persistent_query,
            array()
        );

        ob_start();
        $query_handler->posts_navigation($post_ids[3]);
        $output = ob_get_clean();

        $prev_link = sprintf(
            'http://example.org/?p=%d&#038;query_page=%d&#038;qid=%s',
            $post_ids[2],
            2,
            $qid
        );
        $this->assertOutputContains($prev_link, $output);
        $this->assertOutputContains('Previous', $output);

        $this->assertNotOutputContains('Next', $output);

    }

    public function test_posts_navigation_with_other_query_var() {

        global $wp;

        $wp->request = '/path/to/page';

        $qid = UUID::v4();

        $_GET = array(
            'query_id' => $qid
        );

        $this->persistent_query->shouldReceive('load')->with($qid)
                               ->andReturn(array(
                                   'posts_per_page' => 3,
                                   'post_status' => 'publish',
                                   'orderby' => 'post_title',
                                   'order' => 'asc'
                               ));

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            $this->persistent_query,
            array(),
            array(),
            array(
                'query_var' => 'query_id'
            )
        );

        ob_start();
        $query_handler->posts_navigation($this->post_ids[1]);
        $output = ob_get_clean();

        $prev_link = sprintf(
            'http://example.org/?p=%d&#038;query_page=%d&#038;query_id=%s',
            $this->post_ids[0],
            1,
            $qid
        );
        $this->assertOutputContains($prev_link, $output);
        $this->assertOutputContains('Previous', $output);

        $next_link = sprintf(
            'http://example.org/?p=%d&#038;query_page=%d&#038;query_id=%s',
            $this->post_ids[2],
            1,
            $qid
        );
        $this->assertOutputContains($next_link, $output);
        $this->assertOutputContains('Next', $output);

    }

    public function test_posts_navigation_query_load_fails() {

        global $wp;

        $wp->request = '/path/to/page';

        $qid = UUID::v4();

        $_GET = array(
            'qid' => $qid
        );

        $this->persistent_query->shouldReceive('load')->with($qid)
                               ->andReturn(false);

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            $this->persistent_query,
            array()
        );

        ob_start();
        $query_handler->posts_navigation($this->post_ids[1]);
        $output = ob_get_clean();

        $prev_link = sprintf(
            'http://example.org/?p=%d&#038;query_page=%d&#038;qid=%s',
            $this->post_ids[0],
            1,
            $qid
        );
        $this->assertOutputContains($prev_link, $output);
        $this->assertOutputContains('Previous', $output);

        $next_link = sprintf(
            'http://example.org/?p=%d&#038;query_page=%d&#038;qid=%s',
            $this->post_ids[2],
            1,
            $qid
        );
        $this->assertOutputContains($next_link, $output);
        $this->assertOutputContains('Next', $output);

    }

    public function test_posts_navigation_no_saved_query() {

        global $wp;

        $wp->request = '/path/to/page';

        $qid = UUID::v4();

        $_GET = array();

        $this->persistent_query->shouldReceive('load')->times(0);

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            $this->persistent_query,
            array()
        );

        ob_start();
        $query_handler->posts_navigation($this->post_ids[1]);
        $output = ob_get_clean();

        $this->assertEquals('', $output);

    }

    public function test_build_link() {

        global $post;

        $qid = UUID::v4();

        $_GET = array(
            'qid' => $qid
        );

        $this->persistent_query->shouldReceive('load')->with($qid)
                               ->andReturn(array(
                                   'posts_per_page' => 3,
                                   'post_status' => 'publish',
                                   'orderby' => 'post_title',
                                   'order' => 'asc'
                               ));
        $this->persistent_query->shouldReceive('save')->times(0);

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            $this->persistent_query,
            array()
        );

        $post = get_post($this->post_ids[1]);

        $actual = $query_handler->build_link();
        $expected = sprintf(
            'http://example.org/?p=%d&qid=%s', $post->ID, $qid
        );
        $this->assertEquals($expected, $actual);

    }

    public function test_build_link_no_saved_query() {

        global $post;

        $_GET = array();

        $qid = UUID::v4();

        $this->persistent_query->shouldReceive('load')->times(0);
        $this->persistent_query->shouldReceive('save')->with(array(
            'posts_per_page' => 3,
            'post_status' => 'publish',
            'orderby' => 'post_title',
            'order' => 'asc'
        ))->andReturn($qid);

        $query_handler = new CustomQueryHandler(
            $this->template_loader,
            $this->persistent_query,
            array(
                'posts_per_page' => 3,
                'post_status' => 'publish',
                'orderby' => 'post_title',
                'order' => 'asc'
            )
        );

        $post = get_post($this->post_ids[1]);

        $actual = $query_handler->build_link();
        $expected = sprintf(
            'http://example.org/?p=%d&qid=%s', $post->ID, $qid
        );
        $this->assertEquals($expected, $actual);

    }

    public function assertOutputContains($value, $output) {

        $this->assertTrue(false !== strpos($output, $value));

    }

    public function assertNotOutputContains($value, $output) {

        $this->assertTrue(false === strpos($output, $value));

    }

}
