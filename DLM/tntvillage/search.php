<?php

class DLMSearchTntVillage
{


    private $wurl = 'http://www.tntvillage.scambioetico.org/src/releaselist.php';


    private $qurl = '';

    public function __construct()
    {

        $this->qurl = $this->wurl . $this->qurl;
    }

    public function prepare($curl, $query)
    {

        $url = $this->qurl;

        $fields = array(
            'cat' => '0',
            'page' => '1',
            'srcrel' => urlencode($query)
        );

        //url-ify the data for the POST
        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        //set the url, number of POST vars, POST data
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, count($fields));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);

        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_REFERER, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en; rv:1.9.0.4) Gecko/2008102920 AdCentriaIM/1.7 Firefox/3.0.4');
    }

    public function parse($plugin, $response)
    {
        $pattern = '/<tr>(.+?)<\/tr>/ms';
        preg_match_all($pattern, $response, $rows);

        $rows = array_slice($rows[1], 1);

        foreach ($rows as $key => $value){

            $title = "Unknown title";
            $download = "Unknown download";
            $size = 0;
            $datetime = "1970-01-01";
            $page = "Default page";
            $hash = "Hash unknown";
            $seeds = 0;
            $leechs = 0;
            $category = "Unknown category";

            //magnet
            $pattern = "/href='(.+?)'/";
            preg_match_all($pattern, $value, $links);
            $download = $links[1][1];


            $pattern = '/<td.+?>(.+?)<\/td>/';
            preg_match_all($pattern, $value, $cols);
            $cols = $cols[1];

            $leechs = $cols[3];
            $seeds = $cols[4];
            $c = $cols[5];
            $title = str_replace("</a>","", $cols[6]);

            $plugin->addResult($title, $download, $size, $datetime, $page, $hash, $seeds, $leechs, $category);

        }

        $res = count($rows);

        /*for ($i = 0; $i < count($links[1]); $i++) {
            $linkType = $i % 4;
            $link = $links[1][$i];

            if ($linkType == 1) {
//                $curlDown = curl_init($link);
//                curl_setopt_array($curlDown, array(
//                    CURLOPT_RETURNTRANSFER => 1,
//                    CURLOPT_URL => $link,
//                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en; rv:1.9.0.4) Gecko/2008102920 AdCentriaIM/1.7 Firefox/3.0.4'
//                ));
//                $downPage = curl_exec($curlDown);
//                curl_close($curlDown);
                //$plugin->addResult($linkType, $downPage, $linkType, $linkType, $link, $linkType, $linkType, $linkType, $linkType);
                //$res++;

                $title = "Unknown title";
                $download = "Unknown download";
                $size = 0;
                $datetime = "1970-01-01";
                $page = "Default page";
                $hash = "Hash unknown";
                $seeds = 0;
                $leechs = 0;
                $category = "Unknown category";

//                $titlePattern = "/width='8' height='8' \/>&nbsp;<b>(.+)<\/b>(.+)<\/td>/";
//                preg_match_all($titlePattern, $downPage, $titleArray);
//                $title = $titleArray[1][0] . $titleArray[2][0];

//                $magnetPattern = "/(magnet:.+?)'/";
//                preg_match_all($magnetPattern, $downPage, $magnet);
//                $download = $magnet[1][0];
                $download = $link;

//                $sizePattern = "/Dimensione:<\/td>\s*<td>\s*(\d+)\s*mb/";
//                preg_match_all($sizePattern, $downPage, $sizeArray);
//                $size = $sizeArray[1][0] * 1024 * 1024;

//                $datePattern = "/id='last_seed'>\\s*(\\d{4}-\\d{2}-\\d{2}) \\d{2}:\\d{2}:\\d{2}/";
//                preg_match_all($datePattern, $downPage, $dateArray);
//                $datetime = $dateArray[1][0];

//                $page = $link;
//
//                $hashPattern = "/Info_hash:<\\/td><td>\\s*(.+?)\\s*<\\/td>/";
//                preg_match_all($hashPattern, $downPage, $hashArray);
//                $hash = $hashArray[1][0];

//                $seedsPattern = "/id='seeders'>\\s*(\\d*)\\s*<\\/td>/";
//                preg_match_all($seedsPattern, $downPage, $seedsArray);
//                $seeds = $seedsArray[1][0];
//
//                $leechsPattern = "/id='leechers'>\\s*(\\d*)\\s*<\\/td>/";
//                preg_match_all($leechsPattern, $downPage, $leechsArray);
//                $leechs = $leechsArray[1][0];

                $plugin->addResult($title, $download, $size, $datetime, $page, $hash, $seeds, $leechs, $category);
                $res++;
            }

        }*/
        return $res;
    }
}

//header("Content-Type: text/plain");
//
//$response = file_get_contents('./response.html');
////echo $response;
//$class = new DLMSearchTntVillage();
//$class->parse(null, $response);
