# DOMDocument_HTML5_parser
PHP class to parse HTML5 tags into DOMDocument class

I developed this PHP class file  to parse HTML5 semantic elements like header and section into the PHP DOMDocument class. Presently the loadHTML method used by DOMDocument class fails when it encounters one of these HTML5 tags.

I have included an example test.html file with semantic tags. The index.php file reads this file and then parses it using the  html5_parser class I created. This class file instantiates the PHP DOMDocument object in its constructor. To use your own DOMDocument constructor remove 3 lines from the constructor method shown below. They are lines 7-9 of the file  html5-parser-class.php

  `function __construct() {
    $this->dom = new DOMDocument;
    $this->dom->preserveWhiteSpace = false;
    $this->dom->formatOutput = true;
  }`

To use your own DOMDocument constructor simply pass a reference to the DOMDocument object you want to use instead. You can do something like this where $myDom is your object reference.  
  
 ` function __construct() {
    global $myDom;
    $this->dom = $myDom;
  }
`
