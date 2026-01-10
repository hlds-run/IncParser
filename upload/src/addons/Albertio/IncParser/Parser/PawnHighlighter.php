<?php

namespace Albertio\IncParser\Parser;

class PawnHighlighter
{
    public function highlight(string $code): string
    {
        if (preg_match("/^\s*enum\b/m", $code)) {
            return $this->highlightEnum($code);
        }

        if (preg_match("/^\s*#define\b/m", $code)) {
            return $this->highlightDefine($code);
        }

        return $this->highlightFunction($code);
    }

    protected function highlightFunction(string $code): string
    {
        $out = $code;

        $fnName = null;

        if (preg_match("/\b([A-Za-z_][A-Za-z0-9_]*)\s*\(/", $out, $m)) {
            $fnName = $m[1];
            $out = preg_replace(
                "/\b" . preg_quote($fnName, "/") . "\s*\(/",
                "__FUNC__(",
                $out,
                1
            );
        }

        $out = preg_replace_callback(
            "/(?<![A-Za-z0-9_])(&)?(?:([A-Za-z_][A-Za-z0-9_]*)\s*:)?([A-Za-z_][A-Za-z0-9_]*)(\[\])?/",
            function ($m) {
                $html = "";

                if (!empty($m[1])) {
                    $html .= '<span class="pawn-operator">&amp;</span>';
                }

                if (!empty($m[2])) {
                    $html .= '<span class="pawn-type">' . $m[2] . "</span>:";
                }

                $html .= '<span class="pawn-param">' . $m[3] . "</span>";

                if (!empty($m[4])) {
                    $html .= '<span class="pawn-operator">[]</span>';
                }

                return $html;
            },
            $out
        );

        $out = preg_replace(
            "/\b(0x[0-9A-Fa-f]+|-?\d+)\b/",
            '<span class="pawn-number">$1</span>',
            $out
        );

        $out = preg_replace(
            "/\b(native|stock|public|forward|const)\b/",
            '<span class="pawn-keyword">$1</span>',
            $out
        );

        $out = preg_replace(
            "/(\[|\]|\(|\)|,)/",
            '<span class="pawn-operator">$1</span>',
            $out
        );

        if ($fnName !== null) {
            $out = str_replace(
                "__FUNC__",
                '<span class="pawn-function">' . $fnName . "</span>",
                $out
            );
        }

        return $out;
    }

    protected function highlightEnum(string $code): string
    {
        $lines = preg_split("/\R/", $code);
        $out = [];

        foreach ($lines as $i => $line) {
            if ($i === 0) {
                $line = preg_replace(
                    "/\benum\b/",
                    '<span class="pawn-keyword">enum</span>',
                    $line
                );

                $line = preg_replace(
                    '/<span class="pawn-keyword">enum<\/span>\s+([A-Za-z_][A-Za-z0-9_]*)/',
                    '<span class="pawn-keyword">enum</span> <span class="pawn-type">$1</span>',
                    $line
                );

                $out[] = $line;
                continue;
            }

            $line = preg_replace(
                "/^(\s*)([A-Za-z_][A-Za-z0-9_]*)/",
                '$1<span class="pawn-enum-item">$2</span>',
                $line
            );

            $line = preg_replace_callback(
                '/^(.*?)(\/\*.*?\*\/|\/\/.*)?$/',
                function ($m) {
                    $code = $m[1];
                    $comment = $m[2] ?? "";

                    $code = preg_replace(
                        "/\b(-?\d+)\b/",
                        '<span class="pawn-number">$1</span>',
                        $code
                    );

                    if ($comment !== "") {
                        $comment =
                            '<span class="pawn-comment">' .
                            $comment .
                            "</span>";
                    }

                    return $code . $comment;
                },
                $line
            );

            $out[] = $line;
        }

        return implode("\n", $out);
    }

    protected function highlightDefine(string $line): string
    {
        $comment = "";
        if (preg_match('/(\/\*.*?\*\/|\/\/.*)$/', $line, $m)) {
            $comment = $m[1];
            $line = substr($line, 0, -strlen($comment));
        }

        if (!preg_match('/^(#define)(\s+)(\S+)(.*)$/', $line, $m)) {
            return htmlspecialchars($line, ENT_NOQUOTES, "UTF-8");
        }

        [, $kw, $ws1, $name, $value] = $m;

        $out =
            '<span class="pawn-keyword">#define</span>' .
            $ws1 .
            '<span class="pawn-define">' .
            $name .
            "</span>" .
            $this->highlightDefineValue($value);

        if ($comment !== "") {
            $out .= '<span class="pawn-comment">' . $comment . "</span>";
        }

        return $out;
    }

    protected function highlightDefineValue(string $value): string
    {
        $tokens = preg_split(
            "/(\s+|<<|>>|\(|\)|\+|-|\*|\/|\||&)/",
            $value,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $out = "";

        foreach ($tokens as $t) {
            if ($t === "") {
                continue;
            }

            if (trim($t) === "") {
                $out .= $t;
                continue;
            }

            if (preg_match('/^0x[0-9a-fA-F]+$/', $t)) {
                $out .= '<span class="pawn-number">' . $t . "</span>";
                continue;
            }

            if (preg_match('/^\d+\.\d+$/', $t)) {
                $out .= '<span class="pawn-number">' . $t . "</span>";
                continue;
            }

            if (preg_match('/^\d+$/', $t)) {
                $out .= '<span class="pawn-number">' . $t . "</span>";
                continue;
            }

            if (preg_match('/^(true|false)$/i', $t)) {
                $out .= '<span class="pawn-boolean">' . $t . "</span>";
                continue;
            }

            if ($t[0] === '"' && substr($t, -1) === '"') {
                $out .=
                    '<span class="pawn-string">' .
                    htmlspecialchars($t, ENT_NOQUOTES, "UTF-8") .
                    "</span>";
                continue;
            }

            if (preg_match('/^(<<|>>|\(|\)|\+|-|\*|\/|\||&)$/', $t)) {
                $out .= '<span class="pawn-operator">' . $t . "</span>";
                continue;
            }

            $out .= htmlspecialchars($t, ENT_NOQUOTES, "UTF-8");
        }

        return $out;
    }
}