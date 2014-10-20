<?php
/**
 * Behaviors Framework
 *
 * @package   Behaviors Framework
 * @author    Riccardo D'Angelo <me@riccardodangelo.com>
 * @license   copyrighted
 * @link      http://www.waga.it
 * @copyright 2014 Riccardo D'Angelo and WAGA.it
 */

add_action( 'add_meta_boxes', 'waboot_behavior_create_metabox' );

add_action( 'save_post', 'waboot_behavior_save_metabox' );
add_action( 'pre_post_update', 'waboot_behavior_save_metabox' );
add_action( 'edit_post', 'waboot_behavior_save_metabox' );
add_action( 'publish_post', 'waboot_behavior_save_metabox' );
add_action( 'edit_page_form', 'waboot_behavior_save_metabox' );

//add_action( 'optionsframework_after_validate','waboot_reset_defaults' );

class BehaviorsManager{

	static function getAll(){
		$imported_behaviors = self::importPredefined(); //per ora si possono specificare solo via file...
		$behaviors = array();
		foreach($imported_behaviors as $b){
			$behaviors[] = new Behavior($b);
		}

		return $behaviors;
	}

	static function importPredefined(){
		$predef_behaviors = array();

		//Get behaviors from .json files
		$behavior_file = get_theme_root()."/".get_template()."/inc/behaviors.json";
		if (file_exists($behavior_file)) {
			$predef_behaviors = json_decode(file_get_contents($behavior_file, true),true);
		}

		if(is_child_theme()){
			$child_behavior_file = get_stylesheet_directory()."/inc/behaviors.json";
			if(file_exists($child_behavior_file)){
				$child_behaviors = json_decode(file_get_contents($child_behavior_file, true),true);
				$predef_behaviors = array_replace_recursive($predef_behaviors,$child_behaviors);
			}
		}

		//Get from filters
		$predef_behaviors = apply_filters("waboot_add_behaviors",$predef_behaviors);

		return $predef_behaviors;
	}

	static function get($name){
		$behaviors = self::getAll(); //retrive all behaviours
		$selected_behavior = new stdClass();

		foreach($behaviors as $b){ //find the desidered behaviour
			if($b->name == $name){
				$selected_behavior = $b;
			}
		}

		if($selected_behavior instanceof Behavior){
			$current_behavior_value = $selected_behavior->get_value();
			return $selected_behavior;
		}else{
			return false;
		}
	}

    static function debug($post_id){
        $behaviors = self::getAll();
        echo "<div style='border: 1px solid #ccc;'><pre>";
        foreach($behaviors as $b){
            echo $b->name.": ";
            var_dump($b->get_value($post_id));
        }
        echo "</div></pre>";
    }
}

class Behavior{
	var $name;
	var $metaname;
	var $optionname;
	var $title;
	var $description;
	var $type;

	var $value; //the current value displayed (of current post)
	var $possible_values = array(); //used in case of select,radios,ect
	var $default; //the value set in theme options

	var $filters = array(
		'post_type' => '*',
		'node_id' => '*'
	);

	function __construct($args){
		if(isset($args['name'])){
			$this->name = $args['name'];
			$this->metaname = "_behavior_".$args['name'];
			$this->optionname = "behavior_".$args['name'];
		} else{
			$this->name = "";
			$this->metaname = "";
			$this->optionname = "";
		}
		if(isset($args['title'])) $this->title = $args['title']; else $this->title = "";
		if(isset($args['desc'])) $this->description = $args['desc']; else $this->description = "";
		if(isset($args['type'])) $this->type = $args['type']; else $this->type = "";

        if(isset($args['options'])){
            $this->possible_values = $args['options'];
        }else{
            $this->possible_values = "";
        }

		if(isset($args['default'])){
			$base_default = $args['default'];
			$option_default = of_get_option($this->optionname,$base_default);
            if(is_array($option_default)){
                if($this->type == "checkbox"){
                    foreach($option_default as $name => $v){
                        $this->default[] = $name;
                    }
                }
            }else{
                $this->default = $option_default;
            }
		}else{
			$this->default = of_get_option($this->optionname,"");
		}

		if(isset($args['valid'])){
			$this->filters['post_type'] = array();
			if(is_array($args['valid'])){
				foreach($args['valid'] as $pt){
					array_push($this->filters['post_type'],$pt);
				}
			}else{
				array_push($this->filters['post_type'],$args['valid']);
			}
		}
	}

	function set_value($value){
		$this->value = $value;
	}

	function save_meta($post_id){
        if(is_array($this->value))
		    update_post_meta($post_id,$this->metaname,serialize($this->value));
        else
            update_post_meta($post_id,$this->metaname,$this->value);
	}

	function get_meta($post_id){
		$result = get_post_meta($post_id,$this->metaname,true);
        if(is_serialized($result)) $result = unserialize($result);
        return $result;
	}

