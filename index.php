<?php

$MAX_FILE_SIZE = '200m';                // Default max size of 200MB
$TEMP_AUDIO_FILE = 'audio-download';    // File name for server download
$AUDIO_FORMATS = array('mp3', 'flac');  // Available audio formats

// Handle form input
function handle_form()
{
  global $ERROR, $MAX_FILE_SIZE, $TEMP_AUDIO_FILE, $AUDIO_FORMATS;

  // Delete old audio downloads
  exec("rm -f $TEMP_AUDIO_FILE.*");

  if(isset($_POST['url']) && !empty($_POST['url']) &&
    isset($_POST['format']) && !empty($_POST['format'])) {

    $url = $_POST['url'];
    $format = $_POST['format'];

    // Check that url is valid
    if(filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
      $ERROR = "Invalid URL.";
      return FALSE;
    }

    // Check that format input is in format whitelist
    if(!in_array($format, $AUDIO_FORMATS)) {
      global $ERROR;
      $ERROR = "Invalid file format.";
      return FALSE;
    }

    $url = escapeshellarg($url); // Escape URL

    // Obtain filename
    exec("youtube-dl --cache-dir .cache --get-filename $url -o '%(title)s'", $output, $ret);
    $filename = "$output[0].$format"; // TODO Not escaped (http header injection?)

    // Check that filename is retreived successfully
    if($ret !== 0) {
      $ERROR = "Could not access video.";
      return FALSE;
    }

    // Download audio file
    exec("youtube-dl --cache-dir .cache --max-filesize 200m -x --audio-format=$format $url -o '$TEMP_AUDIO_FILE.%(ext)s'", $output, $ret);

    // Check that audio is downloaded/converted successfully
    if($ret !== 0) {
      $ERROR = "Could not download/convert video.";
      return FALSE;
    }

    // Set mime-type header
    header("Content-Type: application/$format");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Length: " . filesize("$TEMP_AUDIO_FILE.$format"));

    // Read downloaded audio file
    readfile("$TEMP_AUDIO_FILE.$format");

    exit(0);
  }
  return TRUE;
}

handle_form();

?><!Doctype html>
<html>
  <head>
    <title>YouTube Audio Download</title>
    <meta name="robots" content="noindex,nofollow" />
  </head>
  <body>
    <h1>YouTube Audio Download</h1>
    <hr/><br/>
    <?php if(isset($ERROR)) echo '<em style="color:red">' . "ERROR: $ERROR</em><br/>"; ?>
    <form action="" method="post">
      <table>
        <tr><th>YouTube URL</th><th>Download File Type</th><th>Finish</th></tr>
        <tr>
          <td><input type="text" name="url" id="yt-url" /></td>
          <td>
            <select name="format">
              <option value="mp3">mp3</option>
              <option value="flac">flac</option>
            </select>
          </td>
          <td><input type="submit" value="Download"/></td>
        </tr>
      </table>
    </form>
    <p>Note: The size of video clips is limited to 200MB.</p>
    <script>
      // Auto focus input
      document.getElementById("yt-url").focus();
    </script>
	</body>
</html>

