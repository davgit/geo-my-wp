<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GMW_Maps_API' ) ) :

/**
 * GEO my WP Map class
 *
 * This class handles some features that use Google Maps API such as generating a map,  
 * address autocomplete, Directions and more.
 * 
 * All maps generated by this class ( via GEO my WP or its extensions ) collected into the $map_elements array 
 * and sent to the map script ( JavaScript file ).
 *
 * The map script then loops through the collection of maps and generates each map.
 *
 * It is possible to control almost every feature of the map by passing the desired arguments 
 * to the get_map_args() and get_map_element() functions.
 *
 * Usually, to generate a map you first need to use the get_map_element() function 
 * to generate the map element on the page in the desired location. 
 *
 * Then you need to use the get_map_elements() function when you have all the arguments ready. 
 * 
 * For example, when generating Posts Locator map you need to use get_map_element() after the loop, 
 * when you have all the locations that needs to be displayed ready to pass to the map.
 *
 * enqueue_scripts is "attached" to the footer using wp_footer action and will enqueue only the required 
 * JS files based on the map arguments.
 *
 * This way we don't enqueue JS files that are not being used.
 *
 * @since 3.0
 *
 * @author Eyal Fitoussi
 * 
 */
class GMW_Maps_API {

	/**
	 * Array of maps that need to be generated on the page
	 * @var array
	 */
	public static $map_elements = array();

	/**
	 * Collection of address fields that need to have address autocomplete triggered
	 * @var array
	 */
	private static $address_autocomplete = array();

	/**
	 * Marker Clusterer script trigger
	 * @var boolean
	 */
	private static $markers_clusterer = false;

	/**
	 * Marker Spiderfier script trigger
	 * @var boolean
	 */
	private static $markers_spiderfier = false;

	/**
	 * infobox script trigger
	 * @var boolean
	 */
	private static $infobox = false;

	/**
	 * infobox script trigger
	 * @var boolean
	 */
	private static $infobubble = false;

	/**
	 * Get directions script trigger
	 * @var boolean
	 */
	private static $directions = false;

	/**
	 * Popup draggable window script trigger
	 * @var boolean
	 */
	private static $draggable_window = false;

	/**
	 * map enabler
	 * @var boolean
	 */
	private static $map_enabled = false;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {}	

	/**
	 * Generate a map.
	 * 
	 * This function combines both get_map_args() and get_map_elements() methods to generate a map.
	 *
	 * You can use this function when you have all the aruments needed for 
	 * both get_map_args() and get_map_elements() at the same time.
	 *
	 * @param  array  $map_args      map arguments
	 * @param  array  $map_options   map_options ( https://developers.google.com/maps/documentation/javascript/reference#MapOptions )
	 * @param  array  $locations     object locations ( posts, users... )
	 * @param  array  $user_position user position
	 * @param  array  $form          GEO my WP form if exists
	 * 
	 * @return void
	 */
	public static function get_map( $map_args = array(), $map_options = array(), $locations = array(), $user_position = array(), $form = array() ) {

		// generate map args
		self::get_map_args( $map_args, $map_options, $locations, $user_position, $form );

		// generate map element
		return self::get_map_element( $map_args );
	}

