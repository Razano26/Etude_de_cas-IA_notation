<?php
include('./simple HTML DOM/simple_html_dom.php');
$html = str_get_html("<div>foo <b>bar</b></div>");
$e = $html->find("div", 0);

echo $e->tag; // Returns: " div"
echo $e->outertext; // Returns: " <div>foo <b>bar</b></div>"
echo $e->innertext; // Returns: " foo <b>bar</b>"
echo $e->plaintext; // Returns: " foo bar"

?>