    /**
     * Get the current RAW value of the behavior: this mean that the value can be "_default"
     */
    function get_raw_value($post_id = null){
        if(!isset($post_id)){
            global $post;
            $post_id = $post->ID;
        }

        $current_value = $this->get_meta($post_id);

        if($current_value == "" || $current_value == "_default"){
            switch($this->type){
                case 'text':
                case 'textarea':
                    $current_value = "";
                    break;
                case 'select':
                case 'radio':
                    $current_value = "_default";
                    break;
                case 'checkbox':
                    $current_value = "_default";
                    if($this->has_multiple_choices()){
                        $current_value = array("_default");
                    }
                    break;
            }
        }

        return $current_value;
    }

    /**
     * Get the current value of the behavior: this mean that "_default" value will be translated to a real value
     * @param bool $node the id of post or page
     * @return array|bool|mixed|string
     */
	function get_value($node = false){
		global $post;

		if(!$node){
			global $post;
			$node = $post;
		}else{
			$node = get_post(intval($node));
		}

		if(!isset($node) || $node->ID == 0 || !$node instanceof WP_Post){
			$this->value = $this->default;
			return $this->value;
		}else{
			$current_behavior_value = get_post_meta($node->ID,$this->metaname,$this->default);

			if($current_behavior_value == "" && ($this->type != "textarea" || $this->type != "text"))
				$current_behavior_value = "_default";

			if($current_behavior_value == "_default" || (is_array($current_behavior_value) && $current_behavior_value[0] == "_default") )
				$current_behavior_value = $this->default;

			if(is_array($current_behavior_value))
				$current_behavior_value = $current_behavior_value[0];

			$this->value = $current_behavior_value;

			return $this->value;
		}
	}

    function get_choices(){
        if($this->has_multiple_choices()){
            return $this->possible_values;
        }

        return array();
    }

    function has_multiple_choices(){
        if(isset($this->possible_values) && !empty($this->possible_values)){
            return true;
        }

        return false;
    }

	function is_enable_for_node($id){
		$post_type = get_post_type($id);

		if(in_array($post_type,$this->filters['post_type']) || $this->filters['post_type'] == "*"){
			return true;
		}

		return false;
	}

	function is_enabled_for_current_node(){
		global $post;

		return $this->is_enable_for_node($post->ID);
	}

	function generate_of_option(){

        if($this->type == "checkbox" && $this->has_multiple_choices()) $type = "multicheck";
        else $type = $this->type;

		$option = array(
			'name' => $this->title,
			'desc' => $this->description,
			'id' => "behavior_".$this->name,
			'type' => $type,
		);

		switch($type){
			case 'text':
			case 'textarea':
				$option['std'] = $this->default;
				break;
            case 'checkbox':
                if($this->default == '0')
                    $option['std'] = '0';
                else
                    $option['std'] = '1';
                break;
            case 'multicheck':
                //values
                $multicheck_options = array();
                foreach($this->possible_values as $o){
                    $multicheck_options[$o['value']] = $o['name'];
                }
                $option['options'] = $multicheck_options;
                //defaults
                $default = array();
                if(!is_array($this->default)) $default = array($this->default => 1);
                else{
                    foreach($this->default as $d){
                        $default[$d] = 1;
                    }
                }
                $option['std'] = $default;
                break;
            case 'radio':
			case 'select':
                //values
				$select_options = array();
				foreach($this->possible_values as $o){
					$select_options[$o['value']] = $o['name'];
				}
				$option['options'] = $select_options;
                //defaults
				if(isset($this->default)){
					$select_default = array();
					if(is_array($this->default)){
						foreach($this->default as $d){
							$select_default[$d] = 1;
						}
					}else{
						$select_default = $this->default;
					}
					$option['std'] = $select_default;
				}
				break;
		}

		return $option;
	}