	/**
	 * Generate the HTML map element on the page
	 *
	 * @Since 3.0
	 * 
	 * @author Eyal Fitoussi
	 * 
	 * @param  array $args map args to define the map features
	 * 
	 * array( 
	 *   'map_id'		  => '',      // the ID of the map
	 * 	 'map_type'		  => 'na',    // Map type ( posts_locator, memebrs_locator... )
	 *	 'prefix'		  => '',      // map prefix
	 *	 'map_width'	  => '100%',  // map width in pixels or percentage
	 *	 'map_height'	  => '350px', // map height in pixels or percentage
	 *	 'expand_on_load' => false,   // display map full screen when it first loads
	 *	 'form'			  => false,   // GMW form if exists
	 *	 'init_visible'	  => false
	 * );
	 * 
	 * @return HTML element of map
	 * 
	 */
	public static function get_map_element( $args ) {
		
		// default map args
		$default_args = array( 
			'map_id'		=> '',
			'map_type'		=> 'na',
			'prefix'		=> '',
			'map_width'		=> '100%',
			'map_height'	=> '350px',
			'expand_on_load'=> false,
			'init_visible'	=> false,
			//'form'			=> false
		);

		// merge defaults with incoming args
		$args = array_merge( $default_args, $args );

		// modify the map args
		$args = apply_filters( 'gmw_map_output_args', $args );
	    $args = apply_filters( "gmw_map_output_args_{$args['map_id']}", $args );

	    // if expend map on load
	    if ( $args['expand_on_load'] ) {
	    	$expanded = 'gmw-expanded-map';
	    	$trigger  = 'gmw-icon-resize-small';
	    } else {
	    	$expanded = '';
	    	$trigger  = 'gmw-icon-resize-full';
	   	}

		$map_id     = esc_attr( $args['map_id'] );
		$prefix     = esc_attr( $args['prefix'] );
		$map_type   = esc_attr( $args['map_type'] );
		$map_width  = esc_attr( $args['map_width'] );
		$map_height = esc_attr( $args['map_height'] );
		$map_title  = esc_html( __( 'Resize map','GMW' ) );
		$display    = ( $args['init_visible'] || $args['expand_on_load'] ) ? '' : 'display:none;';

		// generate the map element
	    $output['wrap']   = "<div id=\"gmw-map-wrapper-{$map_id}\" class=\"gmw-map-wrapper {$prefix} {$map_type} {$expanded}\" style=\"{$display}width:{$map_width};height:{$map_height};\">";
	    $output['toggle'] = "<span id=\"gmw-resize-map-toggle-{$map_id}\" class=\"gmw-resize-map-toggle {$trigger}\" style=\"display:none;\" title=\"{$map_title}\"></span>";
	    $output['map']    = "<div id=\"gmw-map-{$map_id}\" class=\"gmw-map {$prefix} {$map_type}\" style=\"width:100%; height:100%\" data-map_id=\"{$map_id}\" data-prefix=\"{$prefix}\" data-map_type=\"{$map_type}\"></div>";
	    $output['cover']  = '<div class="gmw-map-cover">';
	    $output['loader'] = "<i id=\"gmw-map-loader-{$map_id}\" class=\"gmw-map-loader gmw-icon-spin-light animate-spin\"></i>";
	    $output['/wrap']  = '</div>';

	    // modify the map element
	    $output = apply_filters( "gmw_map_output", $output, $args );
	    $output = apply_filters( "gmw_map_output_{$args['map_id']}", $output, $args );
	    
	    return implode( ' ', $output );
	}

