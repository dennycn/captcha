<?php
// image format: ok~jpeg/png; bad~bmp
// test: 5496.jpeg->5541 (wrong), 2543.jpeg->2543 (right)
// brief: 识别正确率较低. 

include ('Valite.php');
//error_reporting(0);

function request_by_curl($remote_server, $post_string)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remote_server);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'mypost=' . $post_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
} 

function test($valite_test, $img_name, $type=1)
{
	if ($type == 1){ // local image
		$valite_test->setImage($img_name);
	} else if ($type == 2){  // remote image
		$valite_test->getRemoteImage();
	} else{
		$valite_test->setImage($img_name);
	}
	$code = $valite_test->run();
	printf("%s --> %s\n", $img_name, $code);
}

$valite = new Valite();
//test1: locate image
test($valite, "2534.png", 1);
//test($valite, "2534.gif", 1);
//test($valite, "2534.jpg", 1);
test($valite, "9118.png", 1);
test($valite, "9118.gif", 1);
//test($valite, "5496.jpeg");		
//test($valite, "2293.png", 1);
//test($valite, "8950.bmp");
//test($valite, "8950.jpg");

//test2: remote image
//test($valite, "2534.png", 2);

//test3: login
//$code = $valite->run();
///$remote = "http://localhost/php/gen/login.php";
//$post_str = 'check=$code';
//$data = request_by_curl($remote, $post_str);
//echo $data;

//echo "\n";

?>
