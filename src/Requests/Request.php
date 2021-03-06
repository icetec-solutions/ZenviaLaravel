<?php
/**
 * Created by PhpStorm.
 * User: DevMaker BackEnd
 * Date: 16/04/2018
 * Time: 12:32
 */

namespace Louis\Zenvia\Requests;


use Louis\Zenvia\Exceptions\AuthenticationNotFoundedException;
use Louis\Zenvia\Exceptions\FieldMissingException;
use Louis\Zenvia\Exceptions\RequestException;
use Louis\Zenvia\Responses\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Response as HttpResponse;

class Request
{
    const ENDPOINT = 'https://api-rest.zenvia.com/services';
    private $key;

    /**
     * Request constructor.
     * @param $key
     * @throws AuthenticationNotFoundedException
     */
    public function __construct($key)
    {
        if(blank($key)){
            throw new AuthenticationNotFoundedException();
        }
        $this->key = $key;
    }

    /**
     * @param $url
     * @return array
     * @throws RequestException
     * @throws AuthenticationNotFoundedException
     */
    public function get($url)
    {
        try {
            $curl = new Client();
            $res = $curl->request('GET', self::ENDPOINT . '/' . $this->clearUrl($url), $this->getHeaders());

            if ($res->getStatusCode() > '499') {
                throw new RequestException('Erro na API Zenvia', HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
            }

            return json_decode($res->getBody(), true) ?: [];
        } catch (GuzzleException $e) {
            throw new RequestException($e->getMessage(), $e->getCode());
        }
    }

    public function clearUrl($url): string
    {
        if (strpos($url, '/') === 0) {
            $url = substr($url, 1);
        }
        return $url;
    }

    /**
     * @return array
     * @throws AuthenticationNotFoundedException
     */
    private function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . $this->key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    /**
     * @param $to
     * @param $message
     * @param null $from
     * @return Response
     * @throws AuthenticationNotFoundedException
     * @throws RequestException
     * @throws FieldMissingException
     * @throws RequestException
     */
    public function post($url, $body)
    {
        try {
            $curl = new Client();
            $res = $curl->request('POST', self::ENDPOINT.$url, $this->getOptions($body));

            return $this->makeResponse(json_decode($res->getBody(), true));
        } catch (GuzzleException $e) {
            throw new RequestException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param $body
     * @return array
     * @throws AuthenticationNotFoundedException
     * @throws FieldMissingException
     */
    private function getOptions($body)
    {
        return [
            'headers' => $this->getHeaders(),
            'body' => json_encode($body)
        ];
    }

    private function makeResponse($response){
        $responses = $response['sendSmsMultiResponse']['sendSmsResponseList'] ??  (array) [$response['sendSmsResponse']];
        $responseCollection = collect();
        foreach($responses as $responseItem){
            $responseCollection[] = new Response($responseItem);
        }
        return $responseCollection;
    }
}
