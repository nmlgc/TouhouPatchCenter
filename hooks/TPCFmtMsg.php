<?php

/**
  * Parser for Team Shanghai Alice .msg dialogs.
  * Registers the following template hooks:
  *
  * {{thcrap_msg}}
  * {{thcrap_msg_assist}}
  *
  * @file
  * @author Nmlgc
  */

class TPCFmtMsg {

	const ASSIST_PREFIX = '%s (';
	const ASSIST_POSTFIX = ')';
	const ASSIST_TYPE = 'assist';

	const RUBY_FORMAT = '|%d,%d,%s';

	const FONT_SIZE = 7.0; // Close enough

	const REGEX_CODE = '/#(?P<entry>[\d]+)@(?P<time>[\d]+)/';
	const REGEX_RUBY = '/\{\{\s*ruby\s*\|(.*?)\|\s*(.*?)\s*\}\}/';
	const REGEX_LINE = '#<br\s*/?>|\n#';

	public static function formatSlot( &$time, &$type, &$index ) {
		// Much faster than sprintf, by the way
		if ( $type )	{
			return $time . '_' . $type . '_' . $index;
		} else {
			return $time . '_' . $index;
		}
	}

	protected static function renderRuby( &$lines) {
		foreach ( $lines as $key => &$i ) {
			if ( !preg_match( self::REGEX_RUBY, $i, $m, PREG_OFFSET_CAPTURE) ) {
				continue;
			}
			$i = 
				substr( $i, 0, $m[0][1] ) .
				$m[1][0] .
				substr( $i, $m[0][1] + strlen( $m[0][0] ) )
			;

			$baseLen = $m[2][1] - $m[1][1];
			$start = $m[0][1] * self::FONT_SIZE;
			$span = ( $baseLen / strlen( $m[2][0] ) ) * self::FONT_SIZE;

			$rubyLine = sprintf( self::RUBY_FORMAT, $start, $span, $m[2][0] );
			array_splice( $lines, $key, 0, $rubyLine );
		}
	}

	public static function scrapeLines( &$param ) {
		$param = preg_replace( '/%/', '%%', $param );

		// Do more MediaWiki stuff...

		return preg_split( self::REGEX_LINE, $param, null, PREG_SPLIT_NO_EMPTY );	
	}

	public static function onMsg( &$tpcState, &$title, &$temp ) {
		$code = TPCUtil::dictGet( $temp->params['code'] );
		if ( !preg_match( self::REGEX_CODE, $code, $m) ) {
			return true;
		}
		$entry = $m['entry'];
		$time = $m['time'];

		// Render specific types
		$type = TPCUtil::dictGet( $temp->params[1] );

		// Time index
		$timeIndex = &$tpcState->msgTimeIndex[$type];
		if(
			( $entry === TPCUtil::dictGet( $tpcState->msgLastEntry[$type] ) ) and
			( $time === TPCUtil::dictGet( $tpcState->msgLastTime[$type] ) ) and
			( $tpcState->msgLastType === $type )
		) {
			$timeIndex++;
		} else {
			$timeIndex = 0;
		}

		$lines = self::scrapeLines( $temp->params['tl'] );

		// Line processing
		if ( $lines ) {
			if ( $type === self::ASSIST_TYPE and isset( $tpcState->msgAssistName ) ) {
				// Prefix first line
				$prefix = sprintf( self::ASSIST_PREFIX, $tpcState->msgAssistName );
				$lines[0] = $prefix . $lines[0];

				// TODO: Indent all following lines

				// Postfix last line
				$lines[count($lines) - 1] .= self::ASSIST_POSTFIX;

				$type = null;
			}

			// Yeah, maybe we should only do this based on some previous condition, 
			// but profiling tells that it hardly matters anyway...
			self::renderRuby( $lines );

			// Note that we don't do this for empty lines
			$slot = self::formatSlot( $time, $type, $timeIndex );
			$cont = &$tpcState->jsonContents[$entry][$slot];
			$cont['lines'] = $lines;
			// Set type... or don't, the patcher doesn't care.
			// Don't know why the prototype versions had that in the first place...
			/*if( $type ) {
				$cont['type'] = $type;
			}*/
		}
		
		$tpcState->msgLastEntry[$type] = $entry;
		$tpcState->msgLastTime[$type] = $time;
		$tpcState->msgLastType = $type;
		return true;
	}

	public static function onMsgAssist( &$tpcState, &$title, &$temp ) {
		$tpcState->msgAssistName = $temp->params[1];
		return true;
	}

	public static function onMsgParse( &$tpcState, &$title, &$temp ) {
		$tpcState->switchDataFilePatch( TPCUtil::dictGet( $temp->params['file'] ) );
		return true;
	}
}

$wgTPCHooks['thcrap_msg'][] = 'TPCFmtMsg::onMsg';
$wgTPCHooks['thcrap_msg_assist'][] = 'TPCFmtMsg::onMsgAssist';
// "Historic templates"
$wgTPCHooks['dt'][] = 'TPCFmtMsg::onMsg';
$wgTPCHooks['dialogtable'][] = 'TPCFmtMsg::onMsg';
$wgTPCHooks['msgassist'][] = 'TPCFmtMsg::onMsgAssist';
$wgTPCHooks['msgparse'][] = 'TPCFmtMsg::onMsgParse';