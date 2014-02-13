<?php

use Knp\Snappy\Pdf;

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
    /**
     * @var string
     */
    private $saveDir;
    /**
     * @var bool
     */
    private $comments;

    private $linksTotal = 0;

    private $linksSaved = 0;

    private $failed = array();

    /**
     * @param string $user
     */
    function __construct($user, $saveDir = '../pdf', $comments = false)
    {
        $this->user = $user;
        $this->saveDir = $saveDir;
        $this->comments = $comments;
    }

    /**
     * @return $this
     */
    public function parseUrls()
    {
        $this->log(
            'Parsing favs of user ' . $this->user
            . ' to save in ' . $this->saveDir
            . ($this->comments ? 'with' : 'without') . ' comments'
        );

        $this->log('==================');
        $this->log('PARSING STARTED');
        $this->log('==================');

        $page = 1;

        while (true) {
            if ($this->pagesLimit > 0 && $page > $this->pagesLimit) {
                break;
            }
            $html = @file_get_contents(sprintf($this->favsLink, $this->user, $page));
            if (!$html) {
                break;
            }

            $doc = new DOMDocument();
            $doc->loadHTML($html);
            $xpath = new DOMXPath($doc);
            $posts = $xpath->query("//*[@class='post_title']");
            $linksCount = $posts->length;
            if ($linksCount == 0) {
                break;
            }
            $this->log("Fetched page $page with $linksCount links");
            $this->linksTotal = $linksCount;

            foreach ($posts as $link) {
                $this->urls[] = array(
                    'url' => $link->getAttribute('href'),
                    'title' => $this->makeTitle(trim($link->nodeValue))
                );
            }

            ++$page;
        }
        $this->log('==================');
        $this->log('PARSING FINISHED');
        $this->log('Found ' . count($this->urls) . ' links');
        $this->log('==================');

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

    /**
     * @param $str
     * @return mixed|string
     */
    function makeTitle($str)
    {
        $str = $this->rus2translit(trim($str));
        $str = preg_replace('/[^-a-zA-Z0-9_]+/u', '-', $str);
        $str = strtolower($str);
        $str = trim($str, "-");
        return $str;
    }

    /**
     * @param $string
     * @return string
     */
    function rus2translit($string)
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'i', 'к' => 'k',
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
            'И' => 'I', 'Й' => 'I', 'К' => 'K',
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
        $this->log('==================');
        $this->log('SAVING STARTED');
        $this->log('==================');
        if (!file_exists('pdf')) {
            mkdir('pdf');
        }
        foreach ($this->getUrls() as $url) {
            $this->log('Fetching ' . $url['url']);
            $html = $this->fetchPage($url['url']);
            $saveTo = $this->saveDir . '/' . str_replace('/', '-', $url['title']) . '.pdf';

            $pathToBinary = PHP_OS == 'Darwin' ? 'wkhtml/mac/wkhtmltopdf' : 'wkhtml/linux/wkhtmltopdf';
            $snappy = new Pdf(ROOT_DIR . '/' . $pathToBinary);
            $snappy->generateFromHtml($html, $saveTo, array(), true);

            $this->log('Saved to ' . $saveTo);
            $this->linksSaved++;
        }

        $this->log('==================');
        $this->log('SAVING FINISHED');
        $this->log('Favs found: ' . $this->linksTotal);
        $this->log('Favs saved: ' . $this->linksSaved);
        $this->log('Favs failed: ' . count($this->failed));
        if (count($this->failed) > 0) {
            foreach($this->failed as $f) {
                $this->log($f);
            }
        }
        $this->log('==================');
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
        $html = @file_get_contents($url);
        if (!$html) {
            return $this->failed($url);
        }
        $doc = new DOMDocument();
        $doc->loadHTML($html);

        // Remove comments and other stuff
        $xpath = new DOMXPath($doc);
        $title = $xpath->query("//*[@class='title']");
        if ($title->length == 0) {
            return $this->failed($url);
        }
        $comments = $xpath->query("//*[@class='cmts']")->item(0);
        $bm = $xpath->query("//*[@class='bm']")->item(0);
        $ft = $xpath->query("//*[@class='ft']")->item(0);
        $tm = $xpath->query("//*[@class='tm']")->item(0);

        if (!$this->comments) {
            if (is_object($comments) && is_object($comments->parentNode)) {
                $comments->parentNode->removeChild($comments);
            }
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

    /**
     * @param $url
     * @return bool
     */
    protected function failed($url)
    {
        $this->failed[] = $url;
        return false;
    }
}