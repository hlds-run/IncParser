<?php

namespace Albertio\IncParser\Parser;

class IncParser
{
    protected array $items = [];

    protected ?array $pendingDoc = null;
    protected ?array $activeSection = null;

    public function parse(string $content): array
    {
        $this->items = [];
        $this->pendingDoc = null;
        $this->activeSection = null;

        $lines = preg_split("/\R/", $content);
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $line = rtrim($lines[$i]);

            if (preg_match("/^\s*\/\*\*/", $line)) {
                $doc = [];
                $i++;

                while ($i < $count && !preg_match("/\*\//", $lines[$i])) {
                    $doc[] = $lines[$i];
                    $i++;
                }

                $this->pendingDoc = $this->parseDocBlock(implode("\n", $doc));

                if ($this->pendingDoc && $this->pendingDoc["section"]) {
                    if ($this->activeSection) {
                        $this->items[] = $this->activeSection;
                    }

                    $this->activeSection = [
                        "kind" => "section",
                        "id" => preg_replace(
                            "/[^a-z0-9]+/i",
                            "-",
                            strtolower($this->pendingDoc["section"])
                        ),
                        "title" => $this->pendingDoc["section"],
                        "doc" => $this->pendingDoc,
                        "items" => [],
                    ];
                    $this->pendingDoc = null;
                    continue;
                }

                if ($this->pendingDoc && $this->pendingDoc["endsection"]) {
                    if ($this->activeSection) {
                        $this->items[] = $this->activeSection;
                        $this->activeSection = null;
                    }
                    $this->pendingDoc = null;
                    continue;
                }

                continue;
            }

            if (
                trim($line) === "" ||
                str_starts_with(trim($line), "#include") ||
                str_starts_with(trim($line), "#if") ||
                str_starts_with(trim($line), "#endif") ||
                str_ends_with(trim($line), "_included")
            ) {
                continue;
            }

            if (preg_match("/^\s*#define\s+/", $line)) {
                $lines[$i] = ltrim($lines[$i]);

                $group = [];

                while (
                    $i < $count &&
                    preg_match("/^\s*#define\s+/", $lines[$i])
                ) {
                    $group[] = rtrim($lines[$i]);
                    $i++;
                }
                $i--;

                $str = $group[0];
                $sub = substr($str, 8);
                $pos = strpos($sub, " ");
                $result = $pos !== false ? substr($sub, 0, $pos) : $sub;

                $this->addItem([
                    "kind" => "define",
                    "id" => $result,
                    "signature" => $group,
                    "doc" => $this->pendingDoc ?? [],
                ]);

                $this->pendingDoc = null;
                continue;
            }

            if (preg_match("/^\s*enum\b/", $line)) {
                $enumLines = [$line];
                $brace = 0;

                while ($i + 1 < $count && !str_contains($line, "{")) {
                    $i++;
                    $line = rtrim($lines[$i]);
                    $enumLines[] = $line;

                    if (str_contains($line, "{")) {
                        break;
                    }
                }

                $brace += substr_count($line, "{") - substr_count($line, "}");

                while ($i + 1 < $count && $brace > 0) {
                    $i++;
                    $line = rtrim($lines[$i]);

                    $trim = ltrim($line);

                    if ($trim !== "{" && $trim !== "}" && $trim !== "};") {
                        $line = "    " . $trim;
                    }

                    $enumLines[] = $line;

                    $brace +=
                        substr_count($line, "{") - substr_count($line, "}");
                }

                if ($enumLines[0] === "enum") {
                    $str = $enumLines[2];
                    $sub = substr($str, 4);
                    $pos = strpos($sub, " ");
                    $result = $pos !== false ? substr($sub, 0, $pos) : $sub;
                } else {
                    $str = $enumLines[0];
                    $sub = substr($str, 5);
                    $pos = strpos($sub, " ");
                    $result = $pos !== false ? substr($sub, 0, $pos) : $sub;
                }

                $this->addItem([
                    "kind" => "enum",
                    "id" => $result,
                    "signature" => implode("\n", $enumLines),
                    "doc" => $this->pendingDoc ?? [],
                ]);

                $this->pendingDoc = null;
                continue;
            }

            if (
                preg_match(
                    "/\b(native|forward|public|stock|static)\b.*\(/",
                    $line
                )
            ) {
                if (
                    str_starts_with(trim($line), "//") ||
                    str_starts_with(trim($line), "/*")
                ) {
                    continue;
                }

                $startLine = $i;
                $fnLines = [$line];

                while (!str_contains(end($fnLines), ")") && $i + 1 < $count) {
                    $i++;
                    $fnLines[] = rtrim($lines[$i]);
                }

                $signature = implode("\n", $fnLines);
                $body = null;

                $hasBraceSameLine = str_contains($signature, "{");
                $hasBraceNextLine =
                    isset($lines[$i + 1]) &&
                    preg_match("/^\s*\{/", $lines[$i + 1]);

                if ($hasBraceSameLine || $hasBraceNextLine) {
                    $bodyLines = $fnLines;
                    $brace = 0;

                    if (!$hasBraceSameLine) {
                        $i++;
                        $bodyLines[] = rtrim($lines[$i]);
                    }

                    foreach ($bodyLines as $l) {
                        $brace += substr_count($l, "{");
                        $brace -= substr_count($l, "}");
                    }

                    while ($brace > 0 && $i + 1 < $count) {
                        $i++;
                        $lineBody = rtrim($lines[$i]);
                        $bodyLines[] = $lineBody;

                        $brace += substr_count($lineBody, "{");
                        $brace -= substr_count($lineBody, "}");
                    }

                    $body = implode("\n", $bodyLines);
                    $body = str_replace("\t", "    ", $body);
                }

                if (preg_match("/\b([A-Za-z_]\w*)\s*\(/", $signature, $m)) {
                    $name = $m[1];
                } else {
                    $name = "fn_" . count($this->items);
                }

                $this->addItem([
                    "kind" => "function",
                    "id" => $name,
                    "signature" => rtrim($signature, ";") . ";",
                    "body" => $body,
                    "doc" => $this->pendingDoc ?? [],
                ]);

                $this->pendingDoc = null;
                continue;
            }

            if (preg_match("/\bconst\b/", $line) && str_contains($line, ";")) {
                $posColon = strpos($line, ":");
                $posConst = strpos($line, "const");

                if ($posColon !== false) {
                    $sub = substr($line, $posColon + 1);
                } elseif ($posConst !== false) {
                    $sub = substr($line, $posConst + 6);
                }

                $posBracket = strpos($sub, "[");
                $posSemicolon = strpos($sub, ";");

                if ($posBracket !== false) {
                    $result = substr($sub, 0, $posBracket);
                } elseif ($posSemicolon !== false) {
                    $result = substr($sub, 0, $posSemicolon);
                }

                $this->addItem([
                    "kind" => "const",
                    "id" => $result,
                    "signature" => rtrim($line),
                    "doc" => $this->pendingDoc ?? [],
                ]);
                $this->pendingDoc = null;
                continue;
            }
        }

