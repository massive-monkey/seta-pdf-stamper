<?php
/**
 * This file is part of the SetaPDF-Stamper Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: Stamp.php 1706 2022-03-28 10:40:28Z jan.slabon $
 */

/**
 * The abstract base stamp class
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 */
abstract class SetaPDF_Stamper_Stamp
{
    /**
     * Visibility constant
     * 
     * @var string
     */
    const VISIBILITY_ALL   = 'all';

    /**
     * Visibility constant
     *
     * @var string
     */
    const VISIBILITY_PRINT = 'print';

    /**
     * Visibility constant
     *
     * @var string
     */
    const VISIBILITY_VIEW  = 'view';

    /**
     * The opacity
     * 
     * @var float
     */
    protected $_opacity = 1.00;
    
    /**
     * The blend mode
     * 
     * @var string
     */
    protected $_blendMode = 'Normal';
    
    /**
     * Graphic state objects for handling transparency
     * 
     * @var array Array of {@link SetaPDF_Core_Resource_ExtGState} objects
     */
    protected $_opacityGs = [];
    
    /**
     * The visibility property
     * 
     * @var string
     */
    protected $_visibility = 'all';

    /**
     * An optional content groups that should be used for the stamp
     *
     * @var SetaPDF_Core_Document_OptionalContent_Group
     */
    protected $_optionalContentGroup = null;
    
    /**
     * The currently attached action object
     * 
     * @var SetaPDF_Core_Document_Action
     */
    protected $_action = null;

    /**
     * An internal used id for forcing a recreation if a property was changed
     * 
     * @var integer
     */
    protected $_cacheCounter = 0;

    /**
     * The cache data for the stamp
     *
     * @see SetaPDF_Stamper_Stamp::_cacheStampData()
     * @var array
     */
    protected $_dataCache;

    /**
     * Stamp this stamp object onto a page.
     * 
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Core_Document_Page $page
     * @param array $stampData
     * @return bool
     */
    abstract protected function _stamp(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page, array $stampData);
    
    /**
     * Get the height of the stamp object.
     * 
     * @return float|integer
     */
    abstract public function getHeight();
    
    /**
     * Get the width of the stamp object.
     * 
     * @return float|integer
     */
    abstract public function getWidth();

    /**
     * Get the stamp dimension.
     * 
     * @return array
     */
    public function getDimensions()
    {
        return [
            'width'  => $this->getWidth(),
            'height' => $this->getHeight()
        ];
    }

    /**
     * Set the opacity and blend mode of the stamp object.
     *  
     * @param float $alpha A value between 0 and 1, whereas 1 is defined as 100% opacity
     * @param string $blendMode A blend mode defined in {@link http://wwwimages.adobe.com/www.adobe.com/content/dam/Adobe/en/devnet/pdf/pdfs/PDF32000_2008.pdf#M10.9.32404.2Heading.32.Blend.Mode PDF 32000-1:2008 - 11.3.5, "Blend Mode"}
     * @throws InvalidArgumentException
     */
    public function setOpacity($alpha, $blendMode = 'Normal')
    {
        if (!in_array($blendMode, [
            'Normal', 'Multiply', 'Screen', 'Overlay', 'Darken', 'Lighten',
            'ColorDodge', 'ColorBurn', 'HardLight', 'SoftLight', 'Difference',
            'Exclusion', 'Hue', 'Saturation', 'Color', 'Luminosity'
        ])) {
            throw new InvalidArgumentException(sprintf('Invalid $blendMode parameter: %s', $blendMode));
        }
        
        $this->_opacity = (float)$alpha;
        $this->_blendMode = $blendMode;
        
        $this->updateCacheCounter();
    }

    /**
     * Get the opacity.
     *
     * @return number
     */
    public function getOpacity()
    {
        return $this->_opacity;
    }

    /**
     * Get the blend mode.
     *
     * @see PDF 32000-1:2008 - 11.3.5, "Blend Mode"
     * @return string
     */
    public function getOpacityBlendMode()
    {
        return $this->_blendMode;
    }

