<?php
/**
 * This file is part of the SetaPDF-Stamper Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: Pdf.php 1706 2022-03-28 10:40:28Z jan.slabon $
 */

/**
 * The PDF stamp class
 * 
 * To initiate an instance it is needed to pass a {@link SetaPDF_Core_Document document} instance to
 * the constructor. The second parameter could be used to set the page number of the page which will be
 * used as the stamp appearance.
 * 
 * <code>
 * $document = SetaPDF_Core_Document::loadByFilename('path/to/document.pdf');
 * $stamp = new SetaPDF_Stamper_Stamp_Pdf($document, 2); // use page 2
 * </code>
 * 
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Stamper_Stamp_Pdf extends SetaPDF_Stamper_Stamp
{
    /**
     * The document instance
     * 
     * @var SetaPDF_Core_Document
     */
    protected $_document;
    
    /**
     * The page number
     * 
     * @var integer
     */
    protected $_pageNumber;

    /**
     * The boundary box to use
     *
     * @var string
     */
    protected $_boundaryBox = SetaPDF_Core_PageBoundaries::CROP_BOX;

    /**
     * The XObject of the page
     * 
     * @var SetaPDF_Core_XObject_Form
     */
    protected $_xObject;
    
    /**
     * The individual width of the page
     *
     * @var float|integer
     */
    protected $_width = null;
    
    /**
     * The individual height of the page
     *
     * @var float|integer
     */
    protected $_height = null;
    
    /**
     * The constructor.
     * 
     * @param SetaPDF_Core_Document|string $filenameOrDocument Filename of the pdf or a document instance of the pdf
     * @param integer $pageNumber The page number for the page which should be used for the stamp appearance
     * @param string $boundaryBox Use the boundary constants {@link SetaPDF_Core_PageBoundaries::XXX_BOX}
     */
    public function __construct($filenameOrDocument, $pageNumber = 1, $boundaryBox = SetaPDF_Core_PageBoundaries::CROP_BOX)
    {
        if ($filenameOrDocument instanceof SetaPDF_Core_Document) {
            $this->_document = $filenameOrDocument;
        } else {
            $this->_document = SetaPDF_Core_Document::loadByFilename($filenameOrDocument);
        }

        $this->setPageNumber($pageNumber);
        $this->setBoundaryBox($boundaryBox);
    }

    /**
     * Get the document instance.
     *
     * @return SetaPDF_Core_Document
     */
    public function getDocument()
    {
        return $this->_document;
    }
    
    /**
     * Set the page number for the page which should be used for the stamp appearance.
     * 
     * @param integer $pageNumber The page number
     */
    public function setPageNumber($pageNumber)
    {
        if ($pageNumber != $this->_pageNumber && $this->_xObject !== null) {
            $this->_xObject = null;
        }
        
        $this->_pageNumber = $pageNumber;
    }

    /**
     * Get the page number of the page that should be used for the stamp appearance.
     * 
     * @return number
     */
    public function getPageNumber()
    {
        return $this->_pageNumber;
    }
    
    /**
     * Get the page object.
     * 
     * @return SetaPDF_Core_Document_Page
     */
    protected function _getPage()
    {
        return $this->_document->getCatalog()->getPages()->getPage($this->_pageNumber);
    }

    /**
     * Set the boundary box of the imported page.
     *
     * @param string $boundaryBox Use the boundary constants {@link SetaPDF_Core_PageBoundaries::XXX_BOX}
     */
    public function setBoundaryBox($boundaryBox)
    {
        $this->_boundaryBox = $boundaryBox;
    }

    /**
     * Get the current boundary box name.
     *
     * @return string
     */
    public function getBoundaryBox()
    {
        return $this->_boundaryBox;
    }

    /**
     * Get the form XObject from the page object.
     *
     * @param SetaPDF_Core_Document $document The document instance
     * @return SetaPDF_Core_XObject_Form
     */
    public function getXObject(SetaPDF_Core_Document $document)
    {
        if (null === $this->_xObject) {
            $this->_xObject = $this->_getPage()->toXObject($document, $this->getBoundaryBox());
        }
    
        return $this->_xObject;
    }
    
    /**
     * Set the individual width of the pdf page stamp.
     *
     * @param float|integer $width The new width
     */
    public function setWidth($width)
    {
        $this->_width = $width;
        $this->updateCacheCounter();
    }
    
    /**
     * Get the width of the pdf page stamp.
     *
     * If no individual width is given the width will be received from the page object.
     * Also a defined individual height (if available) will be used to keep the aspect ratio.
     *
     * @return float|integer
     */
    public function getWidth()
    {
        if ($this->_width !== null) {
            return $this->_width;
        }
        
        $height = $this->_height;
        
        $width = $this->_getPage()->getWidth($this->getBoundaryBox());
        if (null === $height) {
            return $width;
        }
        
        return $height * $width / $this->_getPage()->getHeight($this->getBoundaryBox());
    }
    
    /**
     * Set the individual height if the pdf page stamp.
     *
     * @param float|integer $height The new height
     */
    public function setHeight($height)
    {
        $this->_height = $height;
        $this->updateCacheCounter();
    }
    
    /**
     * Get the height of the pdf page stamp.
     *
     * If no individual height is given the height will be received from the page object.
     * Also a defined individual width (if available) will be used to keep the aspect ratio.
     *
     * @return float|integer
     */
    public function getHeight()
    {
        if ($this->_height !== null) {
            return $this->_height;
        }
        
        $width = $this->_width;
        
        $height = $this->_getPage()->getHeight($this->getBoundaryBox());
        if (null === $width) {
            return $height;
        }
        
        return $width * $height / $this->_getPage()->getWidth($this->getBoundaryBox());
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
        $names[SetaPDF_Core_Resource::TYPE_X_OBJECT][] = $page->getCanvas()->addResource($this->getXObject($document));
    
        return $names;
    }
    
    /**
     * Writes the xobject draw operators of this stamp onto the canvas.
     * 
     * A page is internally converted into a xobject.
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
        
        $xObject = $this->getXObject($document);
        
        $opacity = $this->getOpacity();
        if (abs($opacity - 1.0) > SetaPDF_Core::FLOAT_COMPARISON_PRECISION) {
            $xObject->setGroup(new SetaPDF_Core_TransparencyGroup());
        }

        $canvas = $page->getCanvas();
        $xObject->draw($canvas, $x, $y, $this->getWidth(), $this->getHeight());

        return true;
    }
}