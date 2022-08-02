<?php
/**
 * HTML for the tool page.
 */
$ziparchive_available = class_exists( 'ZipArchive' );
$file_label           = $ziparchive_available ? 'ZIP' : 'JSON';
?>
    <div class="wrap">
        <h1>Bookings Helper</h1>
        <hr/>
        <div>
            <h3>Global Availability Rules</h3>
			<?php
			$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
			?>
            <form action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
                <table>
                    <tr>
                        <td>
                            <input type="submit" class="button" value="Export Rules"/> <label>Exports all global availability rules.</label>
                            <input type="hidden" name="action" value="export_globals"/>
							<?php wp_nonce_field( 'export_globals' ); ?>
                        </td>
                    </tr>
                </table>
            </form>

			<?php
			$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
			?>
            <form enctype="multipart/form-data" action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
                <table>
                    <tr>
                        <td>
                            <label>Choose a file (<?php echo $file_label; ?>).</label><input type="file" name="import"/>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <input type="submit" class="button" value="Import Rules"/> <label>Imports global availability rules replacing your current rules.</label>
                            <input type="hidden" name="action" value="import_globals"/>
							<?php wp_nonce_field( 'import_globals' ); ?>
                        </td>
                    </tr>
                </table>
            </form>

            <h3>Booking Products</h3>
			<?php
			$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
			?>
            <form action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
                <table>
                    <tr>
                        <td>
                            <label>Product ID: <input type="number" name="product_id" min="1"/></label>
                            <input type="submit" class="button" value="Export Booking Product"/> <label>Exports a specific Booking product and its settings including resources.</label>
                            <input type="hidden" name="action" value="export_product"/>
							<?php wp_nonce_field( 'export_product' ); ?>
                        </td>
                    </tr>
                </table>
            </form>

			<?php
			$action_url = add_query_arg( array( 'page' => 'bookings-helper' ), admin_url( 'tools.php' ) );
			?>
            <form enctype="multipart/form-data" action="<?php echo $action_url; ?>" method="post" style="margin-bottom:20px;border:1px solid #ccc;padding:5px;">
                <table>
                    <tr>
                        <td>
                            <label>Choose a file (<?php echo $file_label; ?>).</label><input type="file" name="import"/>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <input type="submit" class="button" value="Import Product"/> <label>Imports a booking product.</label>
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
	echo '<div><p><strong style="color:red;">PHP ZipArchive extension is not installed. Import/Export will be in JSON format.</strong></p></div>';
}
