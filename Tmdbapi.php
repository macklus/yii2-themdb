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
        if ($key) {
            $this->newRequest();
            $this->_method = 'GET';
            $this->_url = '/search/company';
            $this->addParam('query', $key);
            $this->addParams($params);
            return $this->doQuery();
        } else {
            throw new Exception("searchCompany requires a search key!");
        }
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
