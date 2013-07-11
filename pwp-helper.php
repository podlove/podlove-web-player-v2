<?php

function podlovewebplayer_render_chapters( $input ) {
  if ( json_decode($input) === null ) {
    $input = trim( $input );
    $chapters = false;
    if ( $input != '' ) {
      if ( substr( $input, 0, 7 ) == 'http://' || substr( $input, 0, 8 ) == 'https://') {
        $http_context = stream_context_create();
        stream_context_set_params($http_context, array('user_agent' => 'UserAgent/1.0'));
        $chapters = trim( @file_get_contents( $input, 0, $http_context ) );
        $json_chapters = json_decode($chapters);
        if($json_chapters !== null) {
          return $json_chapters;
        }
      }
    }
    if ( $chapters == '' ) {
      return '';
    }
    preg_match_all('/((\d+:)?(\d\d?):(\d\d?)(?:\.(\d+))?) ([^<>\r\n]{3,}) ?(<([^<>\r\n]*)>\s*(<([^<>\r\n]*)>\s*)?)?\r?/', $chapters, $chapterArrayTemp, PREG_SET_ORDER);
    $chaptercount = count($chapterArrayTemp);
    for($i = 0; $i < $chaptercount; ++$i) {
      $chapterArray[$i]['start'] = $chapterArrayTemp[$i][1];
      $chapterArray[$i]['title'] = htmlspecialchars($chapterArrayTemp[$i][6], ENT_QUOTES);
      if (isset($chapterArrayTemp[$i][9])) {
        $chapterArray[$i]['image'] = trim($chapterArrayTemp[$i][10], '<> ()\'');
      }
      if (isset($chapterArrayTemp[$i][7])) {
        $chapterArray[$i]['href'] = trim($chapterArrayTemp[$i][8], '<> ()\'');
      }
    }
    return $chapterArray;
  }
  return $input;
}

function podlovewebplayer_build_html( $episode ) {
  function format_xml( $xml ) {
  
    $dom = new \DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML( $xml );
  
    return $dom->saveXML();
  }
  
  function remove_xml_header( $xml ) {
    return trim( str_replace( '<?xml version="1.0"?>', '', $xml ) );
  }
  // build main audio/video tag
  $xml = new \SimpleXMLElement( '<' . 'audio' . '/>' );
  $xml->addAttribute( 'id', $episode['playerId'] );
  $xml->addAttribute( 'controls', 'controls' );
  $xml->addAttribute( 'preload', 'none' );

  $width  = 'auto';
  $height = '30';

  if ( $this->is_video ) {
    $xml->addAttribute( 'poster', $episode['poster'] );
    $xml->addAttribute( 'width', $width );
    $xml->addAttribute( 'height', $height );
  } else {
    $xml->addAttribute(
      'style',
      sprintf(
        'width: %s; height: %s',
        empty( $width ) ||  $width == 'auto' ? 'auto' : $width . 'px',
        empty( $height ) ? '30px' : $height
      )
    );
  }
  var_dump($xml);

  // add all sources
  $flash_fallback_func = function( &$xml ) {};
  foreach ( $episode['files'] as $file ) {
    var_dump($file);
    //$mime_type = $file->episode_asset()->file_type()->mime_type;

    $source = $xml->addChild('source');
    $source->addAttribute( 'src', $file->get_file_url() );
    $source->addAttribute( 'type', $mime_type );

    if ( $mime_type == 'audio/mpeg' ) {
      $flash_fallback_func = function( &$xml ) use ( $file ) {
        $flash_fallback = $xml->addChild('object');
        $flash_fallback->addAttribute( 'type', 'application/x-shockwave-flash' );
        $flash_fallback->addAttribute( 'data', 'flashmediaelement.swf' );

        $params = array(
          array( 'name' => 'movie', 'value' => 'flashmediaelement.swf' ),
          array( 'name' => 'flashvars', 'value' => 'controls=true&file=' . $file->get_file_url() )
        );

        foreach ( $params as $param ) {
          $p = $flash_fallback->addChild( 'param' );
          $p->addAttribute( 'name', $param['name'] );
          $p->addAttribute( 'value', $param['value'] );
        }

      };
    }
  }
  // add flash fallback after all <source>s
  $flash_fallback_func( $xml );

  // prettify and prepare to render
  $xml_string = $xml->asXML();
  $xml_string = format_xml( $xml_string );
  $xml_string = remove_xml_header( $xml_string );

  // set JavaScript options
  $truthy = array( true, 'true', 'on', 1, "1" );
  $init_options = array(
    'pluginPath'          => $episode['pluginPath'],
    'alwaysShowHours'     => true,
    'alwaysShowControls'  => true,
    'timecontrolsVisible' => false,
    'summaryVisible'      => false,
    'hidetimebutton'      => false,
    'hidedownloadbutton'  => false,
    'hidesharebutton'     => false,
    'sharewholeepisode'   => false,
    'loop'                => false,
    'chapterlinks'        => 'all',
    'permalink'           => $episode['permalink'],
    'title'               => $episode['title'],
    'subtitle'            => $episode['subtitle'],
    'summary'             => $episode['summary'],
    'poster'              => $episode['poster'],
    'duration'            => $episode['duration'],
    'chaptersVisible'     => false,
    'features'            => array( "current", "progress", "duration", "tracks", "fullscreen", "volume" )
  );

  if ( $chapters = podlovewebplayer_render_chapters($episode['chapters']) ) {
    $init_options['chapters'] = json_decode( $chapters );
  }

  $xml_string .= "\n"
               . "\n<script>\n"
               . "jQuery('#" . $episode['playerId'] . "').podlovewebplayer(" . json_encode( $init_options ) . ");"
               . "\n</script>\n";
  var_dump($xml_string);
  return $xml_string;
}

?>