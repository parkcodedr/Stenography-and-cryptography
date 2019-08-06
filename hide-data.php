<?php
function rc4( $key_str, $data_str ) {
    // convert input string(s) to array(s)
    $key = array();
    $data = array();
    for ( $i = 0; $i < strlen($key_str); $i++ ) {
       $key[] = ord($key_str{$i});
    }
    for ( $i = 0; $i < strlen($data_str); $i++ ) {
       $data[] = ord($data_str{$i});
    }
   // prepare key
    $state = array( 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,
                    16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,
                    32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,
                    48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,
                    64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,
                    80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,
                    96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,
                    112,113,114,115,116,117,118,119,120,121,122,123,124,125,126,127,
                    128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,
                    144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,
                    160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,
                    176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,191,
                    192,193,194,195,196,197,198,199,200,201,202,203,204,205,206,207,
                    208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,223,
                    224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,
                    240,241,242,243,244,245,246,247,248,249,250,251,252,253,254,255 );
    $len = count($key);
    $index1 = $index2 = 0;
    for( $counter = 0; $counter < 256; $counter++ ){
       $index2   = ( $key[$index1] + $state[$counter] + $index2 ) % 256;
       $tmp = $state[$counter];
       $state[$counter] = $state[$index2];
       $state[$index2] = $tmp;
       $index1 = ($index1 + 1) % $len;
    }
    // rc4
    $len = count($data);
    $x = $y = 0;
    for ($counter = 0; $counter < $len; $counter++) {
       $x = ($x + 1) % 256;
       $y = ($state[$x] + $y) % 256;
       $tmp = $state[$x];
       $state[$x] = $state[$y];
       $state[$y] = $tmp;
       $data[$counter] ^= $state[($state[$x] + $state[$y]) % 256];
    }
    // convert output back to a string
    $data_str = "";
    for ( $i = 0; $i < $len; $i++ ) {
       $data_str .= chr($data[$i]);
    }
    return $data_str;
 }

ini_set("max_execution_time",10000);

function is_even($num)
{
  // returns true if $num is even, false if not
  return ($num%2==0);
}

function asc2bin($char)
{
  // returns 8bit binary value from ASCII char
  // eg; asc2bin("a") returns 01100001
  return str_pad(decbin(ord($char)), 8, "0", STR_PAD_LEFT);
}

function bin2asc($bin)
{
  // returns ASCII char from 8bit binary value
  // eg; bin2asc("01100001") returns a
  // argument MUST be sent as string
  return chr(bindec($bin));
}

function rgb2bin($rgb)
{
  // returns binary from rgb value (according to evenness)
  // this way, we can store one ascii char in 2.6 pixels
  // not a great ratio, but it works (albeit slowly)

  $binstream = "";
  $red = ($rgb >> 16) & 0xFF;
  $green = ($rgb >> 8) & 0xFF;
  $blue = $rgb & 0xFF;

  if(is_even($red))
  {
      $binstream .= "1";
  } else {
      $binstream .= "0";
  }
  if(is_even($green))
  {
      $binstream .= "1";
  } else {
      $binstream .= "0";
  }
  if(is_even($blue))
  {
      $binstream .= "1";
  } else {
      $binstream .= "0";
  }

  return $binstream;
}

