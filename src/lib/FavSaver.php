<?php

use Knp\Snappy\Pdf;
use PHPHtmlParser\Dom;

/**
 * Class FavSaver
 */
class FavSaver
{

    /**
     * @var string
     */
    private $user;
    /**
     * @var string
     */
    private $favsLink = 'http://habrahabr.ru/users/%s/favorites/page%d/';
    /**
     * @var array
     */
    private $urls = array();
    /**
     * @var int
     */
    private $pagesLimit = 0;
    private $saveDir = 'pdf';

    /**
     * @param string $user
     */
    function __construct($user, $saveDir = 'pdf')
    {
        $this->user = $user;
        $this->saveDir = $saveDir;
    }

    /**
     * @return $this
     */
    public function parseUrls()
    {
        $page = 1;
        $dom = new Dom;
        while (true) {
            if ($this->pagesLimit > 0 && $page > $this->pagesLimit) {
                break;
            }
            try {
                $dom->loadFromUrl(sprintf($this->favsLink, $this->user, $page));
                $links = $dom->find('.post_title');
                $linksCount = count($links);
                if ($linksCount == 0) {
                    break;
                }
                $this->log("Fetched page $page with $linksCount links");

                $links->each(function ($link) {
                    $this->urls[] = array(
                        'url' => $link->getAttribute('href'),
                        'title' => $this->makeTitle($link->text)
                    );
                });

                ++$page;
            } catch (Exception $e) {
                break;
            }
        }

        $this->log('Found ' . count($this->urls) . ' links');
        return $this;

    }

    /**
     * @param string $msg
     * @return string
     */
    private function log($msg)
    {
        $msg = date('H:i:s') . ': ' . $msg;
        //file_put_contents('log.txt', $msg, FILE_APPEND);
        echo $msg . PHP_EOL;
        return $msg;
    }

    function makeTitle($str)
    {
        $str = $this->rus2translit(trim($str));
        $str = strtolower($str);
        $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
        $str = trim($str, "-");
        return $str;
    }

    function rus2translit($string)
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => '\'', 'ы' => 'y', 'ъ' => '\'',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => '\'', 'Ы' => 'Y', 'Ъ' => '\'',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        );
        return strtr($string, $converter);
    }

    /**
     * @return void
     */
    public function savePdf()
    {
        if (!file_exists('pdf')) {
            mkdir('pdf');
        }
        foreach ($this->urls as $url) {
            $this->log('Fetching ' . $url['url']);
            $html = $this->fetchPage($url['url']);
            $saveTo = $this->saveDir . '/' . str_replace('/', '-', $url['title']) . '.pdf';

            $pathToBinary = PHP_OS == 'Darwin' ? 'wkhtml/mac/wkhtmltopdf' : 'wkhtml/linux/wkhtmltopdf';
            $snappy = new Pdf($pathToBinary);
            $snappy->generateFromHtml($html, $saveTo, array(), true);

            $this->log('Saved to ' . $saveTo);
        }
    }

    /**
     * @param string $url
     * @return string
     */
    public function fetchPage($url)
    {
        // Get mobile version
        $url = str_replace('//habrahabr', '//m.habrahabr', $url);

        if (strpos($url, 'company')) {
            $url = preg_replace('/company\/.*\/blog/', 'post', $url);
        }

        // Load DOM
        $html = file_get_contents($url);
        $doc = new DOMDocument();
        $doc->loadHTML($html);

        // Remove comments and other stuff
        $xpath = new DOMXPath($doc);
        $comments = $xpath->query("//*[@class='cmts']")->item(0);
        $bm = $xpath->query("//*[@class='bm']")->item(0);
        $ft = $xpath->query("//*[@class='ft']")->item(0);
        $tm = $xpath->query("//*[@class='tm']")->item(0);

        if (is_object($comments) && is_object($comments->parentNode)) {
            $comments->parentNode->removeChild($comments);
        }

        $bm->parentNode->removeChild($bm);
        $ft->parentNode->removeChild($ft);
        $tm->parentNode->removeChild($tm);

        return $doc->saveHTML();
    }

    /**
     * @return array
     */
    public function getUrls()
    {
        return $this->urls;
    }
}