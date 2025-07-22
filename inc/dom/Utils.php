<?php

namespace GetPattern\DOM;

class Utils
{
    public static function loadDom(string $html): \DOMDocument
    {
        $dom = new \DOMDocument();
        $dom->loadHTML($html);

        return $dom;
    }
    public static function saveDom(\DOMDocument $dom): string
    {
        return $dom->saveHTML();
    }
    public static function newDom(): \DOMDocument
    {
        $dom = new \DOMDocument();
        $dom->formatOutput = true;
        return $dom;
    }
    public static function findComments(\DOMNode $node, string $commentName): array
    {
        $startNode = null;
        $endNode = null;

        $iterator = new \RecursiveIteratorIterator(
            new class($node) implements \RecursiveIterator {
                private int $position = 0;
                private array $nodeList = [];

                public function __construct(\DOMNode $domNode)
                {
                    if ($domNode->hasChildNodes()) {
                        foreach ($domNode->childNodes as $childNode) {
                            $this->nodeList[] = $childNode;
                        }
                    }
                }

                public function rewind(): void
                {
                    $this->position = 0;
                }
                public function valid(): bool
                {
                    return isset($this->nodeList[$this->position]);
                }
                public function key(): int
                {
                    return $this->position;
                }
                public function current(): \DOMNode
                {
                    return $this->nodeList[$this->position];
                }
                public function next(): void
                {
                    ++$this->position;
                }

                public function hasChildren(): bool
                {
                    return $this->current()->hasChildNodes();
                }

                public function getChildren(): self
                {
                    return new self($this->current());
                }
            },
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $child) {
            if ($child instanceof \DOMComment) {
                $text = trim($child->nodeValue);
                if ($text === $commentName) {
                    $startNode = $child;
                } elseif ($text === 'end-' . $commentName) {
                    $endNode = $child;
                    break;
                }
            }
        }

        return [$startNode, $endNode];
    }
}
