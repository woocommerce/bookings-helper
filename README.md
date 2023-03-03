# bookings-helper
This extension is a WooCommerce Bookings helper which helps you to troubleshoot bookings setups easier by allowing you to quickly export/import product settings.

All exports will be in JSON file format zipped.

# Minimum Version Requirements

* WordPress 5.6
* WooCommerce 6.0
* PHP 7.0

# Global Availability Rules

Importing global availability rules will overwrite all the rules. This is because a bookable product test case will depend on these rules. Since this is an overwriting feature, you can first export the global availability rules for safe keeping before you import your test rules. This way you can always import back your original rules.

# Bookable Product

You can export any specific bookable product and all of its settings including resources and persons.

If resources are defined, importing the bookable product will generate new resources that will be linked to the specific product you imported.

You can also use wp-cli to import/export bookable products and availability rules.
See the example below.
```bash
# Export all products
wp bookings-helper export-product --all --dir=/absolute/path/to/directory/

# Export specific products
wp bookings-helper export-product --products="1,2" --dir=/absolute/path/to/directory/

# Export all products with global availability rules
wp bookings-helper export-product --all --with-global-rules

# Import all products
wp bookings-helper import-product --file=/absolute/path/to/file

# Export global availability rules
wp bookings-helper export-availability-rules --dir=/absolute/path/to/directory/

# Import global availability rules
wp bookings-helper import-availability-rules --file=/absolute/path/to/directory/

# Import all products with global availability rules
wp bookings-helper import-product --file=/absolute/path/to/file --with-global-rules
```

# Usage

Just install the plugin and activate. Then go to "Tools->Bookings Helper".

