<?php namespace Romby\Box\Http\Adapters;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Romby\Box\Http\Exceptions\NameConflictException;
use Romby\Box\Http\Exceptions\NotFoundException;
use Romby\Box\Http\HttpInterface;

class GuzzleHttpAdapter implements HttpInterface {

    /**
     * The Guzzle client.
     *
     * @var Client
     */
    protected $guzzle;

    /**
     * Instantiate the class and inject the dependencies.
     *
     * @param Client $guzzle the Guzzle client.
     */
    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * Send a GET request to the given url with the given options.
     *
     * @param string $url     the url.
     * @param array  $options the options.
     * @throws NotFoundException if the file is not found.
     * @return array the response.
     */
    public function get($url, $options)
    {
        try
        {
            return $this->guzzle->get($url, $options)->json();
        }
        catch(ClientException $exception)
        {
            $this->handleGetException($exception);
        }
    }

    /**
     * Send a GET request to the given url with the given options, and return the raw response.
     *
     * @param string $url     the url.
     * @param array  $options the options.
     * @throws NotFoundException if the file is not found.
     * @return mixed the response.
     */
    public function getRaw($url, $options)
    {
        try
        {
            return $this->guzzle->get($url, $options)->getBody();
        }
        catch(ClientException $exception)
        {
            $this->handleGetException($exception);
        }
    }

    /**
     * Download a file from the given url with the given options.
     *
     * @param string $url     the url.
     * @param array  $options the options.
     * @param string $name    the filename to store the downloaded file in.
     * @return void
     */
    public function download($url, $options, $name)
    {
        $result = $this->guzzle->get($url, $options);

        file_put_contents($name, $result->getBody());
    }

    /**
     * Send a POST request to the given url with the given options.
     *
     * @param string      $url     the url.
     * @param array       $options the options.
     * @param string|null $file    the path to a file to upload.
     * @return array the response.
     */
    public function post($url, $options, $file = null)
    {
        if(isset($file))
        {
            $options['body']['file'] = fopen($file, 'r');
        }

        return $this->guzzle->post($url, $options)->json();
    }

    /**
     * Send a PUT request to the given url with the given options.
     *
     * @param string $url     the url.
     * @param array  $options the options.
     * @return array the response.
     */
    public function put($url, $options)
    {
        return $this->guzzle->put($url, $options)->json();
    }

    /**
     * Send a DELETE request to the given url with the given options.
     *
     * @param string $url     the url.
     * @param array  $options the options.
     * @return array the response.
     */
    public function delete($url, $options)
    {
        return $this->guzzle->delete($url, $options)->json();
    }

    /**
     * Send an OPTIONS request to the given url with the given options.
     *
     * @param string $url     the url.
     * @param array  $options the options.
     * @throws NameConflictException if the name is already in use.
     * @return array the response.
     */
    public function options($url, $options)
    {
        try
        {
            return $this->guzzle->options($url, $options)->json();
        }
        catch(ClientException $exception)
        {
            $this->handleOptionsException($exception);
        }
    }

    /**
     * Handle a ClientException thrown during an OPTIONS query.
     *
     * @param ClientException $exception the exception.
     * @throws NameConflictException if the original exception is a conflict exception.
     * @return void
     */
    protected function handleOptionsException(ClientException $exception)
    {
        switch($exception->getResponse()->getStatusCode())
        {
            case 409:
                throw new NameConflictException($exception->getResponse()->json()['context_info']['conflicts']);
        }
    }

    /**
     * Handle a ClientException thrown during a GET query.
     *
     * @param ClientException $exception the exception.
     * @throws NotFoundException if the original exception is a file not found exception.
     * @return void
     */
    protected function handleGetException(ClientException $exception)
    {
        switch($exception->getResponse()->getStatusCode())
        {
            case 404:
                throw new NotFoundException();
                break;
            default:
                throw $exception;
        }
    }

}
