<?php

class DLMSearchTntVillage
{


    private $debug = false;
    private $url = 'http://www.tntvillage.scambioetico.org/src/releaselist.php';
    private $query = '';
    private $currentPage = 1;


    public function __construct($debug = false)
    {
        $this->debug = $debug;
        if($this->debug){
            //header("Content-Type: text/plain");
            $response = file_get_contents('./response.html');
            //echo $response;
            echo "<pre>";
            var_dump($this->get_rows($response));
        }

    }

    public function prepare($curl, $query)
    {
        $this->query = $query;
        $fields_string = $this->fields_string($this->query);

        //set the url, number of POST vars, POST data
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_POST, 3);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);

        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_REFERER, $this->url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en; rv:1.9.0.4) Gecko/2008102920 AdCentriaIM/1.7 Firefox/3.0.4');
    }

    public function parse($plugin, $response)
    {
        $rows = $this->get_rows($response);

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

        return $res;
    }

    private function fields_string($query){
        $fields = array(
            'cat' => '0',
            'page' => $this->currentPage,
            'srcrel' => urlencode($query)
        );

        //url-ify the data for the POST
        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        return $fields_string;
    }

    public function get_rows($response){

        $rows = $this->rows($response);

        $pattern = '/<span class=\'total\' a=\'(.*?)\'>/m';
        preg_match_all($pattern, $response, $result);
        $totalPage = $result[1][0];

//        for ($currentPage = 1; $currentPage <= $totalPage; $currentPage++) {
//            if($currentPage == 1)
//                continue;
//
//                $curlDown = curl_init($link);
//                curl_setopt_array($curlDown, array(
//                    CURLOPT_RETURNTRANSFER => 1,
//                    CURLOPT_URL => $link,
//                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en; rv:1.9.0.4) Gecko/2008102920 AdCentriaIM/1.7 Firefox/3.0.4'
//                ));
//                $downPage = curl_exec($curlDown);
//                curl_close($curlDown);
//
//            echo $currentPage;
//        }
//
//
//        var_dump($totalPage);
//        die;



        return $rows;
    }

    private function rows($response){
        $pattern = '/<tr>(.+?)<\/tr>/ms';
        preg_match_all($pattern, $response, $rows);
        $rows = array_slice($rows[1], 1);
        return $rows;
    }

}

//new DLMSearchTntVillage(true);