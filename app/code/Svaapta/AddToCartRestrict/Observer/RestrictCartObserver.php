<?php
namespace Svaapta\AddToCartRestrict\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Svaapta\AddToCartRestrict\Exception\CartUpdateRestrictionException;
use Svaapta\AddToCartRestrict\Logger\Logger;

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
        Cart $cart
    ) {
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->customerSession = $customerSession;
        $this->cart = $cart;
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

        $event = $observer->getEvent();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

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
        $allowedQtyLimit = 5;
        $this->logger->info('Product ID: ' . $product->getId());
        
        // log the product data
        
        $productQty = isset($observer->getEvent()->getData()['info']['qty']) ? (int) $observer->getEvent()->getData()['info']['qty'] : 1;

        $this->logger->info('Product Data: ' . print_r($observer->getEvent()->getInfo(), true));
        // log the product qty
        $this->logger->info('Product Qty: ' . $productQty);

        // Check if the current user is in the restricted group (e.g., Guest or General)
        $customerGroupId = $this->customerSession->getCustomerGroupId();
        $restrictedGroups = [0, 1]; // Example: 0 - Guest, 1 - General Customer Group
        
        
        $this->logger->info('Customer Group ID: ' . $customerGroupId);
        
        if (in_array($customerGroupId, $restrictedGroups)) {
            // Calculate the total quantity in the cart
            $currentQty = 0;
            foreach ($quote->getAllItems() as $item) {
                $currentQty += $item->getQty();
            }

            // Check if the new addition will exceed the allowed limit
            if ($currentQty + $productQty > $allowedQtyLimit) {
                // Prevent adding the item to the cart
                $this->messageManager->addWarningMessage(
                    __('You cannot add more than 5 items to your cart.')
                );

                // Optionally, throw an exception or revert changes
                throw new LocalizedException(__('You cannot add Product.'));
            }

            $this->logger->info('Current Cart Quantity: ' . $currentQty);
        }
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
        $this->logger->info('Cart Data: ' . print_r($quote->getAllItems(), true));

        // Get update info and the item id being updated
        $info = $observer->getEvent()->getInfo()->getData();
        $itemId = key($info);
        $newQty = $info[$itemId]['qty']; // New quantity entered by the customer

        $this->logger->info('New Qty: ' . $newQty);
        $this->logger->info('Current Customer Group: ' . $this->customerSession->getCustomerGroupId());
        $this->logger->info('Item ID: ' . $itemId);

        $currentGroupId = $this->customerSession->getCustomerGroupId();

        // Apply restriction logic for allowed customer groups (Guest and General)
        if (in_array($currentGroupId, $this->restrictedGroups)) {
            $this->logger->info('Applying cart update restrictions.');

            $currentQty = 0;
            $originalQty = 0;

            // Calculate total quantity excluding the item being updated
            foreach ($quote->getAllItems() as $item) {
                if ($item->getId() == $itemId) {
                    $originalQty = $item->getQty(); // Save original quantity
                } else {
                    $currentQty += $item->getQty();
                }
            }

            $this->logger->info('Total Qty from other items: ' . $currentQty);

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

        // Retrieve the quote item and related customer details
        $item = $observer->getEvent()->getData('item');
        $quote = $item->getQuote();
        $customer = $quote->getCustomer();

        $this->logger->info('Item Data: ' . print_r($item->getData(), true));

        // Calculate the total quantity in the cart
        $totalQty = 0;
        foreach ($quote->getAllItems() as $cartItem) {
            $totalQty += $cartItem->getQty();
        }
        $this->logger->info('Total Quantity in Cart: ' . $totalQty);

        // Revert the item quantity if total quantity exceeds allowed maximum
        if ($totalQty > $this->maxAllowedQty) {
            $originalQty = $item->getOrigData('qty');
            $item->setQty($originalQty);
            $this->messageManager->addErrorMessage(
                __('You can only have a maximum of %1 items in your cart.', $this->maxAllowedQty)
            );
        }

        // Reset the processing flag
        self::$isProcessing = false;
    }
}
