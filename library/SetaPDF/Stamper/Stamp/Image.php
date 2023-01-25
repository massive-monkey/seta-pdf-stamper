<?php
/**
 * This file is part of the SetaPDF-Stamper Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: Image.php 1706 2022-03-28 10:40:28Z jan.slabon $
 */

/**
 * The image stamp class
 * 
 * To initiate an instance it's needed to pass an image instance of {@link SetaPDF_Core_Image} to
 * the constructor:
 * 
 * <code>
 * $image = SetaPDF_Core_Image::getByPath('path/to/an/image.jpg');
 * $stamp = new SetaPDF_Stamper_Stamp_Image($image);
 * </code>
 * 
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Stamper_Stamp_Image extends SetaPDF_Stamper_Stamp
{
    /**
     * The image instance
     * 
     * @var SetaPDF_Core_Image
     */
    protected $_image;
    
    /**
     * The XObject instance of the image
     * 
     * @var SetaPDF_Core_XObject_Image
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
     * @param SetaPDF_Core_Image $image The image object
     */
    public function __construct(SetaPDF_Core_Image $image)
    {
        $this->setImage($image);
    }
    
    /**
     * Release resources / cycled references.
     */
    public function cleanUp()
    {
        $this->_xObject = null;
        $this->_image = null;
    }

    /**
     * Set the image.
     * 
     * @param SetaPDF_Core_Image $image The image object
     */
    public function setImage(SetaPDF_Core_Image $image)
    {
        $this->_image = $image;
        $this->_xObject = null;
        $this->updateCacheCounter();
    }

    /**
     * Get the image.
     * 
     * @return SetaPDF_Core_Image
     */
    public function getImage()
    {
        return $this->_image;
    }
    
    /**
     * Get the XObject from the image object.
     * 
     * @param SetaPDF_Core_Document $document The document instance
     * @return SetaPDF_Core_XObject_Image
     */
    public function getXObject(SetaPDF_Core_Document $document)
    {
        if (null === $this->_xObject) {
            $this->_xObject = $this->_image->toXObject($document);
        }
        
        return $this->_xObject;
    }
    
    /**
     * Set the individual width of the image stamp.
     * 
     * @param float|integer $width The width
     */
    public function setWidth($width)
    {
        $this->_width = $width;    
        $this->updateCacheCounter();
    }

    /**
     * Get the width of the image stamp.
     * 
     * If no individual width is given the width will be received from the image object by
     * forwarding an individual height (if available) to keep the aspect ratio. 
     * 
     * @return float|integer
     */
    public function getWidth()
    {
        return $this->_width !== null
            ? $this->_width
            : $this->_image->getWidth($this->_height);
    }

    /**
     * Set the individual height if the image stamp.
     * 
     * @param float|integer $height The height
     */
    public function setHeight($height)
    {
        $this->_height = $height;
        $this->updateCacheCounter();
    }

    /**
     * Get the height of the image stamp.
     * 
     * If no individual height is given the height will be received from the image object by
     * forwarding an individual width (if available) to keep the aspect ratio. 
     * 
     * @return float|integer
     */
    public function getHeight()
    {
        return $this->_height !== null
            ? $this->_height
            : $this->_image->getHeight($this->_width);  
    }

    /**
     * Set the dimensions of this stamp.
     * 
     * @param float|integer $width The width
     * @param float|integer $height The height
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
        $names[SetaPDF_Core_Resource::TYPE_X_OBJECT][] = $page->getCanvas()->addResource($this->getXObject($document));
    
        return $names;
    }
    
    /**
     * Writes the image draw operators of this stamp onto the canvas.
     *  
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Core_Document_Page $page
     * @param array $stampData
     * @return boolean
     */
    protected function _stamp(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page, array $stampData)
    {
        $x = $this->getOriginX($page, $stampData['position']);
        $y = $this->getOriginY($page, $stampData['position']);
        
        $canvas = $page->getCanvas();
        $this->getXObject($document)->draw($canvas, $x, $y, $this->getWidth(), $this->getHeight());
        
        return true;
    }
}