    /**
     * Set the visibility of the stamp object.
     * 
     * This method controls the visibility of the stamp object on screen view and/or printer output.
     * 
     * @param null|string $visibility Use the constants VISIBILITY_XXX or null(equal to VISIBILITY_ALL)
     * @throws InvalidArgumentException
     */
    public function setVisibility($visibility)
    {
        if (!in_array($visibility, [self::VISIBILITY_ALL, self::VISIBILITY_PRINT, self::VISIBILITY_VIEW, null])) {
            throw new InvalidArgumentException(sprintf('Invalid $visibility parameter: %s', $visibility));
        }
        
        $this->_visibility = $visibility === null ? self::VISIBILITY_ALL : $visibility;
        
        $this->updateCacheCounter();
    }
    
    /**
     * Get the visibility of the stamp object.
     * 
     * @return null|string
     */
    public function getVisibility()
    {
        return $this->_visibility;
    }

    /**
     * Add an action object to the stamp object.
     * 
     * @param SetaPDF_Core_Document_Action $action The action object
     */
    public function setAction(SetaPDF_Core_Document_Action $action)
    {
        $this->_action = $action;
    }

    /**
     * Get the current attached action.
     * 
     * @return null|SetaPDF_Core_Document_Action
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * Set a link onto the stamp.
     *  
     * @param string $uri The link
     * @see setAction()
     */
    public function setLink($uri)
    {
        $this->setAction(new SetaPDF_Core_Document_Action_Uri($uri));
    }

    /**
     * Get the x-origin in view to the position of the stamp object.
     *
     * @param SetaPDF_Core_Document_Page $page The page instance
     * @param string $position Position constant {@link SetaPDF_Stamper::POSITION_XXX}
     * @return float|int
     */
    public function getOriginX(SetaPDF_Core_Document_Page $page, $position)
    {
        $box = $page->getCropBox();
        $x = $box->getLlx();
        
        switch ($position) {
            case SetaPDF_Stamper::POSITION_CENTER_TOP:
            case SetaPDF_Stamper::POSITION_CENTER_MIDDLE:
            case SetaPDF_Stamper::POSITION_CENTER_BOTTOM:
                $x += $page->getWidth() / 2;
                $x -= $this->getWidth() / 2;
                break;
        
            case SetaPDF_Stamper::POSITION_RIGHT_TOP:
            case SetaPDF_Stamper::POSITION_RIGHT_MIDDLE:
            case SetaPDF_Stamper::POSITION_RIGHT_BOTTOM:
                $x += $page->getWidth();
                $x -= $this->getWidth();
                break;
        }
        
        return $x;
    }

    /**
     * Get the y-origin in view to the position of the stamp object.
     * 
     * @param SetaPDF_Core_Document_Page $page The page instance
     * @param string $position Position constant {@link SetaPDF_Stamper::POSITION_XXX}
     * @return float|int
     */
    public function getOriginY(SetaPDF_Core_Document_Page $page, $position)
    {
        $box = $page->getCropBox();
        $y = $box->getLly();
        
        switch ($position) {
            case SetaPDF_Stamper::POSITION_LEFT_TOP:
            case SetaPDF_Stamper::POSITION_CENTER_TOP:
            case SetaPDF_Stamper::POSITION_RIGHT_TOP:
                $y += $page->getHeight();
                $y -= $this->getHeight();
                break;
        
            case SetaPDF_Stamper::POSITION_LEFT_MIDDLE:
            case SetaPDF_Stamper::POSITION_CENTER_MIDDLE:
            case SetaPDF_Stamper::POSITION_RIGHT_MIDDLE:
                $y += $page->getHeight() / 2;
                $y -= $this->getHeight() / 2;
                break;
        }
        
        return $y;
    }

    /**
     * Set the optional content group for this stamp.
     *
     * @param SetaPDF_Core_Document_OptionalContent_Group|null $optionalContentGroup
     */
    public function setOptionalContentGroup(SetaPDF_Core_Document_OptionalContent_Group $optionalContentGroup = null)
    {
        $this->_optionalContentGroup = $optionalContentGroup;
    }

    /**
     * Get the optional content group for this stamp.
     *
     * @return SetaPDF_Core_Document_OptionalContent_Group
     */
    public function getOptionalContentGroup()
    {
        return $this->_optionalContentGroup;
    }

