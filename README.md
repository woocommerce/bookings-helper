# bookings-helper
This extension is a WooCommerce Bookings helper which helps you to troubleshoot bookings setup easier by allowing you to quickly export/import product settings.

All exports will be in JSON file format zipped.

# Global Availability Rules

Importing global availability rules will overwrite all the rules. This is because a bookable product test case will depend on these rules. Since this is an overwriting feature, you can first export the global availability rules for safe keeping before you import your test rules. This way you can always import back your original rules.

# Bookable Product

You can export any specific bookable product and all of its settings including resources and persons.

If resources are defined, importing the bookable product will generate new resources that will be linked to the specific product you imported.

# Usage

Just install the plugin and activate. Then go to "Tools->Bookings Helper".
