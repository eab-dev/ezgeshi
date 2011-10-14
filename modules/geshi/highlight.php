<?php
/**
* View used to highlight any file (optionally: only within the eZP root diir)
* @author G. Giunta
* @version $Id$
* @copyright (c) G. Giunta 2010
* @license
*/

$orig_filename = implode( '/', $Params['Parameters'] );
$filename = $orig_filename;
$language = isset( $Params['language'] ) ? $Params['language'] : '';

// start assuming a relative path
/// @todo windows support: local paths, unc
if ( $filename[0] != '/' )
{
    $filename = eZSys::rootDir() . '/' . $filename;
}
// safety measure: check if file is not within ez root dir, if it is not he needs
// to have a specific permission
$user = eZUser::currentUser();
if ( !$user->hasAccessTo( 'geshi', 'view_any_source' ) )
{
    $ezpath = eZSys::rootDir();
    $filepath = realpath( $filename );
    if ( strpos( $ezpath, $filepath ) !== 0 )
    {
        return $Module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
    }
}

$errormsg = false;
$highlighted = '';
if ( is_file( $filename ) )
{
    //include_once( 'extension/ezsh/lib/geshi/geshi.php' );

    $source = file_get_contents( $filename );
    $geshi = new GeSHi( $source, $language );
    if ( $language == '' && strpos( $filename, '.' ) !== false )
    {
        $ext = substr( strrchr( $filename, '.' ), 1 );
        $ez_langs = array(
            'ini' => 'ezini',
            'tpl' => 'eztemplate',
            'append' => 'ezini',
            'htaccess' => 'apache',
            'htaccess_root' => 'apache',
            'php-RECOMMENDED' => 'php',
        );
        if ( array_key_exists( $ext, $ez_langs ) )
        {
            $language = $ez_langs[$ext];
        }
        else if( $ext == 'php' && strpos( $filename, '/settings/' ) !== false )
        {
            $language = 'ezini';
        }
        else if ( $ext == 'sql' && ( strpos( $filename, '/oracle/' ) !== false || strpos( $filename, '/ezoracle/' ) !== false ) )
        {
            $language = 'oracle11';
        }
        else if ( $orig_filename == 'robots.txt' )
        {
            $language = 'robots';
        }
        else
        {
            $language = $geshi->get_language_name_from_extension( $ext );
        }
        $geshi->set_language( $language );
    }

    $highlighted = $geshi->parse_code();
    $error = $geshi->error();
    if ( $error != false )
    {
        $errormsg = strip_tags( $error );
        eZDebug::writeWarning( 'In view geshi/highligt: ' . $errormsg );
    }
}
else
{
    $errormsg = "File $orig_filename nout found";
}

require_once( "kernel/common/template.php" );
$tpl = templateInit();
$tpl->setVariable( 'filename', $orig_filename );
$tpl->setVariable( 'language', $language );
$tpl->setVariable( 'highlighted', $highlighted );
$tpl->setVariable( 'errormsg', $errormsg );

$Result = array();
$Result['content'] = $tpl->fetch( "design:geshi/highlight.tpl" );

$Result['path'] = array( array( 'url' => false,
                                'text' => 'Highlight' ) );

?>