	/**
	 * 
	 * Create new map args
	 *
	 * Pass the desired arguments to generate a map. Each element created here will be added 
	 * to the $map_elements array of maps.
	 *
	 * most of the map options can be defined here.
	 *
	 * More information about google maps options can be found here 
	 * https://developers.google.com/maps/documentation/javascript/reference#MapOptions.
	 *
	 * @Since 3.0
	 * 
	 * @author Eyal Fitoussi
	 * 	 
	 * @param  array  $map_args      map arguments
	 * @param  array  $map_options   map_options
	 * @param  array  $locations     object's locations ( posts, users... )
	 * @param  array  $user_position user position
	 * @param  array  $form          GEO my WP form if exists
	 * 
	 * return array map arguments
	 * 
	 */
	public static function get_map_args( $map_args = array(), $map_options = array(), $locations = array(), $user_location = array(), $form = array() ) {
		
		// randomize map ID if doesn't exists
		$map_id = ! empty( $map_args['map_id'] ) ? $map_args['map_id'] : rand( 100, 1000 );

		// default map args
		$default_map_args = array(
			'map_id' 	 		   => $map_id,
			'map_type'			   => 'na',
			'prefix'			   => 'na',
			'info_window_type'	   => 'standard',
			'info_window_ajax'     => 0,
			'info_window_template' => 'default',
			'zoom_position'		   => false,
			'group_markers'		   => 'standard',
			'draggable_window'	   => 1
		);

		// merge default with incoming map args
		$map_args = array_merge( $default_map_args, $map_args );

		// default map options
		$default_map_options = array(
			'backgroundColor' 		 => '#f7f7f7',			
			'disableDefaultUI' 		 => false,
			'disableDoubleClickZoom' => false,
			'draggable'				 => true,
			'draggableCursor'		 => '',
			'draggingCursor'		 => '',
			'fullscreenControl'      => false,
			'keyboardShortcuts'		 => true,
			'mapMaker'				 => false,
			'mapTypeControl' 		 => true,
			'mapTypeControlOptions'	 => true,
			'mapTypeId'				 => 'ROADMAP',
			'maxZoom'		 		 => null,
			'minZoom'		 		 => null,
			'zoom'				 	 => 13,
			'noClear'				 => false,
			'rotateControl'		     => true,
			'scaleControl'			 => true,
			'scrollwheel'	 		 => true,
			'streetViewControl' 	 => true,
			'styles'				 => null,
			'tilt'					 => null,
			'zoomControl'	 		 => true,
			'resizeMapControl'		 => true,
			'panControl'			 => true
		);

		$map_options = array_merge( $default_map_options, $map_options );

		// default user position
		$default_user_location = array(
			'lat'		 => false,
			'lng'		 => false,
			//'location'	 => false,
			'address' 	 => false,
			'map_icon'	 => 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',
			'iw_content' => null,
			'iw_open'	 => false
		);

		// if user position exists, merge it with default
		if ( ! empty( $user_location['lat'] ) ) {

			$user_location = array_merge( $default_user_location, $user_location );
		
		} else {

			$user_location = $default_user_location;
		}
		
		// push the map args into the global array of maps
		self::$map_elements[$map_id] = array(
			'settings'		=> $map_args,
			'map_options'   => $map_options,
			'locations'		=> $locations,
			'user_location' => $user_location,
			'form'			=> $form 
		);

		// allow plugins modify the map args
		self::$map_elements[$map_id] = apply_filters( 'gmw_map_element', self::$map_elements[$map_id], $form );
		self::$map_elements[$map_id] = apply_filters( "gmw_map_element_{$map_id}", self::$map_elements[$map_id], $form );
		
		// enable maps
		self::$map_enabled = true;

		// enable Markers Clusterer library
		if ( $map_args['group_markers'] == 'markers_clusterer' ) {	
			self::$markers_clusterer = true;
		}

		// enable Markers Spiderfier library
		if ( $map_args['group_markers'] == 'markers_spiderfier' ) {	
			self::$markers_spiderfier = true;
		}

		// enable infobox js file if needed
		if ( $map_args['info_window_type'] == 'infobox' ) {
			self::$infobox = true;
		}

		// enable infobox js file if needed
		if ( $map_args['info_window_type'] == 'infobubble' ) {
			self::$infobubble = true;
		}

		// enable jQuery ui draggable for popup info-windows
		//if ( $map_args['info_window_ajax'] && $map_args['draggable_window'] ) {
		if ( $map_args['info_window_type'] == 'popup' ) {
			self::$draggable_window = true;
		}
		
		return self::$map_elements[$map_id];
	}

	/**
	 * Collection of fields that will have address_autocomplete triggered.
	 *
	 * Use this function to pass the desired fields for address autocomplete.
	 * @param  array  $ac_fields [description]
	 * 
	 * @return [type]            [description]
	 */
	public static function google_places_address_autocomplete( $ac_fields = array() ) {

		if ( ! empty( $ac_fields ) ) {
			self::$address_autocomplete = array_merge( self::$address_autocomplete, $ac_fields );
		}
		return;
   	}

