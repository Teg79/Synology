<?php

class DLMSearchIlcorsaronero
{


    private $wurl = 'https://ilcorsaronero.info/';


    private $qurl = 'argh.php?search=%s';

    public function __construct()
    {

        $this->qurl = $this->wurl . $this->qurl;
    }

    public function prepare($curl, $query)
    {

        $url = $this->qurl;
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_REFERER, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en; rv:1.9.0.4) Gecko/2008102920 AdCentriaIM/1.7 Firefox/3.0.4');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_URL, sprintf($url, urlencode($query)));
    }

    public function parse($plugin, $response)
    {

        $pattern = '/(?<size>\d{1,3}\.\d.+(GB|MB))/';
        preg_match_all($pattern, $response, $matchSize);


        $pattern = '/(?<date>\d{2}\.\d{2}.\d{2})/';
        preg_match_all($pattern, $response, $matchDate);


        $pattern = '/(?<page>HREF=".+?")/';
        $matchNumber = preg_match_all($pattern, $response, $matchLinkPage);


        $pattern = '/(?<seeds>#(\d{2}|C{2})C{2}(\d{2}|C{2})\'>(n|\d).*?(a|\d*<\/font>)<\/td>\s)/';
        preg_match_all($pattern, $response, $matchSeed);


        $pattern = '/(?<leechs>#(\d{4}|C{4})C{2}\'>(n|\d)(.|\d?)(a|\d?)\S+<\/td><\/TR>)/';
        preg_match_all($pattern, $response, $matchLeech);


        $pattern = '/value="(?<magnet>\w+)"/';
        preg_match_all($pattern, $response, $matchMagnet);

        $patternTitle = '/(?<id>\/\d{3,8})\/(?<title>.+)/';


        for ($res = 0; $res < $matchNumber; $res++) {


            foreach ($matchLinkPage['page'] as $key => $value) {

                $parse[$key]["page"] = substr($value, 6, -1);


                preg_match($patternTitle, $parse[$key]["page"], $matchTitle);
                $parse[$key]["title"] = $matchTitle['title'];

            }

            foreach ($matchSize['size'] as $key => $value) {

                $parse[$key]["size"] = $value;
            }


            foreach ($matchDate['date'] as $key => $value) {
                $parse[$key]["datetime"] = $value;
            }


            foreach ($matchSeed['seeds'] as $key => $value) {

                if ($value[9] != "n") {
                    $parse[$key]["seeds"] = substr($value, 9, -13);
                } else {
                    $parse[$key]["seeds"] = '0';
                }
            }


            foreach ($matchLeech['leechs'] as $key => $value) {

                if ($value[9] != "n") {
                    $parse[$key]["leechs"] = substr($value, 9, -17);
                } else {
                    $parse[$key]["leechs"] = '0';
                }
            }


            foreach ($matchMagnet['magnet'] as $key => $value) {
                $parse[$key]["download"] = "magnet:?xt=urn:btih:" . $value;

            }

            $page = $parse[$res]["page"];
            $title = $parse[$res]["title"];
            //$download= $parse[$res]["download"];
            //$download=$page;

            $curlDown = curl_init($page);
            curl_setopt_array($curlDown, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $page,
                CURLOPT_USERAGENT => 'Codular Sample cURL Request',
                CURLOPT_SSL_VERIFYPEER => false,
            ));
            $downPage = curl_exec($curlDown);
            curl_close($curlDown);

            preg_match_all("/href=\"(magnet:.+?)\"/", $downPage, $output_array);
            $download = $output_array[1][0];


            switch (substr($parse[$res]["size"], -2, -1)) {
                case 'M':
                    $parse[$res]["size"] = (float)$parse[$res]["size"] * 1024 * 1024;
                    break;

                default:
                    $parse[$res]["size"] = (float)$parse[$res]["size"] * 1024 * 1024 * 1024;
                    break;
            }

            $size = $parse[$res]["size"];


            $year = '20' . substr($parse[$res]["datetime"], 6, 2);
            $month = substr($parse[$res]["datetime"], 3, 2);
            $day = substr($parse[$res]["datetime"], 0, 2);
            $datetime = $year . "-" . $month . "-" . $day;


            $seeds = (int)$parse[$res]["seeds"];

            $leechs = ((int)$parse[$res]["leechs"]) - $seeds;

            $category = 'DVDRIP';

            $hash = md5($title);

            $plugin->addResult($title, $download, $size, $datetime, $page, $hash, $seeds, $leechs, $category);
        }

        return $res;
    }
}

?>
