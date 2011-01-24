<?php
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ less
// SOFTWARE RELEASE: 1.x
// COPYRIGHT NOTICE: Copyright (C) 2010-2011 Phillip Dornauer, Juan Pablo Stumpf
// SOFTWARE LICENSE: Creative Commons By-Sa 3.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the Creative Commons By-Sa 3.0
//   License.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   Creative Commons By-Sa 3.0 License for more details.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

/**
 * eZ Less Template Operator
 */
class ezLessOperator{
	
	
	/**
	 * $Operators
	 * @access private
	 * @type array
	 */
	private $Operators;
	
	
	/**
	 * $files
	 * @access static
	 * @type array
	 */
	static $files = array();
	
	
	/**
	 * eZ Template Operator Constructor
	 * @return null
	 */
	function __construct(){
		$this->Operators = array('ezless', 'ezless_add');
	}

	
	/**
	 * operatorList
	 * @access public
	 * @return array
	 */
	function &operatorList(){
		return $this->Operators;
	}
	
	/**
	 * namedParameterPerOperator
	 * @return true
	 */
	function namedParameterPerOperator(){
		return true;
	}
	
	
	/**
	 * namedParameterList
	 * @return array 
	 */
	function namedParameterList(){
		return array('ezless' => array(),
					 'ezless_add' => array()
				 );
	}
	
	
	/**
	 * modify
	 * @param & $tpl
	 * @param & $operatorName
	 * @param & $operatorParameters
	 * @param & $rootNamespace
	 * @param & $currentNamespace
	 * @param & $operatorValue
	 * @param & $namedParameters
	 * @return null
	 */
	function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace,
									&$currentNamespace, &$operatorValue, &$namedParameters ){
										
		switch ( $operatorName ){
			case 'ezless':
				$operatorValue = $this->loadFiles( $operatorValue );
				break;
			case 'ezless_add':
				$operatorValue = $this->addFiles( $operatorValue );
				break;
		}
		
	}
	
	
	/**
	 * loadFiles
	 * @param array $files
	 * @return string $html generated html tags
	 */
	public function loadFiles( $files ){
		if( is_array( $files ) )
			foreach( $files as $file )
				$pageLayoutFiles[] = $file;
		
		
		$files = $this->prependArray( self::$files, $pageLayoutFiles );
		
		return $this->generateTag( $files );
	}
	
	
	/**
	 * addFiles
	 * @param array|string $files
	 * @return null
	 */
	public function addFiles($files){
		if( is_array( $files ) )
			foreach( $files as $file )
				self::$files[] = $file;
		else
			self::$files[] = $files;
	}
	
	
	/**
	 * prependArray
	 * @description prepends the $prepend array in front of $array
	 * @param array $array
	 * @param array $prepend
	 * @return array $return
	 */
	private function prependArray( $array, $prepend ){
		$return = $prepend;
		
		foreach( $array as $value)
			$return[] = $value;
		
		return $return;
	}
	
	
	/**
	 * generateTag
	 * @param array $files
	 * @return string $html
	 */
	private function generateTag( $files ){
		$html = $cssContent = '';

		$ini 		= eZINI::instance( 'ezless.ini' );
		$useOneFile = $ini->variable( 'ezlessconfig','useOneFile' );
		$bases   	= eZTemplateDesignResource::allDesignBases();
		$triedFiles = array();
		
		$sys = eZSys::instance();

		$path = $sys->cacheDirectory() . '/ezless/';
		
		require_once dirname( __FILE__ ) . '/../lib/lessc.inc.php';
		
		if( ! $this->checkCacheFolder( $path ) )
			return '';
		
		$less = new lessc();
		
		foreach($files as $file){
			$match = eZTemplateDesignResource::fileMatch( $bases, '', 'stylesheets/'.$file, $triedFiles );
			
			if( $useOneFile == "true" )
				$cssContent .= file_get_contents( $match['path'] );
			else{
				
				$content = self::fixImgPaths( file_get_contents( $match['path'] ), $match['path'] );
				
				$file = $path  . md5( $content ) . ".css";
								
				if( ! file_exists( $file ) )
					file_put_contents( $file, $less->parse( $content ));
				
				$html .= '<link rel="stylesheet" type="text/css" href="/' . $file . '" />' . PHP_EOL;
			
			}
		}
		
		
		if( $useOneFile == "true" ){
			$file = $path  . md5( $cssContent ) . ".css";
			$less = new lessc();
			file_put_contents( $file, $less->parse( $cssContent ));
			
			$html = '<link rel="stylesheet" type="text/css" href="/' . $file . '" />' . PHP_EOL;
		}
		
		return $html;
	}
	
	
	/**
	 * checkCacheFolder
	 * @param string $path
	 * @return bool true|false
	 */
	private function checkCacheFolder( $path ){
		if( ! file_exists( $path ) ){
			if( ! mkdir( $path, 0777, true) ){
                eZDebug::writeWarning( "Could not create Cache Path: $path", __METHOD__ );
                return false;
			}
		}
		return true;
	}
	
	
    /**
     * borrowed from ezjscore
	 * @return string $wwwDir
     */
    static function getWwwDir()
    {
        static $wwwDir = null;
        if ( $wwwDir === null )
        {
            $sys = eZSys::instance();
            $wwwDir = $sys->wwwDir() . '/';
        }
        return $wwwDir;
    }
	
    /**
     * borrowed from ezjscore
	 * @param string $fileContent
	 * @param string $file
	 * @return string $fileontent
     */
	static function fixImgPaths( $fileContent, $file )
    {
        if ( preg_match_all("/url\(\s*[\'|\"]?([A-Za-z0-9_\-\/\.\\%?&#]+)[\'|\"]?\s*\)/ix", $fileContent, $urlMatches) )
        {
           $urlMatches = array_unique( $urlMatches[1] );
           $cssPathArray   = explode( '/', $file );
           // Pop the css file name
           array_pop( $cssPathArray );
           $cssPathCount = count( $cssPathArray );
           foreach( $urlMatches as $match )
           {
               $match = str_replace( '\\', '/', $match );
               $relativeCount = substr_count( $match, '../' );
               
               if ( $match[0] !== '/' and strpos( $match, 'http:' ) === false )
               {
                   $cssPathSlice = $relativeCount === 0 ? $cssPathArray : array_slice( $cssPathArray  , 0, $cssPathCount - $relativeCount  );
                   $newMatchPath = self::getWwwDir() . implode('/', $cssPathSlice) . '/' . str_replace('../', '', $match);
                   $fileContent = str_replace( $match, $newMatchPath, $fileContent );
               }
           }
        }
        return $fileContent;
    }

    /**
     * borrowed from ezjscore
	 * @param string $css
	 * @param string $packLevel
	 * @return string $css
     */
    static function optimizeCSS( $css, $packLevel )
    {
        // normalize line feeds
        $css = str_replace(array("\r\n", "\r"), "\n", $css);

        // remove multiline comments
        $css = preg_replace('!(?:\n|\s|^)/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = preg_replace('!(?:;)/\*[^*]*\*+([^/][^*]*\*+)*/!', ';', $css);

        // remove whitespace from start and end of line + multiple linefeeds
        $css = preg_replace(array('/\n\s+/', '/\s+\n/', '/\n+/'), "\n", $css);

        if ( $packLevel > 2 )
        {
            // remove space around ':' and ','
            $css = preg_replace(array('/:\s+/', '/\s+:/'), ':', $css);
            $css = preg_replace(array('/,\s+/', '/\s+,/'), ',', $css);

            // remove unnecesery line breaks
            $css = str_replace(array(";\n", '; '), ';', $css);
            $css = str_replace(array("}\n","\n}", ';}'), '}', $css);
            $css = str_replace(array("{\n", "\n{", '{;'), '{', $css);

            // optimize css
            $css = str_replace(array(' 0em', ' 0px',' 0pt', ' 0pc'), ' 0', $css);
            $css = str_replace(array(':0em', ':0px',':0pt', ':0pc'), ':0', $css);
            $css = str_replace(' 0 0 0 0;', ' 0;', $css);
            $css = str_replace(':0 0 0 0;', ':0;', $css);

            // these should use regex to work on all colors
            $css = str_replace(array('#ffffff','#FFFFFF'), '#fff', $css);
            $css = str_replace('#000000', '#000', $css);
        }
        return $css;
    }
}

?>