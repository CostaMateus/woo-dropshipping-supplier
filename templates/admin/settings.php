<?php
/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @author 		Mateus Costa <mateus@costamateus.com.br>
 * @package 	Dropshipping_Supplier\Templates\Admin
 * @version 	1.0.0
 */

defined( "ABSPATH" ) || exit;

?>

<div class="wrap" >
	<div class="wrap-title" >
		<img src="<?php echo plugins_url( "assets/images/dsm.png", Dropshipping_Supplier::FILE ); ?>" alt="DSM Logo" >
		<h1 class="wp-heading-inline" ><?php echo esc_html( get_admin_page_title() ); ?></h1>
	</div>

	<nav class="nav-tab-wrapper" >
		<?php
			foreach ( $tabs as $key => $value )
			{
				$page   = Dropshipping_Supplier::DOMAIN;
				$active = ( $key == $tab ) ? "nav-tab-active" : "";
				echo "<a href='?page={$page}&tab={$key}' class='nav-tab {$active}' >{$value}</a>";
			}
		?>
	</nav>

	<div class="tab-content" >
	<?php
		if ( isset ( $tabs[ $tab ] ) )
			require_once plugin_dir_path( Dropshipping_Supplier::FILE ) . "templates/admin/tabs/{$tab}.php";
		else
			require_once plugin_dir_path( Dropshipping_Supplier::FILE ) . "templates/admin/tabs/general.php";
	?>
	</div>
</div>