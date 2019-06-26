<?php
class html5_parser {
  public $dom; 
  const  REGEXP_TAG  = "/^[^<]?[a-z]+/";

  function __construct() {
    $this->dom = new DOMDocument;
    $this->dom->preserveWhiteSpace = false;
    $this->dom->formatOutput = true;
  }
  /**
   * Method looks replaces line returns in text string and adds HTML line break tags instead 
   * @param  string, $pretext [Chunked string of text that has no dom tags]
   * @param  object, $holder  [DOMDocument element object to nest string in]
   * @param  integer, $lim    [counter for constraining output i.e. post excerpt]
   * @return null      
   */
  function wrap_lines($pretext="", $holder=null, $lim=0){
    if( preg_match('/\r?/',  $pretext )){
      $temp = preg_split( '/\r\n|\r|\n/', $pretext );
      if( count($temp) > 1){
        foreach($temp as $k => $v){
          $lim--;
          if($k > 0 ) {
            $br= $this->dom->createElement("br");
            $holder->appendChild( $br);
          }
          $holder->appendChild( $this->dom->createTextnode(html_entity_decode($v)));
          if($lim==0 ){
            return;
          }
        }
      }else{
        $pretext .=" ";
        $holder->appendChild($this->dom->createTextnode(html_entity_decode($pretext)));
      }
    }else{ 
      $holder->appendChild($this->dom->createTextnode(html_entity_decode($pretext)));
    }
  }
  /**
   * Clears potential stray close tags from text content before position of first open tag and nests any plain text into dom element holder
   * @param  string,  $content [string of html text]
   * @param  integer, $openb   [position of first open tag bracket]
   * @param  object,  $holder  [DOMDocument element object to nest in]
   * @return string||null,   [returns content string or null if no dom tags]
   */
  function trim_errors_and_text( $content="", $openb=0, $holder=null){
    $pretext = substr($content, 0, ($openb));
    $content = substr( $content, $openb); 
    $holder->appendChild($this->dom->createTextNode($pretext));
    if(( strpos( $content , '</')===0)||( strpos( $content , '<!-- /')===0)){
      $endtag = strpos( $content, ">");
      $content = substr($content,  $endtag+1);
      $content =  $this->trim_errors_and_text( $content, $openb, $holder);
    }
    $openb =  $openb = strpos($content, "<");
    if(  $openb > 0 ){
      $content = $this->trim_errors_and_text( $content, $openb, $holder);
    }elseif( $openb=== 0 ){
      return $content;   
    }else{
      $holder->appendChild($this->dom->createTextNode($content));
      return null;
    }
  }

  /**
   * Recursive method to locate closing tag when there is a nesting scenario of similar elements like a case of 'divitis'
   * @param  string,  $content [string of html text]
   * @param  string, $tag     [tag identifier (a, div, header ...)]
   * @param  integer, $pos     [position in string of tag open end bracket]
   * @return integer,          [position in string of closing tag]
   */
  function find_nesting_end($content="", $tag="", $pos=0){
    $nest = strpos($content, "<".$tag, $pos);
    $firstclose = strpos($content, "</".$tag, $pos+1 ); 
    if( $nest < $firstclose){
      $firstclose = $this->find_nesting_end($content, $tag, $firstclose);
    }
   if( $firstclose === false){
      $firstclose = $pos;
    }
    return  $firstclose;
  }

