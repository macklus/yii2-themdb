<?php

/**
 * @author Jose Pedro Andres <macklus@debianitas.net>
 * @link http://debianitas.net/
 * @copyright 2010 Debianitas
 * @date 01.12.2015
 */

namespace macklus\themdb;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\ErrorException;
use GuzzleHttp\Exception\ConnectException;

class Tmdbapi extends Component
{

    /**
     * @var apikey
     */
    public $api_key;
    public $data;
    protected $_client;
    protected $_domain = 'http://api.themoviedb.org/3';
    protected $_params;
    protected $_url;
    protected $_method;
    protected $_connection;
    protected $_data;
    protected $_status;
    protected $_acceptedParams = ['query', 'page', 'language', 'append_to_response', 'start_date', 'end_date', 'include_all_movies', 'include_adult', 'sort_by', 'sort_order', 'session_id',
        'country', 'include_image_language', 'movie_credits', 'tv_credits', 'combined_credits', 'external_ids', 'images', 'tagged_images', 'changes'];
    protected $_blockuntil;
    protected $_error = false;
    protected $_errorMsg = false;

    public function init()
    {
        parent::init();
        $this->connect();
        $this->getConfiguration();
    }

    public function getConfiguration()
    {
        if ($this->genericGet('/configuration')) {
            $this->_configuration = $this->data;
        }
    }

    public function connect()
    {
        $this->_connection = new \GuzzleHttp\Client();
    }

    public function searchCompany($key = false, $params = [])
    {
        return $this->genericSearch('company', $key, $params);
    }

    public function searchCollection($key = false, $params = [])
    {
        return $this->genericSearch('collection', $key, $params);
    }

    public function searchKeyword($key = false, $params = [])
    {
        return $this->genericSearch('keyword', $key, $params);
    }

    public function searchList($key = false, $params = [])
    {
        return $this->genericSearch('list', $key, $params);
    }

    public function searchMovie($key = false, $params = [])
    {
        return $this->genericSearch('movie', $key, $params);
    }

    public function searchMulti($key = false, $params = [])
    {
        return $this->genericSearch('multi', $key, $params);
    }

    public function searchPerson($key = false, $params = [])
    {
        return $this->genericSearch('person', $key, $params);
    }

    public function searchTv($key = false, $params = [])
    {
        return $this->genericSearch('tv', $key, $params);
    }

    public function getCollection($id = false, $params = [])
    {
        return $this->generic('/collection/[:id]', $id, $params);
    }

    public function getCollectionImages($id = false, $params = [])
    {
        return $this->generic('/collection/[:id]/images', $id, $params);
    }

    public function getCompany($id = false, $params = [])
    {
        return $this->generic('/company/[:id]', $id, $params);
    }

    public function getCompanyMovies($id = false, $params = [])
    {
        return $this->generic('/company/[:id]/movies', $id, $params);
    }

    public function getGenreMovieList($params = [])
    {
        return $this->generic('/genre/movie/list', 100, $params);
    }

    public function getGenreTVList($params = [])
    {
        return $this->generic('/genre/tv/list', 100, $params);
    }

    public function getGenreMovies($id = false, $params = [])
    {
        return $this->generic('/genre/[:id]/movies', $id, $params);
    }

    public function getJobList($params = [])
    {
        return $this->generic('/job/list', 100, $params);
    }

    public function getKeyword($id = false, $params = [])
    {
        return $this->generic('/keyword/[:id]', $id, $params);
    }

    public function getKeywordMovies($id = false, $params = [])
    {
        return $this->generic('/keyword/[:id]/movies', $id, $params);
    }

    public function getMovie($id = false, $params = [])
    {
        return $this->generic('/movie/[:id]', $id, $params);
    }

    public function getFullMovie($id = false, $params = [])
    {
        $params['append_to_response'] = 'account_states,alternative_titles,credits,images,keywords,releases,videos,translations,reviews,similar,rating';
        return $this->generic('/movie/[:id]', $id, $params);
    }

    public function getPerson($id = false, $params = [])
    {
        return $this->generic('/person/[:id]', $id, $params);
    }

