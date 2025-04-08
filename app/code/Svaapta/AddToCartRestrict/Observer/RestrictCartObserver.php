<?php
namespace Svaapta\AddToCartRestrict\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Svaapta\AddToCartRestrict\Exception\CartUpdateRestrictionException;
use Svaapta\AddToCartRestrict\Logger\Logger;
use Svaapta\AddToCartRestrict\Helper\Data as ConfigHelper;

/**
 * Observer class to restrict cart quantity for certain customer groups.
 */
class RestrictCartObserver implements ObserverInterface
{
    /** @var Logger */
    protected $logger;

    /** @var ManagerInterface */
    protected $messageManager;

    /** @var CustomerSession */
    protected $customerSession;

    /** @var Cart */
    protected $cart;

    /** @var ConfigHelper */
    protected $configHelper;

    /** @var int Maximum allowed quantity in the cart */
    private $maxAllowedQty = 5;

    /** @var array Restricted customer group IDs (Guest & General customers) */
    private $restrictedGroups = [0, 1];

    /** @var bool Flag to prevent recursive processing */
    private static $isProcessing = false;

    /**
     * Constructor.
     *
     * @param Logger $logger
     * @param ManagerInterface $messageManager
     * @param CustomerSession $customerSession
     * @param Cart $cart
     */
    public function __construct(
        Logger $logger,
        ManagerInterface $messageManager,
        CustomerSession $customerSession,
        Cart $cart,
        ConfigHelper $configHelper
    ) {
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->customerSession = $customerSession;
        $this->cart = $cart;
        $this->configHelper = $configHelper;
    }

    /**
     * Execute observer.
     *
     * Determines the type of event and processes accordingly.
     *
     * @param Observer $observer
     * @return void
     * @throws CartUpdateRestrictionException
     */
    public function execute(Observer $observer)
    {
        $this->logger->info('Cart Observer Triggered.');

        // Check if extension is enabled
        if (!$this->configHelper->isEnabled()) {
            return;
        }

        //log
        
        $this->restrictedGroups = $this->configHelper->getCustomerGroups();
        $this->logger->info('Extension is enabled.');
        // log rGroups
        $this->logger->info('Restricted Customer Groups: ' . implode(',', $this->restrictedGroups));

        $event = $observer->getEvent();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $this->maxAllowedQty = $this->configHelper->getMaxAllowedQty();
        // log maxAllowedQty
        $this->logger->info('Max Allowed Quantity: ' . $this->maxAllowedQty);

        // Skip restriction logic if customer group is not restricted
        if (!in_array($customerGroupId, $this->restrictedGroups)) {
            return;
        }

        // Process add-to-cart event
        if ($event->getData('product')) {
            $this->logger->info('Processing Add-To-Cart Event.');
            $this->handleAddToCart($observer);
            return;
        }

        // Process cart update event
        if ($event->getData('cart')) {
            $this->logger->info('Processing Cart Update Event.');
            $this->handleCartUpdate($observer);
            return;
        }

        // Process quote item quantity update event
        if ($event->getData('item')) {
            $this->logger->info('Processing Quote Item Quantity Update.');
            $this->handleQuoteItemUpdate($observer);
            return;
        }
    }

    /**
     * Handle add-to-cart event.
     *
     * @param Observer $observer
     * @return void
     * @throws CartUpdateRestrictionException
     */
    private function handleAddToCart(Observer $observer)
    {
        $this->logger->info('Add to Cart Observer Triggered.');

        $product = $observer->getEvent()->getProduct();
        $quote = $this->cart->getQuote();
        $allowedQtyLimit = $this->maxAllowedQty;

        // $this->logger->info('Product ID: ' . $product->getId());
        
        
        $productQty = isset($observer->getEvent()->getData()['info']['qty']) ? (int) $observer->getEvent()->getData()['info']['qty'] : 1;

        // $this->logger->info('Product Data: ' . print_r($observer->getEvent()->getProduct()->getTypeInstance()->getAssociatedProducts($product), true));

        // // log length
        // $this->logger->info('Product Data Length: ' . count($observer->getEvent()->getProduct()->getTypeInstance()->getAssociatedProducts($product)));


        // is group product qty
        // Check if 'info' contains 'super_group' (which holds child product IDs and their quantities)
        if (isset($eventData['info']['super_group']) && is_array($eventData['info']['super_group'])) {
            $groupedItemsQty = $eventData['info']['super_group'];

            // Log each product ID and its quantity
            foreach ($groupedItemsQty as $productId => $qty) {
                $this->logger->info("Grouped Product ID: $productId, Quantity: $qty");
            }

            // Calculate total quantity of all items in the grouped product
            $productQty = array_sum($groupedItemsQty);
            $this->logger->info('Total Quantity of Grouped Products in Cart: ' . $productQty);
        }
        // Check if the product is a grouped product and get the associated products
        if(count($observer->getEvent()->getProduct()->getTypeInstance()->getAssociatedProducts($product))){
            $productQty = count($observer->getEvent()->getProduct()->getTypeInstance()->getAssociatedProducts($product));
        }


        // log the product qty
        // $this->logger->info('Product Qty: ' . $productQty)
        
        
        // $this->logger->info('Customer Group ID: ' . $customerGroupId);
        

        $currentQty = 0;
        foreach ($quote->getAllItems() as $cartItem) {
            // Skip child items (belonging to bundle or configurable)
            if ($cartItem->getParentItemId()) {
                continue;
            }
        
            // Count only the parent item qty (1 bundle = 1 item)
            $currentQty += $cartItem->getQty();
        }


        // LOG CURRENT QUANTITY
        $this->logger->info('ADD:: Current Cart Quantity: ' . $currentQty);
        // Check if the new addition will exceed the allowed limit
        if ($currentQty + $productQty > $allowedQtyLimit) {
            // Prevent adding the item to the cart
            $this->messageManager->addWarningMessage(
                __('You cannot add more than 5 items to your cart.')
            );
            // Optionally, throw an exception or revert changes
            throw new CartUpdateRestrictionException(__('You cannot add Product.'));
        }
        // $this->logger->info('Current Cart Quantity: ' . $currentQty);
    }

