<?php
if( empty( $_GET ) ){
	exit();
}

define( "ADMIN_DELETE_KEY", "your_key_here", false );

$admin_key = isset( $_GET[ 'admin_key' ] ) && $_GET[ 'admin_key' ] == ADMIN_DELETE_KEY;

if( isset( $_GET[ 'file' ] ) && isset( $_GET[ 'key' ] ) ) {
	$file = $fname = explode("Files/", $_GET[ 'file' ])[1];
	$key = $_GET[ 'key' ];
}

$delete = (int)( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == "delete" );
$deletekey = (int)( $delete && isset( $_GET[ 'deletekey' ] ) ) ? $_GET[ 'deletekey' ] : 0;

$replace = preg_replace( "[^A-Za-z0-9\.]", "", $file );
$key_replace = preg_replace( "[^A-Za-z0-9\!]", "", $key );

if( ( strlen( $key ) !== 32 || $key_replace !== $key ) ) {
	exit( "There's trickery afoot." );
}

$encryption_cypher = "AES-256-CBC-HMAC-SHA256";
$file = base64_decode( file_get_contents( "./Files/$replace" ) );
$ivlen = openssl_cipher_iv_length( $encryption_cypher );
$iv = substr( $file, 0, $ivlen );

$hmac = substr( $file, $ivlen, $sha2len = 64 );
$ciphertext_raw = substr( $file, $ivlen + $sha2len );


$decrypted = openssl_decrypt( $ciphertext_raw, $encryption_cypher, $key, OPENSSL_RAW_DATA, $iv );

$file_type = finfo_open( FILEINFO_MIME_TYPE );
$file_type = finfo_buffer( $file_type, $decrypted, FILEINFO_MIME );

if( !$decrypted ) {
	exit( "No." );
}


if( $delete ) {
	$deletekeyparts = explode( $fname, '.' );
	$deletekeyparts[] = './Files/';

	
	if( $deletekey == md5( implode( '~', $deletekeyparts ) ) || $admin_key ) {
		unlink( "./Files/$fname" );
		exit( "Deleted." );
	} else {
		exit( "No." );
	}
}

$mime = $file_type;

if( $admin_key ) {
	$base64 = base64_encode( $decrypted );
	$html = <<<HTML
    <img src="data:$mime;base64,$base64" style="max-height: 50%; max-width: 50%;"><br><a href="&action=delete&admin_key=$admin_key">delete this image</a>
HTML;
	exit( $html );
}

header( "Content-Type: $mime" );
exit( $decrypted );
