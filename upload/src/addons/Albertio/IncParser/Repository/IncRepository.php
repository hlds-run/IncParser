<?php

namespace Albertio\IncParser\Repository;

use XF\Mvc\Entity\Repository;
use Albertio\IncParser\Parser\IncParser;
use Albertio\IncParser\Parser\PawnHighlighter;

class IncRepository extends Repository
{
    protected function path(): string
    {
        return __DIR__ . '/../include/';
    }

    public function getFileList(): array
    {
        $files = [];

        foreach (glob($this->path() . '*.inc') as $file) {
            $files[] = basename($file, ".inc");
        }

        sort($files);
        return $files;
    }

    public function getFileItems(string $name): array
    {
        $path = $this->path() . $name . '.inc';

        if (!is_file($path)) {
            return [];
        }

        $parser = new IncParser();
        $items = $parser->parse(file_get_contents($path));

        $hl = new PawnHighlighter();
        $this->highlightItems($items, $hl);

        return $items;
    }

    protected function highlightItems(array &$items, PawnHighlighter $hl): void
    {
        foreach ($items as &$item) {
            if ($item["kind"] === "section") {
                $this->highlightItems($item["items"], $hl);
                continue;
            }

            if (!empty($item["signature"])) {
                if (is_array($item["signature"])) {
                    $item["highlighted"] = array_map(
                        fn($line) => $hl->highlight($line),
                        $item["signature"]
                    );
                } else {
                    $item["highlighted"] = $hl->highlight($item["signature"]);
                }
            } else {
                $item["highlighted"] = null;
            }

            if (!empty($item["body"])) {
                $item["highlightedBody"] = array_map(
                    fn($line) => $hl->highlight($line),
                    preg_split("/\R/", $item["body"])
                );
            }
        }
    }

    public function getFileListWithItems(): array
    {
        $out = [];

        foreach ($this->getFileList() as $file) {
            $items = $this->getFileItems($file);

            $names = [];

            foreach ($items as $item) {
                $names[] = [
                    "id" => $item["id"],
                    "name" =>
                        $item["kind"] === "section"
                            ? $item["title"]
                            : $item["id"],
                    "kind" => $item["kind"],
                    "signature" => $item["signature"] ?? null,
                ];
            }

            usort($names, fn($a, $b) => strcasecmp($a["name"], $b["name"]));

            $out[] = [
                "file" => $file,
                "items" => $names,
            ];
        }

        return $out;
    }

    public function searchItems(array $items, string $query): array
    {
        $query = mb_strtolower(trim($query));

        if ($query === "") {
            return $items;
        }

        $result = [];

        foreach ($items as $file) {
            if (empty($file["items"]) || !is_array($file["items"])) {
                continue;
            }

            $matchedItems = [];

            foreach ($file["items"] as $item) {
                $haystack = $this->buildSearchHaystack($item);

                if (mb_stripos($haystack, $query) !== false) {
                    $matchedItems[] = $item;
                }
            }

            if (!empty($matchedItems)) {
                $file["items"] = $matchedItems;
                $result[] = $file;
            }
        }

        return $result;
    }

    protected function buildSearchHaystack(array $item): string
    {
        $parts = [];

        if (!empty($item["id"])) {
            $parts[] = $item["id"];
        }

        if (!empty($item["signature"])) {
            if (is_array($item["signature"])) {
                $parts[] = implode(" ", $item["signature"]);
            } else {
                $parts[] = $item["signature"];
            }
        }

        if (!empty($item["body"])) {
            if (is_array($item["body"])) {
                $parts[] = implode(" ", $item["body"]);
            } else {
                $parts[] = $item["body"];
            }
        }

        if (!empty($item["doc"]) && is_array($item["doc"])) {
            foreach ($item["doc"] as $key => $value) {

                if (is_string($value)) {
                    $parts[] = $value;
                    continue;
                }

                if (is_array($value)) {
                    foreach ($value as $entry) {
                        if (is_string($entry)) {
                            $parts[] = $entry;
                            continue;
                        }

                        if (is_array($entry)) {
                            if (isset($entry["name"])) {
                                $parts[] = $entry["name"];
                            }
                            if (isset($entry["desc"])) {
                                if (is_array($entry["desc"])) {
                                    $parts[] = implode(" ", $entry["desc"]);
                                } else {
                                    $parts[] = $entry["desc"];
                                }
                            }
                        }
                    }
                }
            }
        }

        return mb_strtolower(implode(" ", $parts));
    }
}