    /**
     * Handle cart update event.
     *
     * @param Observer $observer
     * @return void
     * @throws CartUpdateRestrictionException
     */
    private function handleCartUpdate(Observer $observer)
    {
        $this->logger->info('Restricted Cart Update Observer Trigger.');

        $quote = $observer->getEvent()->getCart()->getQuote();
        // $this->logger->info("UO:: Cart Data: " . print_r($quote->getAllItems(), true));

        // Get update info and the item id being updated
        $info = $observer->getEvent()->getInfo()->getData();
        $itemId = key($info);
        $newQty = $info[$itemId]['qty']; // New quantity entered by the customer

        // $this->logger->info('UO:: New Qty: ' . $newQty);
        // $this->logger->info('UO:: Current Customer Group: ' . $this->customerSession->getCustomerGroupId());
        // $this->logger->info('UO:: Item ID: ' . $itemId);

        // Apply restriction logic for allowed customer groups (Guest and General)
        $this->logger->info('Applying cart update restrictions.');
        $currentQty = 0;
        $originalQty = 0;
        // Calculate total quantity excluding the item being updated
        foreach ($quote->getAllItems() as $item) {
            if ($item->getId() == $itemId) {
                $originalQty = $item->getQty(); // Save original quantity
            } else {
                if ($item->getParentItemId()) {
                    continue;
                }
                
                if($item->getHasChildren()){
                    $totalQty = $item->getQty();
                    continue;
                }
                
                // Count only the parent item qty (1 bundle = 1 item)
                $currentQty += $item->getQty();
            }
        }
        $this->logger->info('UO:: Total Qty from other items: ' . $currentQty);
        // Prevent update if total quantity would exceed the maximum allowed
        if ($currentQty + $newQty > $this->maxAllowedQty) {
            $this->messageManager->addWarningMessage(
                __('You can only have a maximum of %1 items in your cart.', $this->maxAllowedQty)
            );
            $this->logger->warning(
                sprintf(
                    'Blocked cart update: Attempt to update item ID %s to qty %s when current cart has %s items.',
                    $itemId,
                    $newQty,
                    $currentQty
                )
            );
            // Optionally, logic to revert the cart quantity can be added here.
            throw new CartUpdateRestrictionException(__('Cart quantity limit exceeded.'));
        }
    }

    /**
     * Handle quote item quantity update.
     *
     * @param Observer $observer
     * @return void
     */
    private function handleQuoteItemUpdate(Observer $observer)
    {
        // Prevent recursion if already processing
        if (self::$isProcessing) {
            return;
        }
        self::$isProcessing = true;

        $this->logger->info('Restricted Cart Update Trigger.');

        // LOG OBSERVER INFO
        // $this->logger->info('QUOTE:: Observer Data: ' . print_r($observer->getData(), true));

        // Retrieve the quote item and related customer details
        $item = $observer->getEvent()->getData('item');
        $quote = $item->getQuote();
        $customer = $quote->getCustomer();

        // $this->logger->info('QUOTE:: Item Data: ' . print_r($quote->getAllItems(), true));

        // Calculate the total quantity in the cart
        $totalQty = 0;
        foreach ($quote->getAllItems() as $cartItem) {
            // Skip child items (belonging to bundle or configurable)
            if ($cartItem->getParentItemId()) {
                continue;
            }

            if($cartItem->getHasChildren()){
                $totalQty = $cartItem->getQty();
                continue;
            }
        
            // Count only the parent item qty (1 bundle = 1 item)
            $totalQty += $cartItem->getQty();
        
            // $this->logger->info(sprintf(
            //     'Counting Parent Item ID %d (SKU: %s): Qty = %d',
            //     $cartItem->getId(),
            //     $cartItem->getSku(),
            //     $cartItem->getQty()
            // ));
        }
        // $this->logger->info('QUOTE:: Total Quantity in Cart: ' . $totalQty);

        // Revert the item quantity if total quantity exceeds allowed maximum
        if ($totalQty > $this->maxAllowedQty) {
            $originalQty = $item->getOrigData('qty');
            $item->setQty($originalQty);
            $this->messageManager->addErrorMessage(
                __('Quote You can only have a maximum of %1 items in your cart.', $this->maxAllowedQty)
            );
        }

        // Reset the processing flag
        self::$isProcessing = false;
    }
}
