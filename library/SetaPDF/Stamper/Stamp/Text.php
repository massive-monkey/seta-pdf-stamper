<?php
/**
 * This file is part of the SetaPDF-Stamper Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: Text.php 1706 2022-03-28 10:40:28Z jan.slabon $
 */

/**
 * The text stamp class
 * 
 * With the text stamp class you can add dynamic text to an existing
 * PDF document. 
 * The text can be of multiple lines and will break automatically if a
 * {@link SetaPDF_Stamper_Stamp_Text::setWidth() width} is defined.
 * 
 * Internally the class uses a {@link SetaPDF_Core_Text_Block} object.
 * 
 * The text stamp class has to be initiated with a font instance.
 * The SetaPDF core system actually offers all standard PDF fonts and a parser 
 * for TrueType font-files:
 * 
 * <code>
 * // create a standard font
 * $font = SetaPDF_Core_Font_Standard_Helvetica::create($document);
 * $stamp = new SetaPDF_Stamper_Stamp_Text($font, 16);
 * 
 * // or create a subset font based on a TrueType font file
 * $font = new SetaPDF_Core_Font_TrueType_Subset($document, 'path/to/font/file.ttf');
 * $stamp = new SetaPDF_Stamper_Stamp_Text($font, 16);
 * </code>
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Stamper_Stamp_Text extends SetaPDF_Stamper_Stamp
{
    /**
     * A text block instance
     * 
     * @var SetaPDF_Core_Text_Block
     */
    protected $_textBlock;
    
    /**
     * The constructor.
     * 
     * @param SetaPDF_Core_Font_FontInterface $font The font type of the text
     * @param integer|float $fontSize The font size of the text
     */
    public function __construct(SetaPDF_Core_Font_FontInterface $font, $fontSize = 12)
    {
        $this->_textBlock = new SetaPDF_Core_Text_Block($font, $fontSize);
        $this->_textBlock->setDataCacheClearCallback([$this, 'updateCacheCounter']);
    }
    
    /**
     * Releases memory / cycled references.
     */
    public function cleanUp()
    {
        $this->_textBlock->cleanUp();
        $this->_textBlock = null;
    }

    /**
     * Get the text block of this stamp.
     * 
     * @return SetaPDF_Core_Text_Block
     */
    public function getTextBlock()
    {
        return $this->_textBlock;    
    }
    
    /**
     * Set the text.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setText()} of the text block instance.
     *
     * @param string $text The new text
     * @param string $encoding The encoding of the text
     */
    public function setText($text, $encoding = 'UTF-8')
    {
        $this->_textBlock->setText($text, $encoding);
    }
    
    /**
     * Get the text.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getText()} of the text block instance.
     *
     * @param string $encoding The encoding of the text
     * @return string
     */
    public function getText($encoding = 'UTF-8')
    {
        return $this->_textBlock->getText($encoding);        
    }
    
    /**
     * Set the font object and size.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::setFont()} of the text block instance.
     *
     * @param SetaPDF_Core_Font_FontInterface $font The new font type
     * @param null|number $fontSize The new font size
     */
    public function setFont(SetaPDF_Core_Font_FontInterface $font, $fontSize = null)
    {
       $this->_textBlock->setFont($font, $fontSize);  
    }
    
    /**
     * Get the current font object.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::getFont()} of the text block instance.
     *
     * @return SetaPDF_Core_Font_FontInterface
     */
    public function getFont()
    {
        return $this->_textBlock->getFont();       
    }
    
    /**
     * Set the font size.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::setFontSize()} of the text block instance.
     *
     * @param number $fontSize The new font size
     */
    public function setFontSize($fontSize)
    {
        $this->_textBlock->setFontSize($fontSize);
    }
    
    /**
     * Get the font size.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::getFontSize()} of the text block instance.
     *
     * @return number
     */
    public function getFontSize()
    {
        return $this->_textBlock->getFontSize();
    }
    
    /**
     * Set the line height / leading.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setLineHeight()} of the text block instance.
     *
     * @param float|integer|null $lineHeight The new line height
     */
    public function setLineHeight($lineHeight)
    {
        $this->_textBlock->setLineHeight($lineHeight);
    }
    
    /**
     * Get the line height / leading.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getLineHeight()} of the text block instance.
     *
     * @return number
     */
    public function getLineHeight()
    {
        return $this->_textBlock->getLineHeight();
    }
    
    /**
     * Set the text color.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setTextColor()} of the text block instance.
     *
     * @see SetaPDF_Core_DataStructure_Color::createByComponents()
     * @param SetaPDF_Core_DataStructure_Color|int|float|string|array|SetaPDF_Core_Type_Array|null $color The new text color
     */
    public function setTextColor($color)
    {
        $this->_textBlock->setTextColor($color);
    }
    
    /**
     * Get the text color object.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getTextColor()} of the text block instance.
     *
     * @return SetaPDF_Core_DataStructure_Color
     */
    public function getTextColor()
    {
        return $this->_textBlock->getTextColor();
    }
    
    /**
     * Set the texts outline color.
     * 
     * Only used with a specific text rendering mode.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::setOutlineColor()} of the text block instance.
     *
     * @see SetaPDF_Stamper_Stamp_Text::setRenderingMode()
     * @see SetaPDF_Core_DataStructure_Color::createByComponents()
     * @param SetaPDF_Core_DataStructure_Color|int|float|string|array|SetaPDF_Core_Type_Array|null $color The new outline color
     */
    public function setOutlineColor($color)
    {
        $this->_textBlock->setOutlineColor($color);
    }
    
    /**
     * Get the texts outline color object.
     *
     * If no outline color is defined the a greyscale black color will be returned.
     * The outline color is only used at specific rendering modes.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::getOutlineColor()} of the text block instance.
     *
     * @see SetaPDF_Stamper_Stamp_Text::setRenderingMode()
     * @return SetaPDF_Core_DataStructure_Color
     */
    public function getOutlineColor()
    {
        return $this->_textBlock->getOutlineColor();
    }
    
    /**
     * Set the outline width.
     * 
     * The outline width is only used at specific rendering modes.
     *  
     * Proxy method to {@link SetaPDF_Core_Text_Block::setOutlineWidth()} of the text block instance.
     *
     * @param float $outlineWidth The new outline width.
     */
    public function setOutlineWidth($outlineWidth)
    {
        $this->_textBlock->setOutlineWidth($outlineWidth);
    }
    
    /**
     * Get the outline width.
     *
     * The outline width is only used at specific rendering modes.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::getOutlineWidth()} of the text block instance.
     *
     * @return float
     */
    public function getOutlineWidth()
    {
        return $this->_textBlock->getOutlineWidth();
    }

    /**
     * Set whether to draw an underline or not.
     *
     * Proxy method to {@Link SetaPDF_Core_Text_Block::setUnderline()} of the text block instance.
     *
     * @param boolean $underline
     */
    public function setUnderline($underline)
    {
        $this->_textBlock->setUnderline($underline);
    }

    /**
     * Gets whether to draw an underline or not.
     *
     * Proxy method to {@Link SetaPDF_Core_Text_Block::getUnderline()} of the text block instance.
     *
     * @return bool
     */
    public function getUnderline()
    {
        return $this->_textBlock->getUnderline();
    }

    /**
     * Set the underline color.
     *
     * Proxy method to {@Link SetaPDF_Core_Text_Block::setUnderlineColor()} of the text block instance.
     *
     * @see SetaPDF_Core_DataStructure_Color::createByComponents()
     * @param SetaPDF_Core_DataStructure_Color|int|float|string|array|SetaPDF_Core_Type_Array|null $color The new underline color
     */
    public function setUnderlineColor($color)
    {
        $this->_textBlock->setUnderlineColor($color);
    }

    /**
     * Get the underline color object.
     *
     * Proxy method to {@Link SetaPDF_Core_Text_Block::getUnderlineColor()} of the text block instance.
     *
     * @return SetaPDF_Core_DataStructure_Color
     */
    public function getUnderlineColor()
    {
        return $this->_textBlock->getUnderlineColor();
    }
    
    /**
     * Set the background color.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::setBackgroundColor()} of the text block instance.
     *
     * @see SetaPDF_Core_DataStructure_Color::createByComponents()
     * @param SetaPDF_Core_DataStructure_Color|int|float|string|array|SetaPDF_Core_Type_Array|null $color The new background color
     */
    public function setBackgroundColor($color)
    {
        $this->_textBlock->setBackgroundColor($color);
    }
    
    /**
     * Get the background color object.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getBackgroundColor()} of the text block instance.
     *
     * @return null|SetaPDF_Core_DataStructure_Color
     */
    public function getBackgroundColor()
    {
        return $this->_textBlock->getBackgroundColor();
    }
    
    /**
     * Set the border color.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::setBorderColor()} of the text block instance.
     *
     * @see SetaPDF_Core_DataStructure_Color::createByComponents()
     * @param SetaPDF_Core_DataStructure_Color|int|float|string|array|SetaPDF_Core_Type_Array|null $color The new border color
     */
    public function setBorderColor($color)
    {
        $this->_textBlock->setBorderColor($color);
    }
    
    /**
     * Get the border color object.
     * 
     * If no border color is defined the a greyscale black color will be returned.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::getBorderColor()} of the text block instance.
     *
     * @return null|SetaPDF_Core_DataStructure_Color
     */
    public function getBorderColor()
    {
        return $this->_textBlock->getBorderColor();
    }
    
    /**
     * Set the border width.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setBorderWidth()} of the text block instance.
     *
     * @param float|integer $borderWidth The new border width
     */
    public function setBorderWidth($borderWidth)
    {
        $this->_textBlock->setBorderWidth($borderWidth);
    }
    
    /**
     * Get the border width.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getBorderWidth()} of the text block instance.
     *
     * @return number
     */
    public function getBorderWidth()
    {
        return $this->_textBlock->getBorderWidth();
    }
    
    /**
     * Set the text alignment.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setAlign()} of the text block instance.
     *
     * @param string $align The new align
     */
    public function setAlign($align)
    {
        $this->_textBlock->setAlign($align);
    }
    
    /**
     * Get the text alignment.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getAlign()} of the text block instance.
     *
     * @return string
     */
    public function getAlign()
    {
        return $this->_textBlock->getAlign();
    }
    
    /**
     * Set the width of the stamp.
     * 
     * Padding is NOT included in the $width parameter.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setWidth()} of the text block instance.
     *
     * @param float|integer $width The new width
     */
    public function setWidth($width)
    {
        $this->_textBlock->setWidth($width);
    }

    /**
     * Set the rendering mode.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::setRenderingMode()} of the text block instance.
     *
     * @see SetaPDF_Core_Canvas_Text::setRenderingMode()
     * @param integer $renderingMode The new rendering mode
     */
    public function setRenderingMode($renderingMode = 0)
    {
        $this->_textBlock->setRenderingMode($renderingMode);
    }
    
    /**
     * Get the defined rendering mode.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getRenderingMode()} of the text block instance.
     *
     * @return number
     */
    public function getRenderingMode()
    {
        return $this->_textBlock->getRenderingMode();
    }
    
    /**
     * Get the width of the stamp object.
     * 
     * This method returns the complete width of the stamp object.
     * 
     * The value set in {@link setWidth()} may be differ to the one returned by this method because of padding values.
     *  
     * Proxy method to {@link SetaPDF_Core_Text_Block::getWidth()} of the text block instance.
     *
     * @see SetaPDF_Stamper_Stamp::getWidth()
     * @return number
     */
    public function getWidth()
    {
        return $this->_textBlock->getWidth();
    }
    
    /**
     * Set the padding.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setPadding()} of the text block instance.
     *
     * @param number $padding The new padding
     */
    public function setPadding($padding)
    {
        $this->_textBlock->setPadding($padding);
    }
    
    /**
     * Set the top padding.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setPaddingTop()} of the text block instance.
     *
     * @param number $paddingTop The new padding top
     */
    public function setPaddingTop($paddingTop)
    {
        $this->_textBlock->setPaddingTop($paddingTop);
    }
    
    /**
     * Get the top padding.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getPaddingTop()} of the text block instance.
     *
     * @return number
     */
    public function getPaddingTop()
    {
        return $this->_textBlock->getPaddingTop();
    }
    
    /**
     * Set the right padding.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setPaddingRight()} instance.
     *
     * @param number $paddingRight The new padding right
     */
    public function setPaddingRight($paddingRight)
    {
        $this->_textBlock->setPaddingRight($paddingRight);
    }
    
    /**
     * Get the right padding.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getPaddingRight()} of the text block instance.
     *
     * @return number
     */
    public function getPaddingRight()
    {
        return $this->_textBlock->getPaddingRight();
    }
    
    /**
     * Set the bottom padding.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setPaddingBottom()} of the text block instance.
     *
     * @param number $paddingBottom The new padding bottom
     */
    public function setPaddingBottom($paddingBottom)
    {
        $this->_textBlock->setPaddingBottom($paddingBottom);
    }
    
    /**
     * Get the bottom padding.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getPaddingBottom()} of the text block instance.
     *
     * @return number
     */
    public function getPaddingBottom()
    {
        return $this->_textBlock->getPaddingBottom();
    }
    
    /**
     * Set the left padding.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::setPaddingLeft()} of the text block instance.
     *
     * @param number $paddingLeft The new padding left
     */
    public function setPaddingLeft($paddingLeft)
    {
        $this->_textBlock->setPaddingLeft($paddingLeft);
    }
    
    /**
     * Get the left padding.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getPaddingLeft()} of the text block instance.
     *
     * @return number
     */
    public function getPaddingLeft()
    {
        return $this->_textBlock->getPaddingLeft();
    }

    /**
     * Set the character spacing value.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::setCharSpacing()} of the text block instance.
     *
     * @param number $charSpacing The new char spacing
     */
    public function setCharSpacing($charSpacing)
    {
        $this->_textBlock->setCharSpacing($charSpacing);
    }
    
    /**
     * Get the character spacing value.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::getCharSpacing()} of the text block instance.
     *
     * @return number
     */
    public function getCharSpacing()
    {
        return $this->_textBlock->getCharSpacing();
    }
    
    /**
     * Set the word spacing value.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::setWordSpacing()} of the text block instance.
     *
     * @param number $wordSpacing The new word spacing
     */
    public function setWordSpacing($wordSpacing)
    {
        $this->_textBlock->setWordSpacing($wordSpacing);
    }
    
    /**
     * Get the word spacing value.
     *
     * Proxy method to {@link SetaPDF_Core_Text_Block::getWordSpacing()} of the text block instance.
     *
     * @return number
     */
    public function getWordSpacing()
    {
        return $this->_textBlock->getWordSpacing();
    }
    
    /**
     * Get the height of this stamp.
     * 
     * Calculation is done by number of lines, line-height and top and bottom padding values.
     * 
     * Proxy method to {@link SetaPDF_Core_Text_Block::getHeight()} of the text block instance.
     *
     * @see SetaPDF_Stamper_Stamp::getHeight()
     * @return number
     */
    public function getHeight()
    {
        return $this->_textBlock->getHeight();
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
        $names[SetaPDF_Core_Resource::TYPE_FONT][] = $page->getCanvas()->addResource($this->_textBlock->getFont());
    
        return $names;
    }
    
    /**
     * Writes the text content of this stamp onto the canvas.
     *
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Core_Document_Page $page
     * @param array $stampData
     * @return bool
     */
    protected function _stamp(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page, array $stampData)
    {
        $canvas = $page->getCanvas();
        $x = $this->getOriginX($page, $stampData['position']);
        $y = $this->getOriginY($page, $stampData['position']);
        
        $this->_textBlock->draw($canvas, $x, $y);
        
        return true;
    }
}