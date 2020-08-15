<?php

namespace WP;

class WP_Rewrites {

    public function add_rewrite_rule($regex, $query, $after) {

        add_rewrite_rule($regex, $query, $after);

    }

    public function flush_rewrite_rules() {

        flush_rewrite_rules();

    }

}
