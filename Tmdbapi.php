<?php

/**
 * @author Jose Pedro Andres <macklus@debianitas.net>
 * @link http://debianitas.net/
 * @copyright 2010 Debianitas
 * @date 01.12.2015
 */

namespace macklus\themdb;

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
    protected $_acceptedParams = ['query', 'page'];
    protected $_blockuntil;
    protected $_error = false;
    protected $_errorMsg = false;

    public function init()
    {
        parent::init();
        $this->connect();
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
        if ($key) {
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
        $this->sleepUntilApiAllow();

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

    public function sleepUntilApiAllow()
    {
        while ($this->_blockuntil) {
            if (time() > $this->_blockuntil) {
                $this->_blockuntil = false;
            } else {
                sleep(1);
            }
        }
    }

    public function ensureApiLimits($headers = [])
    {
        if (isset($headers['X-RateLimit-Remaining']) && isset($headers['X-RateLimit-Reset']) && $headers['X-RateLimit-Remaining'][0] < 3) {
            $this->_blockuntil = $headers['X-RateLimit-Reset'][0];
        } else {
            $this->_blockuntil = false;
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
