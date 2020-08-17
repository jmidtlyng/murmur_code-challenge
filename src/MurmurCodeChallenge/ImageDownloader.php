<?php

namespace MurmurCodeChallenge;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class ImageDownloader
 * @package MurmurCodeChallenge
 *
 * Download images from single column CSV list of iamge URLs.
 */
class ImageDownloader
{
    // default config values if config is missing
    static $inputPath = 'input/images.csv';
    static $inputValidFileExtensions = ['jpeg', 'jpg', 'png'];
    static $outputDirectory = 'output/images';
    static $outputOverwritesImages = 0;

    static $errorLog;

    private static function init(){
        self::$errorLog = new Logger('imageDownloadLogger');
        self::$errorLog->pushHandler(new StreamHandler('output/errors.log', Logger::WARNING));

        if(!file_exists('src/config.json')){
            self::$errorLog->warning('Cannot find config file. Please see documentation in readme for this package.
                Executing using default settings.');
        } else {
            $configString = file_get_contents('src/config.json');
            $configJson = json_decode($configString, true);

            if($configJson !== NULL){
                self::$inputPath = $configJson['input']['path'];
                self::$inputValidFileExtensions = $configJson['input']['extensionsAllowed'];
                self::$outputDirectory = $configJson['output']['images']['directory'];
                self::$outputOverwritesImages = $configJson['output']['images']['overwrite'];
            } else {
                self::$errorLog->warning('Syntax error converting src/config.json to php array. Check src/config.json.
                    Executing using default settings.');
            }
        }
    }

    public static function execute(){
        self::init();

        if(!file_exists(self::$inputPath)){
            self::$errorLog->error('Cannot find input file. Please review readme and check
                src/config.json input path matches input file.');
        } else {
            // for Mac line endings
            $resetAutoDetectLineEndings = 0;
            if (!ini_get('auto_detect_line_endings')) {
                $resetAutoDetectLineEndings = 1;
                ini_set('auto_detect_line_endings', '1');
            }

            $imageListHandle = fopen(self::$inputPath, 'r');

            while (($imageEntry = fgetcsv($imageListHandle)) !== FALSE) {
                if($imageEntry[0]) {
                    $imageContent = self::fileGetContentCurl($imageEntry[0]);
                    $imageFileExtension = str_replace('image/', '', $imageContent['fileType']);

                    if($imageContent['data']){
                        if(in_array($imageFileExtension, self::$inputValidFileExtensions)){
                            $imageName = self::parseFileNameFromUrl($imageEntry[0]);
                            $imagePath = self::$outputDirectory . '/' . $imageName . '.' . $imageFileExtension;

                            if(self::$outputOverwritesImages){
                                unlink($imagePath);
                            } else {
                                $imagePath = self::versionFileName($imagePath, $imageFileExtension);
                            }

                            file_put_contents($imagePath, $imageContent['data']);
                        } else {
                            $fileTypeNotAllowedMsg = 'Did not download ' . $imageEntry[0] . '. File type '.
                                $imageContent['fileType'].' is not allowed by src/config.json.';
                            self::$errorLog->warning($fileTypeNotAllowedMsg);
                        }
                    } else {
                        $curlFailMsg = 'Failed to retrieve ' . $imageEntry[0] . '. Check URL.';
                        self::$errorLog->warning($curlFailMsg);
                    }
                }
            }

            if($resetAutoDetectLineEndings){
                ini_set('auto_detect_line_endings', '0');
            }
        }
    }

    private static function fileGetContentCurl($url){
        $curlResponse = [];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);


        $curlResponse['data'] = curl_exec($ch);
        $curlResponse['fileType'] = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        return $curlResponse;
    }

    private static function parseFileNameFromUrl($url){
        $path = parse_url($url, PHP_URL_PATH);
        $fileName = basename($path);

        return $fileName;
    }

    private static function versionFileName($filePath, $fileExtension, $versionNumber = 0){
        if(file_exists($filePath)){
            $newVersionNumber = $versionNumber + 1;
            $newVersionString = '--' . $newVersionNumber . '.' . $fileExtension;

            if(! $versionNumber){
                $oldVersionPattern = '/\.' . $fileExtension . '$/';
            } else {
                $oldVersionPattern = '/--' . $versionNumber . '\.' . $fileExtension . '$/';
            }

            $newVersionFilePath = preg_replace($oldVersionPattern, $newVersionString, $filePath);

            $filePath = self::versionFileName($newVersionFilePath, $fileExtension, $newVersionNumber);
        }

        return $filePath;
    }
}
