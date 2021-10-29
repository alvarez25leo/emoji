<?php

function multi_strpos($string, $check, $getResults = false)
{
  $result = array();
  $check = (array) $check;

  foreach ($check as $s)
  {
    $pos = strpos($string, $s);

    if ($pos !== false)
    {
      if ($getResults)
      {
        $result[$s] = $pos;
      }
      else
      {
        return $pos;          
      }
    }
  }

  return empty($result) ? false : $result;
}

function  customNameImage($string, $imgName){
    $checks  = ['-tone_'];

    if(sizeof($imgName) > 2){
        if (false !== $pos = multi_strpos($string, $checks)){
            $stringName = substr($string, $pos+6);
            return str_replace('_', '-',  $stringName);
        }
    }

    return str_replace('_', '-', $string);
    
}

function str_replace_first($search, $replace, $subject) {
    $pos = strpos($subject, $search);
    if ($pos !== false) {
        return substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}

function createArrayItem($img,$idImage){
    $imgUrl = substr($img->nodeValue, 0, -3);
    $imgNameUrl = explode('/', $imgUrl);
    $imgName =  explode('_', end($imgNameUrl));

    $imgNameEsplitted = $imgName[1];
    if(sizeof($imgName) > 2){
        $imgNameEsplitted = end($imgNameUrl);
    }

    $arrayImage = [
        // "id" => $idImage,
        "name" => $imgNameEsplitted,
        "imageName" => customNameImage($imgNameEsplitted,$imgName),
        "url144" => $imgUrl,
        "url72" => str_replace_first('144', '72', $imgUrl),
        "urlDowloader" => $imgUrl,
    ];

    return $arrayImage;
}

function try_fetch($url, $path){

	http_fetch($url, $path);

	if (!file_exists($path)){
		return false;
	}

	if (!filesize($path)){
		@unlink($path);
		return false;
	}

	# PNG signature?
	$fp = fopen($path, 'r');
	$sig = fread($fp, 4);
	fclose($fp);

	if ($sig != "\x89PNG"){
		#print_r($sig);
		@unlink($path);
		return false;
	}

	# ok!
	return true;
}

function http_fetch($url, $filename){

    
    if (file_exists($filename)){
		return ;
	}

	$fh = fopen($filename, 'w');

	$options = array(
		CURLOPT_FILE	=> $fh,
		CURLOPT_TIMEOUT	=> 60,
		CURLOPT_URL	=> $url,
	);

	$options[CURLOPT_HTTPHEADER] = array(
		'Referer: https://emojipedia.org/',
		'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.54 Safari/537.36',
	);

	$ch = curl_init();
	curl_setopt_array($ch, $options);
	curl_exec($ch);
	$ret = curl_getinfo($ch);
	curl_close($ch);

	fclose($fh);

	# show http error code?
	#echo "({$ret['http_code']})";

	#print_r($ret);
	#exit;
}

function saveImage72 ($url,$name){
    $img = "facebook144/{$name}";

    if (try_fetch($url, $img)){
		echo '.';
		return;
	}

    echo 'x';
}

print "Buscando emojis.... \n";

$urlPage = "https://emojipedia.org/facebook/";

    $html = file_get_contents($urlPage);
    $dom = new DOMDocument();
    libxml_use_internal_errors(1);
    $dom->loadHTML($html);

    $xpath = new DOMXpath($dom);
    $imgs = $xpath->query('//ul[@class="emoji-grid"]/li/a/img/@srcset');
    $imgsrc = $xpath->query('//ul[@class="emoji-grid"]/li/a/img/@data-srcset');

    $arrayImage= [];
    $idImage = 1;
    foreach ($imgs as $img) {
        $arrayImage[] = createArrayItem($img,$idImage);
        $idImage++;
    }

    foreach ($imgsrc as $img) {
        $arrayImage[] = createArrayItem($img,$idImage);
        $idImage++;
    }

    print $idImage ." emojis encontrados. \n";

    $inputArray = array_map("unserialize", array_unique(array_map("serialize", $arrayImage)));

    print sizeof($inputArray). " emojis disponibles para descargar. \n";

    // echo sizeof($inputArray);
    // echo json_encode($inputArray);
    // exit();
    print "Iniciando descarga.... \n";
    foreach ($arrayImage as $image) {
        saveImage72($image['urlDowloader'],$image['imageName']);
    }
    print "Finally finished \n";

?>