    /**
     * Get and caches opacity graphic states.
     * 
     * @param SetaPDF_Core_Document $document
     * @param float $opacity
     * @return SetaPDF_Core_Resource_ExtGState
     */
    protected function _getOpacityGraphicState(SetaPDF_Core_Document $document, $opacity)
    {
        $key = $opacity . '|' . $this->_blendMode;
        if (!isset($this->_opacityGs[$key])) {
            $gs = new SetaPDF_Core_Resource_ExtGState();
            $gs->setConstantOpacity($opacity);
            $gs->setConstantOpacityNonStroking($opacity);
            $gs->setBlendMode($this->_blendMode);
            $gs->getIndirectObject($document);
            
            $this->_opacityGs[$key] = $gs;
        }
        
        return $this->_opacityGs[$key];
    }
    
    /**
     * Get and adds the visibility group of this stamp to a document.
     * 
     * @param SetaPDF_Core_Document $document
     * @return false|SetaPDF_Core_Document_OptionalContent_Group
     */
    protected function _getVisibilityGroup(SetaPDF_Core_Document $document)
    {
        $visibility = $this->getVisibility();
    
        if ($visibility !== self::VISIBILITY_ALL) {
            $oc = $document->getCatalog()->getOptionalContent();
            $groupName = 'SetaPDF_' . ucfirst($visibility);
            $group = $oc->getGroup($groupName);
            if (false === $group) {
                $group = $oc->addGroup($groupName);
                switch ($visibility) {
                    case self::VISIBILITY_PRINT:
                        $group->usage()->setPrintState('ON');
                        $group->usage()->setViewState('OFF');
                        break;
                    case self::VISIBILITY_VIEW:
                        $group->usage()->setPrintState('OFF');
                        $group->usage()->setViewState('ON');
                        break;
                }
                $oc->addUsageApplication($group);
            }
            
            return $group;
        }
        
        return false;
    }
    
    /**
     * Ensures that all stamp resources are added to the page.
     *
     * This is needed to reuse a cached stamp stream.
     *
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Core_Document_Page $page
     * @return array An array of resource names
     */
    protected function _ensureResources(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page)
    {
        $names = [];

        $opacity = $this->getOpacity();
        if (abs($opacity - 1.0) > SetaPDF_Core::FLOAT_COMPARISON_PRECISION) {
            $gs = $this->_getOpacityGraphicState($document, $opacity);
            $names[SetaPDF_Core_Resource::TYPE_EXT_G_STATE][] = $page->getCanvas()->addResource($gs);
        }
        
        $group = $this->_getVisibilityGroup($document);
        if ($group !== false) {
            $names[SetaPDF_Core_Resource::TYPE_PROPERTIES][] = $page->getCanvas()->addResource($group);
        }

        $optionalContentGroup = $this->getOptionalContentGroup();
        if ($optionalContentGroup !== null) {
            $names[SetaPDF_Core_Resource::TYPE_PROPERTIES][] = $page->getCanvas()->addResource($optionalContentGroup);
        }
        
        return $names;
    }
    
    /**
     * Updates the cache counter.
     */
    public function updateCacheCounter()
    {
        $this->_cacheCounter++;
    }
    
    /**
     * Try to stamp with the page with a cached content stream part.
     * 
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Core_Document_Page $page
     * @param array $stampData
     * @return true|string True if the stamp was written by a cache object, a cache key if it was not found
     * @throws SetaPDF_Stamper_Exception
     */
    protected function _stampByCache(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page, array $stampData)
    {
        $resourceNames = $this->_ensureResources($document, $page);
        $cropBox = $page->getCropBox(true, false);
        if (false === $cropBox) {
            throw new SetaPDF_Stamper_Exception('A page that should be stamped has no CropBox (no size).');
        }

        $cacheKey = md5(
            print_r($resourceNames, true) .
            print_r($cropBox->toPhp(), true) .
            $page->getRotation() . '|' .
            $stampData['position'] . '|' .
            $stampData['translateX'] . '|' .
            $stampData['translateY'] . '|' .
            $stampData['rotation'] . '|' .
            $stampData['underlay'] . '|' .
            $this->_cacheCounter
        );
        
        if (isset($this->_dataCache['stream-' . $cacheKey])) {
            $canvas = $page->getCanvas();
            $canvas->write($this->_dataCache['stream-' . $cacheKey]);
            
            if ($this->_dataCache['quadPoints-' . $cacheKey] !== null) {
                $quadPoints = $this->_dataCache['quadPoints-' . $cacheKey];
                $this->_putAction($document, $page, $stampData, $quadPoints[0], $quadPoints[1], $quadPoints[2], $quadPoints[3]);
            }
            return true;
        }
        
        return $cacheKey;
    }
    
