<?php
/**
 * This file is part of the SetaPDF-Stamper Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: XObject.php 1706 2022-03-28 10:40:28Z jan.slabon $
 */

/**
 * The XObject stamp class
 * 
 * This stamp class is able to use a {@link SetaPDF_Core_XObject} instance as its stamp appearance.
 * XObjects could be {@link SetaPDF_Core_XObject_Image images} or so called {@link SetaPDF_Core_XObject_Form form XObjects}.
 * 
 * A form XObject is a PDF content stream that is a self-contained description of any sequence of
 * graphics objects (including path objects, text objects and sampled images).
 * 
 * This stamp class allows you to stamp separately form XObjects or image XObjects.
 * A form XObject could include for example drawing operations:
 * 
 * <code>
 * // Create a form XObject with the dimensions of 100 x 100
 * $xObject = SetaPDF_Core_XObject_Form::create($document, array(0, 0, 100, 100));
 * $canvas = $xObject->getCanvas();
 * $canvas->path()->setLineWidth(1); 
 * $canvas->draw()
 *     ->rect(0, 0, 100, 100) // Draw a rectangle into the form XObject
 *     ->circle(50, 50, 50); // Draw a circle into the form XObject
 *      
 * $stamp = new SetaPDF_Stamper_Stamp_XObject($xObject);
 * </code>
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Stamper_Stamp_XObject extends SetaPDF_Stamper_Stamp
{
    /**
     * The XObject instance
     *
     * @var SetaPDF_Core_XObject
     */
    protected $_xObject = null;
    
    /**
     * The individual width of the image
     *  
     * @var float|integer
     */
    protected $_width = null;
    
    /**
     * The individual height of the image
     * 
     * @var float|integer
     */
    protected $_height = null;

    /**
     * The constructor.
     * 
     * @param SetaPDF_Core_XObject $xObject The xobject instance
     */
    public function __construct(SetaPDF_Core_XObject $xObject)
    {
        $this->setXObject($xObject);
    }

    /**
     * Release resources / cycled references.
     */
    public function cleanUp()
    {
        $this->_xObject = null;
    }

    /**
     * Set the XObject.
     * 
     * @param SetaPDF_Core_XObject $xObject The new xobject instance
     */
    public function setXObject(SetaPDF_Core_XObject $xObject)
    {
        $this->_xObject = $xObject;
    }

    /**
     * Get the XObject instance.
     *
     * @return SetaPDF_Core_XObject
     */
    public function getXObject()
    {
        return $this->_xObject;
    }

    /**
     * Set the individual width of the XObject stamp.
     * 
     * @param float|integer $width The new width
     */
    public function setWidth($width)
    {
        $this->_width = $width;    
    }

    /**
     * Get the width of the XObject stamp.
     * 
     * If no individual width is given the width will be received from the XObject object by
     * forwarding an individual height (if available) to keep the aspect ratio. 
     * 
     * @return float|integer
     */
    public function getWidth()
    {
        return $this->_width !== null
            ? $this->_width
            : $this->_xObject->getWidth($this->_height);
    }

    /**
     * Set the individual height if the XObject stamp.
     *
     * @param float|integer $height The new height
     */
    public function setHeight($height)
    {
        $this->_height = $height;
    }

    /**
     * Get the height of the XObject stamp.
     * 
     * If no individual height is given the height will be received from the XObject object by
     * forwarding an individual width (if available) to keep the aspect ratio. 
     * 
     * @return float|integer
     */
    public function getHeight()
    {
        return $this->_height !== null
            ? $this->_height
            : $this->_xObject->getHeight($this->_width);  
    }
    
    /**
     * Set the dimensions of this stamp.
     *
     * @param float|integer $width The new width
     * @param float|integer $height The new height
     */
    public function setDimensions($width, $height)
    {
        $this->setWidth($width);
        $this->setHeight($height);
    }

    /**
     * Ensures that all stamp resources are added to the page.
     *
     * This is needed to reuse a cached stamp stream.
     *
     * @see SetaPDF_Stamper_Stamp::_ensureResources()
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Core_Document_Page $page
     * @return array An array of resource names
     */
    protected function _ensureResources(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page)
    {
        $names = parent::_ensureResources($document, $page);
        $names[SetaPDF_Core_Resource::TYPE_X_OBJECT][] = $page->getCanvas()->addResource($this->_xObject);
    
        return $names;
    }
    
    /**
     * Writes the xobject draw operators of this stamp onto the canvas.
     * 
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Core_Document_Page $page
     * @param array $stampData
     * @return bool
     */
    protected function _stamp(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page, array $stampData)
    {
        $x = $this->getOriginX($page, $stampData['position']);
        $y = $this->getOriginY($page, $stampData['position']);
        
        $opacity = $this->getOpacity();
        if ($this->_xObject instanceof SetaPDF_Core_XObject_Form &&
            abs($opacity - 1.0) > SetaPDF_Core::FLOAT_COMPARISON_PRECISION
        ) {
            $this->_xObject->setGroup(new SetaPDF_Core_TransparencyGroup());
        }
        
        $canvas = $page->getCanvas();
        $this->_xObject->draw($canvas, $x, $y, $this->getWidth(), $this->getHeight());
        
        return true;
    }
}