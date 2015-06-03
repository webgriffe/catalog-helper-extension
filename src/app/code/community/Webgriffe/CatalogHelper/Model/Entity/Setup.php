<?php
/**
 * Created by PhpStorm.
 * User: manuele
 * Date: 03/06/15
 * Time: 15:40
 */

class Webgriffe_CatalogHelper_Model_Entity_Setup extends Mage_Eav_Model_Entity_Setup
{
    /**
     * Creates an Attribute Set initialized from "Default" Attribute Set.
     * Returns the Attribute Set created.
     *
     * @param string $attributeSetName
     * @param $sortOrder
     * @return Mage_Eav_Model_Entity_Attribute_Set
     */
    public function createProductAttributeSetFromDefault($attributeSetName, $sortOrder = null)
    {
        /** @var Mage_Eav_Model_Entity_Attribute_Set $attributeSet */
        $attributeSetId = $this->_getProductAttributeSetId($attributeSetName);
        if (!$attributeSetId) {
            $attributeSet = Mage::getModel('eav/entity_attribute_set')
                ->setEntityTypeId($this->_getProductEntityTypeId())
            ;
        } else {
            $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);
        }
        $attributeSet
            ->setAttributeSetName($attributeSetName)
            ->setSortOrder($sortOrder)
        ;
        if (!$attributeSetId) {
            $attributeSet->validate();
        }
        $attributeSet->save();
        $attributeSet
            ->initFromSkeleton($this->getDefaultAttributeSetId($this->_getProductEntityTypeId()))
            ->save()
        ;
        return $attributeSet;
    }

    /**
     * Creates product Attribute with supplied data and adds the attribute to the specified Attribute Group. If the
     * group doesn't exists it will be created in the specified Attribute Set.
     *
     * @param string $attributeCode
     * @param string $attributeGroupName
     * @param string $attributeSetName
     * @param array $attributeData
     */
    public function createProductAttributeWithGroup(
        $attributeCode,
        $attributeGroupName,
        $attributeSetName,
        $attributeData = array()
    ) {
        $this->_createProductAttribute($attributeCode, $attributeData);
        $attributeGroupArray = $this->getAttributeGroup(
            $this->_getProductEntityTypeId(),
            $attributeSetName,
            $attributeGroupName
        );
        if (!$attributeGroupArray) {
            $this->addAttributeGroup($this->_getProductEntityTypeId(), $attributeSetName, $attributeGroupName);
        }
        $this->addAttributeToGroup(
            $this->_getProductEntityTypeId(),
            $attributeSetName,
            $attributeGroupName,
            $attributeCode
        );
    }

    /**
     * @return int
     * @throws Mage_Core_Exception
     */
    protected function _getProductEntityTypeId()
    {
        return $this->getEntityTypeId(Mage_Catalog_Model_Product::ENTITY);
    }

    private function _getProductAttributeSetId($attributeSetName)
    {
        return $this->getAttributeSet($this->_getProductEntityTypeId(), $attributeSetName, 'attribute_set_id');
    }

    /**
     * @param $attributeCode
     * @param $attributeData
     */
    protected function _createProductAttribute($attributeCode, $attributeData)
    {
        $attributeData = array_merge($attributeData, array('user_defined' => '1'));
        $this->addAttribute($this->_getProductEntityTypeId(), $attributeCode, $attributeData);
        $this->updateAttribute($this->_getProductEntityTypeId(), $attributeCode, 'is_user_defined', '0');
    }
}