  /**
   * Creates a DOMDocument element 
   * @param  string, $str [string parameter content inside DOM tag brackets ]
   * @param  string, $tag [tag identifier (a, div, header ...)]
   * @return object,    [DOMDocument element]
   */
  function make_a_node($str="", $tag=""){
    if( $str{strlen($str)-1}== "/"){
      $str= substr( $str, 0, (strlen($str)-1));   
    }
    if( preg_match($this::REGEXP_TAG, trim($tag))){
      $mynode = $this->dom->createElement(trim($tag));
    }else{
      return false; 
    }
    $property = "";
    $param="";
    $endoline = false;
    $vv= explode(" ", $str);
    foreach($vv as $kk => $vvv){///iterate over spaced array
      $assign = strpos( $vvv, "=");
      if( $assign === false){
        $tempstring =$vvv;
      }else{
        $param = trim( substr($vvv, 0, $assign ));
        $tempstring = substr( $vvv, $assign+1 ); ///chop the equals sign
      }
      $frontq=strpos( $tempstring ,'"'); 
      if( $frontq === 0 ){
        $property = substr( $tempstring, 1 ); ///trim front quote
      }else{
        $endq = strpos( $tempstring,'"'); //is there an endquote?
        if( $endq===false){ ///no
          $property .=" ".$tempstring;
        }else{
          $property .=" ".substr( $tempstring, 0, $endq );
        }
      }
     $params[$param]=str_replace('"','',$property);
    }
    if( $tag =="img"){
      $params["class"].=" myclassname";
      if( strlen( $params["alt"] )==0 ){
        $params["alt"]= "image ";
        $nom= explode( "/", $params["src"]);
        $nom = explode("-", $nom[count($nom)-1]);
        foreach( $nom as $n => $nv){
          if( $n < (count($nom)-1)){
            $params["alt"].=" ".$nv."";
          }else{
            $pfix= explode(".", $nv);
            $params["alt"].=" ".$pfix[0]."";
          }
        }
      } 
    }
    foreach( $params as $p => $val){
      if( strlen($p)> 0 ){
        $mynode->setAttribute($p,$val);
      }
    }
    return $mynode;
  }
  /**
   * Elaborate recursive method to parse HTML string into a DOMDocument format. This was required because the loadHTML method does not parse HTML5 semantic elements.
   * @param  string,  $content [HTML5 text content]
   * @param  object,  $holder  [DOMDocument container, i.e the page body] 
   * @param  integer||null,  $lim   [limiter can be an arbitrary number like 10: counts down to zero method exits]
   * @return null     
   */
  function walk_the_dom($content="", $holder=null, $lim=0){
    if($lim===0) {///add ellipsis and exit if output is constrained
      $id = $holder->getAttribute("id");
      if( strpos( $id, "prefix-of-my-id")===0){
        $ellID = $id."-more";
        if( $this->dom->getElementById($ellID) == null ){
          $elli = $this->dom->createElement("div");
          $elli->setAttribute("id", $ellID);
          $elli->setAttribute("class", "dom-ellipsis");
          $elli->appendChild($this->dom->createTextNode(""));
          $holder->appendChild($elli);
        }
        return;
      }
    }
    $lim--;
    if(( strpos( $content , '</')===0)||( strpos( $content , '<!-- /')===0)){
     ///have immediate leading close tag to remove
      $endtag = strpos( $content, ">");
      $content = substr($content,  $endtag+1);
    }
    $openb = strpos($content, "<"); ///first tag 
    $pretext="";
    if( $openb===false){
      $holder->appendChild($this->dom->createTextnode(html_entity_decode($content)));
      return;
    }
    if( $openb > 0 ){
     $content = $this->trim_errors_and_text($content, $openb, $holder);
    if(( strpos( $content , '</')===0)||( strpos( $content , '<!-- /')===0)){
        $endtag = strpos( $content, ">");
        $content = substr($content,  $endtag+1);
       }
      $openb = strpos($content, "<"); 
    }
    $openb = strpos($content, "<");
    $openbx = strpos($content, ">"); ///end of first tag
    $space = strpos($content, " ");
    if(( $space===false)||($openbx <= $space )){
      $taglength=$openbx-$openb-1; 
    }else{
      $taglength = strpos($content, " ", $openb)-1;
      if(  $taglength===false){//havetag with no whitespace
        $taglength = strpos($content, ">", $openb)-1;
      }
    }
    if(( strpos( $content , '</')===0)||( strpos( $content , '<!-- /')===0)){
      $endtag = strpos( $content, ">");
      $content = substr($content,  $endtag+1);
      $this->walk_the_dom($content, $holder, $lim);   
    }
    $tag = substr( $content, $openb+1, $taglength);
    if( strpos( trim($tag), '!--') === 0 ){ 
      $content = trim($content);
      $endofcomment =  strpos($content,"-->");
      $fulltag = substr($content, 4, $endofcomment-4);
      $shorttag= $fulltag;
      ///comment has properties
      if (preg_match("/\\s/", trim($fulltag))) {
        $shorts= explode(" ", trim($fulltag));
        $shorttag = $shorts[0]  ; 
      }
      ///add comment to DOM
      $comm = $this->dom->createComment($fulltag."");
      $holder->appendChild( $comm );
      ///is there a close comment tag as in Wordpress block syntax
      $lookfor = "<".$tag." /".trim($shorttag);
      $test_x_pos= strpos($content, $lookfor);
      if( $test_x_pos===false){///no close comment tag
        $cm_end0 = strpos( $content, "-->");
        ///chunk the content after comment close
        $content = substr( $content,  $cm_end0+3);
        $nexttag = strpos($content, "<");
        if( $nexttag === false){
          $holder->appendChild($this->dom->createTextNode($content));
        }else{
           $inner =  substr( $content, 0, $nexttag-1);
           $holder->appendChild($this->dom->createTextNode($inner));
           $content = substr( $content, $nexttag);
           $this->walk_the_dom($content,$holder,$lim);
           return;
        }         
      }else{
      ///get close tag end
        $opentagend = strpos($content,'-->');
        $blockend_inc = strpos($content, ">" , $test_x_pos  );
        $inner = substr($content, $opentagend+3, $test_x_pos-1-$opentagend-3 );
        $content = substr($content, $blockend_inc+1 );
        $commend = $this->dom->createComment("/".$shorttag."");
        $this->walk_the_dom($inner, $holder, $lim); 
        $holder->appendChild($commend);
        $this->walk_the_dom($content, $holder, $lim); 
      }
    }else{///have a dom node
      $attr =  substr( $content, $taglength, $openbx-$taglength);
      if( strlen($tag)> 0){
        $nunode = $this->make_a_node($attr, trim($tag));
        $lim--;
        if( !$nunode){///if fail on node creation
          $nunode=$this->dom->createElement("span");
          $char = substr($content,1 ,1);
          $nunode->appendChild($this->dom->createTextNode(" <".$char.""));
          $endr = strpos($content, "<",2 );
          $content = substr($content, 2);
          $holder->appendChild( $nunode);
          if( preg_match('/\r?/',  $content )){///have line returns
            $temp = preg_split( '/\r\n|\r|\n/', $content );
            if( count($temp) > 1){
              foreach($temp as $k => $v){
                if($k > 0 ) {
                  $br= $this->dom->createElement("br");
                  $holder->appendChild( $br);
                }
              }
            }
          }
          $this->walk_the_dom(trim($content), $holder, $lim);
          return;
        }else{
          $holder->appendChild( $nunode);
          $lookfor = "</".trim($tag);
          $test_x_pos= strpos($content, $lookfor , $openbx );
          if( $test_x_pos === false){ ///no closing tag
            $lookfor = ">";
            $test_x_pos= strpos($content, $lookfor , $openbx-1 );
            if( $test_x_pos > 0 ){
             $content = substr($content, $test_x_pos+1 );
               $this->walk_the_dom($content, $nunode, $lim);
              return;
            }
          }else{///test for possible nesting of same dom tag
            $inner_nest = strpos( $content , "<".trim($tag)."",  $openbx+1);
            if(( $inner_nest <  $test_x_pos)&&($inner_nest>0)) {
              $endtag = $this->find_nesting_end($content, $tag, $openbx+1);   
              $inner = substr( $content, $openbx+1, $endtag- $openbx-1);
              $content= substr( $content,  $endtag-$openbx);
              $this->walk_the_dom($inner, $nunode, $lim);
              $this->walk_the_dom($content, $holder, $lim);
              return;
            }else{
              $lookfor= "</".$tag;
              $endtag = strpos( $content, $lookfor);
              $inner = substr($content,$openbx+1,  $endtag-$openbx-1 );
              $content = substr($content,  ($endtag+strlen($tag)+3) );
              $this->walk_the_dom($inner, $nunode, $lim);     
            }
          }
        } 
      }
      if( strlen($content)-1 >  0 ){
        $nexttag = strpos($content, "<");
        if( $nexttag===false){
            $this->wrap_lines($content, $holder, $lim, 0);
        }else{
           $this->walk_the_dom($content, $holder, $lim);
        }
      }
    }
  }
}
?>