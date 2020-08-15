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

    private $routes_handler;

    public function __construct() {

        $this->routes_handler = QueryRoutes::create();

    }

    public function get_entities() {

        $result = array();

        $this->routes_handler->check_routes();

        if ($this->routes_handler->default_query) {
            $entity = new QuerySitemapEntity(
                'main',
                $this->routes_handler->default_query,
                site_url()
            );
            $result['main'] = $entity;
        }

        foreach ($this->routes_handler->routes as $route) {
            $entity = new QuerySitemapEntity(
                sanitize_title($route->name),
                $route->query,
                site_url($route->name)
            );
            $result[$route->name] = $entity;
        }
        return $result;

    }

}

class QuerySitemap {

    public static function get_entities() {

        static $entities;

        if (isset($entities)) {
            return $entities;
        }

        $handler = new QuerySitemapHandler();
        $entities = $handler->get_entities();
        return $entities;

    }

}

class QuerySitemapProvider extends WP_Sitemaps_Provider {

    protected $object_type = 'query';

    protected $name = 'query';

    public function get_object_subtypes() {

        $entities = QuerySitemap::get_entities();
        return $entities;

	}

    public function get_url_list( $page_num, $object_subtype = '' ) {

        $entities = QuerySitemap::get_entities();;

        if (!isset($entities[$object_subtype])) {
            return array();
        }

        $entity = $entities[$object_subtype];

        $urls = array();
        foreach ($entity->get_pages() as $page) {
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
