<?php

namespace CustomQuery;

use \Exception;
use \WP_Query;

class CustomQueryHandler {

    private $current_post, $current_url, $navigation_args, $paging_args, $page,
    $persistent_query, $posts, $query, $query_args, $saved_query,
    $template_loader, $wp_query;

    public function __construct(TemplateLoader $template_loader,
                                PersistentQuery $persistent_query,
                                $query_args, $paging_args=array(),
                                $navigation_args=array()) {

        $this->template_loader = $template_loader;
        $this->persistent_query = $persistent_query;
        $this->query_args = $query_args;
        $this->paging_args = wp_parse_args(
            $paging_args,
            array(
                'page_var' => 'query_page',
                'prev_next' => true,
                'prev_text' => __( '&laquo; Previous' ),
                'next_text' => __( 'Next &raquo;' )
            )
        );
        $this->navigation_args = wp_parse_args(
            $navigation_args,
            array(
                'query_var' => 'qid',
                'prev_text' => __( '&laquo; Previous' ),
                'next_text' => __( 'Next &raquo;' )
            )
        );

        $this->setup_query();

    }

    private function setup_query() {

        global $wp;

        $this->current_url = $wp->request;
        $this->query = $_GET;

        $this->page = isset($this->query[$this->paging_args['page_var']]) ?
                      intval($this->query[$this->paging_args['page_var']]) : 1;
        $query_args = $this->query_args;
        $query_args['paged'] = $this->page;

        if (isset($this->query[$this->navigation_args['query_var']])) {
            $this->saved_query = $this->query[$this->navigation_args['query_var']];
            if (false !== $query_args = $this->persistent_query->load($this->saved_query)) {
                $query_args['paged'] = $this->page;
                $this->wp_query = new WP_Query($query_args);
            }
            else {
                error_log(sprintf("Could not load query for id %s", $this->saved_query));
                $this->wp_query = new WP_Query();
            }
        }
        else {
            $this->saved_query = null;
            $this->wp_query = new WP_Query($query_args);
        }

        $this->posts = $this->wp_query->get_posts();
        $this->current_post = 0;

    }

    public function build_link() {

        if (!$this->saved_query) {
            $this->saved_query = $this->save_query();
        }

        $query = $this->query;
        $query[$this->navigation_args['query_var']] = $this->saved_query;
        return $this->join_url(get_permalink(), $query);

    }

    public function save_query() {

        return $this->persistent_query->save($this->query_args);

    }

    public function have_posts() {

        return $this->current_post < count($this->posts);

    }

    public function the_post() {

        global $post;

        if ($this->current_post >= count($this->posts)) {
            throw new Exception("All posts consumed");
        }

        $post = $this->posts[$this->current_post];
        setup_postdata($post);

        $this->current_post += 1;

    }

    public function posts_pagination() {

        $page_links = $this->get_page_links();
        echo $this->template_loader->render(
            'posts_pagination.php',
            array(
                'page_links' => $page_links
            ),
            false
        );

    }

    public function posts_navigation($post_id, $query=array()) {

        $navigation_links = $this->get_navigation_links($post_id, $query);
        echo $this->template_loader->render(
            'posts_navigation.php',
            array(
                'navigation_links' => $navigation_links,
                'prev_text' => $this->navigation_args['prev_text'],
                'next_text' => $this->navigation_args['next_text']
            ),
            false
        );

    }

    private function get_page_links() {

        $page_links = array();

        if ($this->paging_args['prev_next'] && $this->page > 1) {
            $page_links[] = sprintf(
                '<a class="prev page-numbers" href="%s">%s</a>',
                esc_url($this->rebuild_link($this->page - 1)),
                $this->paging_args['prev_text']
            );
        }

        for ($i = 1; $i <= $this->wp_query->max_num_pages; $i++) {
            if ($i == $this->page) {
                $page_links[] = sprintf(
                    '<span aria-current="page" class="page-numbers current">%s</span>',
                    number_format_i18n($i)
                );
            }
            else {
                $page_links[] = sprintf(
                    '<a class="page-numbers" href="%s">%s</a>',
                    esc_url($this->rebuild_link($i)),
                    number_format_i18n($i)
                );
            }
        }

        if ($this->paging_args['prev_next'] && $this->page < $this->wp_query->max_num_pages) {
             $page_links[] = sprintf(
                 '<a class="next page-numbers" href="%s">%s</a>',
                 esc_url($this->rebuild_link($this->page + 1)),
                 $this->paging_args['next_text']
             );
        }

        return $page_links;

    }

    private function rebuild_link($page) {

        $query = $this->query;
        $query[$this->paging_args['page_var']] = $page;
        return $this->join_url($this->current_url, $query);

    }

    private function get_navigation_links($post_id, $query=array()) {

        if (!$this->saved_query) {
            return array(
                'prev_link' => '',
                'next_link' => ''
            );
        }

        $current_post = null;
        $found_post = null;
        $prev_post = null;
        $next_post = null;

        foreach ($this->posts as $post) {
            if ($found_post) {
                $next_post = $post;
                break;
            }
            if ($post->ID == $post_id) {
                if ($current_post) {
                    $prev_post = $current_post;
                }
                $found_post = $post;
            }
            $current_post = $post;
        }

        $query[$this->paging_args['page_var']] = $this->page;
        $query[$this->navigation_args['query_var']] = $this->saved_query;

        $prev_link = $prev_post ? $this->join_url(get_permalink($prev_post), $query) : '';
        $next_link = $next_post ? $this->join_url(get_permalink($next_post), $query) : '';

        return array(
            'prev_link' => $prev_link,
            'next_link' => $next_link
        );

    }

    private function join_url($url, $query) {

        $parts = parse_url($url);

        if (isset($parts['query'])) {
            $query_part = $parts['query'];
            parse_str($query_part, $new_query);
            $new_query = array_merge($new_query, $query);
        }
        else {
            $new_query = $query;
        }

        $scheme_bit = isset($parts['scheme']) ? $parts['scheme'] : 'http';
        $host_bit = isset($parts['host']) ? $parts['host'] : '';
        $port_bit = isset($parts['port']) ? sprintf(':%d', $parts['port']) : '';
        $path_bit = isset($parts['path']) ? $parts['path'] : '';

        if ($scheme_bit && $host_bit) {
            $url = sprintf(
                '%s://%s%s%s', $scheme_bit, $parts['host'], $port_bit, $path_bit
            );
        }
        else {
            $url = $path_bit;
        }

        return $url . '?' . http_build_query($new_query);

    }

}

class CustomQuery {

    private $query_handler;

    public function __construct($query_args, $paging_args=array()) {

        global $wp_custom_query;

        $template_loader = TemplateLoaderFactory::create();
        $query_fields = array();
        foreach ($query_args as $key => $val) {
            if (is_integer($val)) {
                $query_fields[$key] = 'integer';
            }
            elseif (is_array($val)) {
                $query_fields[$key] = 'array';
            }
            else {
                $query_fields[$key] = 'string';
            }
        }
        $persistent_query = PersistentQueryFactory::create($query_fields);
        $this->query_handler = new CustomQueryHandler(
            $template_loader, $persistent_query, $query_args, $paging_args
        );

        $wp_custom_query = $this;

    }

    public function build_link() {

        return $this->query_handler->build_link();

    }

    public function posts_navigation($post_id, $query=array()) {

        $this->query_handler->posts_navigation($post_id, $query);

    }

    public function have_posts() {

        return $this->query_handler->have_posts();

    }

    public function the_post() {

        return $this->query_handler->the_post();

    }

    public function posts_pagination() {

        return $this->query_handler->posts_pagination();

    }

}
