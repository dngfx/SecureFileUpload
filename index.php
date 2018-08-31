<?php
// what are comments and how do i make them
ini_set( "display_errors", "1" );
error_reporting( E_ALL );

function random_string( $len = 32 )
{
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!';
	$randstring = '';
	$strlen = strlen( $characters );
	
	for( $i = 0; $i < $len; $i++ ) {
		$randstring .= $characters[ rand( 0, $strlen - 1 ) ];
	}
	
	
	return $randstring;
}

$skeleton = file_get_contents( "skeleton.html" );

function throw_error( $error )
{
	$skeleton = file_get_contents( "skeleton.html" );
	$content = $error;
	
	$skeleton = str_replace( "{{content}}", $content, $skeleton );
	
	exit($skeleton);
}


if( empty( $_FILES ) ) {
	$content = <<<HTML
    <h2>Upload an Image</h2>
    <span><b>Accepted Image Formats:</b> jpg, jpeg, png, gif. All metadata is stripped when uploading.

    <div class="uploadForm">
        <form  method="post" enctype="multipart/form-data">
            <label for="file">Choose File:</label>
            <input type="file" id="file" name="file">
            <br><br>
            <input type="submit" name="submit" value="Upload File">
        </form>
    </div>
HTML;
	
	$skeleton = str_replace( "{{content}}", $content, $skeleton );
	
	echo $skeleton;
	exit;
}

$file = $_FILES[ "file" ];
$error = $file[ "error" ];

$acceptable_files = array(
	'png'  => 'image/png',
	'jpe'  => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'jpg'  => 'image/jpeg',
	'gif'  => 'image/gif'
);

if( $error !== UPLOAD_ERR_OK ) {
	$php_file_upload_errors = array(
		0 => 'There is no error, the file uploaded with success',
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3 => 'The uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Missing a temporary folder',
		7 => 'Failed to write file to disk.',
		8 => 'A PHP extension stopped the file upload.'
	);
	
	throw_error( $php_file_upload_errors[ (int)$error ] );
}

$tmp_file_name = $file[ "tmp_name" ];

$content_type = mime_content_type( $tmp_file_name );
$valid = 0;

$file_extension = "";

foreach( $acceptable_files as $key => $val ) {
	if( $content_type == $val ) {
		$valid = 1;
		$file_extension = $key;
	}
}

if( empty( $file_extension ) ) {
	throw_error( "Could not find a file extension for this file." );
}

// We have a valid image, prepare to encrypt and put into gallery directory

$encryption_key = random_string();
$encryption_cypher = "AES-256-CTR";
$iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $encryption_cypher ) );

$unique_filename = uniqid( "", true ) . uniqid( "", true );
$upload_name = "./Files/" . $unique_filename . "." . $file_extension;

$encrypted_file = openssl_encrypt( file_get_contents( $tmp_file_name ), $encryption_cypher, $encryption_key, OPENSSL_RAW_DATA, $iv );
$hmac = hash_hmac( "sha256", $encrypted_file, $encryption_key, true );
$sanitised_file = base64_encode( $iv . $hmac . $encrypted_file );

$fname = $unique_filename . "." . $file_extension;

file_put_contents( $upload_name, $sanitised_file );


$deletekeyparts = explode( $fname, '.' );
$deletekeyparts[] = './Files/';


$deletekey = md5( implode( '~', $deletekeyparts ) );

$html = <<<HTML
	<h2>Success! Your file has been successfully uploaded.</h2>
	Please take note of these details as these can <b>never</b> be recovered, and <b>anyone</b> can delete the image using &action=delete.<br><br>
	<b>Filename:</b> {$unique_filename}.{$file_extension}<br>
	<b>Decryption Key:</b> $encryption_key<br>
	<b>URL:</b> <a href="https://dongfix.in/Gallery/Files/{$unique_filename}.{$file_extension}?key={$encryption_key}" target="_blank">Your UNIQUE URL</a><br>
	<b>Delete Link:</b> <a href="https://dongfix.in/Gallery/Files/{$unique_filename}.{$file_extension}?key={$encryption_key}&action=delete&deletekey={$deletekey}" target="_blank">Your UNIQUE URL</a><br>
HTML;

$skeleton = str_replace( "{{content}}", $html, $skeleton );
exit( $skeleton );