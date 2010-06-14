#!/usr/bin/env php
<?php
/***************************************************************************
 *   Copyright (C) 2008 by Ponetayev Ilya aka INSTE                        *
 *   instenet@gmail.com                                                         *
 *   ICQ : 473409594, Jabber/XMPP : inste@jabber.org                       * 
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 *   This program is distributed in the hope that it will be useful,       *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *   GNU General Public License for more details.                          *
 *                                                                         *
 *   You should have received a copy of the GNU General Public License     *
 *   along with this program; if not, write to the                         *
 *   Free Software Foundation, Inc.,                                       *
 *   59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.             *
 ***************************************************************************/

$isReadGIF = FALSE;
$isWriteGIF = FALSE;
$isReadWBMP = FALSE;
$isReadXPM = FALSE;
$isReadXBM = FALSE;
$OutInter = FALSE;
$slash = '/'; //POSIX slash (Linux, BSD) by default
$PREFIX = "imgps_";
$QDEF = 80; // Default JPEG quality

define("IMAGETYPE_GD", "101");
define("IMAGETYPE_GD2", "102");

/*
  Return position of the final slash in the string
*/

function file_getlastslash($str)
{
  global $slash;
  $ps = strpos($str, $slash);
  if ($ps == FALSE)
  {
    return 0;
  };
  while (strpos($str, $slash, $ps + 1) != FALSE)
  {
   $ps = strpos($str, $slash, $ps + 1);
  };
  return $ps; 
};

/*
  Return filename with extension from fullname (with path)
*/

function file_pathtoname($str)
{
  $ps = file_getlastslash($str);
  $s = substr($str, $ps + 1, strlen($str) - $ps);
  return $s;
};

/*
  Return short name (without extension) form filename (without path)
*/

function file_fulltoshort($str)
{
 return substr($str,0,strlen($str) - 4);
};

/*
  Return extension form file name
*/

function file_fulltoext($str)
{
 return substr($str,strlen($str) - 3,strlen($str));
};

/*
  Converting image (core function)
*/

function imageconvert($infilename, $outfilename, $outformat, $dh, $dw, $quality)
{
 global $isWriteGIF;
 global $isReadGIF;
 global $OutInter;
 $infmt = exif_imagetype($infilename);
 if ($infmt == IMAGETYPE_JPEG)
 {
  $image = imagecreatefromjpeg($infilename);
  $IF = "JPEG";
 };
 if ($infmt == IMAGETYPE_PNG)
 {
  $image = imagecreatefrompng($infilename);
  $IF = "PNG";
 };
 if (($infmt == IMAGETYPE_GIF) AND $isReadGIF)
 {
  $image = imagecreatefromgif($infilename);
  $IF = "GIF";
 };
if ($image != FALSE)
{
 $in = getimagesize($infilename);
 $inheight = $in[1];
 $inwidth = $in[2];
 $sw = imagesx($image);
 $sh = imagesy($image);
 if (($dh == -5) AND ($dw == -5))
 {
  /*
    Don't resize
  */
   $dh = $sh;
   $dw = $sw;
 };
 if ($dh == -1) // Autocomputing size
 { 
   $dh = (int)$dw*($sh/$sw);
 };
 $oimage = imagecreatetruecolor($dw, $dh);
 if ($outformat == -1) // Autocomputing format
 {
  $outformat = $infmt;
 };
 if ($outformat == IMAGETYPE_GIF)
 {
    $OF = "GIF";
  if ($isWriteGIF)
  {
    $OF = "GIF";
  } else
  {
    $outformat = IMAGETYPE_JPEG;
  }; 
 };
 if ($outformat == IMAGETYPE_JPEG)
 {
  $OF = "JPEG";
 };
 if ($outformat == IMAGETYPE_PNG)
 {
  $OF = "PNG";
 };
 if ($outformat == IMAGETYPE_GD)
 {
  $OF = "GD";
 };
 if ($outformat == IMAGETYPE_GD2)
 {
  $OF = "GD2";
 };
 if (($OutInter) AND ($outformat != IMAGETYPE_GD2) AND ($outformat != IMAGETYPE_GD))
 {
  $OF = $OF."(i)";
 };
 print("-> Converting '".file_pathtoname($infilename)."' (".$IF." -> ".$OF."; ".$sw."x".$sh." -> ".$dw."x".$dh.")...");
 imagecopyresampled($oimage, $image, 0, 0, 0, 0, $dw, $dh, $sw, $sh);
 if (($OutInter) AND ($outformat != IMAGETYPE_GD2) AND ($outformat != IMAGETYPE_GD))
 {
  imageinterlace($oimage);
 };
 if ($outformat == IMAGETYPE_JPEG)
 {
  imagejpeg($oimage, $outfilename, $quality);
 };
 if ($outformat == IMAGETYPE_PNG)
 {
  imagepng($oimage, $outfilename);
 };
 if ($outformat == IMAGETYPE_GIF)
 {
  imagegif($oimage, $outfilename);
 };
 if ($outformat == IMAGETYPE_GD)
 {
  imagegd($oimage, $outfilename);
 };
 if ($outformat == IMAGETYPE_GD2)
 {
  imagegd2($oimage, $outfilename);
 };
 print("  DONE!".chr(10));
 return 1;
} else
 {
  print("-> Cannot read file '".file_pathtoname($infilename)."': Format doesn't supported!".chr(10));
  return 0;
 };
};
 
