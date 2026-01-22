<?php
/* KanuraganProject - pembuat galeri responsif statis
  * Instruksi
  * Atur input_dir yang benar (mis. gambar/)
  * Jalankan file ini dari cmdline dengan izin yang benar:
  *
  * php xvid.php
  *
  * Ini akan menghasilkan folder thumbnail dan akhirnya indeks.html.
  * Pindahkan folder index.html, thumbnail dan gambar ke mana saja dan itu akan berfungsi.
  * Contoh Settingan Buat Direktori baru /var/www/html/tes/vid/ /images/ dan /thumbs/
  */

// Variables you can edit
$album_name = "Kanuragan Project Album";
$input_dir = 'images/'; 
$thumb_dir = 'thumbs/';

//Variables that don't need editting
$image_size = 94;
$mp4_extensions = Array('mp4','MP4');
$video_convert_extensions = Array('3gp','3GP','mov','MOV','avi','AVI');
$image_extensions = Array('jpg','png','jpeg','JPG');


// Variables that don't need editing
$image_size = 94;
$mp4_extensions = Array('mp4','MP4');
$video_convert_extensions = Array('3gp','3GP','mov','MOV','avi','AVI');
$image_extensions = Array('jpg','png','jpeg','JPG');

// Main loop starts here

if ($handle = opendir($input_dir)) {
    while (false !== ($entry = readdir($handle))) {
        $file_parts = pathinfo($entry);
        
        // Check for video files that need to be converted
        if (in_array($file_parts['extension'], $video_convert_extensions)) {
            convert_vid($entry); 
        }
    }
    closedir($handle);
}

// Loop through the dir again
if ($handle = opendir($input_dir)) {
    while (false !== ($entry = readdir($handle))) {
        $file_parts = pathinfo($entry);
        
        // Check for video files
        if (in_array($file_parts['extension'], $mp4_extensions)){
            process_vid($entry); 
            echo "Processing video $entry\n"; 
        }
        
        // Check for image files
        if (in_array($file_parts['extension'], $image_extensions)) {                                                                                                                                            
            echo "Processing image $entry\n";
            generate_thumb($entry, $input_dir);
        }
    }
    closedir($handle);
}

// Last step generate index.html file 
generate_index();

// Functions follow here

function convert_vid($entry){
    global $input_dir;
    
    if (file_exists("$input_dir$entry.mp4")) {
        echo "The file $entry.mp4 already exists\n";
    } else {
        echo "Converting video $entry to mp4 format\n"; 
        $convert = system("ffmpeg -loglevel quiet -i \"$input_dir$entry\" -qscale 0 -y \"$input_dir$entry.mp4\"");
    }
}

// Process videos
function process_vid($entry){
    global $input_dir;
    global $thumb_dir;

    $extract_frame = system("ffmpeg -loglevel quiet -ss 00:00:00 -i \"$input_dir$entry\" -frames:v 1 -y \"$thumb_dir$entry.jpg\"", $retval);
    $image_name = $entry.".jpg";

    generate_thumb($image_name,$thumb_dir);
    echo "The generated name for the video still is $image_name\n";
}

// Generate thumbnails
function generate_thumb($entry, $work_dir) {
    global $image_size;
    global $thumb_dir;

    // Allow spaces in file names by wrapping file paths in quotes
    $work_path = "$work_dir$entry";
    $thumb_path = "$thumb_dir/tn_$entry";

    // Get the image extension
    $file_parts = pathinfo($entry);
    $jpg_extensions = Array('jpg','jpeg','JPG','JPEG');
    
    if (in_array($file_parts['extension'], $jpg_extensions)){
        correctImageOrientation($work_path);
    }

    list($width, $height) = getimagesize($work_path);
    if ($width > 0 && $width >= $height) {
        $modwidth = $image_size;
        $modheight = $height * $image_size / $width;
    } elseif ($width < $height) {
        $modwidth = $width * $image_size / $height;
        $modheight = $image_size;
    } else {
        echo "Error: something went wrong with $entry\n";
        return; // Exit if there's an error
    }

    // Create thumbnail
    if ($width > 0 && $height > 0) {
        $tn = imagecreatetruecolor($modwidth, $modheight);
        $source = null;

        switch($file_parts['extension']) {
            case "png":
                $source = imagecreatefrompng($work_path);
                break;
            default:
                $source = imagecreatefromjpeg($work_path); 
                break;
        }

        if ($source !== false) {
           imagecopyresampled($tn, $source, 0, 0, 0, 0, $modwidth, $modheight, $width, $height);
           imagejpeg($tn, $thumb_path, 90);
        } else {
            echo "Error: it appears $entry is not a valid JPEG image\n";
        }
    } 
}

