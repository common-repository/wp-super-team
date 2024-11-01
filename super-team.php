<?php
/**
 * Plugin Name: WP Super Team
 * Description: A simple, lightweight, fully Responsive WordPress Team display plugin to create & manage your Team page easily with three different column layouts and 20 different color sets.
 * Version: 1.0.0
 * Author: Vidya LB
 * Author URI: http://vidyalb.com
 * License: GPL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// if direct access.
if ( ! class_exists( 'Super_Team' ) ) {
	/**
	 * Super_Team main class
	 * author : Vidya
	 */
	class Super_Team {
		/**
		 * Plugin settings.
		 */
		private $settings;
		/**
		 * Super_Team constructor
		 */
		public function __construct() {
			$this->settings = array(
				'plugin_path'       => plugin_dir_path( __FILE__ ),
				'plugin_url'        => plugin_dir_url( __FILE__ ),
				'plugin_base'       => dirname( plugin_basename( __FILE__ ) ),
				'plugin_file'       => __FILE__,
				'plugin_version'    => '1.0.0',
			);
			$this->sp_load_plugin_textdomain();
			$this->sp_init();
		}
		/**
		 * Localisation
		 */
		public function sp_load_plugin_textdomain() {
			load_plugin_textdomain( 'wp-super-team', false, $this->settings['plugin_base'] . '/language/' );
		}
		/**
		 * Plugin main functions
		 *
		 * @since: 1.0.0
		 */
		public function sp_init() {
			add_action( 'wp_enqueue_scripts', array( $this, 'sp_shortcode_wp_enqueue_scripts' ) );
			add_action( 'init', array( $this, 'register_sp_cpt' ) );
			add_action( 'add_meta_boxes_wp_super_team', array( $this, 'sp_register_meta_box' ) );
			add_action( 'save_post', array( $this, 'sp_save_meta' ), 10, 3 );
			add_filter( 'manage_wp_super_team_posts_columns', array( $this, 'custom_columns_member' ) );
			add_action( 'manage_wp_super_team_posts_custom_column', array( $this, 'custom_columns_member_data' ), 10, 2 );
			add_shortcode( 'wp-super-team', array( $this, 'sp_render_shortcode' ) );
			add_action( 'admin_menu', array( $this, 'sp_add_settings_page' ), 10 );
			add_action( 'admin_init', array( $this, 'sp_save_form_option' ) );
			add_action( 'init', array( $this, 'sp_custom_image_size' ) );
			add_action( 'after_setup_theme', array( $this, 'sp_thumbnail_support' ) );
		}
		/**
		 * Register preview scripts and styles
		 *
		 * @since: 1.0.0
		 */
		public function sp_shortcode_wp_enqueue_scripts() {
			wp_register_script( 'sp-script', plugins_url( 'js/super-team.js', $this->settings['plugin_file'] ), array( 'jquery' ), $this->settings['plugin_version'], true );
			wp_register_style( 'sp-style', plugins_url( 'css/super-team.css', $this->settings['plugin_file'] ), false, $this->settings['plugin_version'], 'all' );
			wp_register_style( 'sp-font-awesome', plugins_url( 'css/font-awesome/css/font-awesome.min.css', $this->settings['plugin_file'] ), false, $this->settings['plugin_version'], 'all' );
		}
		/**
		 * Create custom post type for team
		 *
		 * @since: 1.0.0
		 */
		public function register_sp_cpt() {
			// Create wp_super_team post type
			if ( post_type_exists( 'wp_super_team' ) ) {
				return;
			}
			$singular = __( 'Staff', 'wp-super-team' );
			$plural   = __( 'Staffs', 'wp-super-team' );
			$labels = array(
				'name' => $plural,
				'singular_name' => $singular,
				'menu_name' => __( 'Super Team', 'wp-super-team' ),
				'add_new' => __( 'Add New Staff', 'wp-super-team' ),
				/* translators: %s: singular term */
				'add_new_item' => sprintf( __( 'Add %s', 'wp-super-team' ), $singular ),
				/* translators: %s: singular term */
				'new_item' => sprintf( __( 'New %s', 'wp-super-team' ), $singular ),
				/* translators: %s: singular term */
				'edit_item' => sprintf( __( 'Edit %s', 'wp-super-team' ), $singular ),
				/* translators: %s: singular term */
				'view_item' => sprintf( __( 'View %s', 'wp-super-team' ), $singular ),
				'all_items' => sprintf( __( 'Staffs', 'wp-super-team' ) ),
				/* translators: %s: plural term */
				'search_items' => sprintf( __( 'Search %s', 'wp-super-team' ), $plural ),
				/* translators: %s: plural term */
				'not_found' => sprintf( __( 'No %s found', 'wp-super-team' ), $plural ),
				/* translators: %s: plural term */
				'not_found_in_trash' => sprintf( __( 'No %s found in trash', 'wp-super-team' ), $plural ),
			);
			$cp_args = array(
				'labels' => $labels,
				/* translators: %s: plural term */
				'description' => sprintf( __( 'This is where you can create and manage %s.', 'wp-super-team' ), $plural ),
				'public'             => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'capability_type' => 'post',
				'supports' => array(
					'title',
					'editor',
					'thumbnail',
					'page-attributes',
				),
				'menu_icon' => 'dashicons-universal-access',
				'show_in_rest' => true,
			);
			register_post_type( 'wp_super_team', $cp_args );
		}
		/**
		 * Render shortcode
		 *
		 * @since: 1.0.0
		 */
		public function sp_render_shortcode() {
			// Include styles and scripts only when shortcode is present.
			wp_enqueue_script( 'sp-script' );
			wp_enqueue_style( 'sp-style' );
			wp_enqueue_style( 'sp-font-awesome' );
			$args = array(
				'post_type' => 'wp_super_team',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key'     => 'sp_status',
						'value'   => '0',
						'compare' => '!=',
					),
				),
				'orderby' => 'menu_order',
				'order'   => 'ASC',
			);
			$output = '';
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				$output .= '<section class="sp-container"><div class="active-with-click">';
				while ( $query->have_posts() ) :
					$social = '';
					$query->the_post();
					$class = 'sp-column-2';
					$color_scheme = ! empty( get_option( 'sp_color_scheme' ) ) ? get_option( 'sp_color_scheme' ) : 'Red';
					$layout = get_option( 'sp_layout' );
					if ( '2' == $layout ) {
						$class = 'sp-column-2';
					} elseif ( '3' == $layout ) {
						$class = 'sp-column-3';
					} elseif ( '4' == $layout ) {
						$class = 'sp-column-4';
					}
					if ( get_post_meta( get_the_ID(), 'sp_fb_url', true ) ) {
						$social .= '<a class="fa fa-fw fa-facebook" href="' . esc_url( get_post_meta( get_the_ID(), 'sp_fb_url', true ) ) . '" target="_blank"></a>';
					}
					if ( get_post_meta( get_the_ID(), 'sp_twitter_url', true ) ) {
						$social .= '<a class="fa fa-fw fa-twitter" href="' . esc_url( get_post_meta( get_the_ID(), 'sp_twitter_url', true ) ) . '" target="_blank"></a>';
					}
					if ( get_post_meta( get_the_ID(), 'sp_linkedin_url', true ) ) {
						$social .= '<a class="fa fa-fw fa-linkedin" href="' . esc_url( get_post_meta( get_the_ID(), 'sp_linkedin_url', true ) ) . '" target="_blank"></a>';
					}
					if ( get_post_meta( get_the_ID(), 'sp_gplus_url', true ) ) {
						$social .= '<a class="fa fa-fw fa-google-plus" href="' . esc_url( get_post_meta( get_the_ID(), 'sp_gplus_url', true ) ) . '" target="_blank"></a>';
					}
					$output .= '<div class="' . $class . '">
									<article class="sp-card ' . $color_scheme . '">
										<h2>
											<span>' . get_the_title() . '</span>
											<strong>
												<i class="fa fa-fw fa-star"></i>
												' . get_post_meta( get_the_ID(), 'sp_staff_designation', true ) . '
											</strong>
										</h2>
										<div class="sp-content">
											<div class="img-container">
												' . $this->staff_thumbnail( get_the_ID() ) . '
											</div>
											<div class="sp-description">
											' . get_the_content() . '
											</div>
										</div>
										<a class="sp-btn-action">
											<i class="fa fa-bars"></i>
										</a>
										<div class="sp-footer"> ' . $social . '
										</div>
									</article>
								</div>';
				endwhile;
				$output .= '</div></section><div class="clear"></div>';
			}
			wp_reset_postdata();
			return $output;
		}
		/**
		 * Get thumbnail of post
		 *
		 * @param  Int $team_id id of the post.
		 *
		 * @since: 1.0.0
		 */
		public function staff_thumbnail( $team_id ) {
			$defaultimage = '<img src="' . $this->settings['plugin_url'] . 'images/default-user.png" alt="' . esc_attr( get_the_title( $team_id ) ) . '">';
			$member_image = ( has_post_thumbnail( $team_id ) ) ? get_the_post_thumbnail( $team_id, 'super_team' ) : $defaultimage;
			return $member_image;
		}
		/**
		 * Get thumbnail of post
		 *
		 * @param  Object $post post object.
		 *
		 * @since: 1.0.0
		 */
		public function sp_register_meta_box( $post ) {
			add_meta_box(
				'sp-meta-data',
				__( 'Staff Details' ),
				array( $this, 'sp_render_meta_box' ),
				'wp_super_team',
				'normal',
				'high'
			);
		}
		/**
		 * Meta data of team
		 *
		 * @param  Object $post post object.
		 *
		 * @since: 1.0.0
		 */
		public function sp_render_meta_box( $post ) {
			wp_nonce_field( 'sp_meta_box_nonce', 'meta_box_nonce' );
			echo '<label for="sp_staff_designation">Enter Designation: </label><input type="text" name="sp_staff_designation" value="' . esc_html( get_post_meta( $post->ID, 'sp_staff_designation', true ) ) . '" size="50"> <div class="clear"></div>';
			echo '<label for="sp_fb_url"> Facebook link: </label> <input type="text" name="sp_fb_url" value="' . esc_url( get_post_meta( $post->ID, 'sp_fb_url', true ) ) . '" size="50"> <div class="clear"></div>';
			echo '<label for="sp_twitter_url"> Twitter link: </label> <input type="text" name="sp_twitter_url" value="' . esc_url( get_post_meta( $post->ID, 'sp_twitter_url', true ) ) . '" size="50"> <div class="clear"></div>';
			echo '<label for="sp_linkedin_url"> Linkedin link: </label> <input type="text" name="sp_linkedin_url" value="' . esc_url( get_post_meta( $post->ID, 'sp_linkedin_url', true ) ) . '" size="50"> <div class="clear"></div>';
			echo '<label for="sp_gplus_url"> Google Plus link: </label> <input type="text" name="sp_gplus_url" value="' . esc_url( get_post_meta( $post->ID, 'sp_gplus_url', true ) ) . '" size="50"> <div class="clear"></div>';
			echo "<label for=sp_status> Select status: </label>
					<select name='sp_status' autocomplete='off'>
						<option value='1' " . ( get_post_meta( $post->ID, 'sp_status', true ) === '1' ? ' selected' : '' ) . " > Active </option>
						<option value='0'  " . ( get_post_meta( $post->ID, 'sp_status', true ) === '0' ? ' selected' : '' ) . " > Inactive </option>
					</select>
					<div class=clear></div>";
		}
		/**
		 * Save post metadata when a post is saved.
		 *
		 * @param int  $post_id The post ID.
		 * @param post $post The post object.
		 * @param bool $update Whether this is an existing post being updated or not.
		 */
		public function sp_save_meta( $post_id, $post, $update ) {
			// verify nonce.
			if ( ! isset( $_POST['meta_box_nonce'] ) || ! wp_verify_nonce(  $_POST['meta_box_nonce'], 'sp_meta_box_nonce' ) ) {
				return;
			}
			$post_type = get_post_type( $post_id );

			// If this isn't a 'wp_super_team' post, don't update it.
			if ( 'wp_super_team' != $post_type ) {
				return;
			}
			// - Update the post's metadata.
			if ( isset( $_POST['sp_staff_designation'] ) ) {
				update_post_meta( $post_id, 'sp_staff_designation', sanitize_text_field( wp_unslash( $_POST['sp_staff_designation'] ) ) );
			}
			if ( isset( $_POST['sp_fb_url'] ) ) {
				update_post_meta( $post_id, 'sp_fb_url', esc_url( $_POST['sp_fb_url'] ) );
			}
			if ( isset( $_POST['sp_twitter_url'] ) ) {
				update_post_meta( $post_id, 'sp_twitter_url', esc_url( $_POST['sp_twitter_url'] ) );
			}
			if ( isset( $_POST['sp_linkedin_url'] ) ) {
				update_post_meta( $post_id, 'sp_linkedin_url', esc_url( $_POST['sp_linkedin_url'] ) );
			}
			if ( isset( $_POST['sp_gplus_url'] ) ) {
				update_post_meta( $post_id, 'sp_gplus_url', esc_url( $_POST['sp_gplus_url'] ) );
			}
			if ( isset( $_POST['sp_status'] ) ) {
				update_post_meta( $post_id, 'sp_status', sanitize_text_field( $_POST['sp_status'] ) );
			}
		}
		/**
		 * Save post metadata when a post is saved.
		 *
		 * @param Array $columns default column array.
		 */
		public function custom_columns_member( $columns ) {
			$columns = array(
				'cb' => '<input type="checkbox" />',
				'title' => __( 'Name', 'wp-super-team' ),
				'featured_image' => __( 'Photo', 'wp-super-team' ),
				'designation' => __( 'Designation', 'wp-super-team' ),
				'status' => __( 'Status', 'wp-super-team' ),
				'date' => 'Date',
			);
			return $columns;
		}
		/**
		 * Custom member table data.
		 *
		 * @param column $column default column array.
		 * @param post   $post_ID The post id.
		 *
		 * @since 1.0
		 */
		public function custom_columns_member_data( $column, $post_ID ) {
			switch ( $column ) {
				case 'featured_image':
					the_post_thumbnail( 'thumbnail' );
					break;
				case 'designation':
					echo esc_html( get_post_meta( $post_ID, 'sp_staff_designation', true ) );
					break;
				case 'status':
					echo ( get_post_meta( $post_ID, 'sp_status', true ) == '1' || empty( get_post_meta( $post_ID, 'sp_status', true ) ) && get_post_meta( $post_ID, 'sp_status', true ) != '0' ) ? 'Active' : 'Inactive';
					break;
			}

		}
		/**
		 * Adding submenu items
		 *
		 * @since 1.0.0
		 */
		public function sp_add_settings_page() {
			add_submenu_page( 'edit.php?post_type=wp_super_team', __( 'Options', 'wp-super-team' ), __( 'Options', 'wp-super-team' ), 'manage_options', 'super-team-options.php?post_type=wp_super_team', array( $this, 'sp_render_options' ) );
		}
		/**
		 * Global plugin options
		 *
		 * @since 1.0.0
		 */
		public function sp_render_options() {
			echo '<div class="wrap">
			<h1 class="wp-heading-inline">Options</h1>
			<hr class="wp-header-end">
			<form name="sp-options" method="post">
				' . wp_nonce_field( 'sp_options_nonce', 'options_nonce' ) . '
				<label for="sp_layout"> Select layout: </label>
				<select name="sp_layout" autocomplete="off">
					<option value="2" ' . ( get_option( 'sp_layout' ) === '2' ? '  selected' : '' ) . ' > 2 column </option>
					<option value="3"  ' . ( get_option( 'sp_layout' ) === '3' ? '  selected' : '' ) . ' > 3 column </option>
					<option value="4"  ' . ( get_option( 'sp_layout' ) === '4' ? '  selected' : '' ) . ' > 4 column </option>
				</select>
				<div class=clear></div>
				<label for="sp_color_scheme"> Select color scheme: </label>
				<select name="sp_color_scheme" autocomplete="off">
					<option value="Red" ' . ( get_option( 'sp_color_scheme' ) === 'Red' ? '  selected' : '' ) . ' > Red </option>
					<option value="Blue-Grey"  ' . ( get_option( 'sp_color_scheme' ) === 'Blue-Grey' ? '  selected' : '' ) . ' > Blue-Grey </option>
					<option value="Pink"  ' . ( get_option( 'sp_color_scheme' ) === 'Pink' ? '  selected' : '' ) . ' > Pink </option>
					<option value="Purple"  ' . ( get_option( 'sp_color_scheme' ) === 'Purple' ? '  selected' : '' ) . ' > Purple </option>
					<option value="Deep-Purple"  ' . ( get_option( 'sp_color_scheme' ) === 'Deep-Purple' ? '  selected' : '' ) . ' > Deep-Purple </option>
					<option value="Indigo"  ' . ( get_option( 'sp_color_scheme' ) === 'Indigo' ? '  selected' : '' ) . ' > Indigo </option>
					<option value="Blue"  ' . ( get_option( 'sp_color_scheme' ) === 'Blue' ? '  selected' : '' ) . ' > Blue </option>
					<option value="Light-Blue"  ' . ( get_option( 'sp_color_scheme' ) === 'Light-Blue' ? '  selected' : '' ) . ' > Light-Blue </option>
					<option value="Cyan"  ' . ( get_option( 'sp_color_scheme' ) === 'Cyan' ? '  selected' : '' ) . ' > Cyan </option>
					<option value="Teal"  ' . ( get_option( 'sp_color_scheme' ) === 'Teal' ? '  selected' : '' ) . ' > Teal </option>
					<option value="Green"  ' . ( get_option( 'sp_color_scheme' ) === 'Green' ? '  selected' : '' ) . ' > Green </option>
					<option value="Light-Green"  ' . ( get_option( 'sp_color_scheme' ) === 'Light-Green' ? '  selected' : '' ) . ' > Light-Green </option>
					<option value="Lime"  ' . ( get_option( 'sp_color_scheme' ) === 'Lime' ? '  selected' : '' ) . ' > Lime </option>
					<option value="Yellow"  ' . ( get_option( 'sp_color_scheme' ) === 'Yellow' ? '  selected' : '' ) . ' > Yellow </option>

					<option value="Amber"  ' . ( get_option( 'sp_color_scheme' ) === 'Amber' ? '  selected' : '' ) . ' > Amber </option>
					<option value="Orange"  ' . ( get_option( 'sp_color_scheme' ) === 'Orange' ? '  selected' : '' ) . ' > Orange </option>
					<option value="Deep-Orange"  ' . ( get_option( 'sp_color_scheme' ) === 'Deep-Orange' ? '  selected' : '' ) . ' > Deep-Orange </option>
					<option value="Brown"  ' . ( get_option( 'sp_color_scheme' ) === 'Brown' ? '  selected' : '' ) . ' > Brown </option>
					<option value="Grey"  ' . ( get_option( 'sp_color_scheme' ) === 'Grey' ? '  selected' : '' ) . ' > Grey </option>
					<option value="Blue-Grey"  ' . ( get_option( 'sp_color_scheme' ) === 'Blue-Grey' ? '  selected' : '' ) . ' > Blue-Grey </option>
				</select>
				<div class=clear></div>
				<p><input type="submit" value="save" class="button button-primary" name="sp_save_options"></p>
			</form>
			<div class=clear></div>
			</div>';
		}
		/**
		 * Save global plugin options
		 *
		 * @since 1.0.0
		 */
		public function sp_save_form_option() {
			// verify nonce.
			if ( ! empty( $_POST['sp_save_options'] ) && check_admin_referer( 'sp_options_nonce', 'options_nonce' ) ) {
				$layout = sanitize_text_field( $_POST['sp_layout'] );
				$color_scheme = sanitize_text_field( $_POST['sp_color_scheme'] );
				update_option( 'sp_layout', $layout );
				update_option( 'sp_color_scheme', $color_scheme );
			}
		}
		/**
		 * Custom image size
		 *
		 * @since 1.0
		 */
		public function sp_custom_image_size() {
			if ( function_exists( 'add_image_size' ) ) {
				add_image_size( 'super_team', 500, 500, true );
			}
		}
		/**
		 * Ensure post thumbnail support is turned on.
		 */
		public function sp_thumbnail_support() {
			if ( ! current_theme_supports( 'post-thumbnails' ) ) {
				add_theme_support( 'post-thumbnails' );
			}
			add_post_type_support( 'wp_super_team', 'thumbnail' );
		}
	}
	new Super_Team();
}