/*
  Preparing filenames for new files
*/

function file_preparefilename($out, $format, $name)
{
  global $PREFIX;
  $newname = "-1";
  if ($format == $out)
  {
    $old = file_fulltoshort($name);
    if ($name != $old.$newext)
    {
      $newname = $old.$newext;
    } else
    {
      $newname = $PREFIX.$old.$newext;
    };
   };
 return $newname;
};

/*
  Preparing and convert image
*/

function file_convert($readname, $OUTFMT, $h, $w, $q, $rmsrc)
{
global $PREFIX;
if (file_exists($readname))
    {
     $name = $readname;
     if ($OUTFMT != -1)
     {
       if ($OUTFMT == IMAGETYPE_JPEG)
       {
         $newext = ".jpg";
         $old = file_fulltoshort($name);
         if ($name != $old.$newext)
         {
           $newname = $old.$newext;
         } else
         {
           $newname = $PREFIX.$old.$newext;
         };
       };
       if ($OUTFMT == IMAGETYPE_PNG)
       {
         $newext = ".png";
         $old = file_fulltoshort($name);
         if ($name != $old.$newext)
         {
           $newname = $old.$newext;
         } else
         {
           $newname = $PREFIX.$old.$newext;
         };
       };
       if ($OUTFMT == IMAGETYPE_GIF)
       {
         $newext = ".gif";
         $old = file_fulltoshort($name);
         if ($name != $old.$newext)
         {
           $newname = $old.$newext;
         } else
         {
           $newname = $PREFIX.$old.$newext;
         };
       };
       if ($OUTFMT == IMAGETYPE_GD)
       {
         $newext = ".gd";
         $old = file_fulltoshort($name);
         if ($name != $old.$newext)
         {
           $newname = $old.$newext;
         } else
         {
           $newname = $PREFIX.$old.$newext;
         };
       };
       if ($OUTFMT == IMAGETYPE_GD2)
       {
         $newext = ".gd2";
         $old = file_fulltoshort($name);
         if ($name != $old.$newext)
         {
           $newname = $old.$newext;
         } else
         {
           $newname = $PREFIX.$old.$newext;
         };
       };
     } else
     {
       $newname = $PREFIX.$name;
     }; 
     $rslt = imageconvert($name, $newname, $OUTFMT, $h, $w, $q);
     if ($rmsrc)
     {
       unlink($name);
     };
     return $rslt;
    }
    else
    {
      print("-> File doesn't exist, skipping!".chr(10));
      return 0;
    };
};

/*
   Main code
*/

list($usec, $sec) = explode(" ",microtime()); $sysstart = ((float)$usec + (float)$sec);
print("Starting Image Processing Tool @ PHP v 0.2/100808".chr(10));
print("Published under GNU GPL v2.".chr(10));
print("Your PHP version: '".PHP_VERSION."'.".chr(10));
print("(c) Ponetayev Ilya aka INSTE, 2008.".chr(10));
print("-> Checking OS version... ");
if ((PHP_OS == "Linux") OR (PHP_OS == "FreeBSD"))
{  
$slash = '/';
print(PHP_OS.", POSIX slash: '".$slash."'.".chr(10));
};
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
{ 
$slash = '\\';
print(PHP_OS.", Win slash: '".$slash."'.".chr(10));
};

