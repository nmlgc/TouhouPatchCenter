<?php

/**
  * Parsers for top-level information stuff.
  * Registers the following template hooks:
  *
  * {{thcrap_patch_info}}
  * {{thcrap_game_info}}
  *
  * @file
  * @author Nmlgc
  */

class TPCInfo {

	public static function onPatchInfo( &$tpcState, $title, $temp ) {
		$pageTitle = strtolower( $title->getDBKey() );

		$patchJS = &$tpcState->getFile( null, 'patch.js' );

		$patchJS['id'] = $pageTitle;

		$patchTitle = TPCUtil::dictGet( $temp->params['title'] );
		$patchJS['title'] = $patchTitle;
		return true;
	}

	public static function onGameInfo( &$tpcState, $title, $temp ) {
		$pageTitle = strtolower( $title->getDBKey() );
		$id = TPCUtil::dictGet( $temp->params['id'], $pageTitle );
		$cont = &$tpcState->getFile( $id );

		foreach ( $temp->params as $key => $val ) {
			switch ( $key ) {
				case 'latest':
					$vars = preg_split( '/\s*,\s*/', $val, null, PREG_SPLIT_NO_EMPTY );
					$cont[$key] = $vars;
					break;
				default:
					if ( !strncasecmp( $key, "format", 6 ) ) {
						$format = substr( $key, 7 );
						$cont['formats'][$format] = $val;
					} else {
						$cont[$key] = $val;
					}
					break;
			}
		}
		return true;
	}
}

$wgTPCHooks['thcrap_patch_info'][] = 'TPCInfo::onPatchInfo';
$wgTPCHooks['thcrap_game_info'][] = 'TPCInfo::onGameInfo';