    public function getFullPerson($id = false, $params = [])
    {
        $params['append_to_response'] = 'movie_credits,tv_credits,combined_credits,external_ids,images,tagged_images,changes';
        return $this->generic('/person/[:id]', $id, $params);
    }

    public function getTv($id = false, $params = [])
    {
        return $this->generic('/tv/[:id]', $id, $params);
    }

    public function getFullTv($id = false, $params = [])
    {
        $params['append_to_response'] = 'account_states,alternative_titles,content_ratings,credits,external_ids,images,keywords,rating,translations,videos';
        return $this->generic('/tv/[:id]', $id, $params);
    }

    public function getTvSeason($id = false, $season = false, $params = [])
    {
        return $this->generic('/tv/[:id]/season/' . $season, $id, $params);
    }

    public function generic($url = '', $id = false, $params = [])
    {
        if ($id) {
            $miurl = str_replace("[:id]", $id, $url);
            return $this->genericGet($miurl, $params);
        } else {
            throw new Exception("search requires a id key!");
        }
    }

    public function genericSearch($search = '', $key = false, $params = [])
    {
        if ($key !== false) {
            $params['query'] = $key;
            return $this->genericGet('/search/' . $search, $params);
        } else {
            throw new Exception("search $search requires a search key!");
        }
    }

    public function genericGet($url, $params = [])
    {
        $this->newRequest();
        $this->_method = 'GET';
        $this->_url = $url;
        $this->addParams($params);
        return $this->doQuery();
    }

    public function doQuery()
    {
        if (strtoupper($this->_method) == 'GET') {
            try {
                $r = $this->_connection->get($this->_domain . $this->_url . '?' . http_build_query($this->_params), [
                    'verify' => false,
                    'exceptions' => false,
                ]);
                $this->ensureApiLimits($r->getHeaders());
                $this->processBody($r->getBody()->__toString());
                return $this->isOk();
            } catch (ConnectException $ex) {
                $this->crash();
            } catch (ErrorException $ex) {
                $this->crash();
            } catch (Exception $ex) {
                $this->crash();
            }
        }
    }

    public function getImage($file_path = false, $dest_file = false, $type = 'profile', $size = 'original')
    {
        if (!$file_path) {
            throw new Exception("getImage requires file_path!");
        }
        if (!$file_path) {
            throw new Exception("getImage requires destination folder!");
        }

        if (!in_array($size, $this->_configuration['images'][$type . '_sizes'])) {
            throw new Exception("getImage requires valid type and size!");
        }

        if ($file = fopen($dest_file, 'w+')) {
            //echo "\n" . $this->_configuration['images']['secure_base_url'] . $size . '/' . $file_path . "\n";
            $request = $this->_connection->get($this->_configuration['images']['secure_base_url'] . $size . '/' . $file_path, [], ['save_to' => $dest_file]);
            fwrite($file, $request->getBody());
            fclose($file);
        }
    }

    public function crash()
    {
        $this->_error = true;
        $this->data = [];
    }

    public function isOk()
    {
        return !$this->_error;
    }

    public function getError()
    {
        return $this->_errorMsg;
    }

    public function processBody($data)
    {
        $json = json_decode($data, true);

        // Detect errors
        if (isset($json['status_code'])) {
            $this->_error = true;
            $this->_errorMsg = $json['status_message'];
        } else {
            $this->_error = false;
        }
        $this->data = $json;
    }

    public function ensureApiLimits($headers = [])
    {
        if ($headers['X-RateLimit-Remaining'][0] < 3) {
            $wait = $headers['X-RateLimit-Reset'][0] - strtotime($headers['Date'][0]) + 2;
            Yii::info("Waiting $wait seconds to ensure API limits", 'tmdb');
            sleep($wait);
        }
    }

    public function addParams($params = [])
    {
        foreach ($params as $k => $v) {
            $this->addParam($k, $v);
        }
    }

    public function addParam($key = false, $value = false)
    {
        if (in_array($key, $this->_acceptedParams)) {
            $this->_params[$key] = $value;
        }
    }

    public function newRequest()
    {
        $this->_params = [
            'api_key' => $this->api_key,
        ];
        $this->_url = '';
        $this->_method = 'GET';
        $this->data = '';
    }
}
