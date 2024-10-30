<?php
/*
Controller name: Core
Controller description: Basic introspection methods
*/

class bizconnector_Core_Controller {
  
  public function info() {
    global $bizconnector;
    $php = '';
    if (!empty($bizconnector->query->controller)) {
      return $bizconnector->controller_info($bizconnector->query->controller);
    } else {
      $dir = bizconnector_dir();
      if (file_exists("$dir/bizconnector.php")) {
        $php = file_get_contents("$dir/bizconnector.php");
      } else {
        // Check one directory up, in case bizconnector.php was moved
        $dir = dirname($dir);
        if (file_exists("$dir/bizconnector.php")) {
          $php = file_get_contents("$dir/bizconnector.php");
        }
      }
      if (preg_match('/^\s*Version:\s*(.+)$/m', $php, $matches)) {
        $version = $matches[1];
      } else {
        $version = '(Unknown)';
      }
      $active_controllers = explode(',', get_option('bizconnector_controllers', 'core'));
      $controllers = array_intersect($bizconnector->get_controllers(), $active_controllers);
      return array(
        'bizconnector_version' => $version,
        'controllers' => array_values($controllers)
      );
    }
  }
  
  public function get_recent_posts() {
    global $bizconnector;
    $posts = $bizconnector->introspector->get_posts();
    return $this->posts_result($posts);
  }
  
  public function get_posts() {
    global $bizconnector;
    $url = parse_url($_SERVER['REQUEST_URI']);
    $defaults = array(
      'ignore_sticky_posts' => true
    );
    $query = wp_parse_args($url['query']);
    unset($query['json']);
    unset($query['post_status']);
    $query = array_merge($defaults, $query);
    $posts = $bizconnector->introspector->get_posts($query);
    $result = $this->posts_result($posts);
    $result['query'] = $query;
    return $result;
  }
  
  public function get_post() {
    global $bizconnector, $post;
    $post = $bizconnector->introspector->get_current_post();
    if ($post) {
      $previous = get_adjacent_post(false, '', true);
      $next = get_adjacent_post(false, '', false);
      $response = array(
        'post' => new bizconnector_Post($post)
      );
      if ($previous) {
        $response['previous_url'] = get_permalink($previous->ID);
      }
      if ($next) {
        $response['next_url'] = get_permalink($next->ID);
      }
      return $response;
    } else {
      $bizconnector->error("Not found.");
    }
  }

  public function get_page() {
    global $bizconnector;
    extract($bizconnector->query->get(array('id', 'slug', 'page_id', 'page_slug', 'children')));
    if ($id || $page_id) {
      if (!$id) {
        $id = $page_id;
      }
      $posts = $bizconnector->introspector->get_posts(array(
        'page_id' => $id
      ));
    } else if ($slug || $page_slug) {
      if (!$slug) {
        $slug = $page_slug;
      }
      $posts = $bizconnector->introspector->get_posts(array(
        'pagename' => $slug
      ));
    } else {
      $bizconnector->error("Include 'id' or 'slug' var in your request.");
    }
    
    // Workaround for https://core.trac.wordpress.org/ticket/12647
    if (empty($posts)) {
      $url = $_SERVER['REQUEST_URI'];
      $parsed_url = parse_url($url);
      $path = $parsed_url['path'];
      if (preg_match('#^http://[^/]+(/.+)$#', get_bloginfo('url'), $matches)) {
        $blog_root = $matches[1];
        $path = preg_replace("#^$blog_root#", '', $path);
      }
      if (substr($path, 0, 1) == '/') {
        $path = substr($path, 1);
      }
      $posts = $bizconnector->introspector->get_posts(array('pagename' => $path));
    }
    
    if (count($posts) == 1) {
      if (!empty($children)) {
        $bizconnector->introspector->attach_child_posts($posts[0]);
      }
      return array(
        'page' => $posts[0]
      );
    } else {
      $bizconnector->error("Not found.");
    }
  }
  
  public function get_date_posts() {
    global $bizconnector;
    if ($bizconnector->query->date) {
      $date = preg_replace('/\D/', '', $bizconnector->query->date);
      if (!preg_match('/^\d{4}(\d{2})?(\d{2})?$/', $date)) {
        $bizconnector->error("Specify a date var in one of 'YYYY' or 'YYYY-MM' or 'YYYY-MM-DD' formats.");
      }
      $request = array('year' => substr($date, 0, 4));
      if (strlen($date) > 4) {
        $request['monthnum'] = (int) substr($date, 4, 2);
      }
      if (strlen($date) > 6) {
        $request['day'] = (int) substr($date, 6, 2);
      }
      $posts = $bizconnector->introspector->get_posts($request);
    } else {
      $bizconnector->error("Include 'date' var in your request.");
    }
    return $this->posts_result($posts);
  }
  
  public function get_category_posts() {
    global $bizconnector;
    $category = $bizconnector->introspector->get_current_category();
    if (!$category) {
      $bizconnector->error("Not found.");
    }
    $posts = $bizconnector->introspector->get_posts(array(
      'cat' => $category->id
    ));
    return $this->posts_object_result($posts, $category);
  }
  