   	/**
   	 * Generate content for the info-window
   	 * 
   	 * @param  object $location location object.
   	 * @param  array  $args     arguments define the content of the info window
   	 *    array(
   	 *    	'prefix'    	  => '',         // addon/object prefix
	 *		'type'   	  	  => 'standard', // info window type ( standard, infobox, infobubble or popup.
	 *	    'url'    		  => '#',        // link to the object's page ( single post page, member's page.... )
	 *	    'title'  		  => '',         // post title, member's name....
	 *	    'image_url' 	  => '',         // image URL.
	 *	    'image'			  => '',         // image element ( will be ignored if image URL provided ). 
	 *	    'address_fields'  => 'formatted_address', // the address field to display, comma separated.
	 *	    'directions_link' => true,       // 1 to show 0 to hide directions link
	 *	    'distance'		  => true,       // 1 to show 0 to hide the distance
	 *	    'location_meta'	  => ''          // list of location meta as array or comma separated.
	 *	  );
   	 * @param  array  $gmw      [description]
   	 * @return [type]           [description]
   	 */
   	public static function get_info_window_content( $location, $args = array(), $gmw = array() ) {
	
		$default_args = array(
			'prefix'    	  => '',
			'type'   	  	  => 'standard',
	        'url'    		  => '#',
	        'title'  		  => '',
	        'image_url' 	  => '',
	        'image'			  => '',
	        'address_fields'  => 'formatted_address',
	        'directions_link' => true,
	        'distance'		  => true,
	        'location_meta'	  => ''
	    );

		$args = apply_filters( 'gmw_info_window_args', $args, $location, $gmw );
		$args = wp_parse_args( $args, $default_args );

		// labels
		$labels = gmw_get_labels()['info_window'];
		
		// object URL
		if ( $args['url'] != '#' ) {
			$args['url'] = esc_url( $args['url'] );
		}
		
		// prefix
		$prefix = $args['prefix'] != '' ? ' '.esc_attr( $args['prefix'] ) : '';

		$output = array();
		
		$output['wrap'] = '<div class="gmw-info-window-inner '.esc_attr( $args['type'] ).'" data-location_id="'.absint( $location->location_id ) .'" data-object="'. esc_attr( $location->object_type ).'" data-prefix="'.$prefix.'">';
		
		// Look for image
		if ( $args['image_url'] != '' || $args['image'] != '' ) {
			if ( $args['image_url'] != '' ) {
				$output['image'] = '<a class="image" href="'. $args['url'] .'"><img tag="'.esc_attr( $args['title'] ).'" src="'.esc_html( $args['image_url'] ) .'" /></a>';
			} else {
				$output['image'] = '<a class="image" href="'. $args['url'] .'">'. $args['image'] .'</a>';
			}
		}

		// title
		if ( $args['title'] != '' ) {
			$output['title'] = '<a class="title" href="'.$args['url'] .'">'. esc_attr( $args['title'] ) .'</a>';
		}
			
		// address
		if ( ! empty( $args['address_fields'] ) ) {
			$output['address'] = '<span class="address gmw-icon-location">'. gmw_get_location_address( $location, $args['address_fields'], $gmw ) .'</span>';
		}

		// distance
		if ( $args['distance'] && isset( $location->distance ) ) {
			$output['distance'] = '<span class="distance">'. esc_attr( $location->distance ) . ' ' .$location->units.'</span>';
		}
		
		// directions link
		if ( $args['directions_link'] ) {
			$output['directions'] = gmw_get_directions_link( $location, $gmw );
		}

		// location meta
		if ( ! empty( $args['location_meta'] ) && apply_filters( 'gmw_enable_info_window_location_meta', true ) ) {

			$location_meta = is_array( $args['location_meta'] ) ? $args['location_meta'] : explode( ',', $args['location_meta'] );

			$output['location_meta'] = gmw_get_location_meta_list( $location, $args['location_meta'], gmw_get_labels()['info_window'] );
		}

		$output['/wrap'] = '</div>';

		// modify the output
		$output = apply_filters( 'gmw_info_window_content', $output, $location, $args, $gmw );

		if ( ! empty( $prefix ) ) {
			$output = apply_filters( "gmw_{$prefix}_info_window_content", $output, $location, $args, $gmw );
		}

		// output content
		return implode( ' ', $output );
	}

