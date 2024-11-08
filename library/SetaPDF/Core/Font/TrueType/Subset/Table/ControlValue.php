<?php
/**
 * This file is part of the SetaPDF-Core Component
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Font
 * @license    https://www.setasign.com/ Commercial
 * @version    $Id: ControlValue.php 1747 2022-06-21 10:25:58Z jan.slabon $
 */

/**
 * This class represents the "cvt " table in a sub-setting context.
 *
 * See {@link https://www.microsoft.com/typography/otspec/cvt.htm} for more details.
 *
 * @method SetaPDF_Core_Font_TrueType_Table_ControlValue getOriginalTable()
 *
 * @copyright  Copyright (c) 2022 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @category   SetaPDF
 * @package    SetaPDF_Core
 * @subpackage Font
 * @license    https://www.setasign.com/ Commercial
 */
class SetaPDF_Core_Font_TrueType_Subset_Table_ControlValue extends SetaPDF_Core_Font_TrueType_Subset_Table
{
    /**
     * The constructor.
     *
     * This table will be copied without changes.
     *
     * @param SetaPDF_Core_Font_TrueType_Table_ControlValue $table
     */
    public function __construct(SetaPDF_Core_Font_TrueType_Table_ControlValue $table)
    {
        parent::__construct($table);
    }
}
