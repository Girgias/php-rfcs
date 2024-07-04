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
    // Italics (negative look ahead/behind to skip code comments)
    '#(?<!/)\*{1}(.+)\*{1}(?!/)#U' => '//${1}//',
    // Code
    '/`{3}php(\X+)`{3}/U' => '<PHP>${1}</PHP>',
    '/`{3}(\X+)`{3}/U' => '<code>${1}</code>',
    '/`{2}(\X+)`{2}/U' => '<php>${1}</php>',
    '/`{1}(\X+)`{1}/U' => '<php>${1}</php>',
    // References/Footnotes
    '/\[1:(.+)\]/' => '((${1}))',
    // Lists
    '/^(?: {0,3})- (.+)$/m' => '  * ${1}',
    // URLS
    // Internal URLS
    '/\[(.+)\]\((?:https?):\/\/wiki\.php\.net\/rfc\/(.+)\)/' => '[[rfc:${2}|${1}]]',
    '/\[(.+)\]\((.+)\)/' => '[[${2}|${1}]]',
    // Clean-up
    // DokuWiki doesn't tolerate code in titles
    '/^(={1,6}.*)(?:<php>)(.*)(?:<\/php>)(.*={1,6})$/m' => '${1}${2}${3}',
];

$TITLE_START_OFFSET = strlen('# PHP RFC: ');
define('TITLE_START_OFFSET', $TITLE_START_OFFSET);

const VOTING_SNIPPET_CODE = <<<'VOTING'
As per the voting RFC a yes/no vote with a 2/3 majority is needed for this proposal to be accepted.

Voting started on 2024-XX-XX and will end on 2024-XX-XX.
 
<doodle title="Accept RFC_TITLE RFC?" auth="girgias" voteType="single" closed="true">
   * Yes
   * No
</doodle>
VOTING;


$REGEX_PATTERNS = array_keys(REGEXES);
$REGEX_REPLACEMENTS = array_values(REGEXES);

define('REGEX_PATTERNS', $REGEX_PATTERNS);
define('REGEX_REPLACEMENTS', $REGEX_REPLACEMENTS);

function convert_md_to_php_dokuwiki(string $input): string {
    $output = preg_replace(REGEX_PATTERNS, REGEX_REPLACEMENTS, $input);
    // Dokuwiki does not support code markup in a link text
    $output = str_replace(
        ['|<php>', '</php>]]'],
        ['|', ']]'],
        $output,
    );
    // Dokuwiki does not support code coloring
    $output = str_replace(
        ['<code>c', '<code>txt', '<code>text'],
        '<code>',
        $output,
    );

    // Fix C pointer code after italics for container offset RFC
    $output = str_replace(
        ['//(//', 'zval //', 'zend_object //', 'void  (//', 'bool  (//'],
        ['*(*', 'zval *', 'zend_object *', 'void  (*', 'bool  (*'],
        $output,
    );


    // Voting snippet
    $offset_first_newline = strpos($input, "\n");
    $title = substr($input, TITLE_START_OFFSET, $offset_first_newline-TITLE_START_OFFSET);
    $voting_code = str_replace('RFC_TITLE', $title, VOTING_SNIPPET_CODE);
    $output = str_replace(
        'VOTING_SNIPPET',
        $voting_code,
        $output,
    );

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
