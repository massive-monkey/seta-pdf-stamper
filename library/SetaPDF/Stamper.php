<?php
/**
 * This file is part of the SetaPDF-Stamper Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: Stamper.php 1706 2022-03-28 10:40:28Z jan.slabon $
 */

/**
 * The main class of the SetaPDF-Stamper Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Stamper
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Stamper
{
    /**
     * Version
     *
     * @var string
     */
    const VERSION = SetaPDF_Core::VERSION;
    
    /**
     * Position constant
     *
     * @var string
     */
    const POSITION_LEFT_TOP = 'LT';

    /**
     * Position constant
     *
     * @var string
     */
    const POSITION_LEFT_MIDDLE = 'LM';

    /**
     * Position constant
     *
     * @var string
     */
    const POSITION_LEFT_BOTTOM = 'LB';

    /**
     * Position constant
     *
     * @var string
     */
    const POSITION_CENTER_TOP = 'CT';

    /**
     * Position constant
     *
     * @var string
     */
    const POSITION_CENTER_MIDDLE = 'CM';

    /**
     * Position constant
     *
     * @var string
     */
    const POSITION_CENTER_BOTTOM = 'CB';

    /**
     * Position constant
     *
     * @var string
     */
    const POSITION_RIGHT_TOP = 'RT';

    /**
     * Position constant
     *
     * @var string
     */
    const POSITION_RIGHT_MIDDLE = 'RM';

    /**
     * Position constant
     *
     * @var string
     */
    const POSITION_RIGHT_BOTTOM = 'RB';


    /**
     * Page constant
     *
     * @var string
     */
    const PAGES_ALL = 'all';

    /**
     * Page constant
     *
     * @var string
     */
    const PAGES_FIRST = 'first';

    /**
     * Page constant
     *
     * @var string
     */
    const PAGES_LAST = 'last';

    /**
     * Page constant
     *
     * @var string
     */
    const PAGES_EVEN = 'even';

    /**
     * Page constant
     *
     * @var string
     */
    const PAGES_ODD = 'odd';

    /**
     * Document which shall be stamped.
     *
     * @var SetaPDF_Core_Document
     */
    protected $_document;

    /**
     * Array of all stamps.
     *
     * @var array
     */
    protected $_stampsData = [];

    /**
     * The currently handled stamp data.
     *
     * @var array
     */
    protected $_currentStampData;
    
    /**
     * The constructor.
     * 
     * @param SetaPDF_Core_Document $document The document instance
     */
    public function __construct(SetaPDF_Core_Document $document)
    {
        SetaPDF_Core_SecHandler::checkPermission($document, SetaPDF_Core_SecHandler::PERM_MODIFY);
        
        $this->_document = $document;
    }

    /**
     * Release objects to free memory and cycled references.
     *
     * After calling this method the instance of this object is unusable.
     *
     * @return void
     */
    public function cleanUp()
    {
        $this->_document = null;
        $this->_currentStampData = null;
    }

    /**
     * Get the document which shall be stamped.
     *
     * @return SetaPDF_Core_Document
     */
    public function getDocument()
    {
        return $this->_document;
    }

    /**
     * Adds a stamp object to the stamper instance.
     *
     * @param SetaPDF_Stamper_Stamp $stamp Stamp object which shall be stamped on the document
     * @param string|array $positionOrConfig Position or array of configuration variables
     * @param int|string|array|callback $showOnPage The configuration defining on which pages the stamp shall be shown.
     *     Possible values are:
     *     <ul>
     *         <li><b>PAGES_XXX</b> constant</li>
     *         <li><b>Integer</b> with the valid page number</li>
     *         <li><b>String</b> with the valid page number or the valid range (e.g. '10-12')</li>
     *         <li><b>Array</b> with all valid page numbers</li>
     *         <li><b>Callback</b> with the arguments (int $pageNumber, int $pageCount)</li>
     *     </ul>
     * @param int $translateX Move the stamp on x-axis by $translateX
     * @param int $translateY Move the stamp on y-axis by $translateX
     * @param float $rotation Rotate the stamp by $rotation degrees
     * @param bool $underlay Defines whether the stamp should be place before or after the existing content
     * @param null|callback $callback Callback which will be called every time before the document will be stamped by this stamp
     *                                if it's not returning true the stamp will not stamped on this run.
     */
    public function addStamp(
        SetaPDF_Stamper_Stamp $stamp,
        $positionOrConfig = self::POSITION_LEFT_TOP,
        $showOnPage = self::PAGES_ALL,
        $translateX = 0,
        $translateY = 0,
        $rotation = .0,
        $underlay = false,
        $callback = null
    )
    {
        if (is_array($positionOrConfig)) {
            $position = self::POSITION_LEFT_TOP;
            foreach ($positionOrConfig AS $key => $value) {
                $$key = $value;
            }
        } else {
            $position = $positionOrConfig;
        }

        $this->checkPositionParameter($position);
        $this->checkShowOnPageParameter($showOnPage);

        $this->_addStampData([
            'stamp' => $stamp,
            'position' => $position,
            'showOnPage' => $showOnPage,
            'translateX' => (float)$translateX,
            'translateY' => (float)$translateY,
            'rotation' => (float)$rotation,
            'underlay' => (boolean)$underlay,
            'callback' => is_callable($callback) ? $callback : null
        ]);
    }

    /**
     * Adds a stamp to the stamper instance.
     *
     * This method is only used for testing purpose.
     *
     * @param array $data The stamp data
     * @internal
     */
    protected function _addStampData(array $data)
    {
        $this->_stampsData[] = $data;
    }

    /**
     * Checks whether the position parameter is valid.
     *
     * This method allows you to check a string value against all predefined position
     * constants: {@link SetaPDF_Stamper::POSITION_XXX}.
     *
     * @param string $position The string value to check for validity
     * @return bool It will return true on success otherwise it will throw an InvalidArgumentException exception.
     * @throws InvalidArgumentException
     */
    public function checkPositionParameter($position)
    {
        if (in_array((string)$position, [
            self::POSITION_CENTER_BOTTOM, self::POSITION_CENTER_MIDDLE, self::POSITION_CENTER_TOP,
            self::POSITION_LEFT_BOTTOM, self::POSITION_LEFT_MIDDLE, self::POSITION_LEFT_TOP,
            self::POSITION_RIGHT_BOTTOM, self::POSITION_RIGHT_MIDDLE, self::POSITION_RIGHT_TOP
        ], true)
        ) {
            return true;
        }

        throw new InvalidArgumentException(
            sprintf('Invalid position parameter: %s', $position)
        );
    }

    /**
     * Checks whether $a is a valid integer.
     *
     * @param mixed $a The variable to check
     * @return bool
     * @internal
     */
    private function _isIntVal($a)
    {
        return is_scalar($a) && ((string)$a === (string)(int)$a);
    }

    /**
     * Checks whether the $showOnPage parameter is valid.
     *
     * @param int|string|array|callback $showOnPage The value to check
     * @return bool
     * @throws InvalidArgumentException
     */
    public function checkShowOnPageParameter($showOnPage)
    {
        if (in_array($showOnPage, [
            self::PAGES_ALL, self::PAGES_EVEN, self::PAGES_FIRST,
            self::PAGES_LAST, self::PAGES_ODD
        ], true)
        ) {
            return true;
        }

        if (is_callable($showOnPage)) {
            return true;
        }

        if (is_array($showOnPage)) {
            $tmpArray = array_filter($showOnPage, [$this, '_isIntVal']);
            if (count($tmpArray) === count($showOnPage)) {
                return true;
            }
        }

        if ($this->_isIntVal($showOnPage)) {
            return true;
        }

        if (is_string($showOnPage) && preg_match('~^(\d+)-(\d*)$~', $showOnPage)) {
            return true;
        }

        throw new InvalidArgumentException(
            sprintf('Invalid showOnPage parameter: %s', $showOnPage)
        );
    }

    /**
     *  This method will stamp the complete document with all added stamps.
     *
     *  @see SetaPDF_Stamper::addStamp()
     */
    public function stamp()
    {
        $pages = $this->_document->getCatalog()->getPages();
        $pageCount = $pages->count();

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $this->stampPageNo($pageNumber);
        }
    }

    /**
     * This method will only stamp the page $pageNumber with all added stamps.
     *
     * @see SetaPDF_Stamper::addStamp()
     * @param integer $pageNumber The number of the page which will be stamped
     * @return bool
     * @throws InvalidArgumentException
     */
    public function stampPageNo($pageNumber)
    {
        $pageNumber = (int)$pageNumber;
        $pages = $this->_document->getCatalog()->getPages();
        $pageCount = $pages->count();

        if ($pageNumber <= 0 || $pageNumber > $pageCount) {
            throw new InvalidArgumentException(sprintf('Invalid page number argument: %s', $pageNumber));
        }

        $contents = $page = null;
        $stamped = false;

        foreach ($this->_stampsData AS $this->_currentStampData) {
            if (!$this->_shouldStamp($pageNumber, $pageCount, $this->_currentStampData['showOnPage'])) {
                continue;
            }

            $page = $pages->getPage($pageNumber);

            $contents = $page->getContents();
            if ($contents->count() > 0) {
                $contents->encapsulateExistingContentInGraphicState();
            }
            break;
        }

        $transparencyUsed = false;
        $firstContentStream = false;
        foreach ($this->_stampsData AS $this->_currentStampData) {
            if (!$this->_shouldStamp($pageNumber, $pageCount, $this->_currentStampData['showOnPage'])) {
                continue;
            }

            if (
                is_callable($this->_currentStampData['callback']) &&
                call_user_func_array(
                    $this->_currentStampData['callback'],
                    [$pageNumber, $pageCount, $page, $this->_currentStampData['stamp'], &$this->_currentStampData]
                ) !== true
            ) {
                continue;
            }

            // Handle underlay
            if ($this->_currentStampData['underlay'] === true) {
                if ($firstContentStream === false) {
                    $contents->prependStream(true);
                }
                $contents->getStreamObjectByOffset(0, true);
                
            } else if (!$contents->isLastStreamActive()) {
                $contents->getLastStreamObject(false, true);
            }

            $pageRotation = $page->getRotation();
            if ($pageRotation !== 0) {
                $canvas = $page->getCanvas();
                $canvas->saveGraphicState();
                $canvas->normalizeRotation($pageRotation, $page->getBoundary());
            }

            if ($this->_currentStampData['stamp']->stamp($this->_document, $page, $this->_currentStampData)) {
                $stamped = true;
                
                if (
                    abs($this->_currentStampData['stamp']->getOpacity() - 1.0) >
                    SetaPDF_Core::FLOAT_COMPARISON_PRECISION
                ) {
                    $transparencyUsed = true;
                }
            }

            if ($pageRotation !== 0) {
                $canvas->restoreGraphicState();
            }
        }

        if ($transparencyUsed === true) {
            $pageDict = $page->getPageObject(true)->ensure(true);
            if (!$pageDict->offsetExists('Group')) {
                $group = new SetaPDF_Core_TransparencyGroup();
                $group->setColorSpace('DeviceRGB');
                $page->setGroup($group);
            }
        }

        return (boolean)$stamped;
    }

    /**
     * Checks whether the page $pageNumber shall be stamped.
     *
     * @param integer $pageNumber
     * @param integer $pageCount
     * @param mixed $showOnPage
     * @return bool|mixed
     * @internal
     */
    protected function _shouldStamp($pageNumber, $pageCount, $showOnPage)
    {
        $isArray = is_array($showOnPage);
        if (
            !$isArray && (
                $showOnPage === self::PAGES_ALL ||
                ($showOnPage === self::PAGES_FIRST && $pageNumber === 1) ||
                ($showOnPage === self::PAGES_LAST && $pageNumber === $pageCount) ||
                ($showOnPage === self::PAGES_EVEN && ($pageNumber & 1) === 0) ||
                ($showOnPage === self::PAGES_ODD && ($pageNumber & 1) === 1) ||
                (is_scalar($showOnPage) && ((int)$showOnPage) === $pageNumber)
            )
        ) {
            return true;
        }

        if (is_string($showOnPage) && preg_match('~^(\d+)-(\d*)$~', $showOnPage, $matches)) {
            $start = (int)$matches[1];
            $end   = $matches[2] ? (int)$matches[2] : $pageCount;

            return $pageNumber >= $start && $pageNumber <= $end;
        }

        if (is_callable($showOnPage)) {
            return $showOnPage($pageNumber, $pageCount);
        }

        if ($isArray) {
            return in_array($pageNumber, $showOnPage);
        }

        return false;
    }
}