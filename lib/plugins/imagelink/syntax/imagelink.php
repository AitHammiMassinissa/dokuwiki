<?php

/**
 * DokuWiki Plugin imagelink (Syntax Component) 
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  peterfromearth    
 */

/*
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_imagelink_imagelink extends DokuWiki_Syntax_Plugin {

    public $pattern_start  = '<imagelink.*?>(?=.*?</imagelink>)';
    public $pattern_end    = '</imagelink>';
    
    /*
     * What kind of syntax are we?
     */
    function getType() {return 'container';}

    /*
     * Where to sort in?
     */
    function getSort() {return 104;}

    /*
     * Paragraph Type
     */
    function getPType(){return 'block';}
    
    public function getAllowedTypes() {
        return array('substition');
    }

     public function connectTo($mode) {
    	$this->Lexer->addEntryPattern($this->pattern_start,$mode,'plugin_imagelink_imagelink');
    }

   public function postConnect() {
   	   $this->Lexer->addPattern("\{\{(?:[^\}]|(?:\}[^\}]))+\}\}",'plugin_imagelink_imagelink');
   	
       $this->Lexer->addExitPattern($this->pattern_end,'plugin_imagelink_imagelink');
   }

    /*
     * Handle the matches
     */
   public function handle($match, $state, $pos, Doku_Handler $handler){
       $data = array();
       $data['state'] = $state;
       switch($state) {
           case DOKU_LEXER_ENTER:
               $match = substr($match, 11,-1);  // strip markup
               $flags = $this->parseFlags($match);
               $data['flags'] = $flags;
               break;
           case DOKU_LEXER_UNMATCHED:
               if (trim($match) !== '') {
                   $handler->_addCall('cdata', array($match), $pos);
               }
               break;
           case DOKU_LEXER_EXIT:
               $this->first_item = false;
               break;

       }
       return $data;
   }
            
    /* 
     * Create output
     */
   function render($mode, Doku_Renderer $renderer, $data)
	{
	    if($mode != 'xhtml') return false;
	    
	    if($data['state'] === DOKU_LEXER_ENTER) {
	        $renderer->doc .= '<div class="plugin_linkimage ">';
	        $renderer->doc .= '<div class="title">';
	        $renderer->cdata($data['flags']['title']);
	        $renderer->doc .= '</div>';
	        
	    } else if($data['state'] === DOKU_LEXER_EXIT) {
	        
	        $renderer->doc .= '</div>';
	    }

	    return true;
	}
	
	protected function parseFlags($confString) {
	    $confString = explode('&',$confString);
	    $flags = array(
	        'title' => '',
            'position' => 'center',
            'width' => '100%',
	        'hover' => false,
	    );
	    foreach($confString as $flag) {
	        
	        switch($flag) {
	            case 'hover':
	                $flags['hover'] = true;
	                break;

	        }
	        
	        $tmp = explode('=',$flag,2);
	        if(count($tmp) === 2) {
	            
	            switch($tmp[0]) {
	                case 'title':
	                    $flags['title'] = $tmp[1];
	                    break;
	            }
	            
	            
	        }
	    }
	    
	    return $flags;
	}
	
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
