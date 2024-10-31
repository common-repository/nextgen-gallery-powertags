<?php

/*
  Plugin Name: NextGen Gallery Powertags
  Plugin URI: http://www.mauromascia.com/portfolio/wordpress-nextgen-gallery-powertags
  Description: Extends NextGen Gallery (prior to version 2.0) / NextCellent Gallery to simplify the image filtering, providing a simple way to filter gallery images with their own tags.
  Version: 1.6.5
  Author: Mauro Mascia (baba_mmx)
  Author URI: http://www.mauromascia.com
  License: GPLv2
  Support: info@mauromascia.com

  Copyright 2012-2014 | Mauro Mascia (email: info@mauromascia.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


$this_plugin = plugin_basename(__FILE__);

if ( ! function_exists( 'is_plugin_active' ) ) {
  include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

// Check for both NextGen and NextCellent
if ( ! is_plugin_active( "nextgen-gallery/nggallery.php" ) && ! is_plugin_active( "nextcellent-gallery-nextgen-legacy/nggallery.php" ) ) {
  add_action(
      'admin_notices',
      create_function(
          '',
          'echo \'<div id="message" class="error"><p>You must install/enable NextGEN Gallery or NextCellent Gallery before using Powertags!</p></div>\';'
      )
  );

  deactivate_plugins($this_plugin);

  return false;
}


include_once( 'image.php' );

/**
 * Localisation.
 */
function nggpowertags_init() {
  $lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages';
  load_textdomain( 'nggpowertags', $lang_dir . '/lang-' . get_locale() . '.mo' );
  load_plugin_textdomain( 'nggpowertags', false, $lang_dir );
}

add_action( 'init', 'nggpowertags_init' );

/**
 * Creates the [nggpowertags] shortcode
 */
function nggpowertags_shortcode( $atts ) {
  extract( shortcode_atts( array(
      'gallery' => null,
      'tagmenu_format' => 'sep', // 'sep' (default) OR 'list' OR 'select' OR 'empty'
      'tagmenu_sep' => ',', // any char or html stuff to separate the tags
      'all_word' => 'All', // tell it in your language, uppercase or what you want
      'template' => '',
      'exclude' => '',
      'include' => '',
      'unique' => ''
      ), $atts ) );

  if ( !empty( $exclude ) ) {
    // create an array of trimmed words
    $exclude = array_map( 'trim', explode( ",", $exclude ) );
  }

  if ( !empty( $include ) ) {
    // create an array of trimmed words
    $include = array_map( 'trim', explode( ",", $include ) );
  }

  if ( empty( $gallery ) ) {
    nggpowertags_log( "You must specify at least one gallery ID." );
    return;
  }

  list($menu, $taglist) = nggpowertags_GetGalleryTags(
    $gallery, $tagmenu_format, $tagmenu_sep, __( $all_word, 'nggpowertags' ), //translated all word
    $exclude, $include
  );

  return nggpowertags_ShowGalleryTags( $gallery, $template, $menu, $taglist, $exclude, $include, $unique );
}

add_shortcode( 'nggpowertags', 'nggpowertags_shortcode' );

/**
 * Shows all images or the filtered ones
 */
function nggpowertags_ShowGalleryTags( $gallery, $template, $menu, $taglist, $exclude, $include, $unique_gallery = '' ) {
  $the_tag = get_query_var( 'gallerytag' ); // $_GET from wp_query
  // Set images for a certain tag or for all tags of the selected gallery
  $taglist = !empty( $the_tag ) && $the_tag != "all" ? $the_tag . "," : $taglist;

  // get related images
  $picturelist = nggpowertags_find_images_for_tags( $taglist, 'ASC', $gallery, $unique_gallery );

  if ( empty( $picturelist ) ) {
    nggpowertags_log( "Picturelist is empty." );
    return;
  }

  // get pictures to be excluded by tag
  $removepicturelist = array( );
  foreach ( $picturelist as $key => $picture ) {
    $tmp_picture_tags = wp_get_object_terms( $picture->pid, 'ngg_tag' );

    // inclusion checks
    foreach ( $tmp_picture_tags as $key => $tag ) {
      if ( isset( $include ) && is_array( $include ) && in_array( $tag->name, $include ) ) {
        continue 2; //skip exclude checks, this picture must be included by its include tag
      }
    }

    // exclusion checks
    foreach ( $tmp_picture_tags as $key => $tag ) {
      if ( isset( $exclude ) && is_array( $exclude ) && in_array( $tag->name, $exclude ) ) {
        $removepicturelist[] = $picture->pid;
        continue 2; //skip other tags, this image will not be shown
      }
    }
  }

  // unset pictures with excluded tags
  foreach ( $removepicturelist as $key ) {
    unset( $picturelist[$key] );
  }

  $out = $menu; // add the menu to the output, doesn't print it directly
  // show gallery
  if ( is_array( $picturelist ) ) {
    $out .= nggCreateGallery( $picturelist, $gallery = false, $template );
  }

  $out = apply_filters( 'ngg_show_gallery_tags_content', $out, $taglist );
  return '<div id="nggpowertags">' . $out . '</div>';
}

