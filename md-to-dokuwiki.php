<?php

const OUTPUT_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'dokuwiki' . DIRECTORY_SEPARATOR;
const INPUT_DIR = __DIR__ . DIRECTORY_SEPARATOR;

const REGEXES = [
    // Titles
    '/^#{6}(?: ?)(.+)$/m' => '= ${1} =',
    '/^#{5}(?: ?)(.+)$/m' => '== ${1} ==',
    '/^#{4}(?: ?)(.+)$/m' => '=== ${1} ===',
    '/^#{3}(?: ?)(.+)$/m' => '==== ${1} ====',
    '/^#{2}(?: ?)(.+)$/m' => '===== ${1} =====',
    '/^#{1}(?: ?)(.+)$/m' => '====== ${1} ======',
    // Code
    '/`{3}php(\X+)`{3}/U' => '<PHP>${1}</PHP>',
    '/`{2}(\X+)`{2}/U' => '<php>${1}</php>',
    '/`{1}(\X+)`{1}/U' => '<php>${1}</php>',
    // Lists
    '/^(?: {0,3})- (.+)$/m' => '  * ${1}',
    // URLS
    // Internal URLS
    '/\[(.+)\]\((?:https?):\/\/wiki\.php\.net\/rfc\/(.+)\)/' => '[[rfc:${2}|${1}]]',
    '/\[(.+)\]\((.+)\)/' => '[[${2}|${1}]]',
    // Italics
    '/\*{1}(.+)\*{1}/U' => '//${1}//',
];

$REGEX_PATTERNS = array_keys(REGEXES);
$REGEX_REPLACEMENTS = array_values(REGEXES);

define('REGEX_PATTERNS', $REGEX_PATTERNS);
define('REGEX_REPLACEMENTS', $REGEX_REPLACEMENTS);

function convert_md_to_php_dokuwiki(string $input): string {
    $output = preg_replace(REGEX_PATTERNS, REGEX_REPLACEMENTS, $input);

    return $output;
}

@mkdir(OUTPUT_DIR);

$dir = glob("*.md");
foreach ($dir as $fileName) {
    $mdContent = file_get_contents(INPUT_DIR . $fileName);
    $dokuwiki = convert_md_to_php_dokuwiki($mdContent);

    file_put_contents(OUTPUT_DIR . $fileName, $dokuwiki);

    echo $fileName . "\n";
}
