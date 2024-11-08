<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Document
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: XObject.php 1733 2022-06-02 07:39:42Z jan.slabon $
 */

/**
 * Abstract class representing an external object
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @license    https://www.setasign.com/ Commercial
 */
abstract class SetaPDF_Core_XObject implements SetaPDF_Core_Resource
{
    /**
     * The indirect object of the XObject
     *
     * @var SetaPDF_Core_Type_IndirectObject
     */
    protected $_indirectObject;

    /**
     * Release XObject instances by a document instance.
     *
     * @param SetaPDF_Core_Document $document
     */
    public static function freeCache(SetaPDF_Core_Document $document)
    {
        $document->clearCache(SetaPDF_Core_Document::CACHE_X_OBJECT);
    }

    /**
     * Get an external object by an indirect object/reference.
     *
     * @param SetaPDF_Core_Type_IndirectObjectInterface $xObjectReference
     * @param string $subType
     * @return SetaPDF_Core_XObject_Form|SetaPDF_Core_XObject_Image
     * @throws SetaPDF_Exception_NotImplemented
     */
    public static function get(SetaPDF_Core_Type_IndirectObjectInterface $xObjectReference, $subType = null)
    {
        $indirectObject = $xObjectReference;

        $document = $indirectObject->getOwnerPdfDocument();
        $ident = $indirectObject->getObjectIdent();
        if ($document->hasCache(SetaPDF_Core_Document::CACHE_X_OBJECT, $ident)) {
            return $document->getCache(SetaPDF_Core_Document::CACHE_X_OBJECT, $ident);
        }

        if ($indirectObject instanceof SetaPDF_Core_Type_IndirectReference) {
            $indirectObject = $indirectObject->getValue();
        }

        $xObjectDict = $indirectObject->ensure(true)->getValue();
        $subType = $subType ?: $xObjectDict->getValue('Subtype')->getValue();
        
        switch ($subType) {
            case 'Image':
                $xObject = new SetaPDF_Core_XObject_Image($indirectObject);
                break;
            case 'Form':
                $xObject = new SetaPDF_Core_XObject_Form($indirectObject);
                break;
            default:
                throw new SetaPDF_Exception_NotImplemented('Not implemented yet. (XObject: ' . $subType . ')');
        }

        $document->addCache(SetaPDF_Core_Document::CACHE_X_OBJECT, $ident, $xObject);

        return $xObject;
    }

    /**
     * The constructor.
     *
     * @param SetaPDF_Core_Type_IndirectObjectInterface $indirectObject
     */
    public function __construct(SetaPDF_Core_Type_IndirectObjectInterface $indirectObject)
    {
        if ($indirectObject instanceof SetaPDF_Core_Type_IndirectReference) {
            $indirectObject = $indirectObject->getValue();
        }

        $this->_indirectObject = $indirectObject;
    }

    /**
     * Release memory and cycled references.
     */
    public function cleanUp()
    {
        $document = $this->_indirectObject->getOwnerPdfDocument();
        $ident = $this->_indirectObject->getObjectIdent();
        $this->_indirectObject = null;
        $document->clearCache(SetaPDF_Core_Document::CACHE_X_OBJECT, $ident);
    }

    /**
     * Get the indirect object of this XObject.
     *
     * @param SetaPDF_Core_Document|null $document
     * @return SetaPDF_Core_Type_IndirectObject
     */
    public function getIndirectObject(SetaPDF_Core_Document $document = null)
    {
        return $this->_indirectObject;
    }

    /**
     * Get the resource type for external objects.
     * 
     * @see SetaPDF_Core_Resource::getResourceType()
     * @return string
     */
    public function getResourceType()
    {
        return SetaPDF_Core_Resource::TYPE_X_OBJECT;
    }
    
    /**
     * Draw the external object on the canvas.
     *
     * @param SetaPDF_Core_Canvas $canvas
     * @param int $x
     * @param int $y
     * @param null|float $width
     * @param null|float $height
     * @return mixed
     */
    abstract public function draw(SetaPDF_Core_Canvas $canvas, $x = 0, $y = 0, $width = null, $height = null);

    /* it is not possible to implement an abstract method which also is defined in an interface by the implementing class...
    abstract function getHeight();
    
    abstract function getWidth();
    */
}