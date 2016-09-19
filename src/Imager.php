<?php

namespace App\Components;

use Nette;
use Nette\Utils\Image;

class Imager
{
    /**
     * todo: move to config
     * @var string
     */
    private static $THUMBS_RELATIVE_PATH = "./img/thumbs";

    /**
     * * todo: move to config
     * @var string
     */
    private static $THUMBS_ABSOLUTE_PATH = WWW_DIR . "/img/thumbs";

    /**
     * @param $end
     * @return mixed|null
     */
    private static function getType($end)
    {
        $types = ["png" => Image::PNG, "jpg" => Image::JPEG, "jpeg" => Image::JPEG, "gif" => Image::GIF];
        return isset($types[$end]) ? $types[$end] : null;
    }

    /**
     * todo: width, height params from config
     * @param $file
     * @param int $width
     * @param int $height
     * @param bool $reload
     * @return string
     * @throws Nette\Utils\UnknownImageFileException
     * @throws \Exception
     */
    public static function getThumbnail($file, $width = 90, $height = 90, $reload = false)
    {
        if(!file_exists(self::$THUMBS_ABSOLUTE_PATH))
        {
            mkdir(self::$THUMBS_ABSOLUTE_PATH);
        }

        if(strpos($file, ":") === false)
        {
            return self::getLocalThumbnail($file, $width, $height, $reload);
        }
        else
        {
            return self::getRemoteThumbnail($file, $width, $height, $reload);
        }
    }

    /**
     * todo: width, height params from config
     * @param $file
     * @param $width
     * @param $height
     * @param $reload
     * @return string
     * @throws Nette\Utils\UnknownImageFileException
     * @throws \Exception
     */
    private static function getLocalThumbnail($file, $width, $height, $reload)
    {
        if(!$file) $file = "/img/none.png";

        $ext        = pathinfo($file)["extension"];
        $hash       = md5($file) . '_' . ($width."_".$height) . "." . $ext;
        $local_file = WWW_DIR . $file;

        // if exists original local file
        if(file_exists($local_file))
        {
            if(in_array(strtolower($ext), ["png", "jpg", "jpeg", "gif"])) {
                // if not exist thumb
                if (!file_exists(self::$THUMBS_ABSOLUTE_PATH . "/" . $hash) || $reload) {
                    try {
                        $image = Image::fromFile($local_file);
                        $image->resize($width, $height, Image::SHRINK_ONLY | Image::STRETCH);
                        $image->save(self::$THUMBS_ABSOLUTE_PATH . "/" . $hash, 100, self::getType($ext));
                    }
                    catch(\Exception $e) {

                    }
                }
            }
            elseif($ext == "svg") {
                
            }
        }
        else
        {
            //throw new \Exception("File in '{$local_file}' not exists!");
            return "";
        }
        // return relative path to file
        return self::$THUMBS_RELATIVE_PATH . "/" . $hash;
    }

    /**
     * todo: width, height params from config
     * @param $file
     * @param $width
     * @param $height
     * @param $reload
     * @return string
     */
    private static function getRemoteThumbnail($file, $width, $height, $reload)
    {
        // todo: from config
        if(!$file) $file =  "/img/none.png";

        $ext        = pathinfo($file)["extension"];
        $hash       = md5($file) . '_' . ($width."_".$height) . "." . $ext;
        $local_path = self::$THUMBS_ABSOLUTE_PATH . "/remote/";
        $local_file = $local_path . $hash;

        // if not exists folder
        if (!file_exists($local_path))
        {
            mkdir($local_path, 0777, true);
        }

        // if not exists local copy
        if(!file_exists($local_file) || $reload)
        {
            Imager::downloadImage($file, $local_file);
        }

        // create thumb
        // todo: from config
        return self::getLocalThumbnail("/img/thumbs/remote/" . $hash, $width, $height, $reload);
    }

    /**
     * @param $imageUrl
     * @param $imageFile
     */
    public static function downloadImage($imageUrl, $imageFile){
        if(file_exists($imageFile) && self::compareImages($imageFile, $imageUrl)) {
            return;
        }

        $fp = fopen ($imageFile, 'w+');
        $ch = curl_init($imageUrl);

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }


    /**
     * @param $localFile
     * @param $remoteFile
     * @return bool
     */
    public static function compareImages($localFile, $remoteFile)
    {
        $curl = curl_init($remoteFile);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0');

        $data = curl_exec($curl);
        curl_close($curl);

        if ($data)  {
            $content_length = 0;
            $status = 0;

            if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) $status = (int)$matches[1];
            if (preg_match("/Content-Length: (\d+)/", $data, $matches)) $content_length = (int)$matches[1];
            if ($status == 200 || ($status > 300 && $status <= 308)) {
                return filesize($localFile) == $content_length;
            }
        }

        return false;
    }
}