        if ($this->activeSection) {
            $this->items[] = $this->activeSection;
            $this->activeSection = null;
        }

        return $this->items;
    }

    protected function parseDocBlock(string $raw): array
    {
        $doc = [
            "description" => [],
            "notes" => [],
            "params" => [],
            "return" => [],
            "deprecated" => [],
            "errors" => [],
            "noreturn" => false,

            "section" => null,
            "endsection" => false,
        ];

        $lines = preg_split("/\R/", $raw);

        $clean = [];
        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace("~^/\*\*~", "", $line);
            $line = preg_replace("~\*/$~", "", $line);
            $line = preg_replace("~^\*\s?~", "", $line);
            $line = trim($line);

            if ($line !== "") {
                $clean[] = $line;
            }
        }

        $current = "description";

        foreach ($clean as $line) {
            if ($line[0] === "@") {
                if (str_starts_with($line, "@section")) {
                    $doc["section"] = trim(substr($line, 8));
                } elseif (str_starts_with($line, "@endsection")) {
                    $doc["endsection"] = true;
                } elseif (str_starts_with($line, "@note")) {
                    $current = "note";
                    $doc["notes"][] = [ trim(substr($line, 5)) ];
                } elseif (str_starts_with($line, "@param")) {
                    $current = "param";
                    if (preg_match("/@param\s+([^\s]+)\s*(.*)/", $line, $m)) {
                        $doc["params"][] = [
                            "name" => $m[1],
                            "desc" => [$m[2] ?? ""],
                        ];
                    }
                } elseif (str_starts_with($line, "@return")) {
                    $current = "return";
                    $doc["return"][] = trim(substr($line, 7));
                } elseif (str_starts_with($line, "@deprecated")) {
                    $current = "deprecated";
                    $doc["deprecated"][] = trim(substr($line, 11));
                } elseif (str_starts_with($line, "@error")) {
                    $current = "error";
                    $doc["errors"][] = [ trim(substr($line, 6)) ];
                } elseif (str_starts_with($line, "@noreturn")) {
                    $doc["noreturn"] = true;
                    $current = null;
                }
                continue;
            }

            switch ($current) {
                case "description":
                    $doc["description"][] = $line;
                    break;

                case "note":
                    $doc["notes"][array_key_last($doc["notes"])][] = $line;
                    break;

                case "param":
                    $doc["params"][array_key_last($doc["params"])]["desc"][] = $line;
                    break;

                case "return":
                    $doc["return"][] = $line;
                    break;

                case "deprecated":
                    $doc["deprecated"][] = $line;
                    break;

                case "error":
                    $doc["errors"][array_key_last($doc["errors"])][] = $line;
                    break;
            }
        }

        return $doc;
    }

    protected function addItem(array $item): void
    {
        if ($this->activeSection) {
            $this->activeSection["items"][] = $item;
        } else {
            $this->items[] = $item;
        }
    }
}