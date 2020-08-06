<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";
require_once 'constants.php';

use Transmission\Client;
use Transmission\Transmission;

class Torrent {

    private Transmission $transmission;

    public function __construct() {
        $client = new Client();
        $client->authenticate(TRANSMISSION_USR, TRANSMISSION_PASS);

        $this->transmission = new Transmission();
        $this->transmission->setClient($client);
    }

    public function startTorrent($id) {
        $this->transmission->start($this->transmission->get($id));
    }

    public function stopTorrent($id) {
        $this->transmission->stop($this->transmission->get($id));
    }

    /**
     * Get all torrents that have to yet start downloading.
     * @return array    An array with all the ids of unfinished torrents.
     */
    public function getIdleTorrents() {
        $torrents = $this->transmission->all();
        $idle = array();
        foreach($torrents as $torrent) {
            if($torrent->getPercentDone() == 0 && !$torrent->isDownloading()) {
                array_push($idle, $torrent->getId());
            }
        }
        return $idle;
    }

    /**
     * Get all names of the torrents which are downloading.
     * @return array    The torrents that are still downloading.
     */
    public function getDownloading() {
        $torrents = $this->transmission->all();
        $downloading = array();
        foreach($torrents as $torrent) {
            if($torrent->isDownloading()) {
                array_push($downloading, $torrent->getName());
            }
        }
        return $downloading;
    }

    /**
     * Get all the files for a torrent.
     * @param int $id   The id of the torrent.
     * @return array    The files.
     */
    public function getFiles(int $id) {
        $files = $this->transmission->get($id)->getFiles();
        $array = array();
        $i = 0;
        foreach($files as $file) {
            array_push($array, array($i, $file->getName()));
            $i++;
        }
        return $array;
    }

    /**
     * Select the files which should be downloaded for this torrent.
     * @param int   $id     The id of the torrent.
     * @param array $files  A list with indices of the files which should be downloaded.
     */
    public function selectFiles(int $id, array $files) {
        $to_download = array();
        foreach($files as $download) {
            array_push($to_download, intval($download));
        }
        $response = (array) $this->transmission->getClient()->call('torrent-set', array(
            'files-unwanted' => [],
            'files-wanted' => $to_download,
            'ids' => array(intval($id))
        ));

        $test = "yay";

        return $response['result'] == "success";
    }

    /**
     * Add a torrent to transmission.
     * @param $link         string  Either a magnet-link or the path to a torrent file.
     * @param $destination  string  The download directory for the file.
     * @return int                  The id of the download.
     */
    public function addTorrent($link, $destination) {
        $link = str_replace("\\", "/", $link);
        $id = $this->transmission->add($link, false, $destination)->getId();
        $this->stopTorrent($id);
        return $id;
    }
}