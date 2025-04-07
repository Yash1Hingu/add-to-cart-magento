# **Svaapta_AddToCartRestrict Module**

## **Overview**
The `Svaapta_AddToCartRestrict` module is designed to restrict the quantity of items that can be added to the cart for specific customer groups in a Magento 2 store. It ensures that customers in restricted groups cannot exceed a predefined maximum cart quantity.

---

## **Features**
1. Restricts the total quantity of items in the cart for specific customer groups.
2. Handles the following scenarios:
   - Adding a product to the cart for the first or second time.
   - Updating product quantity from a modal.
   - Updating the cart from the cart page.
3. Provides customizable maximum quantity limits.
4. Implements a custom logger for detailed debugging and monitoring.
5. Throws custom exceptions for better error handling and debugging.
6. Displays user-friendly error messages when restrictions are violated.
7. Uses common logic for calculating total cart quantity and validating restrictions.

---

## **Installation**

### **Step 1: Enable Developer Mode**
Run the following command to enable developer mode:
```bash
php bin/magento deploy:mode:set developer
```

### **Step 2: Copy the Module Files**
Place the module files in the following directory:
```
app/code/Svaapta/AddToCartRestrict
```

### **Step 3: Enable the Module**
Run the following commands to enable the module:
```bash
php bin/magento module:enable Svaapta_AddToCartRestrict
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

---

## **Configuration**

### **1. Maximum Allowed Quantity**
The maximum allowed quantity is defined in the `RestrictCartObserver` class as a private property:
```php
private $maxAllowedQty = 5;
```
You can modify this value to set a different limit.

### **2. Restricted Customer Groups**
The restricted customer groups are defined as an array in the `RestrictCartObserver` class:
```php
private $restrictedGroups = [0, 1]; // Guest and General customer groups
```
You can update this array to include other customer group IDs.

---

## **Observer Details**

### **Observer Class**
The main observer class is located at:
```
app/code/Svaapta/AddToCartRestrict/Observer/RestrictCartObserver.php
```

### **Events Observed**
The module listens to the following events:
1. **Add to Cart Event**: Restricts adding products to the cart if the total quantity exceeds the limit.
2. **Cart Update Event**: Restricts updating cart quantities from the cart page.
3. **Quote Item Update Event**: Restricts updating product quantities from a modal.

---

## **Common Logic in Key Methods**

The three key methods (`handleAddToCart`, `handleCartUpdate`, and `handleQuoteItemUpdate`) share common logic for calculating the total cart quantity and validating restrictions. This ensures consistency and reduces code duplication.

### **1. Calculating Total Cart Quantity**
The total cart quantity is calculated by iterating through all items in the cart and summing their quantities. This logic is used in all three methods.


### **2. Validating Cart Quantity Restrictions**
The validation logic checks if the total cart quantity exceeds the maximum allowed quantity. If the limit is exceeded, it logs the error, displays a warning message to the user, and throws a custom exception.


## **Key Methods**

### **1. `handleAddToCart`**
Handles restrictions when a product is added to the cart. It uses the common logic to calculate the total cart quantity and validate restrictions.

---

### **2. `handleCartUpdate`**
Handles restrictions when the cart is updated from the cart page. It uses the common logic to calculate the total cart quantity (excluding the item being updated) and validate restrictions.

---

### **3. `handleQuoteItemUpdate`**
Handles restrictions when a product quantity is updated from a modal. It uses the common logic to calculate the total cart quantity and validate restrictions.

---

## **Custom Logger**

The module uses a custom logger to log all cart-related events for debugging and monitoring purposes. Logs are stored in the Magento log files (e.g., `var/log/svaapta_restrict_cart.log`).

---

## **Custom Exceptions**

The module uses a custom exception class, `CartUpdateRestrictionException`, to handle cart restriction errors. This ensures that errors are properly logged and displayed to the user.

---

## **Uninstallation**

### **Step 1: Disable the Module**
Run the following command to disable the module:
```bash
php bin/magento module:disable Svaapta_AddToCartRestrict
```

### **Step 2: Remove the Module Files**
Delete the module directory:
```
rm -rf app/code/Svaapta/AddToCartRestrict
```

### **Step 3: Clean Up**
Run the following commands to clean up:
```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

---

## **Support**
For any issues or questions regarding this module, please contact the development team at **yash23hingu@gmail.com**.

---

## **Changelog**

### **Version 1.0.0**
- Initial release of the module.
- Added support for cart quantity restrictions for specific customer groups.
- Implemented custom logger and exception handling.
- Added user-friendly error messages.

---

This `README.md` file provides a comprehensive guide to installing, configuring, and using the `Svaapta_AddToCartRestrict` module, including details about the common logic, custom logger, and exception handling.
