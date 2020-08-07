<?php

namespace CustomQuery;

use \Exception;
use \WP_Query;

class CustomQueryHandler {

    private $current_post, $current_url, $paging_args, $page, $posts, $query,
    $query_args, $template_loader, $wp_query;

    public function __construct(TemplateLoader $template_loader,
                                $query_args, $paging_args=array()) {

        $this->template_loader = $template_loader;
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
        $this->wp_query = new WP_Query($query_args);
        $this->posts = $this->wp_query->get_posts();
        $this->current_post = 0;

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
            'navigation_markup.php',
            array(
                'page_links' => $page_links
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
        $new_query = http_build_query($query);
        return $this->current_url . '?' . $new_query;

    }

}

class CustomQuery {

    private $query_handler;

    public function __construct($query_args, $paging_args=array()) {

        $template_loader = TemplateLoaderFactory::create();
        $this->query_handler = new CustomQueryHandler(
            $template_loader, $query_args, $paging_args
        );

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
