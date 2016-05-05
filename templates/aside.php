<?php
/*
 * Waboot View
 */
?>
<?php $vars = \Waboot\functions\get_aside_template_vars($name); ?>
<aside id="<?php echo $name ?>" class="<?php echo $vars['classes']; ?>" role="complementary">
	<div class="<?php echo $vars['container_classes'] ?>">
		Waboot Aside
		<?php Waboot()->layout->do_zone_action($name); ?>
		<?php do_action("waboot/aside"); ?>
	</div>
</aside>