    /**
     * Caches a content stream part.
     * 
     * @param string $cacheKey
     * @param string $stream
     * @param array $quadPoints
     */
    protected function _cacheStampData($cacheKey, $stream, $quadPoints)
    {
        $this->_dataCache['stream-' . $cacheKey] = $stream;
        $this->_dataCache['quadPoints-' . $cacheKey] = $quadPoints;
    }
    
    /**
     * Stamp this stamp object onto a page.
     *
     * @param SetaPDF_Core_Document $document The document object
     * @param SetaPDF_Core_Document_Page $page The page object
     * @param array $stampData The stampData array
     * @return bool
     */
    public function stamp(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page, array $stampData)
    {
        $cacheKey = $this->_stampByCache($document, $page, $stampData);
        if (true === $cacheKey) {
            return true;
        }
    
        $canvas = $page->getCanvas();
        $canvas->startCache();
    
        $this->_preStamp($document, $page, $stampData);
        $this->_stamp($document, $page, $stampData);
        $quadPoints = $this->_postStamp($document, $page, $stampData);
        
        if ($quadPoints !== null) {
            $this->_putAction($document, $page, $stampData, $quadPoints[0], $quadPoints[1], $quadPoints[2], $quadPoints[3]);
        }
        
        $this->_cacheStampData($cacheKey, $canvas->getCache(), $quadPoints);
        $canvas->stopCache();
    
        return true;
    }
    
    /**
     * Put the action via an link annotation above the stamp object.
     * 
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Core_Document_Page $page
     * @param array $stampData
     * @param number $xy1
     * @param number $xy2
     * @param number $xy3
     * @param number $xy4
     */
    protected function _putAction(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page, array $stampData, $xy1, $xy2, $xy3, $xy4)
    {
        if ($this->_action !== null) {
            $correction = $stampData['rotation'] !== .0 ? .00002 : 0;
            
            $llx = min($xy1['x'], $xy2['x'], $xy3['x'], $xy4['x']) - $correction;
            $lly = min($xy1['y'], $xy2['y'], $xy3['y'], $xy4['y']) - $correction;
            $urx = max($xy1['x'], $xy2['x'], $xy3['x'], $xy4['x']) + $correction;
            $ury = max($xy1['y'], $xy2['y'], $xy3['y'], $xy4['y']) + $correction;
            
            $annotation = new SetaPDF_Core_Document_Page_Annotation_Link([$llx, $lly, $urx, $ury], $this->_action);
        
            if ($stampData['rotation'] != .0) {
                $annotation->setQuadPoints($xy1['x'], $xy1['y'], $xy2['x'], $xy2['y'], $xy3['x'], $xy3['y'], $xy4['x'], $xy4['y']);
            }
        
            $page->getAnnotations()->add($annotation);
        }
    }
    
