<?php
/*
Plugin Name: Takien Breadcrumb
Plugin URI: http://takien.com/
Description: Display breadcrumb path in your WordPress theme with content preview in each link.
Author: Takien
Version: 0.1
Author URI: http://takien.com/
*/

/*  Copyright 2011 takien.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    For a copy of the GNU General Public License, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
/*

function myplugin_init() {
  load_plugin_textdomain( 'my-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action('init', 'myplugin_init');
*/

if(!defined('ABSPATH')) die();

if (!class_exists('TakienBreadcrumb')) {
	class TakienBreadcrumb {
	
	function TakienBreadcrumb(){
		$this->__construct();
	}
	
	function __construct(){
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin",  array(&$this,'takien_breadcrumb_settings_link' ));
		add_action('admin_head',		array(&$this,'takien_breadcrumb_admin_script'));
		add_action('wp_enqueue_scripts', array(&$this,'takien_breadcrumb_script'));
		add_action('wp_enqueue_scripts', array(&$this,'takien_breadcrumb_style'));
		add_action('admin_menu', 		array(&$this,'takien_breadcrumb_add_page'));
		add_action('contextual_help', 	array(&$this,'takien_breadcrumb_help'));
	}
	
	private function takien_breadcrumb_base($return){
		$takien_breadcrumb_base = Array(
					'name' 			=> 'Takien Breadcrumb',
					'version' 		=> '0.1',
					'base_name'		=> 'takien-breadcrumb'
		);
		return $takien_breadcrumb_base[$return];
	}
	function takien_breadcrumb_render($args=''){
		/* if(is_home()) {
			return false;
		} */

		global $post;
		$defaults = array( 
			'show_preview' 			=> true,
			'separator'				=> ' &raquo; ',
			'echo'					=> true,
			'wrap'					=> 'div',
			'wrap_id'				=> 'takien_breadcrumb',
			'wrap_class'			=> 'takien_breadcrumb',
			'skins'					=> 'core',
			'show_last_title'		=>true,
			'show_last_siblings'	=>true,
			'show_home'				=> true,
			'home_text'				=> 'Home', /* home label, default Home, */
			'show_paged'			=> true,
			'show_post_type'		=> true, /* bool, show post type labels name in the breadcrumb, usually, after home*/
			'show_post_type_if'		=> Array(), /* conditional in which custom post type should show custom post type, default empty array, show in*/
			'show_post_type_if_not'	=> Array('post'),
												/* all custom post type */
			'post_type_link_to_archive'=>true, /* should post type labels linked to it's archive page? always return false if there's no archive page */
			'default_taxonomy'		=> 'category', /* the default taxonomy to show in breadcrumb if there are more than 1 taxonomy attached to the post, this only applied to the single post, if no 'category' taxonomy, it will return other taxonomy, if any */
			'show_taxonomy_name'	=> true,
			'show_taxonomy_name_if'	=> Array(),
			'show_taxonomy_name_if_not'	=> Array()
			);
		$args 	= apply_filters('takien_breadcrumb_args',$args);
		$r 		= wp_parse_args($args, $defaults);
		extract( $r ,EXTR_PREFIX_ALL, 'arg');
		$zindex		= 100;
		$topzindex 	= 200;
		$arg_separator = '<span class="takien-breadcrumb-separator">'.$arg_separator.'</span>';
		$return = '';
		$return .= '<'.$arg_wrap.' id='.$arg_wrap_id.' class="'.$arg_wrap_class.'" itemscope itemtype="http://data-vocabulary.org/Breadcrumb"><ul>'."\r\n";
		$return .= $arg_show_home ? '<li class="first"><a style="z-index:'.$topzindex.'" href="'.site_url('/').'" title="'.get_bloginfo('name').'"><span class="first-breadcrumb"/>'.$arg_home_text.$arg_separator.'</a></li>'."\r\n" : '';


		if(is_single()){
			$custom_post_type 	= get_post_type_object(get_post_type($post->ID));
			$post_type_name 	= $custom_post_type->name;
			$post_type_labels 	= $custom_post_type->labels->name;

			$tax_args 		= Array('object_type'=>array($post_type_name));
			$all_taxonomies = get_taxonomies($tax_args,'objects');
	
			
			foreach((array)$all_taxonomies as $taxs){
				$all_terms = wp_get_object_terms($post->ID, $taxs->name);
					foreach((array)$all_terms as $term){
						if($term->taxonomy == $arg_default_taxonomy){
							$term_id  		= $term->term_id;
							$taxonomy_name	= $term->taxonomy;
							$tax_label		= $taxs->labels->singular_name;
							
							if($term->parent == 0){
								$term_id  		= $term_id ? $term_id 	: $term->term_id;	
								$taxonomy_name 	= $taxonomy_name ? $taxonomy_name 	: $term->taxonomy;
								$tax_label		= $tax_label ? $tax_label	: $taxs->labels->singular_name;
							}
						}
						$term_id  		= $term_id 			? $term_id 			: $term->term_id;	
						$taxonomy_name 	= $taxonomy_name 	? $taxonomy_name 	: $term->taxonomy;
						$tax_label		= $tax_label 		? $tax_label		: $taxs->labels->singular_name;
					}
			}
			if(is_attachment()){
				$return .= $this->takien_build_link(get_permalink($post->post_parent),$zindex=($zindex-1),get_the_title($post->post_parent),$arg_separator,false,true);
				
				if((empty($arg_show_post_type_if) OR in_array($post_type_name,$arg_show_post_type_if)) 
					AND !in_array($post_type_name,$arg_show_post_type_if_not)){
					$child 	 = $this->takien_get_post_parents($post->post_parent,$arg_separator,($zindex-1),true);
					$return .= $this->takien_build_link('#',$zindex=($zindex-1),$post_type_labels,$arg_separator,$child);
				}
			}
			else {
				if((empty($arg_show_post_type_if) OR in_array($post_type_name,$arg_show_post_type_if)) 
					AND !in_array($post_type_name,$arg_show_post_type_if_not)){
					if($arg_show_post_type){
						$return .= $this->takien_build_link(($arg_post_type_link_to_archive ? get_post_type_archive_link($post_type_name) : '#'),$topzindex=($topzindex-1),$post_type_labels,$arg_separator);
					}
					
				}
				if($taxonomy_name){
					if((empty($arg_show_taxonomy_name_if) OR in_array($taxonomy_name,$arg_show_taxonomy_name_if)) 
					AND !in_array($taxonomy_name,$arg_show_taxonomy_name_if_not)){
						$return .= $arg_show_taxonomy_name ? $this->takien_build_link('#',$topzindex=($topzindex-1),$tax_label,$arg_separator) : '';
					}
					$return .= $this->takien_get_term_chain($term_id, $taxonomy_name, $arg_separator,($zindex-1)); 
				}
			}
			$return .= $arg_show_last_title ? '<li><span class="bc-not-clickable">'.get_the_title().'</span></li>' : '';
		}
		
		else if(is_page()){
			$return .= $this->takien_get_post_parents($post->ID,$arg_separator,$zindex);
		}
		if(is_search()){
			$child = false;
			if(function_exists('takien_top_search_query')){
				if($arg_show_preview){
					$child .= '<ul>';
						foreach(takien_top_search_query(5) as $tsq){
							$child .= '<li><a href="'.site_url('/?s='.$tsq['v']).'">'.$tsq['v'].'</a></li>';
						}
					$child .= '</ul>';
				}
			}
			$return .= $this->takien_build_link('#',$zindex=($zindex-1),'Search',$arg_separator,$child);
		}
		if(is_archive()){
		global $paged;
			if(is_category()){
				global $cat;
				$taxonomy = 'category';
				$tax = get_taxonomy($taxonomy);
				if((empty($arg_show_taxonomy_name_if) OR 
				in_array($taxonomy,$arg_show_taxonomy_name_if)) AND 
				!in_array($taxonomy,$arg_show_taxonomy_name_if_not)){
					if($arg_show_taxonomy_name){
						$child 	= $this->takien_get_term_chain( 0, $taxonomy,$arg_separator, $zindex=($zindex-1),true);
						$return .= $this->takien_build_link('#',$zindex=($zindex-1),$tax->labels->singular_name,$arg_separator,$child);
					}
				}
				$return .= $this->takien_get_term_chain( $cat, $taxonomy,$arg_separator, $zindex=($zindex-1));
				if($arg_show_paged AND ($paged > 1)){
					$return .= $this->takien_build_link('#',$zindex=($zindex-2),$paged,$arg_separator);
				}
			}
			else if(is_tag()){
				$taxonomy = 'post_tag';
				if((empty($arg_show_taxonomy_name_if) OR 
				in_array($taxonomy,$arg_show_taxonomy_name_if)) AND 
				!in_array($taxonomy,$arg_show_taxonomy_name_if_not)){
					$return .= $this->takien_build_link('#',$zindex=($zindex-1),'Tags',$arg_separator);
				}
				$return .= $this->takien_build_link('#',$zindex=($zindex-1),single_tag_title('',0),$arg_separator);
			}
			else if(is_tax()){
				global $taxonomy;
				$tax 	 = get_taxonomy($taxonomy);
				$term	 = get_term_by('slug',get_query_var($taxonomy),$taxonomy);
				$term_id = $term->term_id;

				if((empty($arg_show_taxonomy_name_if) OR 
				in_array($taxonomy,$arg_show_taxonomy_name_if)) AND 
				!in_array($taxonomy,$arg_show_taxonomy_name_if_not)){
					if($arg_show_taxonomy_name){
						$child 	 = $this->takien_get_term_chain( $term_id, $taxonomy,$arg_separator, $zindex=($zindex-1),true);
						$return .= $this->takien_build_link('#',$zindex=($zindex-1),$tax->labels->singular_name,$arg_separator,$child);
					}
				}
				$return .= $this->takien_get_term_chain( $term_id, $taxonomy,$arg_separator, $zindex=($zindex-1),false,$tax->hierarchical);

			}
			else if(is_author()){
				global $author;
				
				if($arg_show_preview) {

					$child	 = '<ul>'.wp_list_authors('show_fullname=1&echo=0&optioncount=0&orderby=post_count&order=DESC').'</ul>';
				}
				
				$return .= $this->takien_build_link('#',$zindex=($zindex-1),'Author',$arg_separator,$child);
				$return .= $this->takien_build_link('#',$zindex=($zindex-1),get_the_author_meta( 'user_nicename', $author ),$arg_separator);
			}
			else if(is_date()){
				$year 		= get_query_var('year');
				$monthnum 	= get_query_var('monthnum');
				$day 		= get_query_var('day');
				
				$child   = $this->takien_get_archive_parents('', '','yearly');
				$return .= $this->takien_build_link('#',$zindex=($zindex-1),'Archive',$arg_separator,$child);
				
				$child	 = $this->takien_get_archive_parents($year, 'year','monthly');
				$yearlink = $this->takien_build_link(get_year_link( $year ),$zindex=($zindex-1),$year,$arg_separator,$child);
				
				$monthlink = $yearlink;
				
				$child  	= $this->takien_get_archive_parents($monthnum, 'month','daily');
				$monthlink 	.= $this->takien_build_link(get_month_link($year, $monthnum),$zindex=($zindex-1),date('F', mktime(0,0,0,$monthnum,1)),$arg_separator,$child);
				
				$daylink 	= $monthlink;
				$daylink 	.= $this->takien_build_link('#',$zindex=($zindex-1),$day,$arg_separator);
				
				if(is_year()){
					$return .= $yearlink;
				}
				else if(is_month()){
					$return .= $monthlink;
					
				}
				else if(is_day()){
					$return .= $daylink;
				}
			}
			else{
			//
			}
		}
		else if(is_404()){
			$return .= $this->takien_build_link('#',$zindex=($zindex-1),'404',$arg_separator);
		}

		
		$return .= '</ul></'.$arg_wrap.'>';
/* 		if(is_home()) {
			$return = false;
		} */

		if($arg_echo){
			echo $return;
		}
		else{
			return $return;
		}
	}
	function takien_archive_where($where,$args){
		$year		= isset($args['year']) 		? $args['year'] 	: '';
		$month		= isset($args['month']) 	? $args['month'] 	: '';
		$monthname	= isset($args['monthname']) ? $args['monthname']: '';
		$day		= isset($args['day']) 		? $args['day'] 		: '';
		$dayname	= isset($args['dayname']) 	? $args['dayname'] 	: '';
		
		if($year){
			$where .= " AND YEAR(post_date) = '$year' ";
			$where .= $month ? " AND MONTH(post_date) = '$month' " : '';
			$where .= $day ? " AND DAY(post_date) = '$day' " : '';
		}
		if($month){
			$where .= " AND MONTH(post_date) = '$month' ";
			$where .= $day ? " AND DAY(post_date) = '$day' " : '';
		}
		if($monthname){
			$where .= " AND MONTHNAME(post_date) = '$monthname' ";
			$where .= $day ? " AND DAY(post_date) = '$day' " : '';
		}
		if($day){
			$where .= " AND DAY(post_date) = '$day' ";
		}
		if($dayname){
			$where .= " AND DAYNAME(post_date) = '$dayname' ";
		}
		return $where;
	}
	
	function takien_get_archive_parents( $value='', $key = '',$type='monthly',$zindex='') {
		add_filter( 'getarchives_where',array(&$this,'takien_archive_where'),10,2);
					$args = array(
							'type'            => $type,
							'echo'            => 0,
							$key			 => $value
					); 
		$return = '<ul>'.wp_get_archives($args).'</ul>';
		return $return;
	}
	
	function takien_get_term_chain( $id, $taxonomy='category', $separator = '&raquo;',$zindex='',$childonly=false,$hierarchical=true,$last=1) {
        $chain = '';
        //$parent = &get_category( $id );
		$parent = &get_term($id,$taxonomy);
		if($id==0){
			$parent = &get_terms($taxonomy);
		}
        if ( is_wp_error( $parent ) ) {
              return $parent;
		}
        $name = $parent->name;
        if ( $parent->parent && ( $parent->parent != $parent->term_id ) ) {
              $chain .= $this->takien_get_term_chain( $parent->parent, $taxonomy, $separator ,($zindex+1),false,true,$last+1);
        }
		
		$args = Array(
			'child_of'		=> $id,
			'hide_empty' 	=> 0,
			'title_li'		=> '',
			'echo'			=> 0,
			'taxonomy'		=> $taxonomy,
			'show_option_none'	=> ''
		);
		$childcats = $hierarchical ? wp_list_categories( $args ) : false; 
		
		$childchain = "\r\n\t".'<ul>';
		$childchain .= $childcats; 
		$childchain .= "\t\t".'</ul>'."\r\n";
			
		$child 	= $childcats ? $childchain : false;
		$chain .= $this->takien_build_link(get_category_link( $parent->term_id ),$zindex=($zindex-1),$name,$separator,$child,true);
		
		return $childonly ? $child : $chain;
	}
	
	function takien_get_post_parents( $id, $separator = '&raquo;',$zindex='',$childonly=false) {
        $chain = '';
		$child = false;
		$parent = get_post($id);
		
		if ($parent->post_parent !== 0){
				$chain .= $this->takien_get_post_parents($parent->post_parent,$separator,($zindex+1));
			}
		
		if(is_page()){
			$listchildren = wp_list_pages('child_of='.$id.'&title_li=&echo=0');
			if($listchildren){
				$child .= "\t\t".'<ul>'."\r\n";
				$child .= "\t\t".$listchildren;
				$child .= "\t\t".'</ul>'."\r\n";
			}
		}
		else {
			$listchildren = get_posts('post_parent='.$id.'&numberposts=-1&post_type=attachment');
			
			if($listchildren){
				$child .= "\t\t".'<ul>'."\r\n";
				foreach($listchildren as $lc){
					$child .= '<li><a href="'.get_permalink($lc->ID).'">'.$lc->post_title.'</a></li>';
				}
				$child .= "\t\t".'</ul>'."\r\n";
			}
		}
		
		$chain .= $this->takien_build_link(get_permalink($id),$zindex=($zindex-1),$parent->post_title,$separator,$child,true);

		return $childonly ? $child : $chain;
	}
	private function takien_build_link($href,$zindex,$label,$separator='',$child=false,$microdata=false){
		$microdata = is_single() ? $microdata : false;
		$return = '<li class="'.($child ? 'bc-parent-with-ul' : '').' bc-top"><a '.($microdata ? ' itemprop="url"' : '').' style="z-index:'.$zindex.'" href="'.$href.'"><span '.($microdata ? 'itemprop="title"':'').'>'.$label.'</span>'.((trim($label) !== trim(get_the_title())) ? $separator : '').'</a>';
		$return .= $child;
		$return .= '</li>';
		return $return;
	}
	
	function takien_breadcrumb_script() {
		wp_enqueue_script('takien_breadcrumb_script',plugins_url( 'js/takien-breadcrumb.js' , __FILE__ ),Array('jquery'));
	}
	
	function takien_breadcrumb_style() {
		wp_enqueue_style('takien_breadcrumb_style', plugins_url('skins/core/takien-breadcrumb.css', __FILE__),false,$this->takien_breadcrumb_base('version'));
    }
	
	

	
	function takien_breadcrumb_admin_script(){
	$page = $_GET['page'];
	if($page == $this->takien_breadcrumb_base('base_name')) { ?>
	<script type="text/javascript">
	//<![CDATA[
		jQuery(document).ready(function($){
			
		});
	//]]>
		</script>
	<?php
	}
	} /* end takien_breadcrumb_admin_script*/
	
	function takien_breadcrumb_settings_link($links) {
		  $settings_link = '<a href="options-general.php?page='.$this->takien_breadcrumb_base('base_name').'">Settings</a>';
		  array_unshift($links, $settings_link);
		  return $links;
	}  /* end takien_breadcrumb_settings_link*/
	
	function takien_breadcrumb_help($help) {
		$page = $_GET['page'];
			if($page == $this->takien_breadcrumb_base('base_name')) {
				$help = '
						<h2>'.$this->takien_breadcrumb_base('name').' '.$this->takien_breadcrumb_base('version').'</h2>
						<h5>Instruction:</h5>
						
						';
			}
		return $help;
	} /* end takien_breadcrumb_help */
	
	function takien_breadcrumb_add_page() {
		add_options_page($this->takien_breadcrumb_base('name'), $this->takien_breadcrumb_base('name'), 'administrator', $this->takien_breadcrumb_base('base_name'), array(&$this,'takien_breadcrumb_page'));
	} /* end takien_breadcrumb_add_page*/

	function takien_breadcrumb_page() { ?>
		<div class="wrap">
		sdf
		</div>
	<?php
		}
			
} /* end class */
}
if (class_exists('TakienBreadcrumb')) {
	new TakienBreadcrumb();
}
function takien_breadcrumb($args=''){
	$return = new TakienBreadcrumb;
	return $return->takien_breadcrumb_render($args);
}
 /* lo gw end */
?>