function steg_hide($maskfile, $hidefile)
{
  // hides $hidefile in $maskfile

  // initialise some vars
  $binstream = "";
  $recordstream = "";
  $make_odd = Array();

  // create images
  $pic = ImageCreateFromJPEG($maskfile['tmp_name']);
  $attributes = getImageSize($maskfile['tmp_name']);
  $outpic = ImageCreateFromJPEG($maskfile['tmp_name']);

  if(!$pic || !$outpic || !$attributes)
  {
      // image creation failed
      return "cannot create images - maybe GDlib not installed?";
  }

  // read file to be hidden
  $data = $hidefile;

  // generate unique boundary that does not occur in $data
  // 1 in 16581375 chance of a file containing all possible 3 ASCII char sequences
  // 1 in every ~1.65 billion files will not be steganographisable by this script
  // though my maths might be wrong.
  // if you really want to get silly, add another 3 random chars. (1 in 274941996890625)
  // ^^^^^^^^^^^^ would require appropriate modification to decoder.
  $boundary="";
  do
  {
      $boundary .= chr(rand(0,255)).chr(rand(0,255)).chr(rand(0,255));
  } while(strpos($data,$boundary)!==false && strpos('rahasia.txt',$boundary)!==false);

  // add boundary to data
  $data = $boundary.'rahasia.txt'.$boundary.$data.$boundary;
  // you could add all sorts of other info here (eg IP of encoder, date/time encoded, etc, etc)
  // decoder reads first boundary, then carries on reading until boundary encountered again
  // saves that as filename, and carries on again until final boundary reached

  // check that $data will fit in maskfile
  if(strlen($data)*8 > ($attributes[0]*$attributes[1])*3)
  {
      // remove images
      ImageDestroy($outpic);
      ImageDestroy($pic);
      return "Cannot fit ".'rahasia.txt'." in ".$maskfile['name'].".<br />"."rahasia.txt"." requires mask to contain at least ".(intval((strlen($data)*8)/3)+1)." pixels.<br />Maximum filesize that ".$maskfile['name']." can hide is ".intval((($attributes[0]*$attributes[1])*3)/8)." bytes";
  }
  
  // convert $data into array of true/false
  // pixels in mask are made odd if true, even if false
  for($i=0; $i<strlen($data) ; $i++)
  {
      // get 8bit binary representation of each char
      $char = $data{$i};
      $binary = asc2bin($char);

      // save binary to string
      $binstream .= $binary;

      // create array of true/false for each bit. confusingly, 0=true, 1=false
      for($j=0 ; $j<strlen($binary) ; $j++)
      {
          $binpart = $binary{$j};
          if($binpart=="0")
          {
              $make_odd[] = true;
          } else {
              $make_odd[] = false;
          }
      }
  }

  // now loop through each pixel and modify colour values according to $make_odd array
  $y=0;
  for($i=0,$x=0; $i<sizeof($make_odd) ; $i+=3,$x++)
  {
      // read RGB of pixel
      $rgb = ImageColorAt($pic, $x,$y);
      $cols = Array();
      $cols[] = ($rgb >> 16) & 0xFF;
      $cols[] = ($rgb >> 8) & 0xFF;
      $cols[] = $rgb & 0xFF;

      for($j=0 ; $j<sizeof($cols) ; $j++)
      {
          if($make_odd[$i+$j]===true && is_even($cols[$j]))
          {
              // is even, should be odd
              $cols[$j]++;
          } else if($make_odd[$i+$j]===false && !is_even($cols[$j])){
              // is odd, should be even
              $cols[$j]--;
          } // else colour is fine as is
      }

      // modify pixel
      $temp_col = ImageColorAllocate($outpic,$cols[0],$cols[1],$cols[2]);
      ImageSetPixel($outpic,$x,$y,$temp_col);

      // if at end of X, move down and start at x=0
      if($x==($attributes[0]-1))
      {
          $y++;
          // $x++ on next loop converts x to 0
          $x=-1;
      }
  }

  // output modified image as PNG (or other *LOSSLESS* format)
  $nama_gambar=rand(00000,99999)."encoded.jpeg";
  header("Content-Type: application/octet-stream");
  header("Content-Disposition: attachment; filename=$nama_gambar");
  header('Content-Transfer-Encoding: binary'); 
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); 
  ImagePNG($outpic);

  // remove images
  ImageDestroy($outpic);
  ImageDestroy($pic);
  exit();
}

function steg_recover($gambar)
{
  // recovers file hidden in a PNG image

  $binstream = "";
  $filename = "";

  // get image and width/height
  $attributes = getImageSize($gambar['tmp_name']);
  $pic = ImageCreateFromPNG($gambar['tmp_name']);

  if(!$pic || !$attributes)
  {
      return "could not read image";
  }

  // get boundary
  $bin_boundary = "";
  $boundary="";
  for($x=0 ; $x<8 ; $x++)
  {
      $bin_boundary .= rgb2bin(ImageColorAt($pic, $x,0));
  }
  
  // convert boundary to ascii
  for($i=0 ; $i<strlen($bin_boundary) ; $i+=8)
  {
      $binchunk = substr($bin_boundary,$i,8);
      $boundary .= bin2asc($binchunk);
  }


  // now convert RGB of each pixel into binary, stopping when we see $boundary again

  // do not process first boundary
  $start_x = 8;
  $ascii="";
  for($y=0 ; $y<$attributes[1] ; $y++)
  {
      for($x=$start_x ; $x<$attributes[0] ; $x++)
      {
          // generate binary
          $binstream .= rgb2bin(ImageColorAt($pic, $x,$y));
          // convert to ascii
          if(strlen($binstream)>=8)
          {
              $binchar = substr($binstream,0,8);
              $ascii .= bin2asc($binchar);
              $binstream = substr($binstream,8);
          }

          // test for boundary
          if(strpos($ascii,$boundary)!==false)
          {
              // remove boundary
              $ascii = substr($ascii,0,strlen($ascii)-3);

              if(empty($filename))
              {
                  $filename = $ascii;
                  $ascii = "";
              } else {
                  // final boundary; exit both 'for' loops
                  break 2;
              }
          }
      }
      // on second line of pixels or greater; we can start at x=0 now
      $start_x = 0;
  }

  // remove image from memory
  ImageDestroy($pic);

  /* and output result (retaining original filename)
  header("Content-type: text/plain");
  header("Content-Disposition: attachment; filename=".$filename);*/
  return $ascii;
}

