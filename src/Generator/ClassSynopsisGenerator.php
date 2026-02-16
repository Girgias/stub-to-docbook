<?php

namespace Girgias\StubToDocbook\Generator;

use Dom\Element;
use Dom\XMLDocument;

final class ClassSynopsisGenerator
{
    private const NS = 'http://docbook.org/ns/docbook';

    /**
     * Generate a <classsynopsis> element for a class.
     *
     * @param list<array{name: string, type: string, visibility: string}> $constants
     * @param list<array{name: string, type: string, visibility: string}> $properties
     * @param list<string> $methodNames
     * @param list<string> $implements
     */
    public static function generateClassSynopsis(
        XMLDocument $document,
        string $className,
        string $synopsisType = 'class',
        ?string $extends = null,
        array $implements = [],
        array $constants = [],
        array $properties = [],
        array $methodNames = [],
        bool $isFinal = false,
        bool $isAbstract = false,
    ): Element {
        $synopsis = $document->createElementNS(self::NS, 'classsynopsis');
        $synopsis->setAttribute('class', $synopsisType);

        // ooclass
        $ooclass = $document->createElementNS(self::NS, 'ooclass');
        if ($isFinal) {
            $mod = $document->createElementNS(self::NS, 'modifier');
            $mod->textContent = 'final';
            $ooclass->append("\n   ", $mod);
        }
        if ($isAbstract) {
            $mod = $document->createElementNS(self::NS, 'modifier');
            $mod->textContent = 'abstract';
            $ooclass->append("\n   ", $mod);
        }
        $nameTag = $synopsisType === 'enum' ? 'enumname' : 'classname';
        $nameElement = $document->createElementNS(self::NS, $nameTag);
        $nameElement->textContent = $className;
        $ooclass->append("\n   ", $nameElement, "\n  ");
        $synopsis->append("\n  ", $ooclass);

        // extends
        if ($extends !== null) {
            $extendsInfo = $document->createElementNS(self::NS, 'classsynopsisinfo');
            $extendsInfo->setAttribute('role', 'comment');
            $extendsClass = $document->createElementNS(self::NS, 'classname');
            $extendsClass->textContent = $extends;
            $extendsInfo->append('extends ', $extendsClass);
            $synopsis->append("\n\n  ", $extendsInfo);
        }

        // interfaces
        foreach ($implements as $interface) {
            $oointerface = $document->createElementNS(self::NS, 'oointerface');
            $ifaceName = $document->createElementNS(self::NS, 'interfacename');
            $ifaceName->textContent = $interface;
            $oointerface->append("\n   ", $ifaceName, "\n  ");
            $synopsis->append("\n  ", $oointerface);
        }

        // constants
        if (!empty($constants)) {
            $commentInfo = $document->createElementNS(self::NS, 'classsynopsisinfo');
            $commentInfo->setAttribute('role', 'comment');
            $commentInfo->textContent = 'Constants';
            $synopsis->append("\n\n  ", $commentInfo);

            foreach ($constants as $constant) {
                $field = self::generateFieldSynopsis($document, $constant['name'], $constant['type'], $constant['visibility'], isConstant: true, className: $className);
                $synopsis->append("\n  ", $field);
            }
        }

        // properties
        if (!empty($properties)) {
            $commentInfo = $document->createElementNS(self::NS, 'classsynopsisinfo');
            $commentInfo->setAttribute('role', 'comment');
            $commentInfo->textContent = 'Properties';
            $synopsis->append("\n\n  ", $commentInfo);

            foreach ($properties as $property) {
                $field = self::generateFieldSynopsis($document, $property['name'], $property['type'], $property['visibility']);
                $synopsis->append("\n  ", $field);
            }
        }

        // methods
        if (!empty($methodNames)) {
            $commentInfo = $document->createElementNS(self::NS, 'classsynopsisinfo');
            $commentInfo->setAttribute('role', 'comment');
            $commentInfo->textContent = 'Methods';
            $synopsis->append("\n\n  ", $commentInfo);

            foreach ($methodNames as $methodName) {
                $methodSynopsis = $document->createElementNS(self::NS, 'methodsynopsis');
                $methodSynopsis->setAttribute('role', $className);
                $methodNameEl = $document->createElementNS(self::NS, 'methodname');
                $methodNameEl->textContent = $className . '::' . $methodName;
                $methodSynopsis->append($methodNameEl);
                $synopsis->append("\n  ", $methodSynopsis);
            }
        }

        $synopsis->append("\n ");

        return $synopsis;
    }

    private static function generateFieldSynopsis(
        XMLDocument $document,
        string $name,
        string $type,
        string $visibility,
        bool $isConstant = false,
        ?string $className = null,
    ): Element {
        $field = $document->createElementNS(self::NS, 'fieldsynopsis');

        $modVis = $document->createElementNS(self::NS, 'modifier');
        $modVis->textContent = $visibility;
        $field->append("\n   ", $modVis);

        if ($isConstant) {
            $modConst = $document->createElementNS(self::NS, 'modifier');
            $modConst->textContent = 'const';
            $field->append("\n   ", $modConst);
        }

        $typeEl = $document->createElementNS(self::NS, 'type');
        $typeEl->textContent = $type;
        $field->append("\n   ", $typeEl);

        if ($isConstant) {
            $constEl = $document->createElementNS(self::NS, 'constant');
            $constEl->textContent = ($className ? $className . '::' : '') . $name;
            $field->append("\n   ", $constEl);
        } else {
            $varEl = $document->createElementNS(self::NS, 'varname');
            $varEl->textContent = $name;
            $field->append("\n   ", $varEl);
        }

        $field->append("\n  ");
        return $field;
    }
}
