# Svaapta AddToCartRestrict Extension Documentation

## Overview

The AddToCartRestrict extension allows store administrators to set maximum quantity limits for adding products to the shopping cart. This extension enables you to restrict the maximum quantity of items that can be added to the cart based on customer groups, providing better control over product ordering.

## Features

- Enable/disable extension functionality
- Set maximum allowed quantity for cart items
- Apply restrictions to specific customer groups
- Fully configurable through Magento admin panel
- Works with all product types including simple, configurable, grouped, and bundle products
- Prevents cart updates that would exceed quantity limits
- Detailed logging for troubleshooting

## Installation

### Requirements
- Magento 2.x
- PHP 7.x or higher

### Installation Steps
1. Upload the extension files to `app/code/Svaapta/AddToCartRestrict`
2. Run the following Magento CLI commands:
```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

## Configuration

After installing the extension, you can configure it through the Magento admin panel:

1. Log in to your Magento admin panel
2. Navigate to **Svaapta Extensions > Add To Cart Restrict > Configuration**
   (Alternatively, go to **Stores > Configuration > Svaapta Extensions > Add To Cart Restrict**)

### Configuration Options

#### General Configuration

| Option | Description |
|--------|-------------|
| Enable Extension | Enable or disable the extension functionality (Yes/No) |
| Maximum Allowed Quantity | Set the maximum quantity that a customer can add to cart (numeric value, greater than zero) |
| Apply To Customer Groups | Select customer groups to which the cart quantity restrictions will apply |

## Permission Management

The extension uses Magento's ACL (Access Control List) system to manage permissions. The following permissions are available:

- **Add To Cart Restrict > Configuration**: Controls access to the extension configuration
- **Stores > Configuration > Add To Cart Restrict Section**: Controls access to the extension section in store configuration

To manage these permissions:
1. Go to **System > Permissions > User Roles**
2. Select or create a role
3. In the Role Resources tab, select the appropriate permissions for the role

## Default Configuration

The extension comes with the following default configuration:

- Enabled: No
- Maximum Allowed Quantity: 10
- Customer Groups: All groups (0,1,2,3)

## Technical Structure

The extension has the following structure:

```
Svaapta_AddToCartRestrict/
├── etc/
│   ├── adminhtml/
│   │   ├── menu.xml          # Admin menu configuration
│   │   └── system.xml        # System configuration settings
│   ├── acl.xml               # Access Control List definitions
│   ├── config.xml            # Default configuration values
│   └── events.xml            # Event observer configurations
├── Model/
│   └── Config/
│       └── Source/
│           └── Multiselect.php   # Source model for customer groups
├── Observer/
│   ├── OrderPlaceAfter.php       # Observer for post-order actions
│   └── RestrictCartObserver.php  # Observer for cart restriction logic
├── Exception/
│   └── CartUpdateRestrictionException.php  # Custom exception handling
├── Helper/
│   └── Data.php              # Helper functions for the module
├── Logger/
│   └── Handler.php           # Logging handler
│   └── Logger.php            # Logger implementation
└── view/
    └── adminhtml/
        └── web/
            ├── css/
            │   └── menu-icons.css  # CSS for admin menu icons
            ├── images/
            │   └── icon.svg        # Icon for the extension
            └── layout/
                └── default.xml     # Admin layout customization
```

## Cart Restriction Logic

When a customer attempts to add products to the cart:

1. The extension checks if it is enabled
2. It verifies if the customer belongs to a restricted group
3. It calculates the total quantity of items in the cart
4. If the new total would exceed the maximum allowed quantity, the action is restricted and an error message is displayed

### RestrictCartObserver

The `RestrictCartObserver` class is the core component responsible for implementing the cart restriction logic. It:

- Observes multiple cart-related events to ensure complete coverage
- Handles different product types (simple, configurable, grouped, bundle)
- Correctly calculates quantities for parent and child items
- Prevents recursive processing when multiple events are triggered
- Provides detailed logs for troubleshooting

### Event Handling

The extension listens to the following Magento events:

| Event | Purpose |
|-------|---------|
| `checkout_cart_product_add_before` | Intercepts product addition to cart before it happens |
| `checkout_cart_update_items_after` | Monitors updates to cart items |
| `sales_quote_item_qty_set_after` | Tracks quantity changes to quote items |
| `sales_order_place_after` | Optional logging of successful order placement |

## Handling Different Product Types

The extension is designed to work correctly with all Magento product types:

- **Simple Products**: Direct quantity counting
- **Configurable Products**: Counts parent items only to avoid double-counting
- **Grouped Products**: Counts total quantity of all items in the group
- **Bundle Products**: Treats each bundle as a single item regardless of contents

## Exception Handling

The module uses a custom exception class (`CartUpdateRestrictionException`) to gracefully handle restriction violations, displaying user-friendly messages while preventing cart updates that would exceed the configured limits.


## Compatibility

This extension is compatible with:
- Magento Open Source: 2.3.x, 2.4.x
- Magento Commerce: 2.3.x, 2.4.x

## Troubleshooting

If you encounter issues with the extension:

1. Check if the extension is enabled in the admin configuration
2. Verify that the customer groups are correctly set
3. Review the Magento logs for detailed information about restriction events
4. Clear the Magento cache and try again

## Changelog

**Version 1.0.0**
- Initial release with basic quantity restriction functionality
- Customer group filtering
- Admin configuration
- Support for all product types
- Detailed logging system

---