   	/**
	* Generate get directions form.
	* 
	* @param unknown_type $info - $post, $member...
	* @param unknown_type $gmw
	*/
	public static function get_directions_form( $args = array() ) {

		// default args
		$defaults = array(
			'element_id'			=> '',
			'origin' 				=> '',
			'destination'			=> '',
			'units'					=> 'imperial',
			'avoid'				   	=> false,
			'address_autocomplete' 	=> 1
		);

		// modify the directions argumentes
		$args = apply_filters( 'gmw_directions_form_args', $args, $defaults );

		$args = wp_parse_args( $args, $defaults );

		// check for origin in args. If not exists check for user's current position as origin
		if ( empty( $args['origin'] ) ) {
			
			$user_location = gmw_get_user_current_location();

			if ( ! empty( $user_location->formatted_address ) ) {
				$args['origin'] = $user_location->formatted_address;
			}
		}

		// check for destination.
		if ( empty( $args['destination'] ) ) {

			// check for address in URL to be used as destination.
			// usually on single object page when navigated from the loop
			if ( ! empty( $_GET['address'] ) ) {
				
				$args['destination'] = urldecode( $_GET['address'] );
			
			} else {
				
				// look for $gmw_locaiton global. This usually present in the loop
				global $gmw_location;

				// check for address
				if ( ! empty( $gmw_location ) && ( ! empty( $gmw_location->formatted_address ) || ! empty( $gmw_location->address ) ) ) {
					
					// default destination address will be the address in the loop
					$args['destination'] = ! empty( $gmw_location->formatted_address ) ? $gmw_location->formatted_address : $gmw_location->address; 

					if ( empty( $args['element_id'] ) ) {
						$args['element_id'] = $gmw_location->ID;
					} 	
				}
			}
		}

		$args['element_id'] = ! empty( $args['element_id'] ) ? absint( $args['element_id'] ) : rand( 100, 549 );
		
		// labels
		$labels = apply_filters( 'gmw_get_directions_form_labels', array(
			'origin' 			=> __( 'Origin', 'GMW' ),
			'destination' 		=> __( 'Destination', 'GMW' ),
			'directions_label'	=> __( 'Directions', 'GMW' ),
			'from'				=> __( 'From:' , 'GMW' ),
			'to'				=> __( 'To:' , 'GMW' ),
			'driving'			=> __( 'Driving' , 'GMW' ),
			'walking'			=> __( 'Walking' , 'GMW' ),
			'bicycling'			=> __( 'Bicycling' , 'GMW' ),
			'transit'			=> __( 'Transit' , 'GMW' ),
			'units_mi'			=> __( 'Miles' , 'GMW' ),
			'units_km'			=> __( 'Kilometers' , 'GMW' ),
			'avoid_label'		=> __( 'Avoid' , 'GMW' ),
			'avoid_hw'			=> __( 'highways' , 'GMW' ),
			'avoid_tolls'		=> __( 'Tolls' , 'GMW' )
		), $args );

		$id 		  = esc_attr( $args['element_id'] );
		$origin 	  = stripslashes( sanitize_text_field( esc_attr( $args['origin'] ) ) );
		$destination  = stripslashes( sanitize_text_field( esc_attr( $args['destination'] ) ) );
		$labels 	  = array_map( 'esc_html', $labels );
		$autocomplete = ! empty( $args['address_autocomplete'] ) ? 'gmw-address-autocomplete' : '';

		$output  = "<div id=\"gmw-directions-form-wrapper-{$id}\" class=\"gmw-directions-form-wrapper\">";	
		$output .= 	"<form id=\"get-directions-form-{$id}\">";

		// travel mode
		$output .=  	"<ul id=\"travel-mode-options-{$id}\" class=\"get-directions-options travel-mode-options\">";
		$output .= 	 		"<li><a href=\"#\" id=\"DRIVING\" class=\"travel-mode-trigger active\"><i class=\"gmw-icon-cab\" title=\"{$labels['driving']}\"></i></a></li>";			
		$output .= 	 		"<li><a href=\"#\" id=\"WALKING\" class=\"travel-mode-trigger\"><i class=\"gmw-icon-person\" 	   title=\"{$labels['walking']}\"></i></a></li>";				
		$output .= 	 		"<li><a href=\"#\" id=\"BICYCLING\" class=\"travel-mode-trigger\"><i class=\"gmw-icon-bicycle\"	   title=\"{$labels['bicycling']}\"></i></a></li>";
		$output .= 	 		"<li><a href=\"#\" id=\"TRANSIT\" class=\"travel-mode-trigger\"><i class=\"gmw-icon-bus\" 	   title=\"{$labels['transit']}\"></i></a></li>";
		$output .= 	 	"</ul>";

		// address fields
		$output .= 		"<div class=\"address-fields-wrapper\">";	
		$output .= 			"<div class=\"address-field-wrapper origin-field-wrapper\">";
		$output .= 				"<label for=\"origin-field-{$id}\">{$labels['from']}</label>";
		$output .= 				"<input type=\"text\" id=\"origin-field-{$id}\" class=\"origin-field {$autocomplete}\" value=\"{$origin}\" placeholder=\"{$labels['origin']}\" />";
		$output .= 				"<a href=\"#\" type=\"submit\" id=\"get-directions-submit-{$id}\" class=\"get-directions-submit gmw-icon-search\"></a>";
		$output .= 			"</div>";
					
		$output .= 			"<div class=\"address-field-wrapper destination-field-wrapper\">"; 
		$output .= 				"<label for=\"destination-field-{$id}\">{$labels['to']}</label>";	
		$output .= 				"<input type=\"text\" id=\"destination-field-{$id}\" class=\"destination-field {$autocomplete}\" value=\"{$destination}\" placeholder=\"{$labels['destination']}\" />";
		$output .= 			"</div>";			
		$output .= 		"</div>";
		
		// default to miles		
		if ( $args['units'] == 'imperial' ) {
			
			$output .= "<input style=\"display:none;\" type=\"radio\" id=\"unit-system-imperial-trigger-{$id}\" name=\"unit_system_trigger\" class=\"unit-system-trigger\" value=\"IMPERIAL\" checked=\"checked\" />";
		
		// default to kilometers
		} elseif ( $args['units'] == 'metric' ) {
			
			$output .= "<input style=\"display:none;\" type=\"radio\" id=\"unit-system-metric-trigger-{$id}\" name=\"unit_system_trigger\" class=\"unit-system-trigger\" value=\"METRIC\" checked=\"checked\" />";
		
		// show both for the user to choose from
		} else {

			$output .= 	 	"<div id=\"unit-system-options-{$id}\" class=\"get-directions-options unit-system-options\">";
			$output .= 	 		"<label for=\"unit-system-imperial-trigger-{$id}\" class=\"active\">";
			$output .= 	 			"<input type=\"radio\" id=\"unit-system-imperial-trigger-{$id}\" name=\"unit_system_trigger\" class=\"unit-system-trigger\" value=\"IMPERIAL\" checked=\"checked\" />";
			$output .= 	 			"<span>{$labels['units_mi']}</span>";
			$output .= 	 		"</label>";

			$output .= 	 		"<label for=\"unit-system-metric-trigger-{$id}\">";
			$output .= 	 			"<input type=\"radio\" id=\"unit-system-metric-trigger-{$id}\" name=\"unit_system_trigger\" class=\"unit-system-trigger\" value=\"METRIC\" />";
			$output .= 	 			"<span>{$labels['units_km']}</span>";
			$output .= 	 		"</label>";
			$output .= 	 	"</div>";
		}

		// "Avoid" options
		if ( $args['avoid'] ) {

			$output .= 	 	"<div id=\"route-avoid-options-{$id}\" class=\"get-directions-options route-avoid-options\">";
			//$output .= 	 		"<span>{$labels['avoid_label']}</span>";
			$output .= 	 		"<label for=\"route-avoid-highways-trigger-{$id}\">";
			$output .= 	 			"<input type=\"checkbox\" id=\"route-avoid-highways-trigger-{$id}\" class=\"route-avoid-trigger\" value=\"1\" />";
			$output .= 	 			"<span>{$labels['avoid_label']} {$labels['avoid_hw']}</span>";
			$output .= 	 		"</label>";
			$output .= 	 		"<label for=\"route-avoid-tolls-trigger-{$id}\">";
			$output .= 	 			"<input type=\"checkbox\" id=\"route-avoid-tolls-trigger-{$id}\" class=\"route-avoid-trigger\" value=\"1\" />";
			$output .= 				"<span>{$labels['avoid_label']} {$labels['avoid_tolls']}</span>";
			$output .= 	 		"</label>";
			$output .= 	 	"</div>";
		}

		$output .= 	 	"<input type=\"hidden\" class=\"element-id\" value=\"{$id}\" />";	
		$output .= 	 "</form>";
		$output .= 	 "</div>";

		// set directions to true to enqueue its script
		self::$directions = true;

		return $output;
	}

