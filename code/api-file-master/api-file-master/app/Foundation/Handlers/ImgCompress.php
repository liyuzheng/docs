<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2017/10/21
 * Time: 03:01
 */

namespace App\Foundation\Handlers;

/**
 * 图片压缩类：通过缩放来压缩。
 * 如果要保持源图比例，把参数$percent保持为1即可。
 * 即使原比例压缩，也可大幅度缩小。数码相机4M图片。也可以缩为700KB左右。如果缩小比例，则体积会更小。
 *
 * 结果：可保存、可直接显示。
 */
class ImgCompress
{

    private $width   = null;
    private $height  = null;
    private $src     = null;
    private $image;
    private $imageInfo;
    private $percent = 0.5;

    /**
     * @return $this
     */
    private function getSrc()
    {
        return $this->src;
    }

    /**
     * 设置图片路径
     *
     * @param $src
     *
     * @return $this
     */
    public function setSrc($src)
    {
        $this->src = $src;

        return $this;
    }

    /**
     * @return float|int
     */
    private function getPercent()
    {
        return $this->percent;
    }

    /**
     * 设置压缩比0 - ~
     * 代表压缩和放大
     *
     * @param $percent
     *
     * @return $this
     */
    public function setPercent($percent)
    {
        $this->percent = $percent;

        return $this;
    }


    /**
     * 设置缩率图尺寸
     *
     * @param  int  $width
     * @param  int  $height
     *
     * @return $this
     */
    public function setImageSize($width, $height)
    {
        $this->width  = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * 获得缩率图
     *
     * @return array
     */
    private function getImageSize()
    {
        if ($this->width === null || $this->height === null) {
            $this->width  = $this->imageInfo['width'] * $this->percent;
            $this->height = $this->imageInfo['height'] * $this->percent;
        }

        return array($this->width, $this->height);

    }

    /**
     * 高清压缩图片
     *
     * @param  string  $saveName  提供图片名（可不带扩展名，用源图扩展名）用于保存。或不提供文件名直接显示
     */
    public function compressImg($saveName = '')
    {
        $this->_openImage();
        if (!empty($saveName)) {
            //保存
            $this->_saveImage($saveName);
        } else {
            $this->_showImage();
        }

    }

    /**
     * 内部：打开图片
     */
    private function _openImage()
    {
        list($width, $height, $type, $attr) = getimagesize($this->src);
        $this->imageInfo = array(
            'width'  => $width,
            'height' => $height,
            'type'   => image_type_to_extension($type, false),
            'attr'   => $attr
        );
        $fun             = "imagecreatefrom" . $this->imageInfo['type'];
        $this->image     = $fun($this->src);
        $this->_thumpImage();
    }

    /**
     * 内部：操作图片
     */
    private function _thumpImage()
    {
        list($width, $height) = $this->getImageSize();
        $image_thump = imagecreatetruecolor($width, $height);
        //将原图复制带图片载体上面，并且按照一定比例压缩,极大的保持了清晰度
        imagecopyresampled($image_thump, $this->image, 0, 0, 0, 0, $width, $height, $this->imageInfo['width'],
            $this->imageInfo['height']);
        imagedestroy($this->image);
        $this->image = $image_thump;
    }

    /**
     * 输出图片:保存图片则用saveImage()
     */
    private function _showImage()
    {
        header('Content-Type: image/' . $this->imageInfo['type']);
        $funcs = "image" . $this->imageInfo['type'];
        $funcs($this->image);
    }

    /**
     * 保存图片到硬盘
     *
     * @param $dstImgName  1、可指定字符串不带后缀的名称，使用源图扩展名 。2、直接指定目标图片名带扩展名。
     *
     * @return bool
     */
    private function _saveImage($dstImgName)
    {
        if (empty($dstImgName)) {
            return false;
        }
        //如果目标图片名有后缀就用目标图片扩展名 后缀，如果没有，则用源图的扩展名
        $allowImgs = ['.jpg', '.jpeg', '.png', '.bmp', '.wbmp', '.gif'];
        $dstExt    = strrchr($dstImgName, ".");
        $sourseExt = strrchr($this->src, ".");
        if (!empty($dstExt)) {
            $dstExt = strtolower($dstExt);
        }
        if (!empty($sourseExt)) {
            $sourseExt = strtolower($sourseExt);
        }
        //有指定目标名扩展名
        if (!empty($dstExt) && in_array($dstExt, $allowImgs)) {
            $dstName = $dstImgName;
        } elseif (!empty($sourseExt) && in_array($sourseExt, $allowImgs)) {
            $dstName = $dstImgName . $sourseExt;
        } else {
            $dstName = $dstImgName . $this->imageInfo['type'];
        }
        $funcs = "image" . $this->imageInfo['type'];
        $funcs($this->image, $dstName);
    }

    /**
     * 销毁图片
     */
    public function __destruct()
    {
        imagedestroy($this->image);
    }
}