/**
 * Get images corresponding to a list of tags
 *
 * This is a modification of the original ngg function find_images_for_tags
 * (on nggfunctions.php) to allows the filtering by the gallery ID
 */
function nggpowertags_find_images_for_tags( $taglist, $mode = "ASC", $galleryID = null, $unique_gallery = '' ) {
  global $wpdb;

  // extract it into a array
  $taglist = explode( ",", $taglist );

  if ( !is_array( $taglist ) )
    $taglist = array( $taglist );

  //[FIX 1.5.4] - remove empty values and then trim words
  $taglist = array_map( 'trim', array_filter( $taglist ) );
  $new_slugarray = array_map( 'sanitize_title', $taglist );
  $sluglist = "'" . implode( "', '", $new_slugarray ) . "'";

  //Treat % as a litteral in the database, for unicode support
  $sluglist = str_replace( "%", "%%", $sluglist );

  // first get all $term_ids with this tag
  // FIX 1.4.1: $wpdb->prepare must not be used because all elements has been already sanitized
  $term_ids = $wpdb->get_col(
    "SELECT term_id FROM $wpdb->terms WHERE slug IN ($sluglist) ORDER BY term_id ASC "
  );

  /*
   * this function stores all pictures with the tags passed ...
   * but we want the ones only from our gallery. So I have to re-write
   * also find_images_in_list
   */
  $picids = get_objects_in_term( $term_ids, 'ngg_tag' );

  //Now lookup in the database
  if ( $mode == 'RAND' ) {
    //$pictures = nggdb::find_images_in_list($picids, true, 'RAND');
    $pictures = nggpowertags_find_images_in_list( $picids, true, 'RAND', $galleryID, $unique_gallery );
  }
  else {
    //$pictures = nggdb::find_images_in_list($picids, true, 'ASC');
    $pictures = nggpowertags_find_images_in_list( $picids, true, 'ASC', $galleryID, $unique_gallery );
  }

  return $pictures;
}

/**
 * Get images given a list of IDs
 *
 * This is a modification of the original ngg function find_images_in_list
 * (on ngg-db.php) to allows the filtering by the gallery ID
 */
function nggpowertags_find_images_in_list( $pids, $exclude = false, $order = 'ASC', $galleryID = null, $unique_gallery = '' ) {
  global $wpdb;

  $result = array( );

  // Check for the exclude setting
  $exclude_clause = ($exclude) ? ' AND t.exclude <> 1 ' : '';

  // Check for the exclude setting
  $order_clause = ($order == 'RAND') ? 'ORDER BY rand() ' : ' ORDER BY sortorder ASC'; // v1.3 sortorder

  if ( is_array( $pids ) ) {
    $id_list = "'" . implode( "', '", $pids ) . "'";

    // Save Query database
    // [V 1.3] add more gIDs
    $images = $wpdb->get_results(
      "SELECT t.*, tt.*
            FROM $wpdb->nggpictures AS t
              INNER JOIN $wpdb->nggallery AS tt ON t.galleryid = tt.gid
            WHERE t.pid IN ($id_list)
              AND t.galleryid in ($galleryID)
            $exclude_clause
            $order_clause", OBJECT_K
    );

    // Build the image objects from the query result
    if ( $images ) {
      foreach ( $images as $key => $image ) {
        $picture = new nggImage( $image, $unique_gallery );
        $result[$key] = $picture;
      }
    }
  }
  return $result;
}

/**
 * Returns all ngg tags of a certain gallery as tag-list (to be used into
 * find_images_for_tags) and as menu (depending on the format)
 */
