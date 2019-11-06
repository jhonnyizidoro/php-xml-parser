<?php

class XMLParser
{
    private static $instance = null;
    private static $xmlArray = false;

    public static function parse($xmlString)
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        try {
            $document = new DOMDocument;
            $document->loadXML($xmlString);
            $rootElement = $document->documentElement;
            $output = self::nodeToArray($rootElement);
            $output['@root'] = $rootElement->tagName;
            self::$xmlArray = $output;
        } finally {
            return self::$instance;
        }
    }

    public static function nodeToArray($node)
    {
        $output = [];
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = self::nodeToArray($child);
                    if (isset($child->tagName)) {
                        $tagName = $child->tagName;
                        $tagName = explode(':', $tagName);
                        $tagName = isset($tagName[1]) ? $tagName[1] : $tagName[0];
                        if (!isset($output[$tagName])) {
                            $output[$tagName] = [];
                        }
                        $output[$tagName][] = $v;
                    } elseif ($v || $v === '0') {
                        $output = (string)$v;
                    }
                }
                if ($node->attributes->length && !is_array($output)) {
                    $output = ['@content' => $output];
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $a = [];
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string)$attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $tagName => $v) {
                        if (is_array($v) && count($v) === 1 && $tagName !== '@attributes') {
                            $output[$tagName] = $v[0];
                        }
                    }
                }
                break;
        }
        return $output;
    }

    public function toArray()
    {
        return self::$xmlArray;
    }

    public function toObject()
    {
        if (self::$xmlArray) {
            return self::arrayToObject(self::$xmlArray);
        }
        return false;
    }

    public function arrayToObject($array)
    {
        $object = new stdClass;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (sizeof($value) > 0) {
                    $value = self::arrayToObject($value);
                } else {
                    $value = null;
                }
            }
            $object->{$key} = $value;
        }
        return $object;
    }
}