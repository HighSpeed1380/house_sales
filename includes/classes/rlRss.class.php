<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLRSS.CLASS.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2022 | All copyrights reserved.
 *  
 *  https://www.flynax.com/
 ******************************************************************************/

class rlRss extends reefless
{
    /**
     * @var items list
     **/
    public $items = array('title', 'link', 'description');

    public $items_number = 5;
    public $mXmlParser = null;
    public $mLevel = null;
    public $mTag = null;
    public $mKey = null;
    public $mItem = false;
    public $mRss = array();

    /**
     * clear data
     **/
    public function clear()
    {
        $this->mXmlParser = null;
        $this->mLevel = null;
        $this->mTag = null;
        $this->mKey = null;
        $this->mItem = false;
        $this->mRss = array();
    }

    /**
     * start element for parser
     *
     * @param string $parser - parser object
     * @param string $name - item name
     *
     **/
    public function startElement($parser, $name)
    {
        $this->mLevel++;
        $this->mTag = strtolower($name);

        if ('item' == $this->mTag) {
            $this->mItem = true;
            $this->mKey++;
        }
    }

    /**
     * end element for parser
     *
     * @param string $parser - parser object
     * @param string $name - item name
     *
     **/
    public function endElement($parser, $name)
    {
        $this->mLevel--;

        if ('item' == $this->mTag) {
            $this->mItem = false;
        }
    }

    /**
     * data collection
     *
     * @param string $parser - parser object
     * @param string $data - item data
     *
     **/
    public function charData($parser, $data)
    {
        if ($this->mKey <= $this->items_number) {
            $data = trim($data);

            $items = $this->items;
            foreach ($items as $item) {
                if ($item == $this->mTag && $this->mItem) {
                    if (!empty($data)) {
                        $this->mRss[$this->mKey][$item] = $data;
                    }
                }
            }
        }
    }

    /**
     * create parser
     *
     * @param string $content - content data
     *
     **/
    public function createParser($content)
    {
        require_once RL_LIBS . 'saxyParser' . RL_DS . 'xml_saxy_parser.php';

        $this->mXmlParser = new SAXY_Parser();
        $this->mXmlParser->xml_set_element_handler(array(&$this, "startElement"), array(&$this, "endElement"));
        $this->mXmlParser->xml_set_character_data_handler(array(&$this, "charData"));
        $this->mXmlParser->parse($content);
    }

    /**
     * get RSS content
     *
     **/
    public function getRssContent()
    {
        return $this->mRss;
    }
}
