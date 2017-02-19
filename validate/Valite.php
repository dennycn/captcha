<?php
/**
@beief: 
验证码识别的步骤: 取出字模(建立特征库, 如图片数字1-9), 二值化去燥(图片中验证码部分为1, 背景为0), 计算特征, 对照样本.
样本限制：只针对数值，样本数字的长宽高是预定义的，样本数字只有简单燥点（对于图片数字加斜线、倾斜都无法正确处理）
	此外，exif_imagetype~图片格式暂只支持png/jpeg/gif，不支持bmp格式。
@author: denny
@date: 2013-08-28
**/

/* Report all errors except E_NOTICE */
error_reporting(E_ALL^E_NOTICE);

//定义图形中字模的长/宽/横边距/竖边距/字间隔
define('WORD_WIDTH', 9);
define('WORD_HIGHT', 13);
define('OFFSET_X', 7);
define('OFFSET_Y', 3);
define('WORD_SPACING', 4);

class Valite
{
    public function setImage($Image)
    {
        $this->ImagePath = $Image;
    }
    public function getData()
    {
        return $data;
    }
    public function getResult()
    {
        return $DataArray;
    }

    // 根据图片类型读图像：根据后缀名来识别图片类型调用，file_name 为图片全路径，add by Denny, 20130814
    public function getImage($file_name)
    {
        $extend="";
        $pt=strrpos($file_name, ".");
        $img;
        if ($pt) {
            $extend=substr($file_name, $pt+1, strlen($file_name) - $pt);

            if (strcmp($extend, "jpg") || strcmp($extend, "jpeg")) {
                $img = imagecreatefromjpeg($this->ImagePath);
            } elseif (strcmp($extend, "gif") ) {
                $img = imagecreatefromgif($this->ImagePath);
            } elseif (strcmp($extend, "png") ) {
                $img = imagecreatefrompng($this->ImagePath);
            } elseif (strcmp($extend, "bmp") ) {
                $img = imagecreatefrombmp($this->ImagePath);
            }else{  // default: null
            }
        }// end if($pt)
       return $img;
    }

    // 根据图片类型读图像：调用exif_imagetype，file_name 为图片全路径
    public function getImage2($file_name)
    {
		$image = null;
        $type = exif_imagetype($file_name);
        if($type==IMAGETYPE_GIF){
	        $image = imagecreatefromgif($file_name);
        }elseif($type==IMAGETYPE_JPEG){
	        $image = imagecreatefromjpeg($file_name);
        }elseif($type==IMAGETYPE_PNG){
	        $image = imagecreatefrompng($file_name);
        }
        return $image;
    }

    // 读远程图像: 尚未测试过
    public function getRemoteImage(){
        $url = "http://localhost/php/gen/checkcode.php";
        if(empty($url)){
	        echo "没有图片";
	        die;
        }
		$this->ImagePath = $url;
		return $this->getHec();
    }

    // 取出图片中的字模，过程中排除干扰素
    public function getHec()
    {
		$image = $this->getImage2($this->ImagePath);
        // TEST
        $size = getimagesize($this->ImagePath);
        if ($size <=0 ){
            die();        
        }
        //print_r($size);        
        $data = array();
        for ($i=0; $i < $size[1]; ++$i)
        {
            for ($j=0; $j < $size[0]; ++$j)
            {
                $rgb = imagecolorat($image, $j, $i);
                $rgbarray = imagecolorsforindex($image, $rgb);
                //排除干扰素, 　二值化
                if ($rgbarray['red'] < 125 || $rgbarray['green']<125
                        || $rgbarray['blue'] < 125)
                {
                    $data[$i][$j]=1;
                } else {
                    $data[$i][$j]=0;
                }
            }
        }
        $this->DataArray = $data;
        $this->ImageSize = $size;
    }

	// 实行执行函数 
    public function run()
    {
		// 得到图片的二值化值：$this->DataArray, $this->ImageSize	
		$this->getHec();

        $result="";
        // 查找4个数字，将值存入$date[], 按示例模板切割
        $data = array("", "", "", "");
        for ($i=0; $i<4; ++$i)
        {
            $x = ($i*(WORD_WIDTH+WORD_SPACING))+OFFSET_X;
            $y = OFFSET_Y;
            for ($h = $y; $h < (OFFSET_Y+WORD_HIGHT); ++ $h)
            {
                for ($w = $x; $w < ($x+WORD_WIDTH); ++$w)
                {
                    $data[$i].=$this->DataArray[$h][$w];
                }
            }

        }

        // 进行关键字匹配
        foreach($data as $numKey => $numString)
        {
            $max=0.0;
            $num = 0;
            foreach($this->Keys as $key => $value)
            {
                $percent=0.0;
				// 文本相似度比较
                similar_text($value, $numString, $percent);
                if (intval($percent) > $max)
                {
                    $max = $percent;
                    $num = $key;
                    if (intval($percent) > 95)
                        break;
                }
            }
            $result.=$num;
        }
        $this->data = $result;
        // 查找最佳匹配数字
        return $result;
    }

    public function Draw()
    {
        for ($i=0; $i<$this->ImageSize[1]; ++$i)
        {
            for ($j=0; $j<$this->ImageSize[0]; ++$j)
            {
                echo $this->DataArray[$i][$j];
            }
            echo "\n";
        }
    }
    public function __construct()
    {
        //数字的二值化 1-9，这个字模值的获取？
        $this->Keys = array(
                          '0'=>'000111000011111110011000110110000011110000011110000011110000011110000011110000011110000011011000110011111110000111000', 
                          '1'=>'000111000011111000011111000000011000000011000000011000000011000000011000000011000000011000000011000011111111011111111', 
                          '2'=>'011111000111111100100000110000000111000000110000001100000011000000110000001100000011000000110000000011111110111111110', 
                          '3'=>'011111000111111110100000110000000110000001100011111000011111100000001110000000111000000110100001110111111100011111000', 
                          '4'=>'000001100000011100000011100000111100001101100001101100011001100011001100111111111111111111000001100000001100000001100', 
                          '5'=>
                              '111111110111111110110000000110000000110000000111110000111111100000001110000000111000000110100001110111111100011111000', 
                          '6'=>'000111100001111110011000010011000000110000000110111100111111110111000111110000011110000011011000111011111110000111100', 
                          '7'=>'011111111011111111000000011000000010000000110000001100000001000000011000000010000000110000000110000001100000001100000', 
                          '8'=>'001111100011111110011000110011000110011101110001111100001111100011101110110000011110000011111000111011111110001111100', 
                          '9'=>'001111000011111110111000111110000011110000011111000111011111111001111011000000011000000110010000110011111100001111000', 
                      );
    }
    protected $ImagePath; 
    protected $DataArray;
    protected $ImageSize;
    protected $data;
    protected $Keys;
    protected $NumStringArray;
    protected $res; //图片rgb, del

}
?>