// Some smartphone images have the wrong orientation, this function fixes this
function correctImageOrientation($filename) {
  if (function_exists('exif_read_data')) {
    $exif = exif_read_data($filename);
    if($exif && isset($exif['Orientation'])) {
      $orientation = $exif['Orientation'];
      if($orientation != 1){
        $img = imagecreatefromjpeg($filename);
        $deg = 0;
        switch ($orientation) {
          case 3:
            $deg = 180;
            break;
          case 6:
            $deg = 270;
            break;
          case 8:
            $deg = 90;
            break;
        }
        if ($deg) {
          $img = imagerotate($img, $deg, 0);        
        }
        // then rewrite the rotated image back to the disk as $filename 
        imagejpeg($img, $filename, 95);
      } 
    } 
  }       
}

function generate_index(){
    global $input_dir;
    global $thumb_dir;
    global $album_name;
    global $mp4_extensions;
    $correct_extensions = Array('jpg','png','jpeg','JPG','mp4','MP4');

    $index_file = fopen("index.html", "w") or die("Unable to open file!");
    echo "Writing index.html file\n";

    $head = 
'<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width" />
          <title>'.$album_name.'</title>
            <style type = text/css>
                div.Photo {
                        padding: 2px;
                        float:left;
                        width: 350px;
                        height: 200px;
                        vertical-align: top;
                        position: relative;
                        overflow: hidden;
                        white-space: nowrap;
                        /*text-overflow: ellipsis;*/
                }
                div.imgBorder {
                        width: 356px;
                        height: 202px;
/*                        width: 94px;
                        height: 94px; */
                        border: 1px solid #E0DFE3;
                        background-color: #FFFFFF;
                        overflow: hidden;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                }

                a.Photo {
                        border: 0px;
                        margin: 0px;
                        padding: 0px;
                        color: #000000;
                        font-family: Tahoma, sans-serif;
                        font-weight: normal;
                        font-size: 11px;
                        text-decoration: none;
                }
                h1 {
                        font-family: Tahoma, sans-serif;
                        font-weight: bold;
                        font-size:40px;
                        text-decoration: none;
                   }

                .video-overlay-play-button {
                    box-sizing: border-box;
                    width: 100%;
                    height: 100%;
                    padding: 10px calc(50% - 50px);
                    position: absolute;
                    top: 0;
                    left: 0;
                    display: block;
                    opacity: 0.95;
                    cursor: pointer;
                    background-image: linear-gradient(transparent, #000);
                    transition: opacity 150ms;
                }

                .video-overlay-play-button:hover {
                    opacity: 1;
                }

                .video-overlay-play-button.is-hidden {
                    display: none;
                }

                </style>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.css" />
       </head>
       <h1>'. $album_name.' </h1>
       <body>'; 
    fwrite($index_file, $head);

    if ($handle = opendir($input_dir)) {
    while (false !== ($entry = readdir($handle))) {
            $file_parts = pathinfo($entry);
            $file_parts['extension'];


            // Rename file jika ada spasi pada nama
//            if (in_array($file_parts['extension'], $mp4_extensions)) {
//                $new_entry = str_replace(' ', '-', $entry);
//                if ($new_entry !== $entry) {
//                    rename("$input_dir$entry", "$input_dir$new_entry");
//                    $entry = $new_entry; // Update entry to the new name
//                }
//            }

   // Only process the correct extensions from your image folder
            if (in_array($file_parts['extension'], $correct_extensions)){
                $link = '<div class="Photo" align="center"><div class="imgBorder" align="center"><a data-fancybox=gallery href="';
                $link .= "/tes/vid/images/$entry";
                $link .= '"><img src=' . '"' . $thumb_dir . '' . $entry .'.jpg "/>';

                //Add play button for mp4 videos
                if (in_array($file_parts['extension'], $mp4_extensions)){
                    $link .= '<svg class="video-overlay-play-button" viewBox="0 0 200 200" alt="Play video">
                                <circle cx="100" cy="100" r="90" fill="none" stroke-width="15" stroke="#fff"/>
                                <polygon points="70, 55 70, 145 145, 100" fill="#fff"/>
                            </svg>';
                }

// Add play button for mp4 videos
if (in_array($file_parts['extension'], $mp4_extensions)) {
    $link .= '<div class="video-container">
                <video class="video-overlay" width="350" height="200" muted playsinline>
                    <source src="/tes/vid/images/' . $entry . '" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <svg class="video-overlay-play-button" viewBox="0 0 200 200" alt="Play video">
                    <circle cx="100" cy="100" r="90" fill="none" stroke-width="15" stroke="#fff"/>
                    <polygon points="70, 55 70, 145 145, 100" fill="#fff"/>
                </svg>
              </div>';
}

                // Put the image name in a href, by default this is hidden with CSS
                // Change CSS overflow option to make the image name visible
//                $link .= "</a></div><a href=" . '"'. $input_dir.$entry .'" class="Photo">'. $entry. '</a></div>';
                $link .= "</a></div><a href=" . '"'. '/tes/vid/images/' .$entry .'" class="Photo">'. $entry. '</a></div>';
                $newline = PHP_EOL . $link;
                fwrite($index_file, $newline);
        }
    }
    closedir($handle);
    $bottom = '<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.umd.js"></script>';
    $bottom .= '</body></html>';

    fwrite($index_file, $bottom);
    fclose($index_file);

    }
}
echo "All done!\n";

?>
