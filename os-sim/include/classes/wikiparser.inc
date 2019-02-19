<?php
/**
* wikiparser.inc
*
* This class parses and returns the HTML representation of a document containing
* basic MediaWiki-style wiki markup.
*
*
* USAGE
*
* Refer to class_WikiRetriever.php (which uses this script to parse fetched
* wiki documents) for an example.
*
*
* LICENSE
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2.1 of the License, or (at your option) any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*
* @package    ossim-framework\Events
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2005, Steve Blinch
* @copyright  2007-2013 AlienVault
* @link       http://code.blitzaffe.com
*/

class Wikiparser
{
    function __construct()
    {
        $this->reference_wiki = '';
        $this->image_uri = '';
        $this->ignore_images = FALSE;
    }


    function handle_sections($matches)
    {
        $level = strlen($matches[1]);
        $content = $matches[2];

        $this->stop = TRUE;
        // avoid accidental run-on emphasis
        return $this->emphasize_off() . "\n\n<h{$level}>{$content}</h{$level}>\n\n";
    }


    function handle_newline()
    {
        if ($this->suppress_linebreaks)
        {
            return $this->emphasize_off();
        }

        $this->stop = TRUE;
        // avoid accidental run-on emphasis
        return $this->emphasize_off() . "<br>";
    }


    function handle_list($matches, $close = FALSE)
    {
        $listtypes = array(
            '*'=>'ul',
            '#'=>'ol',
        );

        $output   = "";

        $newlevel = ($close) ? 0 : strlen($matches[1]);


        while ($this->list_level!=$newlevel)
        {
            $listchar = substr($matches[1],-1);
            $listtype = $listtypes[$listchar];

            //$output .= "[".$this->list_level."->".$newlevel."]";

            if ($this->list_level>$newlevel)
            {
                $listtype = '/'.array_pop($this->list_level_types);
                $this->list_level--;
            }
            else
            {
                $this->list_level++;
                array_push($this->list_level_types,$listtype);
            }

            $output .= "\n<{$listtype}>\n";
        }

        if ($close)
        {
            return $output;
        }

        $output .= "<li>".$matches[2]."</li>\n";

        return $output;
    }


    function handle_definitionlist($matches, $close = FALSE)
    {
        if ($close)
        {
            $this->deflist = FALSE;

            return "</dl>\n";
        }


        $output = "";

        if (!$this->deflist)
        {
            $output .= "<dl>\n";
        }

        $this->deflist = TRUE;

        switch($matches[1])
        {
            case ';':
                $term = $matches[2];
                $p    = strpos($term,' :');

                if ($p !== FALSE)
                {
                    list($term,$definition) = explode(':', $term);

                    $output .= "<dt>{$term}</dt><dd>{$definition}</dd>";

                }
                else
                {
                    $output .= "<dt>{$term}</dt>";
                }

                break;

            case ':':
                $definition = $matches[2];
                $output    .= "<dd>{$definition}</dd>\n";

                break;
        }

        return $output;
    }


    function handle_preformat($matches, $close = FALSE)
    {
        if ($close)
        {
            $this->preformat = FALSE;

            return "</pre>\n";
        }

        $this->stop_all = TRUE;

        $output = "";

        if (!$this->preformat)
        {
            $output .= "<pre>";
        }

        $this->preformat = TRUE;

        $output .= $matches[1];

        return $output."\n";
    }


    function handle_horizontalrule()
    {
        return "<hr/>";
    }


    function wiki_link($topic)
    {
        return ucfirst(str_replace(' ', '_', $topic));
    }


    function handle_image($href, $title, $options)
    {
        if ($this->ignore_images)
        {
            return "";
        }

        $href = $this->image_uri . $href;

        $imagetag = sprintf(
            '<img src="%s" alt="%s" />',
            $href,
            $title
        );

        foreach ($options as $option)
        {
            switch($option)
            {
                case 'frame':
                    $imagetag = sprintf(
                        '<div style="float: right; background-color: #F5F5F5; border: 1px solid #D0D0D0; padding: 2px">'.
                        '%s'.
                        '<div>%s</div>'.
                        '</div>',
                        $imagetag,
                        $title
                    );

                    break;

                case 'right':
                    $imagetag = sprintf(
                        '<div style="float: right">%s</div>',
                        $imagetag
                    );

                    break;
            }
        }

        return $imagetag;
    }