  public function get_tag_posts() {
    global $bizconnector;
    $tag = $bizconnector->introspector->get_current_tag();
    if (!$tag) {
      $bizconnector->error("Not found.");
    }
    $posts = $bizconnector->introspector->get_posts(array(
      'tag' => $tag->slug
    ));
    return $this->posts_object_result($posts, $tag);
  }
  
  public function get_author_posts() {
    global $bizconnector;
    $author = $bizconnector->introspector->get_current_author();
    if (!$author) {
      $bizconnector->error("Not found.");
    }
    $posts = $bizconnector->introspector->get_posts(array(
      'author' => $author->id
    ));
    return $this->posts_object_result($posts, $author);
  }
  
  public function get_search_results() {
    global $bizconnector;
    if ($bizconnector->query->search) {
      $posts = $bizconnector->introspector->get_posts(array(
        's' => $bizconnector->query->search
      ));
    } else {
      $bizconnector->error("Include 'search' var in your request.");
    }
    return $this->posts_result($posts);
  }
  
  public function get_date_index() {
    global $bizconnector;
    $permalinks = $bizconnector->introspector->get_date_archive_permalinks();
    $tree = $bizconnector->introspector->get_date_archive_tree($permalinks);
    return array(
      'permalinks' => $permalinks,
      'tree' => $tree
    );
  }
  
  public function get_category_index() {
    global $bizconnector;
    $args = null;
    if (!empty($bizconnector->query->parent)) {
      $args = array(
        'parent' => $bizconnector->query->parent
      );
    }
    $categories = $bizconnector->introspector->get_categories($args);
    return array(
      'count' => count($categories),
      'categories' => $categories
    );
  }
  
  public function get_tag_index() {
    global $bizconnector;
    $tags = $bizconnector->introspector->get_tags();
    return array(
      'count' => count($tags),
      'tags' => $tags
    );
  }
  
  public function get_author_index() {
    global $bizconnector;
    $authors = $bizconnector->introspector->get_authors();
    return array(
      'count' => count($authors),
      'authors' => array_values($authors)
    );
  }
  
  public function get_page_index() {
    global $bizconnector;
    $pages = array();
    $post_type = $bizconnector->query->post_type ? $bizconnector->query->post_type : 'page';
    
    // Thanks to blinder for the fix!
    $numberposts = empty($bizconnector->query->count) ? -1 : $bizconnector->query->count;
    $wp_posts = get_posts(array(
      'post_type' => $post_type,
      'post_parent' => 0,
      'order' => 'ASC',
      'orderby' => 'menu_order',
      'numberposts' => $numberposts
    ));
    foreach ($wp_posts as $wp_post) {
      $pages[] = new bizconnector_Post($wp_post);
    }
    foreach ($pages as $page) {
      $bizconnector->introspector->attach_child_posts($page);
    }
    return array(
      'pages' => $pages
    );
  }
  
  public function get_nonce() {
    global $bizconnector;
    extract($bizconnector->query->get(array('controller', 'method')));
    if ($controller && $method) {
      $controller = strtolower($controller);
      if (!in_array($controller, $bizconnector->get_controllers())) {
        $bizconnector->error("Unknown controller '$controller'.");
      }
      require_once $bizconnector->controller_path($controller);
      if (!method_exists($bizconnector->controller_class($controller), $method)) {
        $bizconnector->error("Unknown method '$method'.");
      }
      $nonce_id = $bizconnector->get_nonce_id($controller, $method);
      return array(
        'controller' => $controller,
        'method' => $method,
        'nonce' => wp_create_nonce($nonce_id)
      );
    } else {
      $bizconnector->error("Include 'controller' and 'method' vars in your request.");
    }
  }
  
  protected function get_object_posts($object, $id_var, $slug_var) {
    global $bizconnector;
    $object_id = "{$type}_id";
    $object_slug = "{$type}_slug";
    extract($bizconnector->query->get(array('id', 'slug', $object_id, $object_slug)));
    if ($id || $$object_id) {
      if (!$id) {
        $id = $$object_id;
      }
      $posts = $bizconnector->introspector->get_posts(array(
        $id_var => $id
      ));
    } else if ($slug || $$object_slug) {
      if (!$slug) {
        $slug = $$object_slug;
      }
      $posts = $bizconnector->introspector->get_posts(array(
        $slug_var => $slug
      ));
    } else {
      $bizconnector->error("No $type specified. Include 'id' or 'slug' var in your request.");
    }
    return $posts;
  }
  
  protected function posts_result($posts) {
    global $wp_query;
    return array(
      'count' => count($posts),
      'count_total' => (int) $wp_query->found_posts,
      'pages' => $wp_query->max_num_pages,
      'posts' => $posts
    );
  }
  
  protected function posts_object_result($posts, $object) {
    global $wp_query;
    // Convert something like "bizconnector_Category" into "category"
    $object_key = strtolower(substr(get_class($object), 9));
    return array(
      'count' => count($posts),
      'pages' => (int) $wp_query->max_num_pages,
      $object_key => $object,
      'posts' => $posts
    );
  }
  
}

?>
