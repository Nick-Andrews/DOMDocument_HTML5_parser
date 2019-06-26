<?php  
require_once( "html5-parser-class.php");
$page = new html5_parser();
$page->dom->loadHTML('<html><head></head><body id="main"></body></html>');
$html5_text = file_get_contents( "test.html");
$main = $page->dom->getElementById("main");
$page->walk_the_dom( $html5_text, $main, 20);
echo $page->dom->saveXML(); 
?>