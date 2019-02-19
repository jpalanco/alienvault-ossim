<?php
/**
* asset_filters.inc
*
* File asset_filters.inc is used to:
*   - Manage asset filter
*   - Manage asset filter types
*
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* This package is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 dated June, 1991.
* You may not use, modify or distribute this program under any other version
* of the GNU General Public License.
*
* This package is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this package; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
* MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

require_once 'av_init.php';


/**
* Asset Filter Class
*
* Class for managing a generic asset filter
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
abstract class Asset_filter
{
    /**
    * Filter ID
    * @var integer
    *
    */
    protected $_id;

    /**
    * Filter Values
    * @var array
    *
    */
    protected $_values;

    /**
    * Flag to indicate if its default value is required
    * @var boolean
    *
    */
    protected $_default_value;

    /**
    * Flag to indicate if the modification of the filter requires a reload of the group
    * @var boolean
    *
    */
    protected $_force_reload;



    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param integer  $id  Filter type ID
    *
    */
    public function __construct($id)
    {
        $this->_id            = $id;
        $this->_default_value = 1;
        $this->_force_reload  = FALSE;

        $this->_set_default_value();
    }


    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    abstract protected function _set_default_value();


    /**
    * This function returns the filter ID
    *
    * @return integer
    */
    public function get_id()
    {
        return $this->_id;
    }


    /**
    * This function returns the filter value
    *
    * @return mixed
    */
    public function get_values()
    {
        return $this->_values;
    }


    /**
    * This function returns if the filter needs to reload the group
    *
    * @return boolean
    */
    public function is_force_reload()
    {
        return $this->_force_reload;
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    public function modify_value($value, $delete = FALSE)
    {
        if ($delete)
        {
            $this->_force_reload = TRUE;
        }
        else
        {
            $this->_force_reload = FALSE;
        }

        $this->_set_value($value, $delete);

        $this->_default_value = 0;
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    abstract protected function _set_value($value, $delete = FALSE);


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    public function get_filter_sql($reload = FALSE)
    {
        $load_default = FALSE;

        //If the flag reload is true, we never load the default value
        if ($reload == FALSE)
        {
            $load_default = $this->_default_value;
        }

        $sql = $this->_get_sql_value($load_default);

        $this->_default_value = 1;

        return $sql;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    abstract protected function _get_sql_value($load_default);


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    abstract public function is_active();


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    abstract public function get_tag_params($conn);



    /************************************************************************************************/
    /************************************    STATIC FUNCTIONS    ************************************/
    /************************************************************************************************/


    /**
    * This function format the date ranges for date filters
    *
    * @param  string  $range  Range type
    * @param  string  $from   From date
    * @param  string  $to     To date
    *
    * @return array
    */
    public static function format_range_dates($range, $from = '', $to = '')
    {
        $day_second = 86400;

        switch ($range)
        {
            case 'day':
                $_from = gmdate('Y-m-d H:i:s', gmdate('U') - $day_second);
                $_to   = gmdate('Y-m-d H:i:s');

                break;

            case 'week':
                $_from = gmdate('Y-m-d H:i:s', gmdate('U') - ($day_second * 7));
                $_to   = gmdate('Y-m-d H:i:s');

                break;

            case 'month':
                $_from = gmdate('Y-m-d H:i:s', gmdate('U') - ($day_second * 30));
                $_to   = gmdate('Y-m-d H:i:s');

                break;

            case 'range':

                $tzc = Util::get_tzc();

                if ($from != '')
                {
                    $_from = gmdate('Y-m-d H:i:s', strtotime($from . " 00:00:00 $tzc"));
                }
                else
                {
                    $_from = gmdate('Y-m-d H:i:s', 0);
                }

                if ($to != '')
                {
                    $_to = gmdate('Y-m-d H:i:s', strtotime($to . " 23:59:59 $tzc"));
                }
                else
                {
                    $_to = gmdate('Y-m-d H:i:s');
                }

                break;

            default:
                $_from = '';
                $_to   = '';

        }

        return array($_from, $_to);
    }
}




/**
* Filter_asset_created Class
*
* Class for managing an asset filter of type asset_created
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_asset_created extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values['value']   = '';
        $this->_values['from']    = '';
        $this->_values['to']      = '';
    }


    /**
    * This function returns the 'value' value form the filter
    *
    * @return string
    */
    public function get_val()
    {
        return $this->_values['value'];
    }


    /**
    * This function returns the 'from' value form the filter
    *
    * @return string
    */
    public function get_from()
    {
        return $this->_values['from'];
    }


    /**
    * This function returns the 'to' value form the filter
    *
    * @return string
    */
    public function get_to()
    {
        return $this->_values['to'];
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        if ($delete)
        {
            $this->_values['value'] = '';
            $this->_values['from']  = '';
            $this->_values['to']    = '';
        }
        else
        {
            $value = explode(';', $value);

            $range = $value[0];
            $from  = $value[1];
            $to    = $value[2];

            ossim_valid($range,    OSS_LETTER, OSS_NULLABLE,    'illegal:' . _('Filter Value'));
            ossim_valid($from,     OSS_DATE, OSS_NULLABLE,      'illegal:' . _('Filter Value'));
            ossim_valid($to,       OSS_DATE, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

            if (ossim_error())
            {
                $exp_msg = _('Error').': '.ossim_get_error();
                ossim_clean_error();

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            if ($range == 'day' || $range == 'week' || $range == 'month')
            {
                $from = '';
                $to   = '';
            }
            elseif($range != 'range')
            {
                $exp_msg = _('Error: Invalid Date Range');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            $this->_values['value']   = $range;
            $this->_values['from']    = $from;
            $this->_values['to']      = $to;
        }

        $this->_force_reload = TRUE;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is range so we need the values "from" and "to".
        Value example (Valid for "from" and "to"): '2013-07-15 08:00:00'.
        The default values are empty.
        */
        $r_value = array
        (
            'from' => '',
            'to'   => ''
        );

        if (!$load_default && $this->is_active())
        {
            list($from, $to) = self::format_range_dates($this->_values['value'], $this->_values['from'], $this->_values['to']);

            $r_value['from'] = $from;
            $r_value['to']   = $to;
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return ($this->_values['value'] != '');
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        $label = _('Assets Added:') . ' ';

        $range = $this->get_val();

        $id    = $this->_id . '###' . $range;

        if ($range == 'range')
        {
            $label .=  _('Date Range');
        }
        elseif ($range != '')
        {
            $_txt   = _('Last ' . ucfirst($range));
            $label .= _($_txt);
        }
        else
        {
            $label .= _('Unknown');
        }

        $tags[$id] = $label;

        return $tags;
    }
}



/**
* Filter_asset_updated Class
*
* Class for managing an asset filter of type asset_updated
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_asset_updated extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array(
            'value' => '',
            'from'  => '',
            'to'    => ''
        );
    }


    /**
    * This function returns the 'value' value form the filter
    *
    * @return string
    */
    public function get_val()
    {
        return $this->_values['value'];
    }


    /**
    * This function returns the 'from' value form the filter
    *
    * @return string
    */
    public function get_from()
    {
        return $this->_values['from'];
    }


    /**
    * This function returns the 'to' value form the filter
    *
    * @return string
    */
    public function get_to()
    {
        return $this->_values['to'];
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        if ($delete)
        {
            $this->_values['value']   = '';
            $this->_values['from']    = '';
            $this->_values['to']      = '';
        }
        else
        {
            $value = explode(';', $value);

            $range = $value[0];
            $from  = $value[1];
            $to    = $value[2];

            ossim_valid($range,    OSS_LETTER, OSS_NULLABLE,    'illegal:' . _('Filter Value'));
            ossim_valid($from,     OSS_DATE, OSS_NULLABLE,      'illegal:' . _('Filter Value'));
            ossim_valid($to,       OSS_DATE, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

            if (ossim_error())
            {
                $exp_msg = _('Error').': '.ossim_get_error();
                ossim_clean_error();

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            if ($range == 'day' || $range == 'week' || $range == 'month')
            {
                $from = '';
                $to   = '';
            }
            elseif($range != 'range')
            {
                $exp_msg = _('Error: Invalid Date Range');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            $this->_values['value']   = $range;
            $this->_values['from']    = $from;
            $this->_values['to']      = $to;
        }

        $this->_force_reload = TRUE;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is range so we need the values "from" and "to".
        Value example (Valid for "from" and "to"): '2013-07-15 08:00:00'.
        The default values are empty.
        */
        $r_value = array
        (
            'from' => '',
            'to'   => ''
        );


        if (!$load_default && $this->is_active())
        {
            list($from, $to) = self::format_range_dates($this->_values['value'], $this->_values['from'], $this->_values['to']);

            $r_value['from'] = $from;
            $r_value['to']   = $to;
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return ($this->_values['value'] != '');
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        $label = _('Last Updated:') . ' ';

        $range = $this->get_val();

        $id    = $this->_id . '###' . $range;

        if ($range == 'range')
        {
            $label .=  _('Date Range');
        }
        elseif ($range != '')
        {
            $_txt   = _('Last ' . ucfirst($range));
            $label .= _($_txt);
        }
        else
        {
            $label .= _('Unknown');
        }

        $tags[$id] = $label;

        return $tags;
    }
}



/**
* Filter_alarms Class
*
* Class for managing an asset filter of type alarm
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_alarms extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = 0;
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        if ($delete)
        {
            $this->_values = 0;
        }
        else
        {
            $this->_values = 1;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is value so we need the field value.
        Value example: 0 or 1.
        The default value is 0.
        */
        $r_value = 0;

        if (!$load_default && $this->is_active())
        {
            $r_value = 1;
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return !empty($this->_values);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        $id    = $this->_id . '###' . $this->_id;
        $label = _('Has Alarms');

        $tags[$id] = $label;

        return $tags;
    }
}



/**
* Filter_events Class
*
* Class for managing an asset filter of type events
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_events extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = 0;
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        if ($delete)
        {
            $this->_values = 0;
        }
        else
        {
            $this->_values = 1;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is value so we need the field value.
        Value example: 0 or 1.
        The default value is 0.
        */
        $r_value = 0;


        if (!$load_default && $this->is_active())
        {
            $r_value = 1;
        }

        return $r_value;

    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return !empty($this->_values);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        $id    = $this->_id . '###' . $this->_id;
        $label = _('Has Events');

        $tags[$id] = $label;

        return $tags;
    }
}



/**
* Filter_vulnerabilities Class
*
* Class for managing an asset filter of type vulnerabilities
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_vulnerabilities extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array(
            'from'  => '',
            'to'    => ''
        );
    }


    /**
    * This function returns the 'from' value form the filter
    *
    * @return integer
    */
    public function get_from()
    {
        return $this->_values['from'];
    }


    /**
    * This function returns the 'to' value form the filter
    *
    * @return integer
    */
    public function get_to()
    {
        return $this->_values['to'];
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        if ($delete)
        {
            $this->_values['from'] = '';
            $this->_values['to']   = '';
        }
        else
        {
            $value = explode(';', $value);

            $from  = $value[0];
            $to    = $value[1];

            ossim_valid($from,     '1-7', OSS_NULLABLE,    'illegal:' . _('Filter Value'));
            ossim_valid($to,       '1-7', OSS_NULLABLE,    'illegal:' . _('Filter Value'));

            if (ossim_error())
            {
                $exp_msg = _('Error').': '.ossim_get_error();
                ossim_clean_error();

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            if ($from == '' || $to == '')
            {
                $from = $to = '';
            }

            $this->_values['from'] = $from;
            $this->_values['to']   = $to;
        }

        $this->_force_reload = TRUE;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is range so we need the values "from" and "to".
        Value example (Valid for "from" and "to"): integer between 1 and 7.
        The default values are 0.
        */
        $r_value = array
        (
            'from' => '',
            'to'   => ''
        );


        if (!$load_default && $this->is_active())
        {
            $r_value['from'] = $this->_values['from'];
            $r_value['to']   = $this->_values['to'];
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return ($this->_values['from'] != '' && $this->_values['to'] != '');
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $__vuln_opts = Filter_list::get_filter_legends('vulns');

        $tags  = array();

        $id    = $this->_id . '###' . $this->get_from() . ';' . $this->get_to();

        $from  = $__vuln_opts[$this->get_from()];
        $to    = $__vuln_opts[$this->get_to()];
        $label = _('Vulnerabilities:') . ' ' . $to . ' - ' . $from;

        $tags[$id] = $label;

        return $tags;
    }
}



/**
* Filter_asset_value Class
*
* Class for managing an asset filter of type asset_value
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_asset_value extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array(
            'from'  => '',
            'to'    => ''
        );
    }


    /**
    * This function returns the 'from' value form the filter
    *
    * @return integer
    */
    public function get_from()
    {
        return $this->_values['from'];
    }

    /**
    * This function returns the 'to' value form the filter
    *
    * @return integer
    */
    public function get_to()
    {
        return $this->_values['to'];
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        if ($delete)
        {
            $this->_values['from']    = '';
            $this->_values['to']      = '';
        }
        else
        {
            $value = explode(';', $value);

            $from  = $value[0];
            $to    = $value[1];

            ossim_valid($from,     '0-5', OSS_NULLABLE,    'illegal:' . _('Filter Value'));
            ossim_valid($to,       '0-5', OSS_NULLABLE,    'illegal:' . _('Filter Value'));

            if (ossim_error())
            {
                $exp_msg = _('Error').': '.ossim_get_error();
                ossim_clean_error();

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            if ($from == '' || $to == '')
            {
                $from = $to = '';
            }

            $this->_values['from'] = $from;
            $this->_values['to']   = $to;
        }

        $this->_force_reload = TRUE;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
         /*
        This filter type is range so we need the values "from" and "to".
        Value example (Valid for "from" and "to"): integer between 1 and 5.
        The default values are 0.
        */
        $r_value = array
        (
            'from' => '',
            'to'   => ''
        );

        if (!$load_default && $this->is_active())
        {
            $r_value['from'] = $this->_values['from'];
            $r_value['to']   = $this->_values['to'];
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return ($this->_values['from'] != '' && $this->_values['to'] != '');
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        $id    = $this->_id . '###' . $this->get_from() . ';' . $this->get_to();
        $label = _('Asset Value:') . ' ' . $this->get_from() . ' - ' . $this->get_to();

        $tags[$id] = $label;

        return $tags;
    }
}



/**
* Filter_network Class
*
* Class for managing an asset filter of type network
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_network extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_HEX, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $this->_values[$value] = $value;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is list so we need the field "value".
        Value example: '0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D'.
        The default value is empty.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            //Now we format the networks spliting by commas and adding the 0x
            $r_value = '0x' . implode(', 0x', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id = $this->_id . '###' . $val;

            try
            {
                $name = Asset_net::get_name_by_id($conn, $val);
            }
            catch(Exception $e)
            {
                $name = '';
            }

            $name  = ($name == '') ? _('Unknown') : $name;

            $label = _('Network:') . ' ' . $name;

            $tags[$id] = $label;
        }

        return $tags;
    }
}



/**
* Filter_software Class
*
* Class for managing an asset filter of type software
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_software extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_INPUT, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);

        }
        else
        {
            $this->_values[$value] = $value;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is list so we need the field "value".
        Value example: 'cpe:/o:yamaha:srt100:10.00.46,cpe:/o:microsoft:virtual_machine_manager:2007'.
        The default value is empty.
        */
        $r_value = '';

        if (!$load_default && $this->is_active())
        {
            //We put all the cpe's values separated by commas
            $r_value = implode(',', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id    = $this->_id . '###' . $val;

            try
            {
                $name = Software::get_info($conn, $val);
            }
            catch(Exception $e)
            {
                $name = '';
            }

            $name  = ($name == '') ? _('Unknown') : $name;

            $label = _('Software:') . ' ' . $name;

            $tags[$id] = $label;
        }

        return $tags;
    }
}



/**
* Filter_fqdn Class
*
* Class for managing an asset filter of type fqdn
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_fqdn extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_DIGIT, OSS_LETTER, OSS_SCORE, OSS_DOT, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $this->_values[$value] = $value;
        }

        $this->_force_reload = TRUE;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
       /*
        This filter type is list so we need the field "value".
        Value example: free string (% is allowed) separated by commas
        The default value is empty.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            //Now we format the networks spliting by commas and adding the 0x
            $r_value = implode(';', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id    = $this->_id . '###' . $val;

            $label = _('Hostname & FQDN:') . ' ' . $val;

            $tags[$id] = $label;
        }

        return $tags;
    }
}



/**
* Filter_location Class
*
* Class for managing an asset filter of type location
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_location extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_HEX, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $this->_values[$value] = $value;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is list so we need the field "value".
        Value example: '0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D'.
        The default value is empty.
        */
        $r_value = '';

        if (!$load_default && $this->is_active())
        {
            //Now we format the networks spliting by commas and adding the 0x
            $r_value = '0x' . implode(', 0x', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags = array();

        foreach ($this->_values as $val)
        {
            $id = $this->_id . '###' . $val;

            try
            {
                $name  = Locations::get_name_by_id($conn, $val);
            }
            catch(Exception $e)
            {
                $name = '';
            }

            $name  = ($name == '') ? _('Unknown') : $name;

            $label = _('Location:') . ' ' . $name;


            $tags[$id] = $label;
        }

        return $tags;
    }
}



/**
* Filter_sensor Class
*
* Class for managing an asset filter of type sensor
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_sensor extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_HEX, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $this->_values[$value] = $value;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is list so we need the field "value".
        Value example: '0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D'.
        The default value is empty.
        */
        $r_value = '';

        if (!$load_default && $this->is_active())
        {
            //Now we format the networks spliting by commas and adding the 0x
            $r_value = '0x' . implode(', 0x', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags = array();

        foreach ($this->_values as $val)
        {
            $id = $this->_id . '###' . $val;

            try
            {
                $name = Av_sensor::get_name_by_id($conn, $val);
            }
            catch(Exception $e)
            {
                $name = '';
            }

            $name  = ($name == '') ? _('Unknown') : $name;

            $label = _('Sensor:') . ' ' . $name;

            $tags[$id] = $label;
        }

        return $tags;
    }
}



/**
* Filter_ip Class
*
* Class for managing an asset filter of type ip
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_ip extends Asset_filter
{

    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,    OSS_IP_ADDRCIDR, OSS_NULLABLE,  'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $cidr  = (preg_match('/\/[0-9]+/', $value)) ? $value : $value . '/32'; //If it is an ip, we add '/32'

            $range = CIDR::expand_CIDR($cidr, 'SHORT', 'IP');

            $this->_values[$value]['value'] = $value;
            $this->_values[$value]['from']  = $range[0];
            $this->_values[$value]['to']    = $range[1];
        }

        $this->_force_reload  = TRUE;
    }



    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is range_list so we need the values "from" and "to".
        Value example (Valid for "from" and "to"):
        The default value is empty.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            $r_value = array();

            //We need to go throught all the values and put the from and to separated by commas
            foreach ($this->_values as $_val)
            {
                $r_value[] = $_val['from'] . ',' . $_val['to'];

            }
            //Now we join al the values separated by ;
            $r_value = implode(';', $r_value);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags = array();

        foreach ($this->_values as $val)
        {
            $id    = $this->_id . '###' . $val['value'];

            $label = _('IP & CIDR:') . ' ' . $val['value'];

            $tags[$id] = $label;
        }

        return $tags;
    }
}



/**
* Filter_device_type Class
*
* Class for managing an asset filter of type device_type
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_device_type extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        $_val = explode(';', $value);

        $from  = $_val[0];
        $to    = $_val[1];

        ossim_valid($from,     OSS_DIGIT, OSS_NULLABLE,    'illegal:' . _('Filter Value'));
        ossim_valid($to,       OSS_DIGIT, OSS_NULLABLE,    'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $id = md5($value);

        if ($delete)
        {
            unset($this->_values[$id]);
        }
        else
        {
            $this->_values[$id]['from'] = $from;
            $this->_values[$id]['to']   = $to;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is range_list so we need the values "from" and "to".
        Value example: typeid,subtypeid - 1,0;4,404
        The default value is empty.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            $r_value = array();

            //We need to go throught all the values and put the from and to separated by commas
            foreach ($this->_values as $_val)
            {
                $r_value[] = $_val['from'] . ',' . $_val['to'];

            }
            //Now we join al the values separated by ;
            $r_value = implode(';', $r_value);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags = array();

        foreach ($this->_values as $val)
        {
            $type    = $val['from'];
            $subtype = $val['to'];

            $id    = $this->_id . '###' . $type . ';' . $subtype;

            try
            {
                $tname = Devices::get_name_by_id($conn, $type);
                $sname = Devices::get_name_by_id($conn, $subtype);
            }
            catch(Exception $e)
            {
                $tname = $sname = '';
            }

            $tname = ($tname == '') ? _('Unknown') : $tname;
            $sname = ($sname == '') ? '' : '/' . $sname;

            $name  = $tname . $sname;

            $label = _('Device Type:') . ' ' . $name;

            $tags[$id] = $label;
        }

        return $tags;
    }
}



/**
* Filter_port_service Class
*
* Class for managing an asset filter of type port_service
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_port_service extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        $_val    = explode(';', $value);

        $port     = $_val[0];
        $protocol = $_val[1];
        $service  = $_val[2];

        ossim_valid($port,          OSS_DIGIT, OSS_NULLABLE,    'illegal:' . _('Filter Value'));
        ossim_valid($protocol,      OSS_DIGIT, OSS_NULLABLE,    'illegal:' . _('Filter Value'));
        ossim_valid($service,       OSS_INPUT, OSS_NULLABLE,    'illegal:' . _('Filter Value'));


        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $id = md5($value);

        if ($delete)
        {
            unset($this->_values[$id]);
        }
        else
        {
            $this->_values[$id]['value'] = $port;
            $this->_values[$id]['from']  = $protocol;
            $this->_values[$id]['to']    = $service;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is range_list and in this particular case we need all the fields.
        Value example: port,protocol,'service' - 80,6,'http';0,1,'PING'
        The default value is empty.
        */
        $r_value = '';

        if (!$load_default && $this->is_active())
        {
            $r_value = array();

            //We need to go throught all the values and put the from and to separated by commas
            foreach ($this->_values as $_val)
            {
                $r_value[] = $_val['value'] . ',' . $_val['from'] . ",'" . $_val['to'] ."'";

            }
            //Now we join al the values separated by ;
            $r_value = implode(';', $r_value);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags = array();

        foreach ($this->_values as $val)
        {
            $port     = $val['value'];
            $protocol = $val['from'];
            $service  = $val['to'];

            $id       = $this->_id . '###' . $port . ';' . $protocol . ';' . $service;

            $protocol = Protocol::get_protocol_by_number($protocol);
            $protocol = (FALSE === $protocol) ? _('Unknown') : $protocol;

            $label    = _('Service:') . ' ' . $port .'/'. $protocol . ' ('. $service . ')';

            $tags[$id] = $label;
        }

        return $tags;
    }
}

/**
* Filter_os Class
*
* Class for managing an asset filter of operating system
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_os extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $id = md5($value);

        if ($delete)
        {
            unset($this->_values[$id]);

        }
        else
        {
            $this->_values[$id] = $value;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is list so we need the field "value".
        Value example: 'Linux/2.6X, Windows Vista'.
        The default value is empty.
        */
        $r_value = '';

        if (!$load_default && $this->is_active())
        {
            //We put all the cpe's values separated by commas
            $r_value = implode(',', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id        = $this->_id . '###' . md5($val);
            $tags[$id] = $val;
        }

        return $tags;
    }
}

/**
* Filter_model Class
*
* Class for managing an asset filter of model
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_model extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_INPUT, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $id = md5($value);

        if ($delete)
        {
            unset($this->_values[$id]);

        }
        else
        {
            $this->_values[$id] = $value;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is list so we need the field "value".
        Value example: 'Linux/2.6X, Windows Vista'.
        The default value is empty.
        */
        $r_value = '';

        if (!$load_default && $this->is_active())
        {
            //We put all the cpe's values separated by commas
            $r_value = implode(',', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id        = $this->_id . '###' . md5($val);
            $tags[$id] = $val;
        }

        return $tags;
    }
}


/**
* Filter_availability Class
*
* Class for managing an asset filter of type availability (Nagios)
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_availability extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = '';
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        if ($delete)
        {
            $this->_values = '';
        }
        else
        {
            ossim_valid($value,     '0-2', OSS_NULLABLE,    'illegal:' . _('Availability Status'));

            if (ossim_error())
            {
                $exp_msg = _('Error').': '.ossim_get_error();
                ossim_clean_error();

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            $this->_values = $value;
        }

        $this->_force_reload = TRUE;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is value so we need the field value.
        Value example: '', 0, 1, 2.
        The default value is ''.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            $r_value = $this->_values;
        }

        return $r_value;

    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return ($this->_values != '');
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $__availability_status = Filter_list::get_filter_legends('availability');

        $tags  = array();

        $id    = $this->_id . '###' . $this->_values;
        $label = _('Availability Status:') . ' ' . $__availability_status[$this->_values];

        $tags[$id] = $label;

        return $tags;
    }
}

/**
* Filter_groups Class
*
* Class for managing an asset filter of type group
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_groups extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_HEX, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $this->_values[$value] = $value;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is list so we need the field "value".
        Value example: '0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D'.
        The default value is empty.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            //Now we format the networks spliting by commas and adding the 0x
            $r_value = '0x' . implode(', 0x', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id = $this->_id . '###' . $val;

            try
            {
                $name = Asset_group::get_name_by_id($conn, $val);
            }
            catch(Exception $e)
            {
                $name = '';
            }

            $name  = ($name == '') ? _('Unknown') : $name;

            $label = _('Group:') . ' ' . $name;

            $tags[$id] = $label;
        }

        return $tags;
    }
}


/**
* Filter_labels Class
*
* Class for managing an asset filter of type labels
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_labels extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_HEX, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $this->_values[$value] = $value;
        }
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is list so we need the field "value".
        Value example: '0xF8EF2A7B9AC2B876C95FC12914BB3754,0x4531A9B0B300105D7DEDC6FC9330E24D'.
        The default value is empty.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            //Now we format the networks spliting by commas and adding the 0x
            $r_value = '0x' . implode(', 0x', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id = $this->_id . '###' . $val;

            try
            {
                $name = Tag::get_name_by_id($conn, $val);
            }
            catch(Exception $e)
            {
                $name = '';
            }

            $name  = ($name == '') ? _('Unknown') : $name;

            $label = _('Label:') . ' ' . $name;

            $tags[$id] = $label;
        }

        return $tags;
    }
}


/**
* Filter_group_name Class
*
* Class for managing an asset filter of type group name
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_group_name extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $this->_values[$value] = $value;
        }

        $this->_force_reload = TRUE;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
       /*
        This filter type is list so we need the field "value".
        Value example: free string (% is allowed) separated by commas
        The default value is empty.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            //Now we format the networks spliting by commas and adding the 0x
            $r_value = implode(';', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id    = $this->_id . '###' . $val;

            $label = _('Group Name:') . ' ' . $val;

            $tags[$id] = $label;
        }

        return $tags;
    }
}


/**
* Filter_network_name Class
*
* Class for managing an asset filter of type network name
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_network_name extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $this->_values[$value] = $value;
        }

        $this->_force_reload = TRUE;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
       /*
        This filter type is list so we need the field "value".
        Value example: free string (% is allowed) separated by commas
        The default value is empty.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            //Now we format the networks spliting by commas and adding the 0x
            $r_value = implode(';', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id    = $this->_id . '###' . $val;

            $label = _('Network Name:') . ' ' . $val;

            $tags[$id] = $label;
        }

        return $tags;
    }
}


/**
* Filter_network_cidr Class
*
* Class for managing an asset filter of type network name
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class Filter_network_cidr extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_IP_CIDR, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $this->_values[$value] = $value;
        }

        $this->_force_reload = TRUE;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
       /*
        This filter type is list so we need the field "value".
        Value example: free string (% is allowed) separated by commas
        The default value is empty.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            //Now we format the networks spliting by commas and adding the 0x
            $r_value = implode(';', $this->_values);
        }

        return $r_value;
    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id    = $this->_id . '###' . $val;

            $label = _('Network CIDR:') . ' ' . $val;

            $tags[$id] = $label;
        }

        return $tags;
    }
}


/**
 * Filter_hids Class
 *
 * Class for managing an asset filter of type HIDS (OSSEC)
 *
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 */
class Filter_hids extends Asset_filter
{
    /**
    * This function set the default value of the filter
    *
    * @return void
    */
    protected function _set_default_value()
    {
        $this->_values = '';
    }


    /**
    * This function sets a single filter value
    *
    * @param  $value   Filter Value
    * @param  $delete  Delete Filter Flag
    *
    * @throws Exception  If the validation of the value to modify is incorrect
    *
    * @return void
    */
    protected function _set_value($value, $delete = FALSE)
    {
        if ($delete)
        {
            $this->_values = '';
        }
        else
        {
            ossim_valid($value,     '0-2', OSS_NULLABLE,    'illegal:' . _('HIDS Status'));

            if (ossim_error())
            {
                $exp_msg = _('Error').': '.ossim_get_error();
                ossim_clean_error();

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            $this->_values = $value;
        }

        $this->_force_reload = TRUE;
    }


    /**
    * This function gets the sql value of a filter
    *
    * @param  boolean  $reload  Flag to force non-default value
    *
    * @return string
    */
    protected function _get_sql_value($load_default)
    {
        /*
        This filter type is value so we need the field value.
        Value example: '', 0, 1, 2.
        The default value is ''.
        */
        $r_value = '';


        if (!$load_default && $this->is_active())
        {
            $r_value = $this->_values;
        }

        return $r_value;

    }


    /**
    * This function check if the filter is active
    *
    * @return boolean
    */
    public function is_active()
    {
        return ($this->_values != '');
    }


    /**
    * This function get the tag of the filter
    *
    * @return array
    */
    public function get_tag_params($conn)
    {
        $__hids_status = Filter_list::get_filter_legends('hids');

        $tags  = array();

        $id    = $this->_id . '###' . $this->_values;
        $label = _('HIDS Status:') . ' ' . $__hids_status[$this->_values];

        $tags[$id] = $label;

        return $tags;
    }
}


/**
 * Filter_plugin Class
 *
 * Class for managing an asset filter of plugin
 *
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 */
class Filter_plugin extends Asset_filter
{
    /**
     * This function set the default value of the filter
     *
     * @return void
     */
    protected function _set_default_value()
    {
        $this->_values = array();
    }


    /**
     * This function sets a single filter value
     *
     * @param  $value   Filter Value
     * @param  $delete  Delete Filter Flag
     *
     * @throws Exception  If the validation of the value to modify is incorrect
     *
     * @return void
     */
    protected function _set_value($value, $delete = FALSE)
    {
        ossim_valid($value,     OSS_INPUT, OSS_NULLABLE,      'illegal:' . _('Filter Value'));

        if (ossim_error())
        {
            $exp_msg = _('Error').': '.ossim_get_error();
            ossim_clean_error();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($delete)
        {
            unset($this->_values[$value]);
        }
        else
        {
            $this->_values[$value] = $value;
        }
    }


    /**
     * This function gets the sql value of a filter
     *
     * @param  boolean  $reload  Flag to force non-default value
     *
     * @return string
     */
    protected function _get_sql_value($load_default)
    {
        /*
         This filter type is list so we need the field "value".
        Value example: 'Linux/2.6X, Windows Vista'.
        The default value is empty.
        */
        $r_value = '';

        if (!$load_default && $this->is_active())
        {
            //We put all the cpe's values separated by commas
            $r_value = implode(',', $this->_values);
        }

        return $r_value;
    }


    /**
     * This function check if the filter is active
     *
     * @return boolean
     */
    public function is_active()
    {
        return (count($this->_values) > 0);
    }


    /**
     * This function get the tag of the filter
     *
     * @return array
     */
    public function get_tag_params($conn)
    {
        $tags  = array();

        foreach ($this->_values as $val)
        {
            $id = $this->_id . '###' . $val;
            
            if ($val == 0)
            {
                $name = _('No Plugin Enabled');
            }
            else
            {
                try
                {
                    $name = ucwords(Plugin::get_name_by_id($conn, $val));
                }
                catch(Exception $e)
                {
                    $name = '';
                }
            }
            
            $name  = ($name == '') ? _('Unknown') : $name;
            
            $label = _('Plugin:') . ' ' . $name;
            
            $tags[$id] = $label;
        }

        return $tags;
    }
}