if(!empty($_POST['secret']))
{
  // ensure a readable mask file has been sent
  $extension = strtolower(substr($_FILES['maskfile']['name'],-3));
  if($extension=="jpg")
  {
      // encrypt RC4
      $key = $_POST['key'];
      $plaintext = $_POST['secret'];;
      $ciphertextRC4 = rc4( $key, $plaintext );
      //$decrypted = rc4( $key, $ciphertext );
     
      // encrypt base64
      $base64=base64_encode($ciphertextRC4);		
      steg_hide($_FILES['maskfile'],$base64);
  } 
  else 
  {
      $result="Only .jpg mask files are supported";
      echo $result;
  }
  
} 





?>
<!doctype html>
<html class="no-js" lang="en">
<head>
    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1"/>
    <meta name="author" content="Theme Industry">
    <!-- description -->
    <meta name="description" content="boltex">
    <!-- keywords -->
    <meta name="keywords" content="">
        <title>Computerized Data Security</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700%7CRoboto%7CJosefin+Sans:100,300,400,500" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    

    <!-- <link rel="stylesheet" href="css/bootstrap3.min.css"> -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/slick.css">
    <link rel="stylesheet" href="css/cubeportfolio.min.css">
    <link rel="stylesheet" type="text/css" href="css/component.css" />

  </head>
  <body style="background-image:url(bg.png)">
  <nav class="navbar navbar-expand-lg fixed-top activate-menu navbar-light bg-light">
    <a class="navbar-brand" href="index.php">COMPUTERIZED DATA SECURITY</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse"    data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav ml-auto">
        <li >
          <a class="nav-link" href="index.php">Home</a>
        </li>
        <li>
          <a class="nav-link" href="#">About</a>
        </li>
        <li>
          <a class="nav-link" href="hide-data.php">Hide data</a>
        </li>
        <li>
          <a class="nav-link" href="#">Contact Us</a>
        </li>
        
      </ul>
    </div>
  </nav>

  <div id="features" class="container col-md-8 pt-15" style= "margin-top:70px; padding:30px">
     
          
      <!-- /.login-logo -->
  <div class="login-box-body">
	<div class="nav-tabs-custom">
		<?php
