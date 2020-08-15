<?php

namespace CustomQuery;

use CustomQuery\Lib\Cache;
use WP\WP_Rewrites;

class QueryRoute {

    public $name, $query;

    public function __construct($name, CustomQuery $query) {

        $this->name = sanitize_title($name);
        $this->query = $query;

    }

    public function build_route_url() {

        $query = http_build_query(array(
            'custom_query' => $this->name
        ));
        return 'index.php?' . $query;

    }

}

class QueryRoutesHandler {

    private $cache;

    public $default_query, $routes, $wp_rewrites;

    public function __construct(WP_Rewrites $wp_rewrites) {

        $this->wp_rewrites = $wp_rewrites;
        $this->cache = new Cache('custom-query-');

        add_action('init', array($this, 'add_rewrites'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_include', array($this, 'set_query'));

    }

    public function add_rewrites() {

        $this->check_routes();

        $rules = array();
        foreach ($this->routes as $name => $route) {
            $this->wp_rewrites->add_rewrite_rule(
                '([a-z0-9-_]+)[/]?$',
                $route->build_route_url(),
                'top'
            );
            array_push($rules, $route->name);
        }

        $saved_rules = $this->cache->get('saved_rules', array());
        sort($rules);
        if ($rules != $saved_rules) {
            error_log('flushing rules');
            $this->cache->set('saved_rules', $rules);
            $this->wp_rewrites->flush_rewrite_rules();
        }

    }

    public function add_query_vars($query_vars) {

        array_push($query_vars, 'custom_query');
        return $query_vars;

    }

    public function set_query($template) {

        global $wp_custom_query;

        $this->check_routes();

        $route_name = get_query_var('custom_query');
        if (!$route_name) {
            if ($this->default_query) {
                $wp_custom_query = $this->default_query;
            }
        }
        else {
            if (isset($this->routes[$route_name])) {
                $route = $this->routes[$route_name];
                $wp_custom_query = $route->query;
            }
        }

        return $template;

    }

    public function check_routes() {

        if (!isset($this->routes)) {
            list($this->routes, $this->default_query) = $this->parse_routes(
                apply_filters('custom_query_routes', array())
            );
        }

    }

    private function parse_routes($route_args) {

        $routes = array();
        $default_query = null;

        foreach ($route_args as $args) {
            $args = wp_parse_args(
                $args,
                array(
                    'name' => '',
                    'query_args' => null,
                    'paging_args' => array(),
                    'navigation_args' => array()
                )
            );
            if (!$args['query_args']) {
                continue;
            }
            elseif (!$args['name']) {
                $default_query = new CustomQuery(
                    $args['query_args'],
                    $args['paging_args'],
                    $args['navigation_args']
                );
            }
            else {
                $query = new CustomQuery(
                    $args['query_args'],
                    $args['paging_args'],
                    $args['navigation_args']
                );
                $route = new QueryRoute(
                    $args['name'],
                    $query
                );
                $routes[$route->name] = $route;
            }
        }

        if (!$default_query && !empty($routes)) {
            $route = reset($routes);
            $default_query = $route->query;
        }

        return array($routes, $default_query);

    }

}

class QueryRoutes {

    public static function create() {

        $wp_rewrites = new WP_Rewrites();
        $routes_handler = new QueryRoutesHandler($wp_rewrites);
        return $routes_handler;

    }

    public static function register() {

        self::create();

    }

}