function nggpowertags_GetGalleryTags( $gallery, $format, $separator, $all_word, $exclude, $include ) {
  global $nggRewrite;

  // create rappresentations of the tags for this gallery(ies)
  $taglist = "";
  $tmp_taglist = "";

  $a = array( );
  $option = array( );
  $active_tag_isset = false;
  $active_tag_string = "active-tag";

  // query all ngg tags
  $ngg_tags = get_terms( 'ngg_tag' );

  foreach ( $ngg_tags as $k => $v ) {
    $tag_name = $ngg_tags[$k]->name;
    $tag_slug = $ngg_tags[$k]->slug;

    if ( empty( $include ) && empty( $exclude ) ) {
      $tmp_taglist .= $tag_slug . ",";
    }
    elseif ( empty( $include ) ) {
      // the include attribute have the precedence on the exclude one
      //if there isn't the include attribute, we can proceed getting all the
      //tags minus the excluded ones...
      if ( !in_array( $tag_name, $exclude ) ) {
        $tmp_taglist .= $tag_slug . ",";
      }
    }
    else {
      // else, get only included tags
      if ( in_array( $tag_name, $include ) ) {
        $tmp_taglist .= $tag_slug . ",";
      }
    }
  }

  // at this point we need to ensure we have only images (and also only tags) in the listed gallery(ies)
  $picturelist = nggpowertags_find_images_for_tags( $tmp_taglist, 'ASC', $gallery );
  if ( empty( $picturelist ) ) {
    return array( null, null );
  }

  foreach ( $picturelist as $key => $picture ) {
    if ( in_array( $picture->galleryid, explode( ',', $gallery ) ) ) { // check the gallery(ies) ID(s)
      // get the tags from this pic
      $picture_tags = wp_get_object_terms( $picture->pid, 'ngg_tag' );

      foreach ( $picture_tags as $key => &$tag ) {
        if ( !isset( $a[$tag->name] ) ) {
          if ( !empty( $include ) ) { // include is specified
            if ( is_array( $include ) && in_array( $tag->name, $include ) ) {
              $add = true;
            }
            else {
              $add = false;
            }
          }
          elseif ( !empty( $exclude ) ) {
            // include isn't specified so check for exclude
            if ( is_array( $exclude ) && in_array( $tag->name, $exclude ) ) {
              $add = false;
            }
            else {
              $add = true;
            }
          }
          else {
            // the "include" or the "exclude" attrs are not specified, so add all
            $add = true;
          }

          if ( $add ) {
            $tag->link = nggpowertags_get_permalink( array( 'gallerytag' => $tag->slug ) );
            $tag_link = '#' != $tag->link ? esc_url( $tag->link ) : '#';

            // Add active tag [V 1.5]
            if ( get_query_var( 'gallerytag' ) == $tag->slug ) {
              $active_tag_isset = true;
              $a[$tag->name] = "<a href='$tag_link' class='tag-link-{$tag->term_id} $active_tag_string'>{$tag->name}</a>";
              $option[$tag->name] = '<option value="' . $tag_link . '" selected="selected">' . $tag->name . '</option>';
            }
            else {
              $a[$tag->name] = "<a href='$tag_link' class='tag-link-{$tag->term_id}'>{$tag->name}</a>";
              $option[$tag->name] = '<option value="' . $tag_link . '">' . $tag->name . '</option>';
            }

            // this is the "real" tag list, including only the ones for the right gallery
            $taglist .= $tag->slug . ",";
          }
        }
      }
    }
  }

  if ( empty( $include ) ) {
    // sort by tag name if include is not specified
    ksort( $a );
    ksort( $option );
  }
  else {
    // use the custom sort order, the one given in the include list
    $flip_taglist = array_flip( explode( ",", $tmp_taglist ) );
    $flip_include = array_flip( $include );

    foreach ( $flip_include as $key => $value ) {
      if ( !array_key_exists( $key, $flip_taglist ) ) {
        unset( $flip_include[$key] );
      }
    }

    $a = array_merge( $flip_include, $a );
  }

  // Add active tag [V 1.5]
  // add the "all" link at the beginning
  if ( $active_tag_isset == false ) {
    array_unshift( $a, '<a href="' . get_permalink() . '" class="tag-link-all ' . $active_tag_string . '">' . $all_word . '</a>' );
    array_unshift( $option, '<option value="' . get_permalink() . '" selected="selected">' . $all_word . '</option>' );
  }
  else {
    array_unshift( $a, '<a href="' . get_permalink() . '" class="tag-link-all">' . $all_word . '</a>' );
    array_unshift( $option, '<option value="' . get_permalink() . '">' . $all_word . '</option>' );
  }

  switch ( $format ):
    case 'list':
      $tags_format = "<ul class='nggpowertags-menu'>\n\t<li>";
      $tags_format .= join( "</li>\n\t<li>", $a );
      $tags_format .= "</li>\n</ul>\n";
      break;
    case 'select':
      $tags_format = "<select class='nggpowertags-menu' onchange=\"location = this.options[this.selectedIndex].value;\">";
      $tags_format .= join( "\n\t", $option );
      $tags_format .= "</select>\n";
      break;
    case 'empty':
      $tags_format = '';
      break;
    default: // sep / separator
      $tags_format = "<div class='nggpowertags-menu'>";
      $tags_format .= join( $separator, $a );
      $tags_format .= "</div>";
      break;
  endswitch;

  return array(
    $tags_format, // used to print the menu
    $taglist // used to get all pics of this gallery
  );
}

