<?php

/**
* Instamojo Widget.
* It extends the WordPress Widget class.
*/
class instamojo_widget extends WP_Widget{


	/**
	*	Default constructor.
	*/
	function instamojo_widget() {
		// Load any other optional scripts
		add_action('load-widgets.php', array(&$this, 'my_custom_load'));

		// Name and class of widget.
		$widget_options = array(
			'classname' => 'instamojo-widget',
			'description' => 'Display Instamojo offers in your blog.');

		// Id, width and height of the widget.
		$control_options = array(
			'id_base' => 'instamojo-widget',
			'width' => 300,
			'height' => 200);

		// Initialize the widget.
		$this->WP_Widget('instamojo-widget', 'instamojo',  $widget_options, $control_options);
	}

	/**
	*	Called in the constructor.
	*/
	function my_custom_load() {

  }

    /**
	*	Implements the widget() function as required by WordPress.
	*	This is responsible for how the widget looks in your WordPress site.
	*/
	function widget($args, $instance) {
		wp_register_style('widgetcss', plugin_dir_url(__FILE__).'assets/css/imojo.css');
		wp_enqueue_style('widgetcss');

		// Holds a mapping for currency to HTML of that currency.
		$currency_html_map = array();
		$currency_html_map["INR"] = "&#8377;";
		$currency_html_map["USD"] = "&#36;";

		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		echo $before_widget;
		$add_feed = (substr($instance['instamojo_url'], -1) == '/') ? "feed.json" : "/feed.json";

		// Getting details from the link given in instamojo_url.
		$offer_array = json_decode(file_get_contents($instance['instamojo_url'].$add_feed));

		$offer_title = $offer_array->{'offer'}->{'title'};
		$offer_description = $offer_array->{'offer'}->{'description'};
		$offer_base_price = $offer_array->{'offer'}->{'base_price'};
		$offer_currency = $offer_array->{'offer'}->{'currency'};
		$offer_image = $offer_array->{'offer'}->{'cover_image'};

		// If title is not given make it My Instamojo Product.
		if ($instance['title']) {
			echo $before_title.$instance['title'].$after_title;
		}
		else {
			echo $before_title.'My Instamojo Product'.$after_title;
		}

		$button_html = '<div class="btn-container"><a href="'.$instance['instamojo_url'].'" ';
		if ($instance['button_style'] != 'none') {
			$button_html .= 'class="im-checkout-btn btn--'.$instance['button_style'].'" ';
		}
		$button_html .= 'target="_blank">Buy Now</a></div>';
		?>
		<div id="wid-small-div">
	    <?php if ($instance['button_pos'] == 'top')	echo $button_html; ?>
			<div id="wid-offer-title">
		    <?php	if ($instance['title'] == '404 error') echo '<h4>Error in offer URL!</h4>'; else echo '<h4>'.$offer_title.'</h4>'; ?>
			</div>
			<div id="wid-currency-price">
				<h4><?php echo $currency_html_map[$offer_currency].' '.$offer_base_price; ?></h4>
			</div>
			<?php if ($instance['button_pos'] == 'bottom') echo $button_html;	?>
		</div>
		<?php
		echo $after_widget;
	}

	/**
	*	Implements the update() function as required by WordPress.
	*	This works when you fill data in the widget form input from the WordPress admin.
	*/
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['button_pos'] = strip_tags($new_instance['button_pos']);
		$instance['instamojo_url'] = strip_tags($new_instance['instamojo_url']);
		$ch = curl_init($instance['instamojo_url']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($ch);
		$instance['instamojo_url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		curl_close($ch);
		if(substr($instance['instamojo_url'], -1) == '/')
			$instance['instamojo_url'] = substr($instance['instamojo_url'], 0, -1);
		if($instance['instamojo_url'][0] == 'h' && $instance['instamojo_url'][5] == 's')
			$instance['instamojo_url'] = substr($instance['instamojo_url'], 9);
		if($instance['instamojo_url'][0] == 'h')
			$instance['instamojo_url'] = substr($instance['instamojo_url'], 8);
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['button_style'] = strip_tags($new_instance['button_style']);
		$response = get_headers($instance['instamojo_url']);
		$responce_code = substr($response[0], 9, 3);
		$url_pieces = explode("/", $instance['instamojo_url']);
		if ((strpos($instance['instamojo_url'], 'www.instamojo.com') === false && $responce_code != "404") || $responce_code == "404" || count($url_pieces) != 3) {
			$instance['title'] = "404 error";
			$instance['instamojo_url'] = "#";
		}
		$instance['instamojo_url'] = 'https://'.$instance['instamojo_url'];
		return $instance;
	}

	/**
	*	Implements the form() function as required by WordPress.
	* 	This is responsible for how the form in the wordpess admin looks.
	*/
	function form($instance) {
		$defaults = array('title' => '', 'instamojo_url' => '', 'button_pos' => 'top', 'button_style' => 'none', 'type' => true);
		$instance = wp_parse_args((array)$instance, $defaults);
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title');?>">Widget Title:</label>
			<input id="<?php echo $this->get_field_id('title');?>"
				name="<?php echo $this->get_field_name('title');?>"
				value="<?php echo $instance['title'];?>"
			style="width:100%"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('instamojo_url');?>">Instamojo Offer URL:</label>
			<input id="<?php echo $this->get_field_id('instamojo_url');?>"
				name="<?php echo $this->get_field_name('instamojo_url');?>"
				value="<?php echo $instance['instamojo_url'];?>"
			style="width:100%"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('button_pos'); ?>"><?php _e('Button Position:'); ?></label>
			<select id="<?php echo $this->get_field_id('button_pos'); ?>" name="<?php echo $this->get_field_name('button_pos'); ?>">
				<option value="top" <?php if($instance['button_pos'] == 'top') echo 'selected="selected"'; ?>>Top</option>
				<option value="bottom" <?php if($instance['button_pos'] == 'bottom') echo 'selected="selected"'; ?>>Bottom</option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('button_style'); ?>">Button Style</label>
			<select id="<?php echo $this->get_field_id('button_style'); ?>" name="<?php echo $this->get_field_name('button_style'); ?>">
				<option value="light" <?php if($instance['button_style'] == 'light') echo 'selected="selected"'; ?>>Light</option>
				<option value="dark" <?php if($instance['button_style'] == 'dark') echo 'selected="selected"'; ?>>Dark</option>
				<option value="flat" <?php if($instance['button_style'] == 'flat') echo 'selected="selected"'; ?>>Flat Light</option>
				<option value="flat-dark" <?php if($instance['button_style'] == 'flat-dark') echo 'selected="selected"'; ?>>Flat Dark</option>
				<option value="none" <?php if($instance['button_style'] == 'none') echo 'selected="selected"'; ?>>None</option>
			</select>
		</p>
		<?php
	}
}
?>
