<?php
/*
Controller name: Respond
Controller description: Comment/trackback submission methods
*/

class bizconnector_Respond_Controller {
  
  function submit_comment() {
    global $bizconnector;
    nocache_headers();
    if (empty($_REQUEST['post_id'])) {
      $bizconnector->error("No post specified. Include 'post_id' var in your request.");
    } else if (empty($_REQUEST['name']) ||
               empty($_REQUEST['email']) ||
               empty($_REQUEST['content'])) {
      $bizconnector->error("Please include all required arguments (name, email, content).");
    } else if (!is_email($_REQUEST['email'])) {
      $bizconnector->error("Please enter a valid email address.");
    }
    $pending = new bizconnector_Comment();
    return $pending->handle_submission();
  }
  
}

?>