    function handle_internallink($matches)
    {
        $nolink    = FALSE;

        $href      = $matches[4];
        $title     = $matches[6] ? $matches[6] : $href.$matches[7];
        $title     = trim($title);

        $namespace = $matches[3];

        $newwindow = FALSE;

        if ($namespace=='Image')
        {
            $options = explode('|',$title);
            $title = array_pop($options);

            return $this->handle_image($href,$title,$options);
        }

        $title = preg_replace('/\(.*?\)/','',$title);
        $title = preg_replace('/^.*?\:/','',$title);

        if ($this->reference_wiki)
        {
            $href = $this->reference_wiki.($namespace ? $namespace.':':'').$this->wiki_link($href);
        }
        else
        {
            $nolink = TRUE;
        }

        if ($nolink)
        {
            return $title;
        }

        return sprintf(
            '<a href="%s"%s>%s</a>',
            $href,
            ($newwindow ? ' target="_blank"':''),
            $title
        );
    }


    function handle_externallink($matches)
    {
        $href  = $matches[2];
        $title = trim($matches[4]);

        if (!$title)
        {
            $this->linknumber++;
            $title = "[{$this->linknumber}]";
        }

        $newwindow = TRUE;

        return sprintf(
            '<a href="%s"%s>%s</a>',
            $href,
            ($newwindow?' target="_blank"':''),
            $title
        );
    }


    function emphasize($amount)
    {
        $amounts = array(
            2 => array('<em>','</em>'),
            3 => array('<strong>','</strong>'),
            4 => array('<strong>','</strong>'),
            5 => array('<em><strong>','</strong></em>'),
        );

        $output = "";

        // handle cases where emphasized phrases end in an apostrophe, eg: ''somethin'''
        // should read <em>somethin'</em> rather than <em>somethin<strong>
        if ( (!$this->emphasis[$amount]) && ($this->emphasis[$amount-1]) )
        {
            $amount--;
            $output = "'";
        }

        $output .= $amounts[$amount][(int) $this->emphasis[$amount]];

        $this->emphasis[$amount] = !$this->emphasis[$amount];

        return $output;
    }


    function handle_emphasize($matches)
    {
        $amount = strlen($matches[1]);

        return $this->emphasize($amount);

    }


    function emphasize_off()
    {
        $output = "";
        $this->emphasis = (empty($this->emphasis)) ? array() : $this->emphasis;

        foreach ($this->emphasis as $amount=>$state)
        {
            if ($state)
            {
                $output .= $this->emphasize($amount);
            }
        }

        return $output;
    }


    function handle_eliminate()
    {
        return "";
    }


    function handle_variable($matches)
    {
        switch($matches[2])
        {
            case 'CURRENTMONTH': return date('m');
            case 'CURRENTMONTHNAMEGEN':
            case 'CURRENTMONTHNAME': return date('F');
            case 'CURRENTDAY': return date('d');
            case 'CURRENTDAYNAME': return date('l');
            case 'CURRENTYEAR': return date('Y');
            case 'CURRENTTIME': return date('H:i');
            case 'NUMBEROFARTICLES': return 0;
            case 'PAGENAME': return $this->page_title;
            case 'NAMESPACE': return 'None';
            case 'SITENAME': return $_SERVER['HTTP_HOST'];
            default: return '';
        }
    }


