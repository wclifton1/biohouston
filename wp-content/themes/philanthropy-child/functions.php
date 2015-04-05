<?php
  add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
  function theme_enqueue_styles() {
      wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
  }

  function revcon_change_post_label() {
    global $menu;
    global $submenu;
    $menu[5][0] = 'Jobs';
    $submenu['edit.php'][5][0] = 'Jobs';
    $submenu['edit.php'][10][0] = 'Add Job';
    $submenu['edit.php'][16][0] = 'Job Tags';
    echo '';
  }
  function revcon_change_post_object() {
      global $wp_post_types;
      $labels = &$wp_post_types['post']->labels;
      $labels->name = 'Jobs';
      $labels->singular_name = 'Job';
      $labels->add_new = 'Add Job';
      $labels->add_new_item = 'Add Job';
      $labels->edit_item = 'Edit Job';
      $labels->new_item = 'Job';
      $labels->view_item = 'View Job';
      $labels->search_items = 'Search Jobs';
      $labels->not_found = 'No Jobs found';
      $labels->not_found_in_trash = 'No Jobs found in Trash';
      $labels->all_items = 'All Jobs';
      $labels->menu_name = 'Jobs';
      $labels->name_admin_bar = 'Jobs';
  }
 
add_action( 'admin_menu', 'revcon_change_post_label' );
add_action( 'init', 'revcon_change_post_object' );
?>