	/**
	 * Placeholder for the directions results
	 * 
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public static function get_directions_panel( $id ) {
		$id = ! empty( $id ) ? absint( $id ) : rand( 100, 549 );
		return '<div id="gmw-directions-panel-wrapper-'. $id .'" class="gmw-directions-panel-wrapper"></div>'; 	
	}

	/**
	 * Generate both get direction form and panel
	 * 
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public static function get_directions_system( $args = array() ) {
		
		$output  = "<div id=\"gmw-directions-wrapper-{$id}\" class=\"gmw-directions-wrapper\">";	
		$output .= self::get_directions_form( $args );
		$output .= self::get_directions_panel( $args['element_id'] = 0 );
		$output .= '</div>';

		return $output;
	}

	/**
	 * Generate directions link 
	 * @param  [type]  $args     [description]
	 * @param  boolean $location [description]
	 * @param  array   $gmw      [description]
	 * 
	 * @return [type]            [description]
	 */
	public static function get_directions_link( $args = array() ) {

		$defaults = array(
			'id'	   => 0,
			'to_lat'   => false,
			'to_lat'   => false,
			'from_lat' => false,
			'from_lng' => false,
			'units'    => 'imperial',
			'label'    => __( 'Get directions', 'GMW' )
		);

		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( 'gmw_get_directions_link_args', $args );

		if ( ! $args['to_lat'] || ! $args['to_lng'] ) {
			return;
		}

		$language 	 = gmw_get_option( 'general_settings', 'langugae_code', 'EN' );
		$region   	 = gmw_get_option( 'general_settings', 'country_code', 'US' );
		$to_latlng   = "{$args['to_lat']},{$args['to_lng']}";
		$from_latlng = ( ! empty( $args['from_lat'] ) && ! empty( $args['from_lng'] ) ) ? "{$args['from_lat']},{$args['from_lng']}" : "";
		$units 		 = $args['units'] == 'imperial' ? 'ptm' : 'ptk';
		$link  		 = esc_url( "http://maps.google.com/maps?f=d&hl={$language}&region={$region}&doflg={$units}&saddr={$from_latlng}&daddr={$to_latlng}&ie=UTF8&z=12" );
		$label 		 = esc_html( $args['label'] );

		return "<a class=\"gmw-get-directions\" title=\"{$label}\" href=\"{$link}\" target=\"_blank\">{$label}</a>";
	}

