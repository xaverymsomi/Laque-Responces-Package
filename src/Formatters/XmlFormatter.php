<?php

declare(strict_types=1);

namespace LaqueResponses\Formatters;

use LaqueResponses\Contracts\ResponseFormatterInterface;
use LaqueResponses\Support\ArrayUtils;
use RuntimeException;
use DOMDocument;
use DOMElement;

/**
 * Formats data as XML
 */
final class XmlFormatter implements ResponseFormatterInterface
{
    /**
     * @param string $rootElementName Name of the root XML element
     * @param string $encoding XML document encoding
     */
    public function __construct(
        private string $rootElementName = 'response',
        private string $encoding = 'UTF-8'
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function contentType(): string
    {
        return 'application/xml';
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException If XML generation fails
     */
    public function format(array|object|string|int|float|bool|null $payload): string
    {
        // Create XML document
        $document = new DOMDocument('1.0', $this->encoding);
        $document->formatOutput = true;
        
        // Prevent XXE attacks by disabling external entities
        $document->substituteEntities = false;
        
        // Create root element
        $root = $document->createElement($this->rootElementName);
        $document->appendChild($root);
        
        // Convert objects to arrays recursively
        if (is_object($payload)) {
            $payload = ArrayUtils::objectToArray($payload);
        }
        
        // Add payload to XML
        $this->addXmlElement($document, $root, $payload);
        
        // Return XML string
        $xml = $document->saveXML();
        
        if ($xml === false) {
            throw new RuntimeException('XML generation failed');
        }
        
        return $xml;
    }
    
    /**
     * Add an element to the XML document
     * 
     * @param DOMDocument $document
     * @param DOMElement $parent
     * @param mixed $data
     * @param string|null $key
     * @return void
     */
    private function addXmlElement(DOMDocument $document, DOMElement $parent, mixed $data, ?string $key = null): void
    {
        if (is_array($data)) {
            // Handle numeric arrays vs associative arrays differently
            if (ArrayUtils::isAssociative($data)) {
                // Associative array becomes child elements
                if ($key !== null) {
                    $element = $document->createElement($this->sanitizeElementName($key));
                    $parent->appendChild($element);
                    
                    foreach ($data as $childKey => $childValue) {
                        $this->addXmlElement($document, $element, $childValue, (string) $childKey);
                    }
                } else {
                    // Direct child of parent with no wrapper
                    foreach ($data as $childKey => $childValue) {
                        $this->addXmlElement($document, $parent, $childValue, (string) $childKey);
                    }
                }
            } else {
                // Numeric array becomes repeated elements
                if ($key !== null) {
                    // For numeric arrays, use singular form of parent as item name
                    $itemName = $this->getSingular($key);
                    
                    foreach ($data as $item) {
                        $this->addXmlElement($document, $parent, $item, $itemName);
                    }
                } else {
                    // Default item name if no key provided
                    $itemName = 'item';
                    
                    foreach ($data as $item) {
                        $this->addXmlElement($document, $parent, $item, $itemName);
                    }
                }
            }
        } else {
            // Simple value becomes element with value
            if ($key !== null) {
                $element = $document->createElement($this->sanitizeElementName($key));
                $parent->appendChild($element);
                
                if ($data !== null) {
                    // Convert to string and add as text node
                    $textNode = $document->createTextNode($this->formatValue($data));
                    $element->appendChild($textNode);
                }
            } else {
                // Direct value with no key - unlikely but handled
                $textNode = $document->createTextNode($this->formatValue($data));
                $parent->appendChild($textNode);
            }
        }
    }
    
    /**
     * Format a scalar value for XML
     * 
     * @param mixed $value
     * @return string
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        return (string) $value;
    }
    
    /**
     * Sanitize a string to be a valid XML element name
     * 
     * @param string $name
     * @return string
     */
    private function sanitizeElementName(string $name): string
    {
        // XML element names must start with a letter or underscore
        if (!preg_match('/^[a-z_]/i', $name)) {
            $name = 'item_' . $name;
        }
        
        // Replace invalid characters with underscores
        return preg_replace('/[^a-z0-9_\-\.]/i', '_', $name);
    }
    
    /**
     * Get singular form of a word (very simple implementation)
     * 
     * @param string $word
     * @return string
     */
    private function getSingular(string $word): string
    {
        // Very basic singularization rules
        if (str_ends_with($word, 'ies')) {
            return substr($word, 0, -3) . 'y';
        }
        
        if (str_ends_with($word, 's')) {
            return substr($word, 0, -1);
        }
        
        return $word;
    }
}