$gi = gd_info();
if (($gi != FALSE) AND (strpos($gi["GD Version"], "2.0") != FALSE))
{
  
  print("-> Checking GD version... '".$gi["GD Version"]."', passed.".chr(10));
} else
{
  die("-> Your PHP configuration doesn't support GD or version too old!".chr(10));
};
print("-> Checking for reading GIF support in GD2...");
if ($gi["GIF Read Support"])
{
  print(" Yes, reading GIF/GD2 works.".chr(10));
  $isReadGIF = TRUE;
} else
{
  print(" No, not found.".chr(10));
  $isReadGIF = FALSE;
};
print("-> Checking for writing GIF support in GD2...");
if ($gi["GIF Create Support"])
{
  print(" Yes, creating GIF/GD2 works.".chr(10));
  $isWriteGIF = TRUE;
} else
{
  print(" No, not found.".chr(10));
  $isWriteGIF = FALSE;
};
print("-> Checking for reading WBMP support in GD2...");
if ($gi["WBMP Support"])
{
  print(" Yes, reading WBMP/GD2 works.".chr(10));
  $isReadWBMP = TRUE;
} else
{
  print(" No, not found.".chr(10));
  $isReadWBMP = FALSE;
};

$types = imagetypes();
print("-> Checking for support JPEG in GD2...");
if ($types & IMG_JPEG)
{
  print(" Yes, JPEG/GD2 works.".chr(10));
} else
{
  die(chr(10)."-> You don't have support JPEG in GD2 and PHP!".chr(10));
};
print("-> Checking for support PNG in GD2...");
if ($types & IMG_PNG)
{
  print(" Yes, PNG/GD2 works.".chr(10));
} else
{
  die(chr(10)."-> You don't have support PNG in GD2 and PHP!".chr(10));
};

print("-> Analyzing command-line arguments...");

if ($argc < 2)
{
  die(chr(10).'-> Run "php imgps.php -h" for help in use.'.chr(10)); 
};

/*
 Describes output format, if "-j" then JPEG, if "-p" then PNG, 
 if "-n" then same as input file (No change, resize only).
*/
$firstcmd = $argv[1]; 
/*
 Describes processing options, if "-i" then read filenames from ARGV/ARGC, 
 if "-f" then from file from ARGV[4]. 
 If this argument contains "r" then source files will be removed after conversion.
 If this argument contains "l" then output files will be interleaved.
 For example: "-irl"
*/
if ($argc > 2)
 { $scndcmd = $argv[2]; };
/*
 Describes output size, may be "1000x500" or only _WIDTH_ ("1000"). If you
 specify only width, height will be computed to save proportional
 of source image, height output := (int)(wigth output * (height input / width input))
 For JPEG you can choose quality in band 0..100, command should be like that:
 "1024x768:75" or "1024:75".
*/
if ($argc > 2)
{ $thrdcmd = $argv[3]; };

