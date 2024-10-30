<?php
/*
Controller name: Posts
Controller description: Data manipulation methods for posts
*/

class bizconnector_Posts_Controller {

  public function create_post() {
    global $bizconnector;
    if (!current_user_can('edit_posts')) {
      $bizconnector->error("You need to login with a user that has 'edit_posts' capacity.");
    }
    if (!$bizconnector->query->nonce) {
      $bizconnector->error("You must include a 'nonce' value to create posts. Use the `get_nonce` Core API method.");
    }
    $nonce_id = $bizconnector->get_nonce_id('posts', 'create_post');
    if (!wp_verify_nonce($bizconnector->query->nonce, $nonce_id)) {
      $bizconnector->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }
    nocache_headers();
    $post = new bizconnector_Post();
    $id = $post->create($_REQUEST);
    if (empty($id)) {
      $bizconnector->error("Could not create post.");
    }
    return array(
      'post' => $post
    );
  }
  
  public function update_post() {
    global $bizconnector;
    $post = $bizconnector->introspector->get_current_post();
    if (empty($post)) {
      $bizconnector->error("Post not found.");
    }
    if (!current_user_can('edit_post', $post->ID)) {
      $bizconnector->error("You need to login with a user that has the 'edit_post' capacity for that post.");
    }
    if (!$bizconnector->query->nonce) {
      $bizconnector->error("You must include a 'nonce' value to update posts. Use the `get_nonce` Core API method.");
    }
    $nonce_id = $bizconnector->get_nonce_id('posts', 'update_post');
    if (!wp_verify_nonce($bizconnector->query->nonce, $nonce_id)) {
      $bizconnector->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }
    nocache_headers();
    $post = new bizconnector_Post($post);
    $post->update($_REQUEST);
    return array(
      'post' => $post
    );
  }
  
  public function delete_post() {
    global $bizconnector;
    $post = $bizconnector->introspector->get_current_post();
    if (empty($post)) {
      $bizconnector->error("Post not found.");
    }
    if (!current_user_can('edit_post', $post->ID)) {
      $bizconnector->error("You need to login with a user that has the 'edit_post' capacity for that post.");
    }
    if (!current_user_can('delete_posts')) {
      $bizconnector->error("You need to login with a user that has the 'delete_posts' capacity.");
    }
    if ($post->post_author != get_current_user_id() && !current_user_can('delete_other_posts')) {
      $bizconnector->error("You need to login with a user that has the 'delete_other_posts' capacity.");
    }
    if (!$bizconnector->query->nonce) {
      $bizconnector->error("You must include a 'nonce' value to update posts. Use the `get_nonce` Core API method.");
    }
    $nonce_id = $bizconnector->get_nonce_id('posts', 'delete_post');
    if (!wp_verify_nonce($bizconnector->query->nonce, $nonce_id)) {
      $bizconnector->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
    }
    nocache_headers();
    wp_delete_post($post->ID);
    return array();
  }
  
}

?>