    /**
     * Method which is called before the main stamp method is executed.
     * 
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Core_Document_Page $page
     * @param array $stampData
     */
    protected function _preStamp(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page, array $stampData)
    {
        $canvas = $page->getCanvas();
        $visibility = $this->getVisibility();
        $optionalContentGroup = $this->getOptionalContentGroup();

        // Optional content group
        if ($optionalContentGroup !== null) {
            $canvas->markedContent()->begin('OC', $optionalContentGroup);
        }

        // Handle stamp visibility
        if ($visibility !== self::VISIBILITY_ALL) {
            $group = $this->_getVisibilityGroup($document);
            $canvas->markedContent()->begin('OC', $group);
        }
        
        if ($stampData['rotation'] != .0) {
            
            $x = $this->getOriginX($page, $stampData['position']);
            $y = $this->getOriginY($page, $stampData['position']);
            
            $angle = $stampData['rotation'];
            $angle = fmod($angle, 360);
            $angle = $angle < 0 ? $angle + 360 : $angle;
            if((int)($angle / 90) % 2 === 0) {
                $a = $this->getWidth();
                $b = $this->getHeight();
            } else {
                $a = $this->getHeight();
                $b = $this->getWidth();
            }
            
            $alpha = deg2rad($angle % 90);
            $beta  = deg2rad(90 - ($angle % 90));
            
            $width = $a * cos($alpha) + $b * cos($beta);
            $height = $b * cos($alpha) + $a * cos($beta);
            
            $translateX = $stampData['translateX'];
            $translateY = $stampData['translateY'];
            
            switch ($stampData['position']) {
                case SetaPDF_Stamper::POSITION_LEFT_TOP:
                    $translateX += ($width - $this->getWidth()) / 2;
                    $translateY += ($height - $this->getHeight()) / -2;
                    break;
                case SetaPDF_Stamper::POSITION_CENTER_TOP:
                    $translateY += ($height - $this->getHeight()) / -2;
                    break;
                    
                case SetaPDF_Stamper::POSITION_RIGHT_TOP:
                    $translateX += ($this->getWidth() - $width) / 2;
                    $translateY += ($height - $this->getHeight()) / -2;
                    break;
                    
                case SetaPDF_Stamper::POSITION_LEFT_MIDDLE:
                    $translateX += ($width - $this->getWidth()) / 2;
                    break;
                    
                case SetaPDF_Stamper::POSITION_RIGHT_MIDDLE:
                    $translateX += ($this->getWidth() - $width) / 2;
                    break;
                    
                case SetaPDF_Stamper::POSITION_LEFT_BOTTOM:
                    $translateX += ($width - $this->getWidth()) / 2;
                    $translateY += ($this->getHeight() - $height) / -2;
                    break;
                    
                case SetaPDF_Stamper::POSITION_CENTER_BOTTOM:
                    $translateY += ($this->getHeight() - $height) / -2;
                    break;
                    
                case SetaPDF_Stamper::POSITION_RIGHT_BOTTOM:
                    $translateX += ($this->getWidth() - $width) / 2;
                    $translateY += ($this->getHeight() - $height) / -2;
                    break;
            }
            
            $canvas->saveGraphicState();
            $canvas->translate($translateX, $translateY);
            $canvas->saveGraphicState();
            $canvas->rotate($x + ($this->getWidth() / 2), $y + ($this->getHeight() / 2), $stampData['rotation']);
            
        } else if($stampData['translateX'] != 0 || $stampData['translateY'] != 0) {
            $canvas->saveGraphicState();
            $canvas->translate($stampData['translateX'], $stampData['translateY']);
        }
        
        $opacity = $this->getOpacity();
        if (abs($opacity - 1.0) > SetaPDF_Core::FLOAT_COMPARISON_PRECISION) {
            $gs = $this->_getOpacityGraphicState($document, $opacity);
            $canvas->saveGraphicState();
            $canvas->setGraphicState($gs);
        }
        
    }

    /**
     * Method which is called after the main stamp method is executed.
     * 
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Core_Document_Page $page
     * @param array $stampData
     * @return array|null
     */
    protected function _postStamp(SetaPDF_Core_Document $document, SetaPDF_Core_Document_Page $page, array $stampData)
    {
        $canvas = $page->getCanvas();
        
        if ($this->_action !== null) {
            $x = $this->getOriginX($page, $stampData['position']);
            $y = $this->getOriginY($page, $stampData['position']);
            $gs = $canvas->graphicState();
            $xy1 = $gs->getUserSpaceXY($x + $this->getWidth(), $y);
            $xy2 = $gs->getUserSpaceXY($x, $y);
            $xy3 = $gs->getUserSpaceXY($x, $y + $this->getHeight());
            $xy4 = $gs->getUserSpaceXY($x + $this->getWidth(), $y + $this->getHeight());
        }
        
        if ($stampData['rotation'] != .0) {
            $canvas->restoreGraphicState();
        }
        
        if ($stampData['rotation'] != .0 ||
            $stampData['translateX'] != 0 ||
            $stampData['translateY'] != 0
        ) {
            $canvas->restoreGraphicState();
        }
        
        if (abs($this->getOpacity() - 1.0) > SetaPDF_Core::FLOAT_COMPARISON_PRECISION) {
            $canvas->restoreGraphicState();
        }

        if ($this->getVisibility() !== self::VISIBILITY_ALL) {
            $canvas->markedContent()->end();
        }

        // Optional content group
        if ($this->getOptionalContentGroup() !== null) {
            $canvas->markedContent()->end();
        }

        return $this->_action !== null ? [$xy1, $xy2, $xy3, $xy4] : null;
    }
}