if (($firstcmd == "-h") OR ($firstcmd == "--help"))
{
 print(" DONE!".chr(10)); 
 echo 
'Usage:
(files from argv): php imgps.php [frmt] -i{rl} [width{xheight}{:quality}] ...files...
(files from list): php imgps.php [frmt] -f{rl} [width{xheight}{:quality}] filelist.txt
[frmt]: 
  -> "-j" : Convert all to JPEG.
  -> "-p" : Convert all to PNG.
  -> "-g" : Convert all to GIF (if supported).
  -> "-n" : Don\'t convert, resize only.
[{rl}]"
  -> "r" : Remove source files after conversion.
  -> "l" : Make target files interleaved.
[width{xheight}{:quality}]:
  -> width : width of output image in pixels, "width".
  -> height (optional) : heigth of output image in pixels.
  -> quality (optional) : quality of output image in range "0..100". (default:\''.$QDEF.'\').
     has effect only for JPEG output.
  -> examples: "1024x768:60", "1200:45", "1600", "1600x1050".
  -> If you don\'t want resize (convert only), specify "-n" in this parameter.
';
 die('');
};


if ($firstcmd == "-j")
 { $OUTFMT = IMAGETYPE_JPEG; };
if ($firstcmd == "-p")
 { $OUTFMT = IMAGETYPE_PNG; };
if ($firstcmd == "-g")
 { 
   if (!$isWriteGIF)
   { 
     die("-> You can't create GIF files, your PHP/GD2 don't support it!".chr(10));
   } else
   {
      $OUTFMT = IMAGETYPE_GIF;
   }; 
 };
if ($firstcmd == "-gd")
 {
  $OUTFMT = IMAGETYPE_GD;
 };
if ($firstcmd == "-gd2")
 {
  $OUTFMT = IMAGETYPE_GD2;
 };
if (($firstcmd != "-j") AND ($firstcmd != "-p") 
    AND ($firstcmd != "-g") AND ($firstcmd != "-gd") 
    AND ($firstcmd != "-gd2"))
 { $OUTFMT = -1; }; // Autocomputing 
 

if ($thrdcmd != "-n")
{
if (strpos($thrdcmd, "x") != FALSE)
 {
  if (strpos($thrdcmd, ":") != FALSE)
  {
   $w = (int)substr($thrdcmd, 0, strpos($thrdcmd, "x"));
   $h = (int)substr($thrdcmd, strpos($thrdcmd, "x") + 1, strpos($thrdcmd, ":"));
   $q = (int)substr($thrdcmd, strpos($thrdcmd, ":") + 1, strlen($thrdcmd));
  } else
  {
   $w = (int)substr($thrdcmd, 0, strpos($thrdcmd, "x"));
   $h = (int)substr($thrdcmd, strpos($thrdcmd, "x") + 1, strlen($thrdcmd));
   $q = $QDEF;
  };
 } else
 {
  if (strpos($thrdcmd, ":") != FALSE) 
  {
   $w = (int)substr($thrdcmd, 0, strpos($thrdcmd, ":"));
   $q = (int)substr($thrdcmd, strpos($thrdcmd, ":") + 1, strlen($thrdcmd));
   $h = -1; // Autocomputing
  } else
  {
   $w = (int)$thrdcmd;
   $h = -1; // Autocomputing
   $q = $QDEF;
  };
};

if (($q < 0) OR ($q > 100))
{
  $q = $QDEF;
};

} else
{
  /*
    Don't resize, convert only.
  */
  $h = -5;
  $w = -5;
  $q = $QDEF;
};
/*
  Remove source after image converting
*/
if (strpos($scndcmd, "r") != FALSE)
{
  $RemoveSource = TRUE;
} else
{
  $RemoveSource = FALSE;
};
/*
  Interlace output image
*/
if (strpos($scndcmd, "l") != FALSE)
{
  $OutInter = TRUE;
} else
{
  $OutInter = FALSE;
};

if (strpos($scndcmd, "f") != FALSE)
{
 $scndcmd = "-f";
};

if (strpos($scndcmd, "i") != FALSE)
{
 $scndcmd = "-i";
};

print("   DONE!".chr(10));

if ($scndcmd == "-i")
 {
  print("-> Converting files from cmd-line...".chr(10));
  for($i = 4;$i < $argc;$i++)
  {
    if (file_exists($argv[$i]))
    {
     $rslt = file_convert($argv[$i], $OUTFMT, $h, $w, $q, $RemoveSource);
     if ($rslt == 1)
     {
      $prcnt = (int)((($i - 3) / ($argc - 4)) * 100);
      list($usec, $sec) = explode(" ",microtime());
      $sysstop = ((float)$usec + (float)$sec);
      $dt = round($sysstop - $sysstart,1);
      $eta = $dt * ($argc - $i);
      $neta = $dt * ($i - 4);
      print("----> Total $prcnt% done, Time: $neta s, ETA: $eta s.".chr(10));
      list($usec, $sec) = explode(" ",microtime()); $sysstart = ((float)$usec + (float)$sec);
     };
    }; 
  };
 };

if ($scndcmd == "-f")
{
 if (file_exists($argv[4]))
 {
  print("-> Converting files from the filelist '".$argv[4]."'...".chr(10));
  $fd = fopen($argv[4], "r");
  while (!feof($fd))
  {
    $readname = fgets($fd, 1024);
    $readname = substr($readname, 0, strlen($readname) - 1);
    file_convert($readname, $OUTFMT, $h, $w, $q, $RemoveSource);
  };
  fclose($fd);
 };
};

print("-> Has completed!".chr(10).chr(10));
?>