/*
 * Sort
 */
function sortArrayByArray( $array, $orderArray ) {
  $ordered = array( );
  foreach ( $orderArray as $key ) {
    if ( array_key_exists( $key, $array ) ) {
      $ordered[$key] = $array[$key];
      unset( $array[$key] );
    }
  }
  return $ordered + $array;
}

function nggpowertags_log( $msg, $severity = "ERROR" ) {
  $info = get_plugin_data( __FILE__ );
  echo "[{$info['Name']}][{$info['Version']}][$severity] : $msg";
}

/**
 * Get the permalink to a picture/album/gallery given its ID/name/...
 *
 * This is a modification of the original ngg function get_permalink
 * (on rewrite.php), needed to add the [FIX 1.4] below
 */
function nggpowertags_get_permalink( $args ) {
  global $wp_rewrite, $wp_query, $nggRewrite;

  // taken from is_frontpage plugin, required for static homepage
  $show_on_front = get_option( 'show_on_front' );
  $page_on_front = get_option( 'page_on_front' );

  //TODO: Watch out for ticket http://trac.wordpress.org/ticket/6627
  if ( $wp_rewrite->using_permalinks() && $nggRewrite->options['usePermalinks'] ) {
    $post = &get_post( get_the_ID() );

    // If the album is not set before get it from the wp_query ($_GET)
    if ( !isset( $args['album'] ) )
      $album = get_query_var( 'album' );
    if ( !empty( $album ) )
      $args ['album'] = $album;

    $gallery = get_query_var( 'gallery' );
    if ( !empty( $gallery ) )
      $args ['gallery'] = $gallery;

    // You first need to check if "gallerytag" is already present
    //else this will cause that all links will have the same slug
    if ( !isset( $args ['gallerytag'] ) ) {
      $gallerytag = get_query_var( 'gallerytag' );
      if ( !empty( $gallerytag ) )
        $args ['gallerytag'] = $gallerytag;
    }

    /** urlconstructor =  post url | slug | tags | [nav] | [show]
      tags : 	album, gallery 	-> /album-([0-9]+)/gallery-([0-9]+)/
      pid 			-> /image/([0-9]+)/
      gallerytag		-> /tags/([^/]+)/
      nav	 : 	nggpage			-> /page-([0-9]+)/
      show : 	show=slide		-> /slideshow/
      show=gallery	-> /images/
     * */
    // 1. Post / Page url + main slug
    $url = trailingslashit( get_permalink( $post->ID ) ) . $nggRewrite->slug;
    //TODO: For static home pages generate the link to the selected page, still doesn't work
    if ( ($show_on_front == 'page') && ($page_on_front == get_the_ID()) )
      $url = trailingslashit( $post->guid ) . $nggRewrite->slug;

    // 2. Album, pid or tags
    if ( isset( $args['album'] ) && ($args['gallery'] == false) )
      $url .= '/' . $args['album'];
    elseif ( isset( $args['album'] ) && isset( $args['gallery'] ) )
      $url .= '/' . $args['album'] . '/' . $args['gallery'];

    if ( isset( $args['gallerytag'] ) )
      $url .= '/tags/' . $args['gallerytag'];

    if ( isset( $args['pid'] ) )
      $url .= '/image/' . $args['pid'];

    // 3. Navigation
    if ( isset( $args['nggpage'] ) && ($args['nggpage']) )
      $url .= '/page-' . $args['nggpage'];
    elseif ( isset( $args['nggpage'] ) && ($args['nggpage'] === false) && ( count( $args ) == 1 ) )
      $url = trailingslashit( get_permalink( $post->ID ) ); // special case instead of showing page-1, we show the clean url

    // 4. Show images or Slideshow
    if ( isset( $args['show'] ) )
      $url .= ( $args['show'] == 'slide' ) ? '/slideshow' : '/images';

    return apply_filters( 'ngg_get_permalink', $url, $args );
  }
  else {
    // we need to add the page/post id at the start_page otherwise we don't know which gallery is clicked
    if ( is_home() )
      $args['pageid'] = get_the_ID();

    if ( ($show_on_front == 'page') && ($page_on_front == get_the_ID()) )
      $args['page_id'] = get_the_ID();

    if ( !is_singular() )
      $query = htmlspecialchars( add_query_arg( $args, get_permalink( get_the_ID() ) ) );
    else
      $query = htmlspecialchars( add_query_arg( $args ) );

    return apply_filters( 'ngg_get_permalink', $query, $args );
  }
}

?>