if(!empty($_FILES['gambar']['tmp_name'])) {
	$result = steg_recover($_FILES['gambar']);
	// decode base 64
	$base64=base64_decode($result);
	
	// decode RC4
	$key = $_POST['key_deskripsi'];
	$plaintext = rc4( $key, $base64 );
	
	?>
    


    <div class="card">
  <h5 class="card-header">Decruted Image</h5>
  <div class="card-body">
    
    <p class="card-text">
    <ul class="list-group list-group-flush">
    <li class="list-group-item"><label><b>Base64 Chipertext:</b></label><?php echo "   ".$result ?></li>
    <li class="list-group-item"><label><b>RC4 Chipertext:</b></label><?php echo  "  ".$base64 ?></li>
    <li class="list-group-item"><label><b>Key:</b></label><?php echo "   ".$key ?></li>
  </ul>

    </p>
    <hr>
    <div class="card bg-light mb-3" style="max-width: 58rem; col-md-8">
  <div class="card-header">Message</div>
  <div class="card-body">
    
    <p class="card-text"><?php echo $plaintext?></p>
  </div>
</div>
   
  </div>
</div>
    
				
		



	<?php
	
}
?>
        <ul class="nav nav-tabs pull-right">
            <li ><a href="#tab_2" class="" data-toggle="tab" ><button style="padding: 6px 12px;font-weight: normal;line-height: 1.42857143;font-size: 14px; cursor: pointer;display: inline-block;background-image: none;border: 1px solid transparent;border-radius: 4px;background:green; color:white"><i class="fa fa-unlock"> </i> Encryption  </button></a> </li>
            
            <li class="active"><a href="#tab_1" data-toggle="tab" ><button style="padding: 6px 12px;font-weight: normal;line-height: 1.42857143;font-size: 14px; cursor: pointer;display: inline-block;background-image: none;border: 1px solid transparent;border-radius: 4px;background:green; color:white"><i class="fa fa-lock"></i>  Decryption</button></a></li>
        </ul>
            <div class="tab-content">
              <div class="tab-pane" id="tab_1">
				<h2 align=center>Decryption</h2>
                <form action="<?php $_SERVER['PHP_SELF']?>" method="post" enctype="multipart/form-data">
				  <div class="form-group has-feedback">
					  <label for="key_deskripsi">Decryption Key:</label><span class="fa fa-key form-control-feedback"></span>
					<input type="text" name="key_deskripsi" id="key_deskripsi" class="form-control" placeholder="Enter Decrption Key" required>
					 
				  </div>
				  <label>Image to Decrypt (jpeg): </label>
				  <div class="input-group" style="margin-bottom:30px">
					<span class="input-group-addon"><i class="fa fa-image"></i></span>
					<input type="file" class="form-control" accept="image/*" name="gambar" id="gambar" required>
				  </div>
				  <div class="row">
					<!-- /.col -->
					<div class="col-xs-12">
					  <button type="submit" class="btn btn-primary btn-flat pull-right" style="margin-left:690px" data-toggle="modal" data-target="#myModal">Extract &nbsp <i class="fa fa-unlock"></i></button>
					</div>
					<!-- /.col -->
				  </div>
				</form>
				
              </div>
              <!-- /.tab-pane -->
              <div class="tab-pane active" id="tab_2">  
				<h2 align=center>Encryption</h2>
				<form id="form_stegano" action="<?php $_SERVER['PHP_SELF']?>" method="post" enctype="multipart/form-data">
				  <div class="form-group has-feedback">
				  <label for="key_deskripsi">Encryption Key:</label>
					<input type="text" id="key_enskripsi" name="key" class="form-control" placeholder="Enter Encryption Key">
					<span class=" form-control-feedback"></span>
				  </div>
				  <div class="form-group has-feedback">
				  <label for="key_deskripsi">Message:</label>
					<textarea id="secret" name="secret" class="form-control" rows=3 placeholder="Enter your Message" required></textarea>
					<span class=" form-control-feedback"></span>
				  </div>
				  <label>Image to Embede Message (jpg): </label>
				  <div class="input-group" style="margin-bottom:30px">
					<span class="input-group-addon"><i class="fa fa-image"></i></span>
				<!--<input type="file" class="form-control" accept="image/jpeg" name="maskfile" id="maskfile" required>-->
					<input type="file" class="form-control" accept="image/jpg" name="maskfile" required>
				  </div>
				  <div class="row">
					<!-- /.col -->
					<div class="col-xs-12">
					  <button type="submit" class="btn btn-primary btn-blovk pull-right" style="margin-left:690px" >Embed &nbsp <i class="fa fa-lock"></i></button>
					</div>
					<!-- /.col -->
				  </div>
				</form>
				
              </div>
              <!-- /.tab-pane -->
            </div>
            <!-- /.tab-content -->

    </div>	
  </div>
 
</div>
  <!-- /.login-box-body -->	
  </div>

  <!--================ Showcase section ===================-->
  
  </div>
  <footer class="text-center pos-re">
    <div class="container">
      <div class="footer__box">
        <!-- Logo -->
        <a class="logo" href="#">
           <p> By: <b>Ogbole Esther</b> </p>
        </a>

        <div class="social">
            <a href="#0"><i class="fa fa-facebook" aria-hidden="true"></i></a>
            <a href="#0"><i class="fa fa-twitter" aria-hidden="true"></i></a>
            <a href="#0"><i class="fa fa-linkedin" aria-hidden="true"></i></a>
        </div>

        <p>&copy; 2019 Computerized Data Security. All Rights Reserved.</p>
      </div>
    </div>

    <div class="curve curve-top curve-center"></div>
</footer>

  <script src="./js/jquery.min.js"></script>
  <script src="./js/modernizr.custom.js"></script>
  <script src="./js/bootstrap.min.js"></script>
  <script src="./js/slick.min.js"></script>
  <script src="./js/scrollreveal.min.js"></script>
  <script src="./js/jquery.cubeportfolio.min.js"></script>
  <script src="./js/jquery.matchHeight-min.js"></script>
  <script src="./js/masonry.pkgd.min.js"></script>
  <script src="./js/jquery.flexslider-min.js"></script>
  <script src="./js/classie.js"></script>
	<script src="./js/helper.js"></script>
  <script src="./js/grid3d.js"></script>
  <script src="./js/script.js"></script>


</body>
</html>