    function parse_line($line)
    {
        $line_regexes = array(
            //'preformat'=>'^\s(.*?)$',
            'definitionlist' => '^([\;\:])\s*(.*?)$',
            'newline' => '^$',
            'list' => '^([\*\#]+)(.*?)$',
            'sections' => '^(={1,6})(.*?)(={1,6})$',
            'horizontalrule' => '^----$',
        );

        $char_regexes = array(
            //'link'=>'(\[\[((.*?)\:)?(.*?)(\|(.*?))?\]\]([a-z]+)?)',
            'internallink' => '('.
                '\[\['. // opening brackets
                    '(([^\]]*?)\:)?'. // namespace (if any)
                    '([^\]]*?)'. // target
                    '(\|([^\]]*?))?'. // title (if any)
                '\]\]'. // closing brackets
                '([a-z]+)?'. // any suffixes
                ')',
            'externallink' => '('.
                '\['.
                    '((https?:\/)?\/[^\]\s]+)'.
                    '(\s+[^\]]*?)?'.
                '\]'.
                ')',
            'emphasize' => '(\'{2,5})',
            'eliminate' => '(__TOC__|__NOTOC__|__NOEDITSECTION__)',
            'variable' => '('. '\{\{' . '([^\}]*?)' . '\}\}' . ')',
        );

        $this->stop     = FALSE;
        $this->stop_all = FALSE;

        $called = array();

        $line   = rtrim($line);

        foreach ($line_regexes as $func => $regex)
        {
            if (preg_match("/$regex/i", $line, $matches))
            {
                $called[$func] = TRUE;

                $func = "handle_".$func;
                $line = $this->$func($matches);

                if ($this->stop || $this->stop_all)
                {
                    break;
                }

            }
        }

        if (!$this->stop_all)
        {
            $this->stop = FALSE;

            foreach ($char_regexes as $func => $regex)
            {
                $line = preg_replace_callback("/$regex/i", array(&$this,"handle_".$func), $line);

                if ($this->stop)
                {
                    break;
                }
            }
        }

        $isline = strlen(trim($line))>0;

        // if this wasn't a list item, and we are in a list, close the list tag(s)
        if (($this->list_level>0) && !$called['list'])
        {
            $line = $this->handle_list(FALSE, TRUE) . $line;
        }

        if ($this->deflist && !$called['definitionlist'])
        {
            $line = $this->handle_definitionlist(FALSE, TRUE) . $line;
        }

        if ($this->preformat && !$called['preformat'])
        {
            $line = $this->handle_preformat(FALSE, TRUE) . $line;
        }

        // suppress linebreaks for the next line if we just displayed one; otherwise re-enable them
        if ($isline)
        {
            $this->suppress_linebreaks = ($called['newline'] || $called['sections']);
        }

        if(!$called['list'])
        {
            if(!preg_match("<br>", $line))
            {
                $line.='<br>';
            }
        }

        return $line;
    }


    function parse($text, $title = "")
    {
        $this->redirect            = FALSE;

        $this->nowikis             = array();
        $this->list_level_types    = array();
        $this->list_level          = 0;

        $this->deflist             = FALSE;
        $this->linknumber          = 0;
        $this->suppress_linebreaks = FALSE;

        $this->page_title          = $title;

        $output = "";

        $text  = preg_replace("/<br>/", "\n", $text);
        $text  = preg_replace("/<b>|<\/b>/", "'''", $text);

        $text   = Util::htmlentities($text, ENT_NOQUOTES);
        $text   = preg_replace("/&lt;(\/)?(pre|code|strike)&gt;/", "<\\1\\2>", $text);
        $text   = preg_replace("/\n/","<br>", $text);
        $text   = preg_replace_callback('/<nowiki>([\s\S]*)<\/nowiki>/i', array(&$this, "handle_save_nowiki"), $text);

        $lines = explode("<br>", $text);


        if (preg_match('/^\#REDIRECT\s+\[\[(.*?)\]\]$/', trim($lines[0]), $matches))
        {
            $this->redirect = $matches[1];
        }

        foreach ($lines as $line)
        {
            $line    = $this->parse_line($line);
            $output .= $line;
        }

        $output = preg_replace_callback('/<nowiki><\/nowiki>/i', array(&$this, "handle_restore_nowiki"), $output);

        $output = preg_replace('/\A(<br>)+/', '', $output);
        $output = preg_replace('/<hr\/><br>/', '<hr>', $output);
        //$output = preg_replace('/(<br>){3,}/', '<br><br>', $output);
        $output = preg_replace('/(<br>)+\z/', '', $output);

        return $output;
    }


    function handle_save_nowiki($matches)
    {
        array_push($this->nowikis, $matches[1]);

        return "<nowiki></nowiki>";
    }


    function handle_restore_nowiki()
    {
        return array_pop($this->nowikis);
    }
}

/* End of file wikiparser.inc */
/* Location: ../include/classes/wikiparser.inc */
