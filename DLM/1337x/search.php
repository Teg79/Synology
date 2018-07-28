<?php
/*********************************************************************\
| (c)2017 codemonster                                                 |
|---------------------------------------------------------------------|
| This program is free software; you can redistribute it and/or       |
| modify it under the terms of the GNU General Public License         |
| as published by the Free Software Foundation; either version 2      |
| of the License, or (at your option) any later version.              |
|                                                                     |
| This program is distributed in the hope that it will be useful,     |
| but WITHOUT ANY WARRANTY; without even the implied warranty of      |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the       |
| GNU General Public License for more details.                        |
|                                                                     |
| You should have received a copy of the GNU General Public License   |
| along with this program; If not, see <http://www.gnu.org/licenses/> |
\*********************************************************************/
?>
<?php
class CodemonsterDlmSearchOneThreeThreeSevenX {
		private $url_domain = 'https://1337x.to';
		private $qurl = '/sort-search/%s/seeders/desc/1/';
        private $debug = false;
       
        private function DebugLog($str) 
		{
			if ($this->debug==true) 
			{
				file_put_contents('/tmp/1337x_dlm.log',$str."\r\n\r\n",FILE_APPEND);
			}
        }

        public function __construct() {
			$this->qurl=$this->url_domain.$this->qurl;
        }

        public function prepare($curl, $query) {
			$this->configureCurl($curl, sprintf($this->qurl, urlencode($query)));                
        }

        public function parse($plugin, $response) {
			$this->processResultList($plugin, $response);

            $urlNavigationArray = array();
            $regexGetPageUrl = '"active"><a href="(\/.*search\/.*)"';
            if(preg_match_all("/$regexGetPageUrl/sU", $response, $pageUrls, PREG_SET_ORDER))
            {
                foreach ($pageUrls as $pageUrl)
                {
                    $urlNavigationArray = explode('/', $pageUrl[1]);
                }
            }

            for ($i = 2; $i <= 5; $i++)
            {
                if(count($urlNavigationArray) >= 2)
                {
                    $urlNavigationArray[count($urlNavigationArray)-2] = $i; // 5 -> change page number
                    $url = $this->url_domain.implode("/", $urlNavigationArray);

                    $response = $this->executeCurl($url);

                    if(substr_count($response,'No results were returned'))
                    {
                        return;
                    }
                    else
                    {
                        $this->processResultList($plugin, $response);
                    }
                }
            }
        }
		
		/* Begin private methods */
		
		private function configureCurl($curl, $url)
		{
			$headers = array
				(
					'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*;q=0.8',
					'Accept-Language: ru,en-us;q=0.7,en;q=0.3',
					'Accept-Encoding: deflate',
					'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7'
				);
				
				curl_setopt($curl, CURLOPT_HTTPHEADER,$headers); 
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_FAILONERROR, 1);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($curl, CURLOPT_TIMEOUT, 120);
				curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
		}
		
		private function executeCurl($url)
		{
			$curl = curl_init();
			$this->configureCurl($curl, $url);
			$content = curl_exec($curl);
			curl_close($curl);
			return $content;
		}
		
		private function processResultList($plugin, $response)
		{
			$regex_records = '<tr>(.*)<\/tr>';
            $regex_recordDetails = '<a href="(\/torrent.*)">.*seeds">(.*)<.*leeches">(.*)<.*date">(.*)<.*';
			if(preg_match_all("/$regex_records/siU", $response, $records, PREG_SET_ORDER))
			{
				foreach($records as $record) 
				{
					$recordToAdd = array();
					
					if(preg_match_all("/$regex_recordDetails/Us", $record[1], $recordDetails, PREG_SET_ORDER)) {
                        foreach ($recordDetails as $recordDetail)
                        {
                            $recordToAdd["page"] = $this->url_domain . $recordDetail[1]; //1. url
                            $recordToAdd["seeds"] = $recordDetail[2]; //2. seeds
                            $recordToAdd["leechs"] = $recordDetail[3]; //3. leechs
                            $recordToAdd["datetime"] = $recordDetail[4]; //4. date

                            // Navigate to page and collect more data
                            $pageContent = $this->executeCurl($recordToAdd["page"]);
                            $recordToAdd = $this->processDetailsPage($pageContent, $recordToAdd);

                            if (array_key_exists('title', $recordToAdd) && strlen($recordToAdd["title"]) > 0) {
                                $plugin->addResult($recordToAdd["title"],
                                    $recordToAdd["download"],
                                    (float)$recordToAdd["size"],
                                    date('Y-m-d',strtotime(str_replace("'", "", $recordToAdd["datetime"]))),
                                    $recordToAdd["page"],
                                    $recordToAdd["hash"],
                                    (int)$recordToAdd["seeds"],
                                    (int)$recordToAdd["leechs"],
                                    $recordToAdd["category"]);
                            }
                        }
					}
				}
			}
		}
		
		private function processDetailsPage($response, $recordToAdd)
		{
			$regex_details = '<h1>(.*)<\/h1>.*href="(magnet.*)".*Category<\/strong> <span>(.*)<\/span>.*size<\/strong> <span>(.*)<\/span>.*Infohash :<\/strong> <span>(.*)<\/span>';
			if(preg_match_all("/$regex_details/Us", $response, $details, PREG_SET_ORDER))
			{
                foreach ($details as $detail) {
                    $recordToAdd["title"] = $detail[1]; //5. title
                    $recordToAdd["download"] = $detail[2]; //6. magnet
                    $recordToAdd["category"] = $detail[3]; //7. category
                    $recordToAdd["size"] = $this->getSize($detail[4]); //8. size
                    $recordToAdd["hash"] = $detail[5]; //9. hash
                    return $recordToAdd;
                }
			}
		}

        private function getSize($rawSize)
        {
            $size_arr = explode(" ", $rawSize);
            $size = str_replace(",",".",$size_arr[0]);
            switch (trim($size_arr[1]))
            {
                case 'KB':
                    $size = $size * 1024;
                    break;
                case 'MB':
                    $size = $size * 1024 * 1024;
                    break;
                case 'GB':
                    $size = $size * 1024 * 1024 * 1024;
                    break;
            }
            $size = floor($size);
            return $size;
        }
		/* End private methods */
}
?>