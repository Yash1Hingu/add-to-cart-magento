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


## Cart Methods

#### Method Overview

| Aspect | Details |
|--------|---------|
| **Method Name** | `execute` |
| **Type** | Public method |
| **Purpose** | Entry point for cart quantity restriction functionality |
| **Parameters** | `Observer $observer`: Magento event observer object |
| **Return Value** | `void` |
| **Exceptions** | `CartUpdateRestrictionException`: When cart update violates restrictions |

#### Handler Methods Comparison

| Feature | `handleAddToCart` | `handleCartUpdate` | `handleQuoteItemUpdate` |
|---------|-------------------|--------------------|-----------------------|
| **Triggered By** | Product added to cart | Cart quantity updated | Quote item quantity changed |
| **Event Data** | Contains `product` | Contains `cart` | Contains `item` |
| **Error Action** | Throws exception | Throws exception | Reverts quantity silently |
| **UI Notification** | No (commented out) | Yes, warning message | Yes, error message |
| **Handles Recursion** | No | No | Yes, with static flag |
| **Logs Failure** | No | Yes | No |

#### Workflow Steps

| Step | Action | Purpose |
|------|--------|---------|
| 1. Initialization | Log activity and check if enabled | Early termination if not relevant |
| 2. Configuration | Load restricted groups and limits | Prepare restriction parameters |
| 3. Group Filtering | Check customer's group | Skip irrelevant customer groups |
| 4. Event Analysis | Check event data structure | Determine which handler to call |
| 5. Handler Delegation | Call appropriate handler method | Process specific cart operation type |

#### Event Type Detection Logic

| If Event Contains | Then | Handler Called |
|-------------------|------|---------------|
| `product` data | Product is being added | `handleAddToCart()` |
| `cart` data | Cart is being updated | `handleCartUpdate()` |
| `item` data | Quote item is changing | `handleQuoteItemUpdate()` |

#### Dependencies and Services

| Dependency | Type | Purpose |
|------------|------|---------|
| `$this->logger` | PSR Logger | Records operational information |
| `$this->configHelper` | Helper Class | Provides extension configuration |
| `$this->customerSession` | Magento Session | Accesses customer information |
| `$this->restrictedGroups` | Array | Stores configured restricted groups |
| `$this->maxAllowedQty` | Integer | Maximum items allowed in cart |

#### Complex Product Type Handling

| Product Type | Handling Approach | Counting Method |
|--------------|-------------------|----------------|
| Simple Products | Direct count | Each item counted individually |
| Child Items | Skip in counting | Parent items represent these |
| Bundle Products | Count as single items | Bundle = 1 regardless of contents |
| Grouped Products | Special detection logic | Sum of all items or count of associated products |

#### Integration Points

| Integration Point | Details | Event Name |
|-------------------|---------|------------|
| Add to Cart | Register in events.xml | `checkout_cart_product_add_after` |
| Cart Update | Register in events.xml | `checkout_cart_update_items_after` |
| Quote Item Update | Register in events.xml | `sales_quote_item_qty_set_after` |


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