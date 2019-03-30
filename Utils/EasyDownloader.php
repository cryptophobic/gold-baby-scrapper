<?php

namespace Utils;

/**
 * Created by PhpStorm.
 * User: dima
 * Date: 09.06.16
 * Time: 17:47
 */
class EasyDownloader
{
    private $_url = '';

    private $_fileName = '';

    private $_fileInfo = '';

    private $_errors = [];

    private $_redirectFileName = '';

    /**
     * @param $url
     * @param $fileName
     * @return EasyDownloader
     * @throws \Exception
     */
    private function _setFiles($url, $fileName)
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!in_array($scheme, ['http', 'https', 'sftp', 'ftp'])) {
            throw new \Exception('Cannot download file');
        }

        if (preg_match('#^(sftp)\:\/\/.+#', $url)) {
            $url = "ssh2.{$url}";
        }

        $this->_url = $url;

        $this->_fileName = $fileName;

        return $this;
    }

    public function getFileInfo()
    {
        if (empty($this->_fileInfo)) {
            $this->setFileInfo();
        }

        return $this->_fileInfo;
    }

    private function downloadErrors($errno, $errstr, $errfile, $errline, $errcontext)
    {
        throw new \Exception('lang_cannot_download_file');
    }

    /**
     * download switcher for ftp and others
     * @return bool
     * @throws \Exception
     */
    private function _download()
    {
        $parsedUrl = parse_url($this->_url);

        switch ($parsedUrl['scheme']) {
            case 'ftp':
                return $this->_ftpDownload();
                break;
            case 'ssh2.sftp':
                return $this->_streamCopyDownload();
                break;
            default:
                return $this->_easyCopy();
                //return $this->_curlCopyDownload();
        }
    }

    /**
     * ftp download for setting FTP oriented settings
     * @return bool
     * @throws \Exception
     * @author Dmitry Uglach <DmitryUglach@gmail.com>
     */
    private function _ftpDownload()
    {
        $parsedUrl = parse_url($this->_url);
        $conn_id = ftp_connect($parsedUrl['host']);
        if (!empty($parsedUrl['user'])) {
            $loginResult = ftp_login($conn_id, $parsedUrl['user'], $parsedUrl['pass']);
        } else {
            $loginResult = ftp_login($conn_id, "anonymous", "");
        }

        if ($loginResult === true) {
            ftp_get($conn_id, $this->_fileName, $parsedUrl['path'], FTP_BINARY);
        } else {
            throw new \Exception("Can't login" . $this->_url);
        }

        return true;
    }

    private function _easyCopy()
    {
        copy($this->_url, $this->_fileName);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function _curlCopyDownload()
    {
        $ch = curl_init();
        $out = fopen($this->_fileName, 'wb');

        curl_setopt($ch, CURLOPT_FILE, $out);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_URL, $this->_url);
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $res = curl_exec($ch);
        if ($res === false) {
            throw new \Exception($this->_url . ": " . curl_error($ch));
        }

        return true;
    }

    /**
     * soft and easy stream based downloader. Want to replace it with cURL in nearest future
     * @return bool
     */
    private function _streamCopyDownload()
    {
        $src = fopen(urldecode($this->_url), 'rb');
        $des = fopen($this->_fileName, 'wb');
        stream_copy_to_stream($src, $des);
        fclose($src);
        fclose($des);

        return true;
    }

    /**
     * @param $url
     * @param $fileName
     * @return bool
     * @throws \Exception
     */
    public function downloadFile($url, $fileName)
    {
        $this->_setFiles($url, $fileName);

        $this->_errors = [];
        set_error_handler([$this, "downloadErrors"]);
        try {
            if ($this->_download()) {
                $des = fopen($this->_fileName, 'r');
                $this->setFileNameForRedirect($des);
                fclose($des);
            } else {
                throw new \Exception('cannot download file');
            }
        } catch (\Exception $e) {
            $this->_errors[] = $e->getMessage();
        }
        restore_error_handler();

        return count($this->_errors) === 0 ? true : false;
    }

    public function getRedirectFileName()
    {
        return $this->_redirectFileName;
    }

    private function setFileNameForRedirect($src)
    {
        $data = stream_get_meta_data($src);
        if (!empty($data['wrapper_data'])) {
            foreach ($data['wrapper_data'] as $item) {
                if (preg_match('/^Location:(.+)$/', $item, $match)) {
                    $this->_redirectFileName = trim($match[1]);
                }
            }
        }
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function getFilePath()
    {
        return $this->_fileName;
    }

    private function setFileInfo()
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $this->_fileInfo = finfo_file($finfo, $this->_fileName);
        finfo_close($finfo);
    }
}