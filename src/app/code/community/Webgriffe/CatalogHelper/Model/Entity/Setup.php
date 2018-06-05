<?php
/**
 * Created by PhpStorm.
 * User: manuele
 * Date: 03/06/15
 * Time: 15:40
 */

class Webgriffe_CatalogHelper_Model_Entity_Setup extends Mage_Catalog_Model_Resource_Setup
{
    /**
     * Creates an Attribute Set initialized from "Default" Attribute Set.
     * Returns the Attribute Set created.
     *
     * @param string $attributeSetName
     * @param $sortOrder
     * @return Mage_Eav_Model_Entity_Attribute_Set
     * @throws Mage_Core_Exception
     * @throws Exception
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
     * Possible attribute data are:
     *
     * 'backend_model'   => $this->_getValue($attr, 'backend'),
     * 'backend_type'    => $this->_getValue($attr, 'type', 'varchar'),
     * 'backend_table'   => $this->_getValue($attr, 'table'),
     * 'frontend_model'  => $this->_getValue($attr, 'frontend'),
     * 'frontend_input'  => $this->_getValue($attr, 'input', 'text'),
     * 'frontend_label'  => $this->_getValue($attr, 'label'),
     * 'frontend_class'  => $this->_getValue($attr, 'frontend_class'),
     * 'source_model'    => $this->_getValue($attr, 'source'),
     * 'is_required'     => $this->_getValue($attr, 'required', 1),
     * 'is_user_defined' => $this->_getValue($attr, 'user_defined', 0),
     * 'default_value'   => $this->_getValue($attr, 'default'),
     * 'is_unique'       => $this->_getValue($attr, 'unique', 0),
     * 'note'            => $this->_getValue($attr, 'note'),
     * 'is_global'       => $this->_getValue($attr, 'global', 1),
     *
     * @param string $attributeCode
     * @param string $attributeGroupName
     * @param string $attributeSetName
     * @param array $attributeData
     * @throws Mage_Core_Exception
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
     * Replacement for Mage_Eav_Model_Entity_Setup::addAttribute which adds attribute only if not it already exists.
     * @param $entityTypeId
     * @param $code
     * @param array $attr
     * @throws Mage_Core_Exception
     */
    public function createProductAttributeIfNotExists($code, array $attr)
    {
        if ($this->getAttributeId($this->_getProductEntityTypeId(), $code)) {
            return;
        }
        $this->_createProductAttribute($code, $attr);
    }

    /**
     * @return int
     * @throws Mage_Core_Exception
     */
    protected function _getProductEntityTypeId()
    {
        return $this->getEntityTypeId(Mage_Catalog_Model_Product::ENTITY);
    }

    /**
     * @param $attributeSetName
     * @return int
     * @throws Mage_Core_Exception
     */
    private function _getProductAttributeSetId($attributeSetName)
    {
        return (int)$this->getAttributeSet($this->_getProductEntityTypeId(), $attributeSetName, 'attribute_set_id');
    }

    /**
     * @see Mage_Eav_Model_Entity_Setup::_prepareValues() for the native mapping used by Magento in addAttribute()
     *
     * @param $attributeCode
     * @param $attributeData
     * @throws Mage_Core_Exception
     */
    protected function _createProductAttribute($attributeCode, $attributeData)
    {
        if (!isset($attributeData['type']) && isset($attributeData['input'])) {
            $attributeData['type'] = $this->_getBackendTypeFromInput($attributeData['input']);
        }
        if ($attributeData['input'] === 'select' &&
            (!isset($attributeData['source']) || null === $attributeData['source'])) {
            $attributeData['source'] = 'eav/entity_attribute_source_table';
        }
        if ($attributeData['input'] === 'multiselect' &&
            (!isset($attributeData['backend']) || null === $attributeData['backend'])) {
            $attributeData['backend'] = 'eav/entity_attribute_backend_array';
        }
        if ($attributeData['input'] === 'boolean' &&
            (!isset($attributeData['source']) || null === $attributeData['source'])) {
            $attributeData['source'] = 'eav/entity_attribute_source_boolean';
        }

        //Avoids Magento's behavior that associates an attribute to all attribute sets
        $attributeData = array_merge($attributeData, array('user_defined' => '1'));

        $this->addAttribute($this->_getProductEntityTypeId(), $attributeCode, $attributeData);

        //Sets the attribute to "not user defined" as desired
        $this->updateAttribute($this->_getProductEntityTypeId(), $attributeCode, 'is_user_defined', '0');
    }

    protected function _getBackendTypeFromInput($input)
    {
        return Mage::getModel('eav/entity_attribute')->getBackendTypeByInput($input);
    }
}