	/**
	 * Load custom scripts that will load when ever the main map script is loaded
	 *
	 * to load a script you need to pass the data as an array via the function or
	 *
	 * the filter in the function.
	 *
	 * $defaults = array(
	 *		'handle'    => '', // required
	 *		'src'       => '', // required
	 *		'deps'      => array( 'gmw-map' ),
	 *		'ver'       => GMW_VERSION,
	 *		'in_footer' => true
	 *	);
     *
	 * @param  [type] $scripts [description]
	 * @return [type]          [description]
	 */
	public static function load_custom_map_scripts( $scripts = array() ) {

		// add custom scripts data
		$map_scripts = apply_filters( 'gmw_enqueue_map_scripts', $scripts );

		$defaults = array(
			'handle'    => '',
			'src'       => '',
			'deps'      => array( 'gmw-map' ),
			'ver'       => GMW_VERSION,
			'in_footer' => true
		);

		foreach ( $map_scripts as $args ) {

			$args = wp_parse_args( $args, $defaults );

			if ( empty( $args['handle'] ) || empty( $args['src'] ) ) {
				continue;
			}

			if ( ! wp_script_is( $args['handle'], 'enqueued' ) ) {
				
				wp_enqueue_script( $args['handle'], $args['src'], $args['deps'], $args['ver'], $args['in_footer'] );
			}
		}
	}

