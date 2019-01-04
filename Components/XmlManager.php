<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

/**
 * @abstract    Xml Encoding & Decoding Functions Collector Class
 * @author      B. Paquier <contact@splashsync.com>
 */

namespace   Splash\Components;

use ArrayObject;
use Exception;
use SimpleXMLElement;
use stdClass;
use XMLWriter;

//====================================================================//
//  CLASS DEFINITION
//====================================================================//

class XmlManager
{
    // Fault String
    public $fault;

    /**
     *      @abstract   XMLWriter Class
     *      @var        XMLWriter
     *      @static
     */
    private static $xml;

    /**
     * @abstract      Class Constructor
     */
    public function __construct()
    {
        //====================================================================//
        // Initialize XML Parser
        self::$xml = new XmlWriter();
        self::$xml->openMemory();
        self::$xml->startDocument('1.0');
        self::$xml->setIndent(true);
    }

    //====================================================================//
    //  XML Parser High Level User Functions
    //====================================================================//

    /**
     * @abstract     Method to convert Object into XML string
     * @param        array      $obj
     * @return       string
     */
    public function objectToXml($obj)
    {
        //====================================================================//
        // Create Transmition Object
        $tmp = new stdClass();
        $tmp->SPLASH    =   $obj;
        //====================================================================//
        // Convert Object to XML Recursively
        $this->objectToXmlCore(self::$xml, $tmp);
        //====================================================================//
        // Put End Element on Xml
        self::$xml->endElement();
        //====================================================================//
        // Output Result
        return self::$xml->outputMemory(true);
    }
    
    /**
     * @abstract     Method to convert XML string into ArrayObject
     * @param        string     $xml
     * @return       ArrayObject|false|string
     */
    public function xmlToArrayObject($xml)
    {
        //====================================================================//
        // SimpleXMLElement Object to Array
        return  self::simpleXmlToArrayObject($this->xmlToElements($xml));
    }

    /**
     * @abstract     Method to convert XML string into Array
     * @param        string     $xml
     * @return      array|false|string
     */
    protected function xmlToArray($xml)
    {
        //====================================================================//
        // SimpleXMLElement Object to Array
        return self::simpleXmlToArray($this->xmlToElements($xml));
    }

    //====================================================================//
    //  XML Parser Low Level Core Functions
    //====================================================================//
    
    /**
     * @abstract     Method to convert XML string into SimpleXmlElement Object
     * @param        string                 $xml
     * @return       false|SimpleXMLElement
     */
    private function xmlToElements($xml)
    {
        //====================================================================//
        // Convert XML to Object Recursively
        try {
            $result = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOERROR);
        } catch (Exception $ex) {
            $this->fault = $ex->getMessage();

            return false;
        }

