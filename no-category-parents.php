<?php
/*
Plugin Name: No category parents
Description: Removes category parents from your category permalinks.
Version: 0.1
Author: Sergio Milardovich
Author URI: Author URI: http://milardovich.com.ar/
*/

/*  
    Based on "WP No Category Base" code -> http://wordpresssupplies.com/

    Copyright 2009  Sergio Milardovich  (email : smilardovich@yahoo.com.ar)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Refresh rules on activation/deactivation/category changes
register_activation_hook(__FILE__,'no_category_parents_refresh_rules');
add_action('created_category','no_category_parents_refresh_rules');
add_action('edited_category','no_category_parents_refresh_rules');
add_action('delete_category','no_category_parents_refresh_rules');
function no_category_parents_refresh_rules() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}
register_deactivation_hook(__FILE__,'no_category_parents_deactivate');
function no_category_parents_deactivate() {
	remove_filter('category_rewrite_rules', 'no_category_parents_rewrite_rules'); // We don't want to insert our custom rules again
	no_category_parents_refresh_rules();
}

// Remove category base
add_filter('category_link', 'no_category_parents',1000,2);
function no_category_parents($catlink, $category_id) {
	$category = &get_category( $category_id );
	if ( is_wp_error( $category ) )
		return $category;
	$category_nicename = $category->slug;

	$catlink = trailingslashit(get_option( 'home' )) . user_trailingslashit( $category_nicename, 'category' );
	return $catlink;
}

// Add our custom category rewrite rules
add_filter('category_rewrite_rules', 'no_category_parents_rewrite_rules');
function no_category_parents_rewrite_rules($category_rewrite) {
	//print_r($category_rewrite); // For Debugging
	
	$category_rewrite=array();
	$categories=get_categories(array('hide_empty'=>false));
	foreach($categories as $category) {
		$category_nicename = $category->slug;
		$category_rewrite['('.$category_nicename.')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
		$category_rewrite['('.$category_nicename.')/page/?([0-9]{1,})/?$'] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
		$category_rewrite['('.$category_nicename.')/?$'] = 'index.php?category_name=$matches[1]';
	}
	// Redirect support from Old Category Base
	global $wp_rewrite;
	$old_base = $wp_rewrite->get_category_permastruct();
	$old_base = str_replace( '%category%', '(.+)', $old_base );
	$old_base = trim($old_base, '/');
	$category_rewrite[$old_base.'$'] = 'index.php?category_redirect=$matches[1]';
	
	//print_r($category_rewrite); // For Debugging
	return $category_rewrite;
}

// Add 'category_redirect' query variable
add_filter('query_vars', 'no_category_parents_query_vars');
function no_category_parents_query_vars($public_query_vars) {
	$public_query_vars[] = 'category_redirect';
	return $public_query_vars;
}
// Redirect if 'category_redirect' is set
add_filter('request', 'no_category_parents_request');
function no_category_parents_request($query_vars) {
	//print_r($query_vars); // For Debugging
	if(isset($query_vars['category_redirect'])) {
		$catlink = trailingslashit(get_option( 'home' )) . user_trailingslashit( $query_vars['category_redirect'], 'category' );
		status_header(301);
		header("Location: $catlink");
		exit();
	}
	return $query_vars;
}
?>