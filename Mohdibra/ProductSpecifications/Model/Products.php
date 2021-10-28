<?php

namespace Mohdibra\ProductSpecifications\Model;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Mohdibra\ProductSpecifications\Api\ProductsInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Phrase;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\Product\Attribute\Repository;

class Products implements ProductsInterface
{
       /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

      /**
     * @var Product
     */
    protected $_product = null;
    protected $attribute;
    protected $attributeRepository;


    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $resourceModel;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;
    protected $eavConfig;
    private $productRepository; 

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $resourceModel
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        Attribute $attribute,
	    Repository $attributeRepository,
        \Magento\Catalog\Model\ResourceModel\Product $resourceModel,
        \Magento\Eav\Model\Config $eavConfig,
        array $data = []
    ) {
        $this->productFactory       =  $productFactory;
        $this->priceCurrency = $priceCurrency;
        $this->_coreRegistry = $registry;
        $this->productRepository = $productRepository;
        $this->attribute = $attribute;
        $this->attributeRepository = $attributeRepository;
        $this->eavConfig = $eavConfig;
        $this->resourceModel        =  $resourceModel;
    }

    public function getAdditional($sku, $editMode = false, $storeId = null, $forceReload = false)
    {
        $data = [];
        $excludeAttr = [];
        //$product = $this->productRepository->get($sku);
        $product = $this->productFactory->create();
        $productId = $this->resourceModel->getIdBySku($sku);
        $product->load($productId);



        $attributes = $product->getAttributes();
        
        foreach ($attributes as $attribute) {
            if ($this->isVisibleOnFrontend($attribute, $excludeAttr)) {
                
                $code = $attribute->getAttributeCode();
                
                $value = $attribute->getFrontend()->getValue($product);

                if ($value instanceof Phrase) {
                    $value = (string)$value;
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = $this->priceCurrency->convertAndFormat($value);
                }

               // if (is_string($value) && strlen(trim($value))) {
                    $data[] = [
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code' => $attribute->getAttributeCode(),
                        'type' => $attribute->getFrontendInput(),
                    ];
               // }

            }
        }
        return $data;
    }

    protected function isVisibleOnFrontend(
        \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute,
        array $excludeAttr
    ) {
        return ($attribute->getIsVisibleOnFront() && !in_array($attribute->getAttributeCode(), $excludeAttr));
    }
}