        return $result;
    }
    
    /**
     * @abstract     Recursive Method to Object  XML string
     * @param        XMLWriter  $xml
     * @param        mixed      $object
     * @return       array      $result
     */
    private function objectToXmlCore(XMLWriter $xml, $object)
    {
        //====================================================================//
        // Read each entitie of an object
        foreach ($object as $key => $value) {
            //====================================================================//
            // Insert Object
            //====================================================================//
            if (is_object($value)) {
                //====================================================================//
                // Rename Numeric Keys
                $key = self::keysFilter($key, 'object-');
                //====================================================================//
                // Recurcive Add Of This Object
                $xml->startElement($key);
                $this->objectToXmlCore($xml, $value);
                $xml->endElement();

                continue;
                //====================================================================//
            // Insert Array
            }
            if (is_array($value)) {
                //====================================================================//
                // Rename Numeric Keys
                $key = self::keysFilter($key, 'array-');
                //====================================================================//
                // Safety Check
                if (true != $xml->startElement($key)) {
                    $this->fault = "Xml Parser - Wrong StartElement Key "
                            . ": " . print_r($key, true) . " Value : " . print_r($value, true);
                }
                //====================================================================//
                // Recurcive Add Of This Array
                $this->objectToXmlCore($xml, $value);
                $xml->endElement();

                continue;
                //====================================================================//
            // Insert String
            //====================================================================//
            }
            if (is_string($value)) {
                //====================================================================//
                // Rename Numeric Keys
                $key = self::keysFilter($key, 'string-');
            
            //====================================================================//
            // Insert Numeric
            } elseif (is_numeric($value)) {
                //====================================================================//
                // Rename Numeric Keys
                $key = self::keysFilter($key, 'int-');
            
            //====================================================================//
            // Insert Boolean
            } elseif (is_bool($value)) {
                //====================================================================//
                // Rename Numeric Keys
                $key = self::keysFilter($key, 'bool-');
                //====================================================================//
                // Convert Value To String
                $value = $value?"1":"0";
            }
            //====================================================================//
            // Insert Data
            $xml->writeElement($key, base64_encode($value));
        }
    }

    /**
     * @abstract     Filter XML Key to remove single numeric keys by adding a prefix
     * @param        string     $key
     * @param        string     $prefix
     * @return       string     $result
     */
    private static function keysFilter($key, $prefix)
    {
        //====================================================================//
        // Convert Numeric Keys with Prefix
        if (is_numeric($key)) {
            return $prefix . $key;
            //====================================================================//
        // Keap Scalar Keys
        }
  
        return $key;
    }
    
    /**
     * @abstract    Convert a SimpleXML object to an Array
     * @param       false|SimpleXMLElement    $element
     * @return      array|false|string
     */
    private static function simpleXmlToArray($element)
    {
        //====================================================================//
        // Safety Check
        if (false === $element) {
            return false;
        }
        //====================================================================//
        // Init Result
        $result         =   array();
        //====================================================================//
        // Get First Level Childrens
        $children       =   $element->children();
        $isArrayElement =   false;
        //====================================================================//
        // For All Childrens
        foreach ($children as $elementName => $node) {
            //====================================================================//
            // This Element is an ArrayElement
            $isArrayElement = true;
            //====================================================================//
            // If Element Doesn't Already Exists => Store as Single Element
            if (!isset($result[$elementName])) {
                $result[$elementName]    = array();
                $result[$elementName]    = self::simpleXmlToArray($node);

                continue;
            }
            //====================================================================//
            // Element Already Exists => Store as Array Element
            //====================================================================//
            // Convert Single Element to Array Element
            if (!is_array($result[$elementName])) {
                $singleElement          =   $result[$elementName];       // Store Firts Element
                $result[$elementName]    =   array();                    // Create New Array
                $result[$elementName][]  =   $singleElement;             // Append To Array
            }
            //====================================================================//
            // Append Array Element
            $result[$elementName][] = self::simpleXmlToArray($node);
        }
        //====================================================================//
        // Return Single Element
        if (!$isArrayElement && '' == $children->getName()) {
            $result =  base64_decode((string) $element, true);
        }

        return $result;
    }
    
    /**
     * @abstract    Convert a SimpleXML object to an ArrayObject
     * @param       false|SimpleXMLElement    $element
     * @return      ArrayObject|false|string
     */
    private static function simpleXmlToArrayObject($element)
    {
        //====================================================================//
        // Safety Check
        if (false === $element) {
            return false;
        }
        //====================================================================//
        // Init Result
        $result             =   new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
        //====================================================================//
        // Get First Level Childrens
        $children           =   $element->children();
        $isArrayElement     =   false;
        //====================================================================//
        // For All Childrens
        foreach ($children as $elementName => $node) {
            //====================================================================//
            // This Element is an ArrayElement
            $isArrayElement = true;
            //====================================================================//
            // If Element Doesn't Already Exists => Store as Single Element
            if (!isset($result[$elementName])) {
                $result[$elementName]    = array();
                $result[$elementName]    = self::simpleXmlToArrayObject($node);

                continue;
            }
            //====================================================================//
            // Element Already Exists => Store as Array Element
            //====================================================================//
            // Convert Single Element to Array Element
            if (!is_array($result[$elementName])) {
                $singleElement           =   $result[$elementName];       // Store First Element
                $result[$elementName]    =   array();                    // Create New Array
                $result[$elementName][]  =   $singleElement;             // Append To Array
            }
            //====================================================================//
            // Append Array Element
            $result[$elementName][] = self::simpleXmlToArrayObject($node);
        }
        //====================================================================//
        // Return Single Element
        if (!$isArrayElement && '' == $children->getName()) {
            $result =  base64_decode((string) $element, true);
        }

        return $result;
    }
}
