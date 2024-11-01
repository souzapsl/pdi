<?php

namespace Paulo\AjaxCompare\Setup;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Validator\ValidateException;
use Paulo\AjaxCompare\Model\Config\Source\OptionsPromo;

class InstallData implements InstallDataInterface
{
    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(protected EavSetupFactory $eavSetupFactory) {

    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            'promo',
            [
                'type' => 'int',
                'label' => 'Promo',
                'input' => 'select',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'source' => OptionsPromo::class,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => 0,
                'comparable' => true,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'unique' => false,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'observation',
            [
                'type' => 'text',
                'label' => 'Observation',
                'input' => 'text',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => 0,
                'comparable' => true,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'unique' => false,
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Product::ENTITY);
        $eavSetup->addAttributeToSet(
            Product::ENTITY,
            $attributeSetId,
            'General',
            'promo'
        );
        $eavSetup->addAttributeToSet(
            Product::ENTITY,
            $attributeSetId,
            'General',
            'observation'
        );
    }
}