	function print_metabox($post_id){
		$current_value = $this->get_raw_value($post_id);
        if($current_value == "" && ($this->type == "text" || $this->type == "textarea")) $check_predefined = true;

		switch($this->type){
			case "text":
				?>
				<p><strong><?php echo $this->title ?></strong></p>
				<label class="screen-reader-text" for="<?php echo $this->metaname ?>"><?php echo $this->title ?></label>
				<input type="text" name="<?php echo $this->metaname ?>" id="<?php echo $this->metaname ?>" value="<?php echo $current_value; ?>" placeholder="<?php echo $this->default; ?>" />
				<input type="checkbox" name="<?php echo $this->metaname ?>_default" id="<?php echo $this->metaname ?>_default" value="1" <?php if(isset($check_predefined)) echo "checked"; ?>><?php _e("Use default value","waboot"); ?>
				<?php
				break;
			case "textarea":
				?>
				<p><strong><?php echo $this->title ?></strong></p>
				<label class="screen-reader-text" for="<?php echo $this->metaname ?>"><?php echo $this->title ?></label>
				<textarea name="<?php echo $this->metaname ?>" id="<?php echo $this->metaname ?>" placeholder="<?php echo $this->default; ?>"><?php echo $current_value; ?></textarea>
				<br />
				<input type="checkbox" name="<?php echo $this->metaname ?>_default" id="<?php echo $this->metaname ?>_default" value="1" <?php if(isset($check_predefined)) echo "checked"; ?>><?php _e("Use default value","waboot"); ?>
				<?php
				break;
			case "checkbox":
				?>
                <p><strong><?php echo $this->title ?></strong></p>
				<ul>
                    <?php if($this->has_multiple_choices()) : $values = $this->get_choices(); ?>
                        <?php foreach($values as $c) : ?>
                            <li>
                                <input type="checkbox" name="<?php echo $this->metaname ?>[]" id="<?php echo $this->metaname ?>" value="<?php echo $c['value']; ?>" <?php if(in_array($c['name'],(array)$current_value)) echo "checked"?>><?php echo $c['name']; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li>
                            <input type="checkbox" name="<?php echo $this->metaname ?>" id="<?php echo $this->metaname ?>" value="1" <?php if($current_value == 1) echo "checked"?>><?php _e("Enable","waboot") ?>
                        </li>
                    <?php endif; ?>
					<li>
						<input type="checkbox" name="<?php echo $this->metaname ?>_default" id="<?php echo $this->metaname ?>_default" value="_default" <?php if($current_value == "_default" || in_array("_default",(array)$current_value)) echo "checked"?>><?php _e("Use default value","waboot"); ?>
					</li>
				</ul>
				<?php
				break;
            case "radio":
                ?>
                <p><strong><?php echo $this->title ?></strong></p>
                <label class="screen-reader-text" for="<?php echo $this->metaname ?>"><?php echo $this->title ?></label>
                <?php foreach($this->possible_values as $k => $v) : ?>
                    <input type="radio" name="<?php echo $this->metaname ?>" value="<?php echo $v['name']; ?>" <?php if($v['value'] == $current_value) echo "checked"?> /><?php echo $v['value']; ?><br />
                <?php endforeach; ?>
                <input type="radio" name="<?php echo $this->metaname ?>" value="_default" <?php if($current_value == "_default") echo "checked"?>/><?php _e("Default"); ?>
                <?php
                break;
			case "select":
				?>
				<p><strong><?php echo $this->title ?></strong></p>
				<label class="screen-reader-text" for="<?php echo $this->metaname ?>"><?php echo $this->title ?></label>
				<select name="<?php echo $this->metaname ?>" id="<?php echo $this->metaname ?>">
					<?php foreach($this->possible_values as $k => $v) : ?>
						<option value="<?php echo $v['value']; ?>" <?php if($v['value'] == $current_value) echo "selected"?>><?php echo $v['name']; ?></option>
					<?php endforeach; ?>
					<option value="_default" <?php if($current_value == "_default") echo "selected"?>><?php echo __("Default") ?></option>
				</select>
				<?php
				break;
		}
	}
}

/**
 * Get a behaviour.
 * @param $name
 * @param string $return (value OR array)
 * @return array|bool|mixed|string
 */
function get_behavior($name,$return = "value"){
    global $post;

	$b = BehaviorsManager::get($name);

	if($return == "value"){
		return $b->value;
	}else{
		return $b;
	}
}

function waboot_behavior_create_metabox(){
    $behaviors = BehaviorsManager::getAll();
    add_meta_box("behavior","Behaviors","waboot_behavior_display_metabox",null,"advanced","core",array($behaviors));
}

function waboot_behavior_display_metabox(WP_Post $post,array $behaviors){
	$behaviors = $behaviors['args'][0];

	wp_nonce_field('behaviors_meta_box','behaviors_meta_box_nonce');

	?>
	<?php $opt_n=0; foreach($behaviors as $b) : ?>
		<?php if($b->is_enable_for_node($post->ID)) : ?>
			<?php
				$opt_n++;
				$b->print_metabox($post->ID);
			?>
		<?php endif; ?>
	<?php endforeach; ?>
	<?php if($opt_n == 0) : ?>
		<p>No behavior available for this post type.</p>
	<?php endif;
}

function waboot_behavior_save_metabox($post_id){
    // Check if our nonce is set.
    if ( ! isset( $_POST['behaviors_meta_box_nonce'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['behaviors_meta_box_nonce'], 'behaviors_meta_box' ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }

    } else {

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    // Then save behaviors...

    $behaviors = BehaviorsManager::getAll();
    foreach($behaviors as $b){
        $metaname = $b->metaname;

	    if($b->is_enable_for_node($post_id)){
	        if(!isset($_POST[$metaname])){
		        if($b->type == "checkbox"){
                    if($b->has_multiple_choices())
                        $_POST[$metaname] = array();
                    else
			            $_POST[$metaname] = "0";
		        }
	        }

	        if(isset($_POST[$metaname])){
		        if(isset($_POST[$metaname."_default"]) || (is_array($_POST[$metaname]) && in_array("_default",$_POST[$metaname]))){
			        $b->set_value("_default");
		        }else{
			        $b->set_value($_POST[$metaname]);
		        }
		        $b->save_meta($post_id);
	        }
	    }
    }
}