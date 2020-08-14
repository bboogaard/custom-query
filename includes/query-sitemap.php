<?php

namespace CustomQuery;

use \WP_Sitemaps_Provider;

class QuerySitemapEntity {

    public $name;

    private $query, $base_url;

    public function __construct($name, CustomQuery $query, $base_url) {

        $this->name = $name;
        $this->query = $query;
        $this->base_url = $base_url;

    }

    public function get_pages() {

        return $this->query->get_pages($this->base_url);

    }

}

class QuerySitemapHandler {

    private $queries;

    public function __construct() {

        $this->queries = $this->parse_queries(
            apply_filters('custom_query_sitemap_queries', array())
        );

    }

    private function parse_queries($queries) {

        $result = array();
        foreach ($queries as $_query) {
            $query = new QuerySitemapEntity(
                sanitize_title($_query['name']),
                $_query['query'],
                $_query['base_url']
            );
            $result[$query->name] = $query;
        }
        return $result;

    }

    public function get_queries() {

        return $this->queries;

    }

}

class QuerySitemap {

    public static function get_queries() {

        static $queries;

        if (isset($queries)) {
            return $queries;
        }

        $handler = new QuerySitemapHandler();
        $queries = $handler->get_queries();
        return $queries;

    }

}

class QuerySitemapProvider extends WP_Sitemaps_Provider {

    protected $object_type = 'query';

    protected $name = 'query';

    public function get_object_subtypes() {

        $queries = QuerySitemap::get_queries();
        return $queries;

	}

    public function get_url_list( $page_num, $object_subtype = '' ) {

        $queries = QuerySitemap::get_queries();

        if (!isset($queries[$object_subtype])) {
            return array();
        }

        $query = $queries[$object_subtype];

        $urls = array();
        foreach ($query->get_pages() as $page) {
            $sitemap_entry = array(
                'loc' => $page,
            );
            array_push($urls, $sitemap_entry);
        }
        return $urls;

    }

	public function get_max_num_pages( $object_subtype = '' ) {

        return 1;

    }

}

class QuerySitemaps {

    public static function register() {

        add_filter('init', array(__CLASS__, 'register_provider'));

    }

    public static function register_provider() {

        $provider = new QuerySitemapProvider();
        wp_register_sitemap_provider( 'query' , $provider );

    }

}