   	/**
   	 * Enqueue the JavaScript elements required for the map and only when needed
   	 * 
   	 * @return [type] [description]
   	 */
	public static function enqueue_scripts() {
	
		// trigger map and related script
		if ( self::$map_enabled ) {
			
			do_action( 'gmw_map_options' );

			// load main JavaScript and Google APIs if not already loaded
			if ( ! wp_script_is( 'gmw', 'enqueued') ) {
				wp_enqueue_script( 'gmw' );
			}

			// Load marker clusterer library - to be used with premium features
			if ( self::$markers_clusterer && ! wp_script_is( 'gmw-marker-clusterer', 'enqueued' )  ) {	
				
				wp_enqueue_script( 'gmw-marker-clusterer', GMW_URL . '/assets/lib/marker-clusterer/markerclusterer.min.js', array( 'gmw-map' ), GMW_VERSION, true );

				$cluster_image = apply_filters( 'gmw_clusters_folder' , is_ssl() ? 'https' : 'http' .'://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclustererplus/images/m' );
				wp_localize_script( 'gmw-marker-clusterer', 'clusterImage', $cluster_image );
			}

			// load marker spiderfier library - to be used with premium features
			if ( self::$markers_spiderfier && ! wp_script_is( 'gmw-marker-spiderfier', 'enqueued' )  ) {	
				wp_enqueue_script( 'gmw-marker-spiderfier', GMW_URL . '/assets/lib/marker-spiderfier/markerspiderfier.min.js', array( 'gmw-map' ), GMW_VERSION, true );
			}

			// load infobox js 
			if ( self::$infobox && ! wp_script_is( 'gmw-infobox', 'enqueued' ) ) {

				//$infobox_close_btn = $protocol.'://www.google.com/intl/en_us/mapfiles/close.gif';
				//wp_localize_script( 'gmw-infobox', 'closeButton', $infobox_close_btn );

				wp_enqueue_script( 'gmw-infobox', GMW_URL . '/assets/lib/infobox/infobox.min.js', array( 'gmw-map' ), GMW_VERSION, true );
			}

			// load infobox js 
			if ( self::$infobubble && ! wp_script_is( 'gmw-infobubble', 'enqueued' ) ) {

				//$infobox_close_btn = $protocol.'://www.google.com/intl/en_us/mapfiles/close.gif';
				//wp_localize_script( 'gmw-infobox', 'closeButton', $infobox_close_btn );

				wp_enqueue_script( 'gmw-infobubble', GMW_URL . '/assets/lib/infobubble/infobubble.min.js', array( 'gmw-map' ), GMW_VERSION, true );
			}

			//load jQuery ui draggable for popup info-windows
			if ( self::$draggable_window && ! wp_script_is( 'jquery-ui-draggable', 'enqueued' ) ) {
				wp_enqueue_script( 'jquery-ui-draggable' );
			}

			do_action( 'gmw_before_map_triggered', GMW_Maps_API::$map_elements );

			//pass the mapVarss to JS
			wp_localize_script( 'gmw-map', 'gmwMapObjects', GMW_Maps_API::$map_elements );
			
			//enqueue the map script
			if ( ! wp_script_is( 'gmw-map', 'enqueued' ) ) {
				wp_enqueue_script( 'gmw-map' ); 
			}

			self::load_custom_map_scripts( $scripts = array() );

			do_action( 'gmw_map_api_enqueue_script' );

		}

		// modify the autocomplete global
		self::$address_autocomplete = apply_filters( 'gmw_google_places_address_autocomplete_fields', self::$address_autocomplete );

		// trigger address autocomplete
		wp_localize_script( 'gmw', 'gmwAutocompleteFields', self::$address_autocomplete );

		// load live directions js file
		if ( self::$directions && ! wp_script_is( 'gmw-get-directions', 'enqueued' ) ) {
			wp_enqueue_script( 'gmw-get-directions', GMW_URL . '/assets/js/gmw.directions.min.js', array( 'gmw' ), GMW_VERSION, true );
		}
	}
}
//fire the enqueue_script in the footer
add_action( 'wp_footer', array( 'GMW_Maps_API', 'enqueue_scripts' ) );
endif;