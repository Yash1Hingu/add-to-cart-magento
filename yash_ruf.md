# Magento Commands

## SUPER COMMAND
sudo php bin/magento setup:upgrade && sudo php bin/magento setup:di:compile && sudo php bin/magento setup:static-content:deploy -f && sudo php bin/magento indexer:reindex && sudo php bin/magento cache:flush && sudo php bin/magento cache:clean && sudo chmod -R 777 var/ pub/ generated/

---

## REFERESH COMMAND
php bin/magento indexer:reindex && php bin/magento cache:flush && php bin/magento cache:clean

---

## See Error
tail -f var/log/exception.log

---


# Extension In Magento
## Install Extension
composer require <extension-provider>/<extension-name>:<extension-version>

Example:
-------
composer require sparsh/quick-view-magento-2-extension:1.3.2
composer require mb/magento2-whatsappchat:1.0.2
composer require swissup/module-marketplace
**SUPER COMMAND**

    
## Uninstall Extension
php bin/magento module:status
php bin/magento module:disable <ext-provider_ext-name> --clear-static-content

Example:
--------
composer remove ExtensionProvider/ExtensionName
composer remove swissup/module-marketplace

## Image Upload
chmod -R 777 var/import/images
