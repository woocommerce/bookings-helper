<?php
/**
 * HTML for the tool page.
 */

$ziparchive_available = class_exists( 'ZipArchive' );
$file_label           = $ziparchive_available ? 'ZIP' : 'JSON';
?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bookings Helper', 'bookings-helper' ); ?></h1>
		<hr/>
		<div>
			<h3><?php esc_html_e( 'Global Availability Rules', 'bookings-helper' ); ?></h3>
			<?php
			$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
			?>
			<form action="<?php echo esc_url( $action_url ); ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
				<table>
					<tr>
						<td>
							<input type="submit" class="button" value="<?php esc_attr_e( 'Export Rules', 'bookings-helper' ); ?>"/> <label><?php esc_html_e( 'Exports all global availability rules.', 'bookings-helper' ); ?></label>
							<input type="hidden" name="action" value="export_globals"/>
							<?php wp_nonce_field( 'export_globals' ); ?>
						</td>
					</tr>
				</table>
			</form>

			<?php
			$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
			?>
			<form enctype="multipart/form-data" action="<?php echo esc_url( $action_url ); ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
				<table>
					<tr>
						<td>
							<label><?php esc_html_e( 'Choose a file', 'bookings-helper' ); ?> (<?php echo esc_html( $file_label ); ?>).</label><input type="file" name="import"/>
						</td>
					</tr>

					<tr>
						<td>
							<input type="submit" class="button" value="<?php esc_attr_e( 'Import Rules', 'bookings-helper' ); ?>"/> <label><?php esc_html_e( 'Imports global availability rules replacing your current rules.', 'bookings-helper' ); ?></label>
							<input type="hidden" name="action" value="import_globals"/>
							<?php wp_nonce_field( 'import_globals' ); ?>
						</td>
					</tr>
				</table>
			</form>

			<h3><?php esc_html_e( 'Booking Products', 'bookings-helper' ); ?></h3>
			<?php
			$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
			?>
			<form action="<?php echo esc_url( $action_url ); ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
				<table>
					<tr>
						<td>
							<label><?php esc_html_e( 'Product ID', 'bookings-helper' ); ?>: <input type="number" name="product_id" min="1"/></label>
							<input type="submit" class="button" value="<?php esc_attr_e( 'Export Booking Product', 'bookings-helper' ); ?>"/> <label><?php esc_html_e( 'Exports a specific Booking product and its settings including resources.', 'bookings-helper' ); ?></label>
							<input type="hidden" name="action" value="export_product"/>
							<?php wp_nonce_field( 'export_product' ); ?>
						</td>
					</tr>
				</table>
			</form>

			<?php
			$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
			?>
			<form enctype="multipart/form-data" action="<?php echo esc_url( $action_url ); ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
				<table>
					<tr>
						<td>
							<label><?php esc_html_e( 'Choose a file', 'bookings-helper' ); ?> (<?php echo esc_html( $file_label ); ?>).</label><input type="file" name="import"/>
						</td>
					</tr>
					<tr>
						<td>
							<input type="checkbox" id="include_global_rules" name="include_global_rules" style="margin:10px 0;"/>
							<label for="include_global_rules"><?php esc_html_e( 'Import global availability rules (if your ZIP contains those) replacing your current rules.', 'bookings-helper' ); ?></label>
						</td>
					</tr>
					<tr>
						<td>
							<input type="submit" class="button" value="<?php esc_attr_e( 'Import Product', 'bookings-helper' ); ?>"/> <label><?php esc_html_e( 'Imports a booking product.', 'bookings-helper' ); ?></label>
							<input type="hidden" name="action" value="import_product"/>
							<?php wp_nonce_field( 'import_product' ); ?>
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>
<?php

if ( ! $ziparchive_available ) {
	?>
	<div>
		<p>
			<strong style="color:red;">
				<?php esc_html_e( 'PHP ZipArchive extension is not installed. Import/Export will be in JSON format.', 'bookings-helper' ); ?>
			</strong>
		</p>
	</div>
	<?php
}
